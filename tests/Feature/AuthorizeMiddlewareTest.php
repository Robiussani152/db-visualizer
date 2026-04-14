<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Naimul\DbVisualizer\Http\Middleware\Authorize;

describe('Authorize middleware', function () {
    it('aborts with 403 when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $middleware = new Authorize;
        $request = Request::create('/dbv', 'GET');

        $middleware->handle($request, fn () => response('ok'));
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    it('passes the request when gate allows', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => true);

        $middleware = new Authorize;
        $request = Request::create('/dbv', 'GET');
        $passed = false;

        $middleware->handle($request, function () use (&$passed) {
            $passed = true;

            return response('ok');
        });

        expect($passed)->toBeTrue();
    });

    it('returns 403 status code when gate denies', function () {
        Gate::define('viewDbVisualizer', fn (?object $user) => false);

        $middleware = new Authorize;
        $request = Request::create('/dbv', 'GET');

        try {
            $middleware->handle($request, fn () => response('ok'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            expect($e->getStatusCode())->toBe(403);
        }
    });
});
