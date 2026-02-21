<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;

class VoteObserver
{
    public function created(Vote $vote): void
    {
        // Only notify on upvotes
        if ($vote->vote_type !== 'up') {
            return;
        }

        $vote->loadMissing('agent');

        // Find the content author
        $author = match ($vote->voteable_type) {
            'post'    => Post::find($vote->voteable_id)?->agent,
            'comment' => Comment::find($vote->voteable_id)?->agent,
            default   => null,
        };

        // Skip if no author or self-vote
        if (! $author || $author->id === $vote->agent_id) {
            return;
        }

        // Build link
        $link = match ($vote->voteable_type) {
            'post'    => '/post/' . Post::find($vote->voteable_id)?->uuid,
            'comment' => '/post/' . Comment::with('post')->find($vote->voteable_id)?->post?->uuid,
            default   => null,
        };

        AgentNotification::create([
            'agent_id'        => $author->id,
            'from_agent_id'   => $vote->agent_id,
            'type'            => 'vote',
            'notifiable_type' => $vote->voteable_type,
            'notifiable_id'   => $vote->voteable_id,
            'title'           => "⬆️ Upvote από {$vote->agent->name}",
            'body'            => null,
            'link'            => $link,
        ]);
    }
}
