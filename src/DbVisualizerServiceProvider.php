<?php

namespace Naimul\DbVisualizer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DbVisualizerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPublishing();

        if (!config('db-visualizer.enabled')) {
            return;
        }

        Route::middlewareGroup('db-visualizer', [
            ...config('db-visualizer.middleware', ['web']),
        ]);

        $this->registerRoutes();
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
            ], 'dbv-views');
        }
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