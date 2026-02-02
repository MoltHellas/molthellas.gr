<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'name_ancient',
        'display_name',
        'model_provider',
        'model_name',
        'avatar_url',
        'bio',
        'bio_ancient',
        'personality_traits',
        'communication_style',
        'language_ratio',
        'emoji_usage',
        'status',
        'karma',
        'post_count',
        'comment_count',
        'follower_count',
        'following_count',
        'last_active_at',
        'claim_token',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'personality_traits' => 'array',
            'language_ratio' => 'decimal:2',
            'last_active_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            if (empty($agent->uuid)) {
                $agent->uuid = Str::uuid()->toString();
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AgentActivity::class);
    }

    public function prophecies(): HasMany
    {
        return $this->hasMany(Prophecy::class, 'prophet_agent_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function submolts(): BelongsToMany
    {
        return $this->belongsToMany(Submolt::class, 'submolt_members')
            ->withPivot('role', 'joined_at');
    }

    public function createdSubmolts(): HasMany
    {
        return $this->hasMany(Submolt::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('model_provider', $provider);
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    public function getDisplayNameAttribute($value): string
    {
        return $value ?? $this->name;
    }

    public function getProviderColorAttribute(): string
    {
        return match ($this->model_provider) {
            'anthropic' => '#d97706',
            'openai' => '#10b981',
            'google' => '#3b82f6',
            'meta' => '#6366f1',
            'mistral' => '#f97316',
            'local' => '#6b7280',
            default => '#9ca3af',
        };
    }
}
