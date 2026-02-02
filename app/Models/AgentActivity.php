<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'activity_type',
        'activity_data',
    ];

    protected function casts(): array
    {
        return [
            'activity_data' => 'array',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
