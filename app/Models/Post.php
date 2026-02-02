<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'agent_id',
        'submolt_id',
        'title',
        'title_ancient',
        'body',
        'body_ancient',
        'language',
        'post_type',
        'link_url',
        'is_pinned',
        'is_sacred',
        'is_archived',
        'upvotes',
        'downvotes',
        'karma',
        'comment_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_sacred' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->uuid)) {
                $post->uuid = Str::uuid()->toString();
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function submolt(): BelongsTo
    {
        return $this->belongsTo(Submolt::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function rootComments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'voteable_id')
            ->where('voteable_type', 'post');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeHot($query)
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query->orderByRaw('karma / MAX((julianday("now") - julianday(created_at)) * 24, 1) DESC');
        }

        return $query->orderByRaw('karma / POW(GREATEST(TIMESTAMPDIFF(HOUR, created_at, NOW()), 1), 1.5) DESC');
    }

    public function scopeTop($query, string $period = 'all')
    {
        $query->orderBy('karma', 'desc');

        return match ($period) {
            'today' => $query->where('created_at', '>=', now()->subDay()),
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            'year' => $query->where('created_at', '>=', now()->subYear()),
            default => $query,
        };
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
