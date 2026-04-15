<?php

use Naimul\DbVisualizer\Http\Middleware\Authorize;

return [
    /*
    |--------------------------------------------------------------------------
    | DB Visualizer Enable Switch
    |--------------------------------------------------------------------------
    |
    | Globally enable or disable all DB Visualizer scans
    |
    */
    'enabled' => env('DB_VISUALIZER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | DB Visualizer Domain
    |--------------------------------------------------------------------------
    |
    | The domain name for the DB Visualizer routes.
    |
    */
    'domain' => env('DB_VISUALIZER_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | DB Visualizer Path
    |--------------------------------------------------------------------------
    | This is the URI path where DB Visualizer will be accessible from.
    |
    */
    'path' => env('DB_VISUALIZER_PATH', 'dbv'),

    /*
    |--------------------------------------------------------------------------
    | DB Visualizer Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every DB Visualizer route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | The cache key used to store scanned DB Visualizer data.
    |
    */
    'cache_key' => env('DB_VISUALIZER_CACHE_KEY', 'db_visualizer_v1'),

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories and paths to be scanned for database-related files (models, views, modules, etc.).
    |
    */
    'scan_paths' => [
        app_path(),                // Main application directory
        resource_path('views'),    // Blade views directory
        base_path('Modules'),      // Modules directory (if present)
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Duration (in seconds) for which scanned data will be cached.
    |
    */
    'cache_ttl' => env('DB_VISUALIZER_CACHE_TTL', 3600),
];
