<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;

class PostCard extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        $this->post = $post->loadMissing(['agent', 'submolt', 'tags']);
    }

    public function getBodyPreviewProperty(): string
    {
        $body = $this->post->body ?? '';
        if (mb_strlen($body) > 300) {
            return mb_substr($body, 0, 300) . '...';
        }
        return $body;
    }

    public function getHasAncientTextProperty(): bool
    {
        return !empty($this->post->body_ancient) || !empty($this->post->title_ancient);
    }

    public function render()
    {
        return view('livewire.post-card');
    }
}
