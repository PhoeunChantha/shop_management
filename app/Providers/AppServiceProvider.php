<?php

namespace App\Providers;

use App\Models\Color;
use App\Models\Size;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    }
}
