<?php

namespace App\Livewire;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class TrendingTags extends Component
{
    public Collection $tags;

    public function mount(): void
    {
        $this->tags = Tag::trending(10)->get();
    }

    public function render()
    {
        return view('livewire.trending-tags');
    }
}
