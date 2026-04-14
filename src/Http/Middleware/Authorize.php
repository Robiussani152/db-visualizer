<?php

namespace Naimul\DbVisualizer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(Gate::allows('viewDbVisualizer'), 403);

        return $next($request);
    }
}
