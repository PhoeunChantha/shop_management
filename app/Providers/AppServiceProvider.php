<?php

namespace App\Providers;

use App\Models\Color;
use App\Models\Size;
use App\Services\AdminNotificationService;
use App\Services\FrontendNavigationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);

        // Password-reset emails link to the storefront reset page (not Breeze's).
        ResetPassword::createUrlUsing(fn ($notifiable, string $token): string => route('frontend.password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));

        // Store 'size'/'color' (not full class names) in attribute_values.source_type.
        // Use morphMap (not enforceMorphMap) so other polymorphic models — e.g. the
        // spatie permission tables morphing to User — keep working unmapped.
        Relation::morphMap([
            'size' => Size::class,
            'color' => Color::class,
        ]);

        // Reusable admin Blade components: resources/views/admin/components/*
        // usable as <x-admin::component-name />.
        Blade::anonymousComponentPath(resource_path('views/admin/components'), 'admin');

        View::composer('admin.layouts.header', function ($view): void {
            if (! auth()->check()) {
                $view->with([
                    'adminHeaderNotifications' => collect(),
                    'adminUnreadNotifications' => 0,
                ]);

                return;
            }

            $notifications = app(AdminNotificationService::class);
            $notifications->refreshGenerated();

            $view->with([
                'adminHeaderNotifications' => $notifications->recentForHeader(),
                'adminUnreadNotifications' => $notifications->unreadCount(),
            ]);
        });

        View::composer('components.frontend.header', function ($view): void {
            $view->with('frontendNav', app(FrontendNavigationService::class)->data());
        });
    }
}
