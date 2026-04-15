<?php

namespace Naimul\DbVisualizer\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    public function __invoke()
    {
        $key = config('db-visualizer.cache_key');

        Cache::forget($key);
        Cache::forget($key.'_full_result');
        Cache::forget($key.'_file_hash');
        Cache::forget($key.'_composer');

        return back()->with('success', 'Cache cleared successfully!');
    }
}
