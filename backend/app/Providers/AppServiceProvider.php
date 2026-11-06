<?php

namespace App\Providers;

use App\Models\AirbnbListing;
use App\Models\ContactSubmission;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\PageMeta;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyTag;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use App\Models\Stat;
use App\Models\SustainabilityPillar;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Value;
use App\Observers\AuditLogObserver;
use App\Observers\ContentObserver;
use App\Policies\ContentPolicy;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        TrustProxies::at(config('app.trusted_proxies', []));
        Gate::define('manage-content', fn (User $user): bool => app(ContentPolicy::class)->manageContent($user));
        Gate::define('delete-content', fn (User $user): bool => app(ContentPolicy::class)->deleteContent($user));
        Gate::define('view-content', fn (User $user): bool => app(ContentPolicy::class)->viewAny($user));
        foreach ([AirbnbListing::class, ContactSubmission::class, ContentBlock::class, Media::class, Menu::class, MenuItem::class, PageMeta::class, Property::class, PropertyImage::class, PropertyTag::class, Service::class, ServiceItem::class, SiteSetting::class, Stat::class, SustainabilityPillar::class, Testimonial::class, Value::class] as $model) {
            $model::observe(ContentObserver::class);
        }
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols());
        Event::subscribe(AuditLogObserver::class);
    }
}
