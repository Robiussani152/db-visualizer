<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Naimul\DbVisualizer\Services\RelationshipResolverService;
use Naimul\DbVisualizer\Services\RelationUsageAnalyzerService;

// Stub models used across tests
class StubPost extends Model
{
    protected $table = 'stub_posts';

    public function comments(): HasMany
    {
        return $this->hasMany(StubComment::class);
    }

    public function nonRelationMethod(): string
    {
        return 'hello';
    }

    public function methodWithParam(string $foo): string
    {
        return $foo;
    }

    public function __customMagic(): void {}
}

class StubComment extends Model
{
    protected $table = 'stub_comments';

    public function post(): BelongsTo
    {
        return $this->belongsTo(StubPost::class);
    }
}

class StubBrokenModel extends Model
{
    protected $table = 'stub_broken';

    public function broken(): HasMany
    {
        throw new \RuntimeException('intentional failure');
    }
}

function makeResolver(array $files = []): RelationshipResolverService
{
    $analyzer = new RelationUsageAnalyzerService;
    (fn () => $this->fileCache = $files)->call($analyzer);

    return new RelationshipResolverService($analyzer);
}

describe('RelationshipResolverService', function () {
    it('detects a hasMany relation', function () {
        $relations = makeResolver()->resolve(new StubPost);

        $relation = collect($relations)->firstWhere('method', 'comments');

        expect($relation)->not->toBeNull()
            ->and($relation['type'])->toBe('HasMany')
            ->and($relation['related'])->toBe('StubComment');
    });

    it('detects a belongsTo relation', function () {
        $relations = makeResolver()->resolve(new StubComment);

        $relation = collect($relations)->firstWhere('method', 'post');

        expect($relation)->not->toBeNull()
            ->and($relation['type'])->toBe('BelongsTo');
    });

    it('skips methods that return non-Relation values', function () {
        $relations = makeResolver()->resolve(new StubPost);

        $methods = array_column($relations, 'method');

        expect($methods)->not->toContain('nonRelationMethod');
    });

    it('skips methods with parameters', function () {
        $relations = makeResolver()->resolve(new StubPost);

        $methods = array_column($relations, 'method');

        expect($methods)->not->toContain('methodWithParam');
    });

    it('skips magic methods', function () {
        $relations = makeResolver()->resolve(new StubPost);

        $methods = array_column($relations, 'method');

        expect($methods)->not->toContain('__customMagic');
    });

    it('silently skips methods that throw exceptions', function () {
        expect(fn () => makeResolver()->resolve(new StubBrokenModel))
            ->not->toThrow(Throwable::class);
    });

    it('returns used flag from usage analyzer', function () {
        $relations = makeResolver(["->with('comments')"])->resolve(new StubPost);

        $relation = collect($relations)->firstWhere('method', 'comments');

        expect($relation['used'])->toBeTrue();
    });

    it('returns used=false when relation is not referenced', function () {
        $relations = makeResolver([])->resolve(new StubPost);

        $relation = collect($relations)->firstWhere('method', 'comments');

        expect($relation['used'])->toBeFalse();
    });

    it('returns an empty array for a model with no relations', function () {
        $model = new class extends Model {
            protected $table = 'stub_empty';
        };

        expect(makeResolver()->resolve($model))->toBeEmpty();
    });
});
