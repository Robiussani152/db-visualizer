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
}
