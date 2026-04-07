<?php

namespace Naimul\DbVisualizer\Services;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Naimul\DbVisualizer\Repositories\ModelRepository;
use Naimul\DbVisualizer\Repositories\SchemaRepository;

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

            $tracker = app(FileChangeTrackerService::class);
            $changedFiles = $tracker->getChangedFiles($paths);

            if (empty($changedFiles)) {
                return Cache::get('db_visualizer_last_full_result', []);
            }

            // LOAD MODELS
            $models = $this->models->all();

            foreach ($models as $modelClass) {

                try {
                    $model = new $modelClass;

                    if (! method_exists($model, 'getTable')) {
                        continue;
                    }

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

            //  NEW: TABLE ANALYSIS
            $allTables = $this->schema->allTables();

            $modelTables = array_map(function ($item) {
                return $item['table'];
            }, $data);

            $orphanTables = array_values(array_diff($allTables, $modelTables));

            // ANALYZE
            $result = $this->usageAnalyzer->analyze($data);

            // FINAL RESPONSE
            $final = [
                'models' => $result,
                'meta' => [
                    'total_models' => count($result),
                    'total_tables' => count($allTables),
                    'orphan_tables_count' => count($orphanTables),
                    'orphan_tables' => $orphanTables,
                ],
            ];

            Cache::put('db_visualizer_last_full_result', $final, now()->addDays(7));

            return $final;
        });
    }

    private function hasSoftDeletes($model)
    {
        return in_array(
            SoftDeletes::class,
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

            if (! is_dir($path)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            ) as $file) {

                if ($file->isDir()) {
                    continue;
                }

                if (! in_array($file->getExtension(), ['php', 'blade.php'])) {
                    continue;
                }

                $hashString .= $file->getPathname().':'.$file->getMTime().';';
            }
        }

        return md5($hashString);
    }
}
