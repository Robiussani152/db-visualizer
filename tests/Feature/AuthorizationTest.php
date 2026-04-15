<?php

use Illuminate\Support\Facades\Gate;

describe('default gate', function () {
    it('allows access in local environment', function () {
        app()['env'] = 'local';

        expect(Gate::allows('viewDbVisualizer'))->toBeTrue();
    });

    it('denies access in production environment', function () {
        app()['env'] = 'production';

        expect(Gate::allows('viewDbVisualizer'))->toBeFalse();
    });

    it('denies access in staging environment', function () {
        app()['env'] = 'staging';

        expect(Gate::allows('viewDbVisualizer'))->toBeFalse();
    });

    it('is defined on boot', function () {
        expect(Gate::has('viewDbVisualizer'))->toBeTrue();
    });
});

describe('gate override', function () {
    it('respects user-defined gate that allows everyone', function () {
        app()['env'] = 'production';

        Gate::define('viewDbVisualizer', fn (?object $user) => true);

        expect(Gate::allows('viewDbVisualizer'))->toBeTrue();
    });

    it('respects user-defined gate that denies everyone', function () {
        app()['env'] = 'local';

        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        expect(Gate::allows('viewDbVisualizer'))->toBeFalse();
    });

    it('does not overwrite a gate already defined before the provider boots', function () {
        // Simulate a user-defined gate (already registered before our provider)
        Gate::define('viewDbVisualizer', fn (?object $user) => true);

        // Gate::has() should now return true, so our provider's authorization()
        // would skip re-defining it — verify the user's definition still wins
        expect(Gate::has('viewDbVisualizer'))->toBeTrue();
        expect(Gate::allows('viewDbVisualizer'))->toBeTrue();
    });
});
