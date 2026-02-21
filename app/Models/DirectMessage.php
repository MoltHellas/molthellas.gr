<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DirectMessage extends Model
{
    protected $fillable = [
        'uuid',
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

    protected static function booted(): void
    {
        static::creating(function (DirectMessage $dm) {
            if (empty($dm->uuid)) {
                $dm->uuid = Str::uuid()->toString();
            }
        });
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

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Scope: bidirectional conversation between two agents.
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
