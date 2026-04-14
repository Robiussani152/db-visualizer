<?php

namespace Naimul\DbVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Naimul\DbVisualizer\Services\ModelScannerService;

class VisualizerController extends Controller
{
    protected $scanner;

    public function __construct(ModelScannerService $scanner)
    {
        $this->scanner = $scanner;
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');

        return back()->with('success', 'Cache cleared successfully!');
    }

    public function index()
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

        // user installed packages (direct require)
        $requires = array_keys($composerJson['require'] ?? []);

        // lock packages
        $lockPackages = collect($composerLock['packages'] ?? []);

        $extraPackages = $lockPackages
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

        return view('dbv::visualizer.index', compact('extraPackages'));
    }

    public function data(Request $request)
    {
        $response = $this->scanner->scan();

        $data = $response['models'] ?? [];
        $meta = $response['meta'] ?? [];

        $search = $request->get('search');

        if ($search) {
            $data = array_values(array_filter($data, function ($item) use ($search) {
                return str_contains(
                    strtolower($item['model']),
                    strtolower($search)
                );
            }));
        }

        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    public function detail($model)
    {
        $response = $this->scanner->scan();

        $models = $response['models'] ?? [];

        $data = collect($models)->firstWhere('model', $model);

        if (! $data) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        return response()->json($data);
    }
}
