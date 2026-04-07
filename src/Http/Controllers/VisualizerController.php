<?php

namespace Naimul\DbVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Naimul\DbVisualizer\Services\ModelScannerService;

class VisualizerController extends Controller
{
    protected $scanner;

    public function __construct(ModelScannerService $scanner)
    {
        if (app()->environment(['production', 'staging'])) {
            abort(403, 'DB Visualizer disabled');
        }
        $this->scanner = $scanner;
    }

    public function index()
    {
        return view('dbv::visualizer.index');
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
