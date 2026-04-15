<?php

use Naimul\DbVisualizer\Services\RelationUsageAnalyzerService;

// Helper: build a service instance with a controlled file set
function makeAnalyzer(array $files): RelationUsageAnalyzerService
{
    $service = new RelationUsageAnalyzerService;
    (fn () => $this->fileCache = $files)->call($service);

    return $service;
}

describe('isRelationUsed', function () {
    it('detects with() call', function () {
        $service = makeAnalyzer(["->with('posts')"]);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects with() using double quotes', function () {
        $service = makeAnalyzer(['->with("posts")']);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects with() array syntax', function () {
        $service = makeAnalyzer(["->with(['posts', 'comments'])"]);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects whereHas()', function () {
        $service = makeAnalyzer(["->whereHas('posts')"]);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects load()', function () {
        $service = makeAnalyzer(["->load('posts')"]);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects withCount()', function () {
        $service = makeAnalyzer(["->withCount('posts')"]);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('detects direct property access', function () {
        $service = makeAnalyzer(['$user->posts']);
        expect($service->isRelationUsed('posts'))->toBeTrue();
    });

    it('returns false when relation is not used', function () {
        $service = makeAnalyzer(['$user->name']);
        expect($service->isRelationUsed('posts'))->toBeFalse();
    });

    it('returns false on empty file set', function () {
        $service = makeAnalyzer([]);
        expect($service->isRelationUsed('posts'))->toBeFalse();
    });

    it('handles relation names with regex special characters safely', function () {
        $service = makeAnalyzer(["->with('user.profile')"]);
        // Should not throw — preg_quote prevents regex injection
        expect(fn () => $service->isRelationUsed('user.profile'))->not->toThrow(Exception::class);
    });
});

describe('isColumnUsed', function () {
    it('detects property access', function () {
        $service = makeAnalyzer(['$model->email']);
        expect($service->isColumnUsed('email'))->toBeTrue();
    });

    it('detects string reference', function () {
        $service = makeAnalyzer(["'email'"]);
        expect($service->isColumnUsed('email'))->toBeTrue();
    });

    it('detects orderBy()', function () {
        $service = makeAnalyzer(["->orderBy('email')"]);
        expect($service->isColumnUsed('email'))->toBeTrue();
    });

    it('detects where()', function () {
        $service = makeAnalyzer(["->where('email')"]);
        expect($service->isColumnUsed('email'))->toBeTrue();
    });

    it('always marks system columns as used', function () {
        $service = makeAnalyzer([]);

        expect($service->isColumnUsed('id'))->toBeTrue()
            ->and($service->isColumnUsed('created_at'))->toBeTrue()
            ->and($service->isColumnUsed('updated_at'))->toBeTrue()
            ->and($service->isColumnUsed('deleted_at'))->toBeTrue();
    });

    it('returns false for unused column', function () {
        $service = makeAnalyzer(['$model->name']);
        expect($service->isColumnUsed('email'))->toBeFalse();
    });
});

describe('detectNPlusOne', function () {
    it('detects N+1 pattern inside foreach', function () {
        $service = makeAnalyzer([
            "foreach (\$users as \$user) { \$user->posts; }",
        ]);
        expect($service->detectNPlusOne('posts'))->toBeTrue();
    });

    it('returns false when eager loaded', function () {
        $service = makeAnalyzer([
            "->with('posts'); foreach (\$users as \$user) { \$user->posts; }",
        ]);
        expect($service->detectNPlusOne('posts'))->toBeFalse();
    });

    it('returns false when no loop present', function () {
        $service = makeAnalyzer(['$user->posts']);
        expect($service->detectNPlusOne('posts'))->toBeFalse();
    });

    it('returns false when relation not in file', function () {
        $service = makeAnalyzer(['some unrelated content']);
        expect($service->detectNPlusOne('posts'))->toBeFalse();
    });
});

describe('isEagerLoaded', function () {
    it('returns true when with() is present', function () {
        $service = makeAnalyzer(["->with('posts')"]);
        expect($service->isEagerLoaded('posts'))->toBeTrue();
    });

    it('returns false when with() is absent', function () {
        $service = makeAnalyzer(['$user->posts']);
        expect($service->isEagerLoaded('posts'))->toBeFalse();
    });
});

describe('isCacheUsed', function () {
    it('detects Cache::remember', function () {
        $service = makeAnalyzer(['Cache::remember(']);
        expect($service->isCacheUsed())->toBeTrue();
    });

    it('detects cache()->remember', function () {
        $service = makeAnalyzer(['cache()->remember(']);
        expect($service->isCacheUsed())->toBeTrue();
    });

    it('returns false when no cache usage', function () {
        $service = makeAnalyzer(['$user->posts']);
        expect($service->isCacheUsed())->toBeFalse();
    });
});

describe('usesApiResource', function () {
    it('detects model resource usage', function () {
        $service = makeAnalyzer(['UserResource::collection(']);
        expect($service->usesApiResource('User'))->toBeTrue();
    });

    it('returns false when resource not used', function () {
        $service = makeAnalyzer(['PostResource::collection(']);
        expect($service->usesApiResource('User'))->toBeFalse();
    });
});

describe('analyze', function () {
    it('adds performance and quality fields to each model', function () {
        $service = makeAnalyzer([]);

        $result = $service->analyze([
            [
                'model' => 'User',
                'columns' => ['id', 'email'],
                'relations' => [],
                'soft_deletes' => false,
            ],
        ]);

        expect($result[0])
            ->toHaveKey('performance_score')
            ->toHaveKey('quality_label')
            ->toHaveKey('complexity')
            ->toHaveKey('columns_detailed')
            ->toHaveKey('unused_relations_count')
            ->toHaveKey('unused_columns_count');
    });

    it('performance score is clamped between 0 and 100', function () {
        $service = makeAnalyzer([]);

        // Many unused relations to drive score below zero
        $relations = array_map(fn ($i) => ['method' => "relation{$i}"], range(1, 20));

        $result = $service->analyze([[
            'model' => 'User',
            'columns' => [],
            'relations' => $relations,
            'soft_deletes' => false,
        ]]);

        expect($result[0]['performance_score'])->toBeGreaterThanOrEqual(0);
    });

    it('assigns quality labels correctly', function () {
        $service = makeAnalyzer([]);

        $result = $service->analyze([[
            'model' => 'User',
            'columns' => ['id'],
            'relations' => [],
            'soft_deletes' => false,
        ]]);

        expect($result[0]['quality_label'])->toBeIn([
            'Excellent Quality', 'Good Quality', 'Average Quality', 'Poor Quality',
        ]);
    });

    it('soft_deletes adds bonus points', function () {
        // Use unused columns to push the score below 100 so the +5 bonus is visible
        $unusedColumns = ['col1', 'col2', 'col3', 'col4', 'col5', 'col6', 'col7'];
        $model = ['model' => 'User', 'columns' => $unusedColumns, 'relations' => []];

        $with = makeAnalyzer([])->analyze([array_merge($model, ['soft_deletes' => true])]);
        $without = makeAnalyzer([])->analyze([array_merge($model, ['soft_deletes' => false])]);

        expect($with[0]['performance_score'])->toBeGreaterThan($without[0]['performance_score']);
    });
});
