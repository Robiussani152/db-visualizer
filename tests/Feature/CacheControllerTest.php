<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

describe('CacheController', function () {
    beforeEach(function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => true);
    });

    it('forgets all db-visualizer cache keys', function () {
        $key = config('db-visualizer.cache_key');

        Cache::put($key, 'scan-data');
        Cache::put($key.'_full_result', 'full-result');
        Cache::put($key.'_file_hash', 'file-hash');
        Cache::put($key.'_composer', 'composer-data');

        $this->post('/dbv/cache-clear');

        expect(Cache::has($key))->toBeFalse()
            ->and(Cache::has($key.'_full_result'))->toBeFalse()
            ->and(Cache::has($key.'_file_hash'))->toBeFalse()
            ->and(Cache::has($key.'_composer'))->toBeFalse();
    });

    it('redirects back with a success message', function () {
        $this->post('/dbv/cache-clear')
            ->assertRedirect()
            ->assertSessionHas('success', 'Cache cleared successfully!');
    });

    it('returns 403 when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->post('/dbv/cache-clear')->assertStatus(403);
    });
});
