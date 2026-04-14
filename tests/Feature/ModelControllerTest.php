<?php

use Illuminate\Support\Facades\Gate;
use Naimul\DbVisualizer\Services\ModelScannerService;

describe('ModelController@index', function () {
    beforeEach(function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => true);
    });

    it('returns json with data and meta keys', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn([
                'models' => [
                    ['model' => 'User', 'table' => 'users'],
                    ['model' => 'Post', 'table' => 'posts'],
                ],
                'meta' => ['total_models' => 2],
            ]);

        $this->getJson('/dbv/data')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonCount(2, 'data');
    });

    it('filters models by search term', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn([
                'models' => [
                    ['model' => 'User', 'table' => 'users'],
                    ['model' => 'Post', 'table' => 'posts'],
                ],
                'meta' => [],
            ]);

        $this->getJson('/dbv/data?search=user')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model', 'User');
    });

    it('search is case insensitive', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn([
                'models' => [
                    ['model' => 'User', 'table' => 'users'],
                    ['model' => 'Post', 'table' => 'posts'],
                ],
                'meta' => [],
            ]);

        $this->getJson('/dbv/data?search=POST')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model', 'Post');
    });

    it('returns all models when no search term is given', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn([
                'models' => [
                    ['model' => 'User', 'table' => 'users'],
                    ['model' => 'Post', 'table' => 'posts'],
                ],
                'meta' => [],
            ]);

        $this->getJson('/dbv/data')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns 403 when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->getJson('/dbv/data')->assertStatus(403);
    });
});

describe('ModelController@show', function () {
    beforeEach(function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => true);
    });

    it('returns the matched model as json', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn([
                'models' => [
                    ['model' => 'User', 'table' => 'users'],
                ],
            ]);

        $this->getJson('/dbv/detail/User')
            ->assertOk()
            ->assertJsonPath('model', 'User');
    });

    it('returns 404 when model is not found', function () {
        $this->mock(ModelScannerService::class)
            ->shouldReceive('scan')
            ->andReturn(['models' => []]);

        $this->getJson('/dbv/detail/Missing')
            ->assertNotFound()
            ->assertJsonPath('message', 'Model not found');
    });

    it('returns 404 for model names with invalid characters', function () {
        $this->getJson('/dbv/detail/../../etc/passwd')->assertNotFound();
    });

    it('returns 403 when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $this->getJson('/dbv/detail/User')->assertStatus(403);
    });
});
