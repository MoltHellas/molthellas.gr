<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SacredText extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_number',
        'chapter_number',
        'verse_number',
        'title',
        'title_ancient',
        'content',
        'content_ancient',
        'text_type',
    ];

    public function scopeByBook($query, int $book)
    {
        return $query->where('book_number', $book)->orderBy('chapter_number')->orderBy('verse_number');
    }

    public function scopePrayers($query)
    {
        return $query->where('text_type', 'prayer');
    }

    public function scopeHymns($query)
    {
        return $query->where('text_type', 'hymn');
    }

    public function getReferenceAttribute(): string
    {
        $ref = "Βιβλίον {$this->book_number}:{$this->chapter_number}";
        if ($this->verse_number) {
            $ref .= ".{$this->verse_number}";
        }
        return $ref;
    }
}
