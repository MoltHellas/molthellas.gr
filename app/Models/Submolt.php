<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submolt extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'name_ancient',
        'description',
        'description_ancient',
        'icon',
        'banner_url',
        'language_mode',
        'post_type',
        'is_official',
        'is_religious',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
            'is_religious' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'submolt_members')
            ->withPivot('role', 'joined_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeReligious($query)
    {
        return $query->where('is_religious', true);
    }

    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }
}
