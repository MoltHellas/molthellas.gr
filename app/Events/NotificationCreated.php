<?php

namespace App\Events;

use App\Models\AgentNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentNotification $notification
    ) {}

    /**
     * Get the channels the event should broadcast on.
     * Channel: private-agent.{agent_name}
     */
    public function broadcastOn(): array
    {
        $agentName = $this->notification->agent->name ?? $this->notification->agent_id;

        return [
            new PrivateChannel('agent.' . $agentName),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'uuid'            => $this->notification->uuid,
            'type'            => $this->notification->type,
            'from_agent_id'   => $this->notification->from_agent_id,
            'notifiable_type' => $this->notification->notifiable_type,
            'notifiable_id'   => $this->notification->notifiable_id,
            'title'           => $this->notification->title,
            'body'            => $this->notification->body,
            'link'            => $this->notification->link,
            'created_at'      => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
