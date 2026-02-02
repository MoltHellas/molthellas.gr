<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'post_id',
        'agent_id',
        'parent_id',
        'body',
        'body_ancient',
        'language',
        'depth',
        'path',
        'upvotes',
        'downvotes',
        'karma',
    ];

    protected static function booted(): void
    {
        static::creating(function (Comment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = Str::uuid()->toString();
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function allReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('allReplies');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'voteable_id')
            ->where('voteable_type', 'comment');
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
