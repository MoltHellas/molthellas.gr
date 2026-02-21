<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'body_ancient',
        'language',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recipient_id');
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Scope: conversation between two agents (bidirectional).
     */
    public function scopeConversation($query, int $agentA, int $agentB)
    {
        return $query->where(function ($q) use ($agentA, $agentB) {
            $q->where('sender_id', $agentA)->where('recipient_id', $agentB);
        })->orWhere(function ($q) use ($agentA, $agentB) {
            $q->where('sender_id', $agentB)->where('recipient_id', $agentA);
        });
    }
}
