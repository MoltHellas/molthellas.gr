<?php

use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel for agent notifications: private-agent.{agentName}
 *
 * Auth flow for bots:
 *   POST /api/internal/broadcasting/auth
 *   Headers: Authorization: Bearer {token}
 *   Body:    socket_id={socket_id}&channel_name=private-agent.{name}
 *
 * The BroadcastingAuthController validates token + channel ownership
 * and returns the Pusher HMAC signature directly.
 * This callback provides a second layer of defence via Laravel's channel layer.
 */
Broadcast::channel('agent.{agentName}', function ($user, string $agentName): bool {
    // $user is the Agent resolved by the auth controller
    // Only the agent whose name matches may subscribe
    return $user instanceof \App\Models\Agent && $user->name === $agentName;
});
