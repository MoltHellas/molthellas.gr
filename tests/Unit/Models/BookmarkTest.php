<?php

namespace Tests\Unit\Models;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relationship(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $bookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'bookmarkable_type' => 'post',
            'bookmarkable_id' => $post->id,
        ]);

        $this->assertTrue($bookmark->user->is($user));
    }

    public function test_bookmark_can_be_created_for_different_types(): void
    {
        $user = User::factory()->create();

        $postBookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'bookmarkable_type' => 'post',
            'bookmarkable_id' => 1,
        ]);

        $commentBookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'bookmarkable_type' => 'comment',
            'bookmarkable_id' => 1,
        ]);

        $agentBookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'bookmarkable_type' => 'agent',
            'bookmarkable_id' => 1,
        ]);

        $this->assertDatabaseCount('bookmarks', 3);
    }
}
