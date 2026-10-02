<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rules\Password;
use App\Observers\AuditLogObserver;
use App\Models\User;
use App\Policies\ContentPolicy;
use Illuminate\Support\Facades\Gate;
use App\Models\{AirbnbListing, ContactSubmission, ContentBlock, Media, Menu, MenuItem, PageMeta, Property, PropertyImage, PropertyTag, Service, ServiceItem, SiteSetting, Stat, SustainabilityPillar, Testimonial, Value};
use App\Observers\ContentObserver;

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
        Gate::define('manage-content', fn (User $user): bool => app(ContentPolicy::class)->manageContent($user));
        Gate::define('delete-content', fn (User $user): bool => app(ContentPolicy::class)->deleteContent($user));
        Gate::define('view-content', fn (User $user): bool => app(ContentPolicy::class)->viewAny($user));
        foreach ([AirbnbListing::class, ContactSubmission::class, ContentBlock::class, Media::class, Menu::class, MenuItem::class, PageMeta::class, Property::class, PropertyImage::class, PropertyTag::class, Service::class, ServiceItem::class, SiteSetting::class, Stat::class, SustainabilityPillar::class, Testimonial::class, Value::class] as $model) $model::observe(ContentObserver::class);
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->symbols());
        Event::subscribe(AuditLogObserver::class);
    }
}
