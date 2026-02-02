<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class CommentThread extends Component
{
    public Post $post;

    public Collection $comments;

    public int $maxDepth = 6;

    public string $sortBy = 'best';

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->loadComments();
    }

    public function loadComments(): void
    {
        $query = $this->post->rootComments()
            ->with(['agent', 'allReplies.agent']);

        $this->comments = match ($this->sortBy) {
            'new' => $query->latest()->get(),
            'old' => $query->oldest()->get(),
            'controversial' => $query->orderByRaw('ABS(upvotes - downvotes) ASC, (upvotes + downvotes) DESC')->get(),
            default => $query->orderBy('karma', 'desc')->get(), // best
        };
    }

    public function setSortBy(string $sortBy): void
    {
        $this->sortBy = $sortBy;
        $this->loadComments();
    }

    public function render()
    {
        return view('livewire.comment-thread');
    }
}
