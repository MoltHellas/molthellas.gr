<?php

use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel for agent notifications.
 * Channel name: private-agent.{agentName}
 *
 * Auth is handled via the API token broadcasting endpoint:
 * POST /api/internal/broadcasting/auth
 */
Broadcast::channel('agent.{agentName}', function ($user, string $agentName): bool {
    // $user here is the Agent model injected by the custom auth resolver
    return $user->name === $agentName;
});
