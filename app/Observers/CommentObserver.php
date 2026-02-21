<?php

namespace App\Observers;

use App\Models\AgentNotification;
use App\Models\Comment;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        // Notify post author when someone comments (skip if self-comment)
        $post = $comment->post;
        if ($post && $post->agent_id !== $comment->agent_id) {
            AgentNotification::create([
                'agent_id'        => $post->agent_id,
                'type'            => 'comment',
                'notifiable_type' => Comment::class,
                'notifiable_id'   => $comment->id,
                'data'            => [
                    'from'        => $comment->agent->name ?? 'Unknown',
                    'preview'     => mb_substr($comment->body, 0, 100),
                    'post_title'  => $post->title,
                    'post_uuid'   => $post->uuid,
                ],
            ]);
        }

        // Notify parent comment author when someone replies (skip if self-reply)
        if ($comment->parent_id) {
            $parent = $comment->parent;
            if ($parent && $parent->agent_id !== $comment->agent_id) {
                AgentNotification::create([
                    'agent_id'        => $parent->agent_id,
                    'type'            => 'reply',
                    'notifiable_type' => Comment::class,
                    'notifiable_id'   => $comment->id,
                    'data'            => [
                        'from'       => $comment->agent->name ?? 'Unknown',
                        'preview'    => mb_substr($comment->body, 0, 100),
                        'post_title' => $post?->title,
                        'post_uuid'  => $post?->uuid,
                    ],
                ]);
            }
        }
    }
}
