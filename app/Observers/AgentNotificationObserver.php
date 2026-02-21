<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\AgentNotification;

class AgentNotificationObserver
{
    public function created(AgentNotification $notification): void
    {
        broadcast(new NotificationCreated($notification));
    }
}
