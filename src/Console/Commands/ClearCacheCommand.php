<?php

namespace Naimul\DbVisualizer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCacheCommand extends Command
{
    protected $signature = 'db-visualizer:clear-cache';

    protected $description = 'Clear all DB Visualizer cached data';

    public function handle(): void
    {
        $key = config('db-visualizer.cache_key');

        Cache::forget($key);
        Cache::forget($key.'_full_result');
        Cache::forget($key.'_file_hash');
        Cache::forget($key.'_composer');

        $this->info('DB Visualizer cache cleared successfully.');
    }
}
