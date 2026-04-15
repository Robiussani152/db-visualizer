<?php

namespace Naimul\DbVisualizer\Repositories;

use Illuminate\Support\Facades\File;

class ModelRepository
{
    /**
     * Get all models (App + Modules)
     */
    public function all()
    {
        return array_merge(
            $this->getAppModels(),
            $this->getModuleModels()
        );
    }

    /**
     * App\Models থেকে models load
     */
    protected function getAppModels()
    {
        $models = [];
        $path = app_path('Models');

        if (! is_dir($path)) {
            return [];
        }

        foreach (File::allFiles($path) as $file) {

            $class = $this->getClassFromFile($file, 'App\\Models');

            if ($class && class_exists($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * Modules থেকে models load (nwidart)
     */
    protected function getModuleModels()
    {
        $models = [];
        $modulesPath = base_path('Modules');

        if (! is_dir($modulesPath)) {
            return [];
        }

        $modules = scandir($modulesPath);

        foreach ($modules as $module) {

            if ($module === '.' || $module === '..') {
                continue;
            }

            $moduleBase = $modulesPath.DIRECTORY_SEPARATOR.$module;

            /**
             * 1. Entities (existing)
             */
            $entitiesPath = $moduleBase.DIRECTORY_SEPARATOR.'Entities';

            if (is_dir($entitiesPath)) {
                foreach (File::allFiles($entitiesPath) as $file) {

                    $class = $this->getClassFromFile(
                        $file,
                        "Modules\\{$module}\\Entities"
                    );

                    if ($class && class_exists($class)) {
                        $models[] = $class;
                    }
                }
            }

            /**
             * 2. Models (existing)
             */
            $modelsPath = $moduleBase.DIRECTORY_SEPARATOR.'Models';

            if (is_dir($modelsPath)) {
                foreach (File::allFiles($modelsPath) as $file) {

                    $class = $this->getClassFromFile(
                        $file,
                        "Modules\\{$module}\\Models"
                    );

                    if ($class && class_exists($class)) {
                        $models[] = $class;
                    }
                }
            }

            /**
             * 3. NEW: App/Models (YOUR CASE)
             */
            $appModelsPath = $moduleBase.DIRECTORY_SEPARATOR.'App'.DIRECTORY_SEPARATOR.'Models';

            if (is_dir($appModelsPath)) {
                foreach (File::allFiles($appModelsPath) as $file) {

                    $class = $this->getClassFromFile(
                        $file,
                        "Modules\\{$module}\\App\\Models"
                    );
                    $anotherClass = $this->getClassFromFile(
                        $file,
                        "Modules\\{$module}\\Models"
                    );

                    if ($class && class_exists($class)) {
                        $models[] = $class;
                    }

                    if ($anotherClass && class_exists($anotherClass)) {
                        $models[] = $anotherClass;
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Convert file → full class name
     */
    protected function getClassFromFile($file, $baseNamespace)
    {
        $filename = $file->getFilenameWithoutExtension();

        return $baseNamespace.'\\'.$filename;
    }
}
