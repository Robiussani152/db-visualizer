<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

describe('route registration', function () {
    it('registers visualizer routes when enabled', function () {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values();

        expect($routes)
            ->toContain('visualizer.index')
            ->toContain('visualizer.models')
            ->toContain('visualizer.models.show')
            ->toContain('visualizer.cache.clear');
    });

    it('registers the asset route', function () {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values();

        expect($routes)->toContain('visualizer.assets');
    });

    it('uses the configured path prefix', function () {
        $route = Route::getRoutes()->getByName('visualizer.index');

        expect($route->uri())->toStartWith('dbv');
    });
});

describe('route access control', function () {
    it('returns 403 when gate denies access to index', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->get('/dbv')->assertStatus(403);
    });

    it('returns 403 on data endpoint when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->get('/dbv/data')->assertStatus(403);
    });

    it('returns 403 on detail endpoint when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->get('/dbv/detail/User')->assertStatus(403);
    });
});
