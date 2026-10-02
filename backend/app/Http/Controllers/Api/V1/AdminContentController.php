<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ContentRequest;
use App\Http\Requests\Api\V1\Admin\ReorderRequest;
use App\Http\Resources\ContentResource;
use App\Models\ContactSubmission;
use App\Models\AirbnbListing;
use App\Models\PageMeta;
use App\Models\Property;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Stat;
use App\Models\SustainabilityPillar;
use App\Models\Testimonial;
use App\Models\Value;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminContentController extends Controller
{
    private const MODELS = [
        'settings' => SiteSetting::class, 'page-meta' => PageMeta::class, 'stats' => Stat::class,
        'values' => Value::class, 'services' => Service::class, 'properties' => Property::class,
        'pillars' => SustainabilityPillar::class, 'testimonials' => Testimonial::class,
        'submissions' => ContactSubmission::class, 'airbnb-listings' => AirbnbListing::class,
    ];

    public function index(Request $request, string $resource)
    {
        $model = $this->model($resource);
        $query = $this->withRelations($model::query(), $model);
        $fillable = (new $model)->getFillable();

        if ($request->exists('published')) {
            $publishedColumn = in_array('published', $fillable, true) ? 'published' : (in_array('is_published', $fillable, true) ? 'is_published' : null);
            if ($publishedColumn) $query->where($publishedColumn, $request->boolean('published'));
        }
        if ($request->filled('section') && in_array('section', $fillable, true)) $query->where('section', $request->query('section'));

        $search = trim((string) $request->query('search', $request->query('q', '')));
        if ($search !== '') {
            $searchable = array_values(array_intersect(['title', 'name', 'label', 'value', 'text', 'quote', 'message', 'phone', 'interest', 'description', 'short_description', 'location', 'route', 'email', 'author_name'], $fillable));
            if ($searchable !== []) {
                $query->where(function ($builder) use ($searchable, $search): void {
                    foreach ($searchable as $column) $builder->orWhere($column, 'like', '%' . addcslashes($search, '%_\\') . '%');
                });
            }
        }

        $sortable = array_values(array_intersect(['order', 'title', 'name', 'created_at', 'updated_at'], $fillable));
        $sort = (string) $request->query('sort', $request->query('order_by', 'order'));
        if (in_array($sort, $sortable, true)) {
            $direction = strtolower((string) $request->query('direction', in_array(strtolower((string) $request->query('order')), ['asc', 'desc'], true) ? $request->query('order') : 'asc'));
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        return ContentResource::collection($query->paginate($this->perPage($request)));
    }

    public function show(string $resource, int $id): ContentResource
    {
        $model = $this->model($resource);
        return new ContentResource($this->withRelations($model::query(), $model)->findOrFail($id));
    }

    public function store(ContentRequest $request, string $resource): JsonResponse
    {
        $model = $this->model($resource);
        $attributes = $request->validated();
        unset($attributes['slug']);
        if ($model === PageMeta::class) unset($attributes['route']);
        $record = DB::transaction(function () use ($model, $attributes): Model {
            [$attributes, $nested] = $this->splitNested($attributes);
            if (in_array('order', (new $model)->getFillable(), true) && ! array_key_exists('order', $attributes)) $attributes['order'] = (int) $model::max('order') + 1;
            if (in_array('slug', (new $model)->getFillable(), true)) $attributes['slug'] = $this->uniqueSlug($model, (string) ($attributes['title'] ?? 'listing'));
            if ($model === PageMeta::class) $attributes['route'] = $this->uniquePageRoute((string) ($attributes['title'] ?? 'page'));
            $record = $model::create($attributes);
            $this->syncNested($record, $nested);
            return $record;
        });

        return (new ContentResource($this->withRelations($record->newQuery(), $model)->findOrFail($record->getKey())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ContentRequest $request, string $resource, int $id): ContentResource
    {
        $model = $this->model($resource);
        $record = $model::findOrFail($id);
        $attributes = $request->validated();
        unset($attributes['slug']);
        if ($model === PageMeta::class) unset($attributes['route']);
        DB::transaction(function () use ($record, $attributes): void {
            [$attributes, $nested] = $this->splitNested($attributes);
            $record->update($attributes);
            $this->syncNested($record, $nested);
        });

        return new ContentResource($this->withRelations($record->newQuery(), $model)->findOrFail($record->getKey()));
    }

    public function destroy(string $resource, int $id): array
    {
        $record = $this->model($resource)::findOrFail($id);
        abort_unless(request()->user()->can('delete-content'), 403);
        $record->delete();
        return ['message' => 'Deleted.'];
    }

    public function reorder(ReorderRequest $request, string $resource): array
    {
        $model = $this->model($resource);
        abort_unless(in_array('order', (new $model)->getFillable(), true), 422, 'This resource cannot be reordered.');
        $ids = $request->validated('ids');
        DB::transaction(function () use ($model, $ids): void {
            $found = $model::query()->whereKey($ids)->count();
            abort_unless($found === count($ids), 422, 'One or more records do not belong to this resource.');
            foreach ($ids as $order => $id) $model::whereKey($id)->update(['order' => $order]);
        });
        $this->audit('content.reordered', null, ['resource' => $resource, 'ids' => $ids]);
        return ['message' => 'Reordered.'];
    }

    private function withRelations($query, string $model)
    {
        return match ($model) {
            Service::class => $query->with('items'),
            Property::class => $query->with(['tags', 'images.media']),
            PageMeta::class => $query->with('blocks'),
            AirbnbListing::class => $query,
            default => $query,
        };
    }

    private function splitNested(array $attributes): array
    {
        $nested = [];
        foreach (['items', 'tags', 'media_ids', 'blocks'] as $key) {
            if (array_key_exists($key, $attributes)) $nested[$key] = $attributes[$key];
            unset($attributes[$key]);
        }

        return [$attributes, $nested];
    }

    private function syncNested(Model $record, array $nested): void
    {
        if ($record instanceof Service && array_key_exists('items', $nested)) {
            $this->replaceChildren($record->items(), $nested['items'], ['text', 'order']);
        }
        if ($record instanceof PageMeta && array_key_exists('blocks', $nested)) {
            $nested['blocks'] = array_map(static function (array $block): array {
                if (array_key_exists('content', $block)) $block['content'] = HtmlSanitizer::sanitize((string) $block['content']);
                return $block;
            }, $nested['blocks']);
            $this->replaceChildren($record->blocks(), $nested['blocks'], ['key', 'type', 'content', 'order', 'published']);
        }
        if ($record instanceof Property) {
            if (array_key_exists('tags', $nested)) {
                $record->tags()->delete();
                foreach (array_values($nested['tags']) as $order => $tag) {
                    $label = is_array($tag) ? ($tag['label'] ?? '') : $tag;
                    $record->tags()->create(['label' => $label, 'order' => is_array($tag) ? ($tag['order'] ?? $order) : $order]);
                }
            }
            if (array_key_exists('media_ids', $nested)) {
                $images = $record->images()->withTrashed()->get()->keyBy('media_id');
                $keep = [];
                foreach (array_values($nested['media_ids']) as $order => $mediaId) {
                    $image = $images->get($mediaId);
                    if ($image) {
                        if ($image->trashed()) $image->restore();
                        $image->update(['order' => $order]);
                    } else {
                        $image = $record->images()->create(['media_id' => $mediaId, 'order' => $order]);
                    }
                    $keep[] = $image->getKey();
                }
                $record->images()->whereNotIn('id', $keep)->delete();
            }
        }
    }

    private function replaceChildren($relation, array $children, array $fields): void
    {
        $keep = [];
        $withTrashed = method_exists($relation->getRelated(), 'trashed') ? $relation->withTrashed() : $relation;
        foreach (array_values($children) as $position => $child) {
            $attributes = array_intersect_key($child, array_flip($fields));
            $attributes['order'] ??= $position;
            if (isset($child['id'])) {
                $existing = $withTrashed->whereKey($child['id'])->first();
                abort_unless($existing, 422, 'A nested record does not belong to its parent.');
                if (method_exists($existing, 'trashed') && $existing->trashed()) $existing->restore();
                $existing->update($attributes);
                $keep[] = $existing->getKey();
            } else {
                $existing = isset($attributes['key']) ? $withTrashed->where('key', $attributes['key'])->first() : null;
                if ($existing) {
                    if (method_exists($existing, 'trashed') && $existing->trashed()) $existing->restore();
                    $existing->update($attributes);
                    $created = $existing;
                } else {
                    $created = $relation->create($attributes);
                }
                $keep[] = $created->getKey();
            }
        }
        $withTrashed->whereNotIn('id', $keep)->delete();
    }

    private function uniqueSlug(string $model, string $title): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $suffix = 2;
        while ($model::withTrashed()->where('slug', $slug)->exists()) $slug = $base . '-' . $suffix++;

        return $slug;
    }

    private function uniquePageRoute(string $title): string
    {
        $base = '/' . (Str::slug($title) ?: 'page');
        $route = $base;
        $suffix = 2;
        while (PageMeta::query()->where('route', $route)->exists()) $route = $base . '-' . $suffix++;

        return $route;
    }

    private function model(string $resource): string { abort_unless(isset(self::MODELS[$resource]), 404); return self::MODELS[$resource]; }
    private function perPage(Request $request): int { return min(max((int) $request->query('per_page', 20), 1), 100); }
    private function audit(string $action, ?Model $record, array $meta = []): void { DB::table('audit_logs')->insert(['user_id' => request()->user()->id, 'action' => $action, 'ip' => request()->ip(), 'user_agent' => request()->userAgent(), 'meta' => json_encode($meta + ['model' => $record ? get_class($record) : null, 'id' => $record?->getKey()]), 'created_at' => now(), 'updated_at' => now()]); }
}