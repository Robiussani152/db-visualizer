<?php

use Illuminate\Support\Facades\Cache;
use Naimul\DbVisualizer\Repositories\ModelRepository;
use Naimul\DbVisualizer\Repositories\SchemaRepository;
use Naimul\DbVisualizer\Services\FileChangeTrackerService;
use Naimul\DbVisualizer\Services\ModelScannerService;
use Naimul\DbVisualizer\Services\RelationshipResolverService;
use Naimul\DbVisualizer\Services\RelationUsageAnalyzerService;

function makeScanner(
    array $models = [],
    array $columns = [],
    array $allTables = [],
    array $changedFiles = ['some/file.php'],
): ModelScannerService {
    $modelRepo = Mockery::mock(ModelRepository::class);
    $modelRepo->shouldReceive('all')->andReturn($models);

    $schemaRepo = Mockery::mock(SchemaRepository::class);
    $schemaRepo->shouldReceive('columns')->andReturn($columns);
    $schemaRepo->shouldReceive('allTables')->andReturn($allTables);

    $resolver = Mockery::mock(RelationshipResolverService::class);
    $resolver->shouldReceive('resolve')->andReturn([]);

    $analyzer = Mockery::mock(RelationUsageAnalyzerService::class);
    $analyzer->shouldReceive('analyze')->andReturnUsing(fn ($data) => $data);

    $tracker = Mockery::mock(FileChangeTrackerService::class);
    $tracker->shouldReceive('getChangedFiles')->andReturn($changedFiles);

    app()->instance(FileChangeTrackerService::class, $tracker);

    return new ModelScannerService($modelRepo, $schemaRepo, $resolver, $analyzer);
}

beforeEach(function () {
    $key = config('db-visualizer.cache_key');
    Cache::forget($key);
    Cache::forget($key.'_full_result');
});

describe('ModelScannerService', function () {
    it('returns models and meta keys', function () {
        $result = makeScanner()->scan();

        expect($result)->toHaveKey('models')->toHaveKey('meta');
    });

    it('meta contains expected counts', function () {
        $result = makeScanner(allTables: ['users', 'posts'])->scan();

        expect($result['meta'])
            ->toHaveKey('total_models')
            ->toHaveKey('total_tables')
            ->toHaveKey('orphan_tables_count')
            ->toHaveKey('orphan_tables');
    });

    it('identifies orphan tables not backed by a model', function () {
        $result = makeScanner(allTables: ['users', 'orphan_table'])->scan();

        expect($result['meta']['orphan_tables'])->toContain('orphan_table');
    });

    it('caches the result after the first scan', function () {
        $key = config('db-visualizer.cache_key');

        makeScanner()->scan();

        expect(Cache::has($key))->toBeTrue();
    });

    it('returns cached result on subsequent calls', function () {
        $scanner = makeScanner();

        $first = $scanner->scan();
        $second = $scanner->scan();

        expect($second)->toEqual($first);
    });

    it('returns last full result when no files have changed', function () {
        $key = config('db-visualizer.cache_key').'_full_result';
        $cached = ['models' => [], 'meta' => ['total_models' => 99]];
        Cache::put($key, $cached, now()->addHour());

        $result = makeScanner(changedFiles: [])->scan();

        expect($result['meta']['total_models'])->toBe(99);
    });

    it('returns empty array when no files changed and no cached full result', function () {
        $result = makeScanner(changedFiles: [])->scan();

        expect($result)->toBeArray()->toBeEmpty();
    });
});
