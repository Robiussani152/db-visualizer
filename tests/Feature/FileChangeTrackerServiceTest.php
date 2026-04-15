<?php

use Illuminate\Support\Facades\Cache;
use Naimul\DbVisualizer\Services\FileChangeTrackerService;

describe('FileChangeTrackerService', function () {
    beforeEach(function () {
        Cache::forget(config('db-visualizer.cache_key').'_file_hash');
    });

    it('reports all files as changed on first run', function () {
        $dir = __DIR__.'/../';

        $changed = (new FileChangeTrackerService)->getChangedFiles([$dir]);

        expect($changed)->not->toBeEmpty();
    });

    it('reports no changes on second run when files are unchanged', function () {
        $dir = __DIR__.'/../';

        $service = new FileChangeTrackerService;
        $service->getChangedFiles([$dir]); // first run — primes the cache

        $changed = $service->getChangedFiles([$dir]); // second run

        expect($changed)->toBeEmpty();
    });

    it('persists the file hash map in cache after a run', function () {
        $dir = __DIR__.'/../';
        $key = config('db-visualizer.cache_key').'_file_hash';

        (new FileChangeTrackerService)->getChangedFiles([$dir]);

        expect(Cache::has($key))->toBeTrue();
    });

    it('detects a new file as changed', function () {
        $dir = sys_get_temp_dir().'/dbv_test_'.uniqid();
        mkdir($dir);
        $file = $dir.'/model.php';

        try {
            $service = new FileChangeTrackerService;
            $service->getChangedFiles([$dir]); // first run — empty dir

            file_put_contents($file, '<?php // new file');

            $changed = $service->getChangedFiles([$dir]);

            expect($changed)->toContain($file);
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    });

    it('detects a modified file as changed', function () {
        $dir = sys_get_temp_dir().'/dbv_test_'.uniqid();
        mkdir($dir);
        $file = $dir.'/model.php';
        file_put_contents($file, '<?php // v1');

        try {
            $service = new FileChangeTrackerService;
            $service->getChangedFiles([$dir]); // prime cache

            file_put_contents($file, '<?php // v2'); // modify

            $changed = $service->getChangedFiles([$dir]);

            expect($changed)->toContain($file);
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    });

    it('ignores non-PHP files', function () {
        $dir = sys_get_temp_dir().'/dbv_test_'.uniqid();
        mkdir($dir);
        $file = $dir.'/styles.css';
        file_put_contents($file, 'body {}');

        try {
            $changed = (new FileChangeTrackerService)->getChangedFiles([$dir]);

            expect($changed)->not->toContain($file);
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    });

    it('skips non-existent paths', function () {
        $changed = (new FileChangeTrackerService)->getChangedFiles(['/non/existent/path']);

        expect($changed)->toBeEmpty();
    });
});
