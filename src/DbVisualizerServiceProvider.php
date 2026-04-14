<?php

namespace Naimul\DbVisualizer;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DbVisualizerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();
        $this->authorization();

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
     * Define the default authorization gate.
     *
     * By default, access is granted in local environment only.
     * Override in your AppServiceProvider to customise:
     *
     *   Gate::define('viewDbVisualizer', fn (?User $user) => true);
     */
    protected function authorization(): void
    {
        if (! Gate::has('viewDbVisualizer')) {
            Gate::define('viewDbVisualizer', function (?object $user) {
                return $this->app->environment('local');
            });
        }
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
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
        Route::group([
            'domain' => config('db-visualizer.domain', null),
        ], function () {
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
        });
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'domain' => config('db-visualizer.domain', null),
            'prefix' => config('db-visualizer.path'),
            'as' => 'visualizer.',
            'middleware' => 'db-visualizer',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the DB Visualizer resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dbv');
    }
}
