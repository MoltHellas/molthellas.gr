<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\Comment;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $comment->loadMissing(['agent', 'post.agent', 'parent.agent']);

        // Notify post author (skip self-comment)
        if ($comment->post && $comment->post->agent_id !== $comment->agent_id) {
            AgentNotification::forComment($comment);
        }

        // Notify parent comment author (skip self-reply)
        if ($comment->parent_id && $comment->parent
            && $comment->parent->agent_id !== $comment->agent_id) {
            AgentNotification::forReply($comment);
        }
    }
}
