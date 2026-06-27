<?php

use App\Helpers\ImageManager;

if (! function_exists('Imageurl')) {
    /**
     * Global shortcut to ImageManager::url() — usable bare in any Blade/PHP:
     *   {{ Imageurl($category->image, 'categories') }}
     */
    function Imageurl(?string $name, string $folder): ?string
    {
        return ImageManager::url($name, $folder);
    }
}
