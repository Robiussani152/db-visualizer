<?php

use Illuminate\Support\Facades\Cache;

describe('db-visualizer:clear-cache command', function () {
    it('forgets all db-visualizer cache keys', function () {
        $key = config('db-visualizer.cache_key');

        Cache::put($key, 'scan-data');
        Cache::put($key.'_full_result', 'full-result');
        Cache::put($key.'_file_hash', 'file-hash');
        Cache::put($key.'_composer', 'composer-data');

        $this->artisan('db-visualizer:clear-cache');

        expect(Cache::has($key))->toBeFalse()
            ->and(Cache::has($key.'_full_result'))->toBeFalse()
            ->and(Cache::has($key.'_file_hash'))->toBeFalse()
            ->and(Cache::has($key.'_composer'))->toBeFalse();
    });

    it('outputs a success message', function () {
        $this->artisan('db-visualizer:clear-cache')
            ->expectsOutput('DB Visualizer cache cleared successfully.')
            ->assertExitCode(0);
    });

    it('runs successfully when cache keys do not exist', function () {
        $this->artisan('db-visualizer:clear-cache')
            ->assertExitCode(0);
    });
});
