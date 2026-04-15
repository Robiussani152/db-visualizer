<?php

namespace Naimul\DbVisualizer\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class FileChangeTrackerService
{
    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = config('db-visualizer.cache_key').'_file_hash';
    }

    public function getChangedFiles(array $paths): array
    {
        $previous = Cache::get($this->cacheKey, []);
        $current = [];

        $changedFiles = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {

                if (
                    $file->getExtension() !== 'php' &&
                    ! $this->isBlade($file)
                ) {
                    continue;
                }

                $filePath = $file->getPathname();
                $hash = md5_file($filePath);

                $current[$filePath] = $hash;

                if (! isset($previous[$filePath]) || $previous[$filePath] !== $hash) {
                    $changedFiles[] = $filePath;
                }
            }
        }

        Cache::put($this->cacheKey, $current, now()->addDays(7));

        return $changedFiles;
    }

    protected function isBlade($file): bool
    {
        return str_ends_with($file->getFilename(), '.blade.php');
    }
}
