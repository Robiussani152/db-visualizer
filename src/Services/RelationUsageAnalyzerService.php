<?php

namespace Naimul\DbVisualizer\Services;

use Illuminate\Support\Facades\File;

class RelationUsageAnalyzerService
{
    protected array $fileCache = [];

    /**
     * System columns always considered used
     */
    protected array $systemColumns = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /* =========================
        LOAD FILES (CACHED)
    ========================= */
    protected function getAllFiles(): array
    {
        if (!empty($this->fileCache)) {
            return $this->fileCache;
        }

        $paths = [
            app_path(),
            resource_path('views'),
        ];

        $modulesPath = base_path('Modules');

        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $module) {
                if ($module === '.' || $module === '..') continue;

                $modulePath = $modulesPath . DIRECTORY_SEPARATOR . $module;
                $paths[] = $modulePath;

                $views = $modulePath . '/Resources/views';
                if (is_dir($views)) {
                    $paths[] = $views;
                }
            }
        }

        $files = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) continue;

            foreach (File::allFiles($path) as $file) {

                if (
                    $file->getExtension() === 'php' ||
                    str_ends_with($file->getFilename(), '.blade.php')
                ) {
                    $files[] = File::get($file);
                }
            }
        }

        return $this->fileCache = $files;
    }

    /* =========================
        REMOVE COMMENTS
    ========================= */
    protected function removeComments(string $code): string
    {
        return preg_replace([
            '/\/\/.*$/m',
            '/\/\*.*?\*\//s'
        ], '', $code);
    }

    /* =========================
        RELATION USAGE DETECTION
    ========================= */
    public function isRelationUsed(string $relation): bool
    {
        foreach ($this->getAllFiles() as $content) {

            $content = $this->removeComments($content);

            if (
                // with('relation')
                preg_match("/with\(\s*['\"]{$relation}['\"]\s*\)/", $content) ||

                // with(['relation'])
                preg_match("/with\(\s*\[.*['\"]{$relation}['\"].*\]\s*\)/s", $content) ||

                // whereHas('relation')
                preg_match("/whereHas\(\s*['\"]{$relation}['\"]\s*\)/", $content) ||

                // load('relation')
                preg_match("/load\(\s*['\"]{$relation}['\"]\s*\)/", $content) ||

                // withCount('relation')
                preg_match("/withCount\(\s*['\"]{$relation}['\"]\s*\)/", $content) ||

                // direct access ->relation
                preg_match("/->{$relation}\b/", $content)
            ) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        COLUMN USAGE
    ========================= */
    public function isColumnUsed(string $column): bool
    {
        if (in_array($column, $this->systemColumns, true)) {
            return true;
        }

        foreach ($this->getAllFiles() as $content) {

            $content = $this->removeComments($content);

            if (
                preg_match("/->{$column}\b/", $content) ||
                preg_match("/['\"]{$column}['\"]/", $content) ||
                preg_match("/orderBy\(['\"]{$column}['\"]\)/", $content) ||
                preg_match("/where\(['\"]{$column}['\"]\)/", $content)
            ) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        N+1 DETECTION
    ========================= */
    public function detectNPlusOne(string $relation): bool
    {
        foreach ($this->getAllFiles() as $content) {

            if (!str_contains($content, $relation)) {
                continue;
            }

            $clean = $this->removeComments($content);

            $hasLoop = preg_match('/foreach\s*\(.*\)\s*\{/', $clean);

            $hasRelationInLoop = preg_match(
                "/foreach\s*\(.*\)\s*\{[^}]*->{$relation}\b/s",
                $clean
            );

            $hasEagerLoad = preg_match(
                "/with\(\s*['\"]{$relation}['\"]\s*\)/",
                $clean
            );

            if ($hasLoop && $hasRelationInLoop && !$hasEagerLoad) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        EAGER LOAD CHECK
    ========================= */
    public function isEagerLoaded(string $relation): bool
    {
        foreach ($this->getAllFiles() as $content) {
            if (preg_match("/with\(\s*['\"]{$relation}['\"]\s*\)/", $content)) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        CACHE DETECTION
    ========================= */
    public function isCacheUsed(): bool
    {
        foreach ($this->getAllFiles() as $content) {
            if (
                str_contains($content, 'Cache::remember') ||
                str_contains($content, 'cache()->remember')
            ) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        API RESOURCE DETECTION
    ========================= */
    public function usesApiResource(string $modelName): bool
    {
        foreach ($this->getAllFiles() as $content) {
            if (str_contains($content, $modelName . 'Resource')) {
                return true;
            }
        }

        return false;
    }

    /* =========================
        MAIN ANALYZE
    ========================= */
    public function analyze(array $modelsData): array
    {
        foreach ($modelsData as &$model) {

            $unusedRelations = 0;
            $unusedColumns = 0;
            $nPlusOne = 0;
            $missingEager = 0;

            /* =========================
                RELATIONS
            ========================= */
            foreach ($model['relations'] ?? [] as &$relation) {

                $name = $relation['method'];

                $relation['used'] = $this->isRelationUsed($name);
                $relation['n_plus_one'] = $this->detectNPlusOne($name);
                $relation['missing_eager'] = $relation['used'] && !$this->isEagerLoaded($name);

                if (!$relation['used']) $unusedRelations++;
                if ($relation['n_plus_one']) $nPlusOne++;
                if ($relation['missing_eager']) $missingEager++;
            }

            /* =========================
                COLUMNS
            ========================= */
            $columnsDetailed = [];

            foreach ($model['columns'] ?? [] as $col) {

                $used = $this->isColumnUsed($col);

                $columnsDetailed[] = [
                    'name' => $col,
                    'used' => $used,
                ];

                if (!$used) $unusedColumns++;
            }

            /* =========================
                COMPLEXITY
            ========================= */
            $scoreBase = count($model['columns'] ?? []) + (count($model['relations'] ?? []) * 2);

            $complexity = match (true) {
                $scoreBase <= 10 => 'Low',
                $scoreBase <= 25 => 'Medium',
                $scoreBase <= 65 => 'Better',
                default => 'High',
            };

            /* =========================
                PERFORMANCE SCORE
            ========================= */
            $score = 100;

            $score -= $unusedRelations * 10;
            $score -= $unusedColumns * 2;
            $score -= $nPlusOne * 15;
            $score -= $missingEager * 10;

            if ($complexity === 'High') $score -= 10;

            if (!empty($model['soft_deletes'])) $score += 5;
            if ($this->isCacheUsed()) $score += 5;
            if ($this->usesApiResource($model['model'])) $score += 5;

            $model['unused_relations_count'] = $unusedRelations;
            $model['unused_columns_count'] = $unusedColumns;
            $model['n_plus_one_issues'] = $nPlusOne;
            $model['missing_eager_loads'] = $missingEager;
            $model['columns_detailed'] = $columnsDetailed;
            $model['complexity'] = $complexity;
            $model['performance_score'] = max(0, min(100, $score));

            $model['quality_label'] = match (true) {
                $model['performance_score'] >= 90 => 'Excellent Quality',
                $model['performance_score'] >= 75 => 'Good Quality',
                $model['performance_score'] >= 50 => 'Average Quality',
                default => 'Poor Quality',
            };
        }

        return $modelsData;
    }
}