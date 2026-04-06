<?php

namespace Naimul\DbVisualizer\Services;

use Naimul\DbVisualizer\Repositories\ModelRepository;
use Naimul\DbVisualizer\Repositories\SchemaRepository;
use Illuminate\Support\Facades\Cache;

class ModelScannerService
{
    protected $models;
    protected $schema;
    protected $relations;
    protected $usageAnalyzer;

    public function __construct(
        ModelRepository $models,
        SchemaRepository $schema,
        RelationshipResolverService $relations,
        RelationUsageAnalyzerService $usageAnalyzer
    ) {
        $this->models = $models;
        $this->schema = $schema;
        $this->relations = $relations;
        $this->usageAnalyzer = $usageAnalyzer;
    }

    public function scan()
    {
        $cacheKey = 'db_visualizer_incremental_v1';

        return Cache::remember($cacheKey, 3600, function () {

            $data = [];

            $paths = [
                app_path(),
                resource_path('views'),
                base_path('Modules'),
            ];

            // 🔥 STEP 1: get changed files only
            $tracker = app(\Naimul\DbVisualizer\Services\FileChangeTrackerService::class);
            $changedFiles = $tracker->getChangedFiles($paths);

            // If nothing changed → return cached full result
            if (empty($changedFiles)) {
                return Cache::get('db_visualizer_last_full_result', []);
            }

            // 🔥 STEP 2: still need models list
            $models = $this->models->all();

            foreach ($models as $modelClass) {

                try {
                    $model = new $modelClass;

                    if (!method_exists($model, 'getTable')) continue;

                    $table = $model->getTable();

                    $data[] = [
                        'name' => class_basename($modelClass),
                        'model' => class_basename($modelClass),
                        'table' => $table,
                        'columns' => $this->schema->columns($table),
                        'relations' => $this->relations->resolve($model),
                        'soft_deletes' => $this->hasSoftDeletes($model),
                    ];

                } catch (\Throwable $e) {
                    continue;
                }
            }

            $result = $this->usageAnalyzer->analyze($data);

            // STORE FULL RESULT FOR FAST NEXT TIME
            Cache::put('db_visualizer_last_full_result', $result, now()->addDays(7));

            return $result;
        });
    }

    private function hasSoftDeletes($model)
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($model)
        );
    }
    protected function generateProjectHash(): string
    {
        $paths = [
            app_path(),
            resource_path('views'),
            base_path('Modules'),
        ];

        $hashString = '';

        foreach ($paths as $path) {

            if (!is_dir($path)) continue;

            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            ) as $file) {

                if ($file->isDir()) continue;

                if (!in_array($file->getExtension(), ['php', 'blade.php'])) {
                    continue;
                }

                $hashString .= $file->getPathname() . ':' . $file->getMTime() . ';';
            }
        }

        return md5($hashString);
    }
}