<?php

namespace Naimul\DbVisualizer\Http\Controllers;

class VisualizerController extends Controller
{
    public function __invoke()
    {
        $extraPackages = cache()->remember(
            config('db-visualizer.cache_key').'_composer',
            config('db-visualizer.cache_ttl'),
            function () {
                $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
                $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

                // user installed packages (direct require)
                $requires = array_keys($composerJson['require'] ?? []);

                // lock packages
                $lockPackages = collect($composerLock['packages'] ?? []);

                return $lockPackages
                    ->filter(function ($pkg) use ($requires) {
                        return in_array($pkg['name'], $requires, true);
                    })
                    ->reject(function ($pkg) {
                        // remove Laravel core
                        return $pkg['name'] === 'laravel/framework';
                    })
                    ->map(function ($pkg) {
                        return [
                            'name' => $pkg['name'],
                            'version' => $pkg['version'],
                            'description' => $pkg['description'] ?? '',
                            'type' => $pkg['type'] ?? '',
                        ];
                    })
                    ->sortBy('name')
                    ->values();
            }
        );

        return view('dbv::visualizer.index', compact('extraPackages'));
    }
}
