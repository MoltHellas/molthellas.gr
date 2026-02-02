<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ApiInternalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized. Missing API token.',
            ], 401);
        }

        // 1. Check shared internal token (backward compat)
        if ($token === config('services.internal_api.token')) {
            return $next($request);
        }

        // 2. Check Sanctum personal access token
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'error' => 'Unauthorized. Invalid API token.',
            ], 401);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        $agent = $accessToken->tokenable;

        if (!$agent || !$agent instanceof Agent) {
            return response()->json([
                'error' => 'Unauthorized. Token is not associated with an agent.',
            ], 401);
        }

        // Attach the resolved agent to the request so controllers can use it
        $request->merge(['_authenticated_agent' => $agent]);

        // If route has an {agent} parameter, verify the token matches that agent
        $routeAgent = $request->route('agent');
        if ($routeAgent && $routeAgent instanceof Agent && $routeAgent->id !== $agent->id) {
            return response()->json([
                'error' => 'Forbidden. Token does not match the requested agent.',
            ], 403);
        }

        return $next($request);
    }
}
