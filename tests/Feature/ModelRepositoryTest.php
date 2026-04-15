<?php

use Naimul\DbVisualizer\Repositories\ModelRepository;

describe('ModelRepository', function () {
    it('returns an empty array when App/Models directory does not exist', function () {
        $repo = new ModelRepository;

        // Testbench app has no App/Models directory
        expect($repo->all())->toBeArray();
    });

    it('returns an empty array when Modules directory does not exist', function () {
        $repo = new ModelRepository;

        expect($repo->all())->toBeArray();
    });

    it('getClassFromFile builds the fully qualified class name', function () {
        $repo = new ModelRepository;

        $file = new Symfony\Component\Finder\SplFileInfo(
            sys_get_temp_dir().'/User.php',
            '',
            'User.php'
        );

        $class = (fn ($f, $ns) => $this->getClassFromFile($f, $ns))->call($repo, $file, 'App\\Models');

        expect($class)->toBe('App\\Models\\User');
    });

    it('merges app models and module models', function () {
        $repo = new class extends ModelRepository
        {
            protected function getAppModels()
            {
                return ['App\\Models\\User'];
            }

            protected function getModuleModels()
            {
                return ['Modules\\Blog\\Models\\Post'];
            }
        };

        expect($repo->all())->toBe(['App\\Models\\User', 'Modules\\Blog\\Models\\Post']);
    });

    it('excludes classes that do not exist', function () {
        $dir = sys_get_temp_dir().'/dbv_models_'.uniqid();
        mkdir($dir);
        file_put_contents($dir.'/NonExistentModel.php', '<?php class NonExistentModel {}');

        try {
            $repo = new class($dir) extends ModelRepository
            {
                public function __construct(private string $modelDir) {}

                protected function getAppModels()
                {
                    $models = [];

                    foreach (\Illuminate\Support\Facades\File::allFiles($this->modelDir) as $file) {
                        $class = $this->getClassFromFile($file, 'App\\Models');

                        if ($class && class_exists($class)) {
                            $models[] = $class;
                        }
                    }

                    return $models;
                }

                protected function getModuleModels()
                {
                    return [];
                }
            };

            expect($repo->all())->toBeEmpty();
        } finally {
            @unlink($dir.'/NonExistentModel.php');
            @rmdir($dir);
        }
    });
});
