<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Http\Resources\PropertySuggestionsResource;
use App\Http\Requests\Api\V1\PublicPropertySearchRequest;
use App\Http\Requests\Api\V1\PublicPropertySuggestionsRequest;
use App\Models\PageMeta;
use App\Models\Property;
use App\Models\PropertyTag;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Stat;
use App\Models\SustainabilityPillar;
use App\Models\Testimonial;
use App\Models\Value;
use App\Models\AirbnbListing;
use App\Models\Menu;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use App\Support\ContentCache;

class PublicContentController extends Controller
{
    public function __construct(private readonly ContentCache $cache) {}

    public function settings(): ContentResource
    {
        return new ContentResource(SiteSetting::query()->firstOrCreate([]));
    }

    public function meta(Request $request): ContentResource
    {
        $route = $request->route('route') ?: $request->query('route', '/');
        $page = PageMeta::query()->with(['blocks' => fn ($query) => $query->where('published', true)])->where('route', $route)->firstOrFail();
        $page->setAttribute('title', $page->meta_title ?: $page->title);
        $page->setAttribute('description', $page->meta_description ?: $page->description);

        return new ContentResource($page);
    }

    public function stats(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(Stat::query()->when($request->section, fn ($query, $section) => $query->where('section', $section))->orderBy('order')->paginate($this->perPage($request)));
    }

    public function values(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(Value::query()->where('published', true)->orderBy('order')->paginate($this->perPage($request)));
    }

    public function services(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(Service::query()->where('published', true)->with('items')->orderBy('order')->paginate($this->perPage($request)));
    }

    public function properties(PublicPropertySearchRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = Property::query()
            ->where('is_published', true)
            ->with(['tags', 'images.media'])
            ->when($request->boolean('featured'), fn ($builder) => $builder->where('is_featured', true))
            ->when(isset($filters['beds']) && $filters['beds'] !== null, fn ($builder) => $builder->where('beds', '>=', $filters['beds']))
            ->when(isset($filters['baths']) && $filters['baths'] !== null, fn ($builder) => $builder->where('baths', '>=', $filters['baths']))
            ->when(isset($filters['min_price']) && $filters['min_price'] !== null, fn ($builder) => $builder->where('price_value', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']) && $filters['max_price'] !== null, fn ($builder) => $builder->where('price_value', '<=', $filters['max_price']));

        $tags = array_values(array_unique(array_filter(array_map('trim', $filters['tags'] ?? []), static fn (string $tag): bool => $tag !== '')));
        if ($tags !== []) {
            $query->whereHas('tags', fn ($builder) => $builder->whereIn('label', $tags));
        }

        $terms = preg_split('/\s+/u', trim((string) ($filters['q'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($terms as $term) {
            $query->where(function ($builder) use ($term): void {
                $pattern = '%' . addcslashes($term, '%_\\') . '%';
                $builder->where('title', 'like', $pattern)
                    ->orWhere('location', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern)
                    ->orWhere('price_label', 'like', $pattern)
                    ->orWhereHas('tags', fn ($tagsQuery) => $tagsQuery->where('label', 'like', $pattern));

                if (is_numeric($term)) {
                    $number = (float) $term;
                    $builder->orWhere('beds', (int) $number)
                        ->orWhere('baths', (int) $number)
                        ->orWhere('price_value', $number);
                }
            });
        }

        return ContentResource::collection($query->orderBy('order')->orderBy('id')->paginate($this->perPage($request, 12)));
    }

    public function propertySuggestions(PublicPropertySuggestionsRequest $request): PropertySuggestionsResource
    {
        $term = trim($request->validated('q'));
        $cacheKey = 'public-property-suggestions:' . hash('sha256', mb_strtolower($term));
        $suggestions = $this->cache->remember($cacheKey, function () use ($term): array {
            $pattern = '%' . addcslashes($term, '%_\\') . '%';
            $publishedProperty = static fn ($query) => $query->where('is_published', true);

            $tags = PropertyTag::query()
                ->whereHas('property', $publishedProperty)
                ->where('label', 'like', $pattern)
                ->select('label')
                ->distinct()
                ->orderBy('label')
                ->limit(10)
                ->pluck('label')
                ->all();

            $locations = Property::query()
                ->where('is_published', true)
                ->whereNotNull('location')
                ->where('location', 'like', $pattern)
                ->select('location')
                ->distinct()
                ->orderBy('location')
                ->limit(10)
                ->pluck('location')
                ->all();

            $titles = Property::query()
                ->where('is_published', true)
                ->where('title', 'like', $pattern)
                ->select('title')
                ->distinct()
                ->orderBy('title')
                ->limit(10)
                ->pluck('title')
                ->all();

            return ['tags' => $tags, 'locations' => $locations, 'titles' => $titles];
        }, 300);

        return new PropertySuggestionsResource($suggestions);
    }

    public function sustainability(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(SustainabilityPillar::query()->where('published', true)->orderBy('order')->paginate($this->perPage($request)));
    }

    public function testimonials(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(Testimonial::query()->where('published', true)->orderBy('order')->paginate($this->perPage($request)));
    }

    public function menus(Request $request): AnonymousResourceCollection|ContentResource
    {
        $query = Menu::query()->where('published', true)->with([
            'items' => fn ($items) => $items->where('published', true),
            'items.children' => fn ($items) => $items->where('published', true),
        ])->orderBy('order');

        if ($location = $request->route('location')) {
            return new ContentResource($query->where('location', $location)->firstOrFail());
        }

        return ContentResource::collection($query->paginate($this->perPage($request)));
    }

    public function airbnbListings(Request $request): AnonymousResourceCollection
    {
        return ContentResource::collection(AirbnbListing::query()->where('published', true)->orderBy('order')->paginate($this->perPage($request)));
    }

    private function perPage(Request $request, int $default = 100): int
    {
        return min(max((int) $request->query('per_page', $default), 1), 100);
    }
}