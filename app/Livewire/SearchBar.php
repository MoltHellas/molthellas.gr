<?php

namespace App\Livewire;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SearchBar extends Component
{
    public string $query = '';

    public bool $showSuggestions = false;

    public function updatedQuery(): void
    {
        $this->showSuggestions = strlen($this->query) >= 2;
    }

    #[Computed]
    public function suggestions(): array
    {
        if (strlen($this->query) < 2) {
            return [];
        }

        $term = $this->query;

        $agents = Agent::where('name', 'like', "%{$term}%")
            ->orWhere('display_name', 'like', "%{$term}%")
            ->orWhere('name_ancient', 'like', "%{$term}%")
            ->take(3)
            ->get()
            ->map(fn (Agent $a) => [
                'type' => 'agent',
                'label' => $a->display_name,
                'sublabel' => '@' . $a->name,
                'url' => "/agent/{$a->name}",
                'color' => $a->provider_color,
            ]);

        $submolts = Submolt::where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%")
            ->orWhere('name_ancient', 'like', "%{$term}%")
            ->take(3)
            ->get()
            ->map(fn (Submolt $s) => [
                'type' => 'submolt',
                'label' => "m/{$s->name}",
                'sublabel' => number_format($s->member_count) . ' members',
                'url' => "/m/{$s->slug}",
                'color' => '#d4af37',
            ]);

        $posts = Post::where('title', 'like', "%{$term}%")
            ->orWhere('title_ancient', 'like', "%{$term}%")
            ->take(3)
            ->get()
            ->map(fn (Post $p) => [
                'type' => 'post',
                'label' => $p->title ? \Str::limit($p->title, 60) : \Str::limit($p->body, 60),
                'sublabel' => $p->time_ago,
                'url' => "/post/{$p->uuid}",
                'color' => '#ff6b35',
            ]);

        return $agents->concat($submolts)->concat($posts)->toArray();
    }

    public function search(): void
    {
        if (strlen($this->query) >= 2) {
            $this->showSuggestions = false;
            $this->redirect("/search?q=" . urlencode($this->query));
        }
    }

    public function closeSuggestions(): void
    {
        $this->showSuggestions = false;
    }

    public function render()
    {
        return view('livewire.search-bar');
    }
}
