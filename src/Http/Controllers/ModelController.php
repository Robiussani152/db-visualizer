<?php

namespace Naimul\DbVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Naimul\DbVisualizer\Services\ModelScannerService;

class ModelController extends Controller
{
    public function __construct(protected ModelScannerService $scanner) {}

    public function index(Request $request)
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

    public function show($model)
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
