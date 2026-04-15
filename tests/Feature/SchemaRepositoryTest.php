<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Naimul\DbVisualizer\Repositories\SchemaRepository;

describe('SchemaRepository', function () {
    describe('columns()', function () {
        beforeEach(function () {
            Schema::create('dbv_test_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        });

        afterEach(function () {
            Schema::dropIfExists('dbv_test_users');
        });

        it('returns the column listing for a table', function () {
            $columns = (new SchemaRepository)->columns('dbv_test_users');

            expect($columns)->toContain('id')
                ->toContain('name')
                ->toContain('email')
                ->toContain('created_at')
                ->toContain('updated_at');
        });

        it('returns an empty array for a non-existent table', function () {
            $columns = (new SchemaRepository)->columns('dbv_non_existent');

            expect($columns)->toBeArray()->toBeEmpty();
        });
    });

    describe('allTables()', function () {
        it('returns a flat array of table names', function () {
            DB::shouldReceive('select')
                ->with('SHOW TABLES')
                ->andReturn([
                    (object) ['Tables_in_db' => 'users'],
                    (object) ['Tables_in_db' => 'posts'],
                ]);

            $tables = (new SchemaRepository)->allTables();

            expect($tables)->toBe(['users', 'posts']);
        });

        it('returns an empty array when no tables exist', function () {
            DB::shouldReceive('select')
                ->with('SHOW TABLES')
                ->andReturn([]);

            $tables = (new SchemaRepository)->allTables();

            expect($tables)->toBeArray()->toBeEmpty();
        });
    });
});
