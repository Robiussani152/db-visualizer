<?php

namespace Naimul\DbVisualizer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DbVisualizerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        if (! config('db-visualizer.enabled')) {
            return;
        }

        Route::middlewareGroup('db-visualizer', [
            ...config('db-visualizer.middleware', ['web']),
        ]);

        $this->registerRoutes();
        $this->registerAssetRoutes();
        $this->registerResources();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/db-visualizer.php', 'db-visualizer');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/db-visualizer.php' => config_path('db-visualizer.php'),
            ], 'dbv-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/dbv'),
            ], 'dbv-resources');

        }
    }

    /**
     * Register routes that serve the package's static assets (CSS/JS).
     * Assets are served directly from the package — no publishing required.
     */
    protected function registerAssetRoutes(): void
    {
        Route::get(config('db-visualizer.path').'/assets/{type}/{file}', function (string $type, string $file) {
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
            ];

            abort_unless(isset($mimeTypes[$type]), 404);

            $resourceBase = realpath(__DIR__.'/../resources');
            $path = realpath("{$resourceBase}/{$type}/{$file}");

            abort_if($path === false || ! str_starts_with($path, $resourceBase), 404);

            return response()->file($path, ['Content-Type' => $mimeTypes[$type]]);
        })->name('visualizer.assets');
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'domain' => config('db-visualizer.domain', null),
            'namespace' => 'Naimul\DbVisualizer\Http\Controllers',
            'prefix' => config('db-visualizer.path'),
            'as' => 'visualizer.',
            'middleware' => 'db-visualizer',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the DB Visualizer resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dbv');
    }
}
