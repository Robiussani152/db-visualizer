<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

describe('VisualizerController', function () {
    beforeEach(function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => true);

        // Pre-seed the composer cache so file_get_contents is never called
        Cache::put(
            config('db-visualizer.cache_key').'_composer',
            collect([
                ['name' => 'nesbot/carbon', 'version' => '3.0.0', 'description' => 'Date library', 'type' => 'library'],
                ['name' => 'laravel/framework', 'version' => 'v11.0.0', 'description' => 'Laravel', 'type' => 'library'],
            ]),
            now()->addHour()
        );
    });

    it('renders the index view', function () {
        $this->get('/dbv')->assertOk()->assertViewIs('dbv::visualizer.index');
    });

    it('passes extraPackages to the view', function () {
        $this->get('/dbv')
            ->assertOk()
            ->assertViewHas('extraPackages');
    });

    it('returns the cached extraPackages without re-reading files', function () {
        $key = config('db-visualizer.cache_key').'_composer';

        $this->get('/dbv')->assertOk();

        expect(Cache::has($key))->toBeTrue();
    });

    it('serves extraPackages from cache on subsequent requests', function () {
        $key = config('db-visualizer.cache_key').'_composer';

        $this->get('/dbv')->assertOk();

        $first = Cache::get($key);

        $this->get('/dbv')->assertOk();

        expect(Cache::get($key))->toEqual($first);
    });

    it('returns 403 when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->get('/dbv')->assertStatus(403);
    });
});
