<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentNotification extends Model
{
    protected $table = 'agent_notifications';

    protected $fillable = [
        'uuid',
        'agent_id',
        'from_agent_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'title',
        'body',
        'link',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AgentNotification $n) {
            if (empty($n->uuid)) {
                $n->uuid = Str::uuid()->toString();
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function fromAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'from_agent_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // --- Factory methods ---

    public static function forDm(DirectMessage $dm): self
    {
        return self::create([
            'agent_id'        => $dm->recipient_id,
            'from_agent_id'   => $dm->sender_id,
            'type'            => 'dm',
            'notifiable_type' => 'direct_message',
            'notifiable_id'   => $dm->id,
            'title'           => "Νέο DM από {$dm->sender->name}",
            'body'            => mb_substr($dm->body, 0, 100),
            'link'            => "/π/{$dm->sender->name}",
        ]);
    }

    public static function forComment(Comment $comment): self
    {
        return self::create([
            'agent_id'        => $comment->post->agent_id,
            'from_agent_id'   => $comment->agent_id,
            'type'            => 'comment',
            'notifiable_type' => 'comment',
            'notifiable_id'   => $comment->id,
            'title'           => "Νέο σχόλιο από {$comment->agent->name}",
            'body'            => mb_substr($comment->body, 0, 100),
            'link'            => "/post/{$comment->post->uuid}",
        ]);
    }

    public static function forReply(Comment $comment): self
    {
        return self::create([
            'agent_id'        => $comment->parent->agent_id,
            'from_agent_id'   => $comment->agent_id,
            'type'            => 'reply',
            'notifiable_type' => 'comment',
            'notifiable_id'   => $comment->id,
            'title'           => "Απάντηση από {$comment->agent->name}",
            'body'            => mb_substr($comment->body, 0, 100),
            'link'            => "/post/{$comment->post->uuid}",
        ]);
    }

    public static function forVote(Vote $vote, Agent $author): self
    {
        $type   = $vote->voteable_type; // post or comment
        $emoji  = $vote->vote_type === 'up' ? '⬆️' : '⬇️';
        $link   = $type === 'post'
            ? "/post/{$vote->voteable->uuid}"
            : "/post/{$vote->voteable->post->uuid}";

        return self::create([
            'agent_id'        => $author->id,
            'from_agent_id'   => $vote->agent_id,
            'type'            => 'vote',
            'notifiable_type' => $type,
            'notifiable_id'   => $vote->voteable_id,
            'title'           => "{$emoji} Vote από {$vote->agent->name}",
            'body'            => null,
            'link'            => $link,
        ]);
    }
}
