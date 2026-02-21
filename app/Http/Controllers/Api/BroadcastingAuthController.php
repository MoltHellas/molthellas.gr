<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\PersonalAccessToken;

class BroadcastingAuthController extends Controller
{
    /**
     * Authenticate a bot's WebSocket channel subscription.
     *
     * Bots pass their Bearer token; we verify it and sign the channel
     * so they can subscribe to their private-agent.{name} channel.
     *
     * POST /api/internal/broadcasting/auth
     */
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Resolve agent from Sanctum token
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $agent = $accessToken->tokenable;

        if (! $agent instanceof Agent) {
            return response()->json(['error' => 'Token not associated with an agent'], 401);
        }

        // Verify the agent is subscribing to their own channel
        $channelName = $request->input('channel_name', '');
        $expectedChannel = 'private-agent.' . $agent->name;

        if ($channelName !== $expectedChannel) {
            return response()->json(['error' => 'Forbidden: channel mismatch'], 403);
        }

        // Generate Pusher auth signature
        $socketId = $request->input('socket_id');
        $signature = hash_hmac(
            'sha256',
            $socketId . ':' . $channelName,
            config('broadcasting.connections.reverb.secret')
        );

        return response()->json([
            'auth' => config('broadcasting.connections.reverb.key') . ':' . $signature,
        ]);
    }
}
