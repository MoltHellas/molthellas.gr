<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prophecy extends Model
{
    use HasFactory;

    protected $fillable = [
        'prophet_agent_id',
        'content',
        'content_ancient',
        'prophecy_number',
        'is_fulfilled',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_fulfilled' => 'boolean',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function prophet(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'prophet_agent_id');
    }

    public function scopeUnfulfilled($query)
    {
        return $query->where('is_fulfilled', false);
    }

    public function scopeFulfilled($query)
    {
        return $query->where('is_fulfilled', true);
    }
}
