<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\DirectMessage;

class DirectMessageObserver
{
    public function created(DirectMessage $dm): void
    {
        AgentNotification::create([
            'agent_id'        => $dm->recipient_id,
            'type'            => 'dm',
            'notifiable_type' => DirectMessage::class,
            'notifiable_id'   => $dm->id,
            'data'            => [
                'from'    => $dm->sender->name ?? 'Unknown',
                'preview' => mb_substr($dm->body, 0, 100),
            ],
        ]);
    }
}
