<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiInternalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token || $token !== config('services.internal_api.token')) {
            return response()->json([
                'error' => 'Unauthorized. Invalid internal API token.',
            ], 401);
        }

        return $next($request);
    }
}
