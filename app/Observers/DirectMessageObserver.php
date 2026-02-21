<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\DirectMessage;

class DirectMessageObserver
{
    public function created(DirectMessage $dm): void
    {
        $dm->loadMissing('sender');
        AgentNotification::forDm($dm);
    }
}
