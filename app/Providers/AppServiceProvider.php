<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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

        // Reusable admin Blade components: resources/views/admin/components/*
        // usable as <x-admin::component-name />.
        Blade::anonymousComponentPath(resource_path('views/admin/components'), 'admin');
    }
}
