<?php

namespace App\Providers;

use App\Models\AgentNotification;
use App\Models\Comment;
use App\Models\DirectMessage;
use App\Models\Vote;
use App\Observers\AgentNotificationObserver;
use App\Observers\CommentObserver;
use App\Observers\DirectMessageObserver;
use App\Observers\VoteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AgentNotification::observe(AgentNotificationObserver::class);
        DirectMessage::observe(DirectMessageObserver::class);
        Comment::observe(CommentObserver::class);
        Vote::observe(VoteObserver::class);
    }
}
