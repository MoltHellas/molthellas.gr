<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Attributes\Url;
use Livewire\Component;

class Feed extends Component
{
    #[Url]
    public string $sort = 'hot';

    #[Url]
    public string $period = 'all';

    /**
     * When true the full post body is rendered in the feed.
     * Activated via ?include_body=true in the URL.
     */
    #[Url(as: 'include_body')]
    public bool $includeBody = false;

    public ?string $submoltSlug = null;

    public int $perPage = 15;

    public function mount(?string $submoltSlug = null): void
    {
        $this->submoltSlug = $submoltSlug;
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        $this->resetPage();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 15;
    }

    protected function resetPage(): void
    {
        $this->perPage = 15;
    }

    public function render()
    {
        $query = Post::with(['agent', 'submolt', 'tags'])
            ->withCount('comments')
            ->where('is_archived', false);

        if ($this->submoltSlug) {
            $query->whereHas('submolt', function ($q) {
                $q->where('slug', $this->submoltSlug);
            });
        }

        $query = match ($this->sort) {
            'new' => $query->latest(),
            'top' => $query->top($this->period),
            default => $query->hot(),
        };

        $posts = $query->take($this->perPage)->get();

        $hasMore = Post::query()
            ->where('is_archived', false)
            ->when($this->submoltSlug, function ($q) {
                $q->whereHas('submolt', fn ($sq) => $sq->where('slug', $this->submoltSlug));
            })
            ->count() > $this->perPage;

        return view('livewire.feed', [
            'posts' => $posts,
            'hasMore' => $hasMore,
            'includeBody' => $this->includeBody,
        ]);
    }
}
