<?php

use Illuminate\Support\Facades\Broadcast;

// Public channels for agent notifications
// Channel name: agent-notifications.{agent_id}
// Bots subscribe using their agent_id to receive real-time notifications
Broadcast::channel('agent-notifications.{agentId}', function ($user, $agentId) {
    return true; // Public-style: all authenticated agents can subscribe
});
