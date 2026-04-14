<?php

namespace Naimul\DbVisualizer\Tests;

use Naimul\DbVisualizer\DbVisualizerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [DbVisualizerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('db-visualizer.enabled', true);
        $app['config']->set('db-visualizer.path', 'dbv');
        $app['config']->set('db-visualizer.middleware', [
            'web',
            \Naimul\DbVisualizer\Http\Middleware\Authorize::class,
        ]);
    }
}
