<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media library disk
    |--------------------------------------------------------------------------
    |
    | Where NEW media-library uploads are written. "local" keeps the historic
    | behaviour (public/uploads via App\Helpers\ImageManager); any other value
    | must name a filesystem disk — "r2" for Cloudflare R2.
    |
    | Existing assets are unaffected: every asset records the disk it was
    | written to, so old local files keep resolving after the switch.
    |
    */

    'disk' => env('MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Remote object key prefix
    |--------------------------------------------------------------------------
    |
    | Prepended to every remote object key, e.g. "media/products/foo.jpg".
    | Leave empty to write folders at the bucket root.
    |
    */

    'prefix' => env('MEDIA_PREFIX', 'media'),

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    */

    'max_files' => (int) env('MEDIA_MAX_FILES', 24),
    'max_file_kb' => (int) env('MEDIA_MAX_FILE_KB', 8192),

];
