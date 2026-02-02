<?php

namespace Tests\Unit\Models;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookmarks_relationship(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $bookmark = Bookmark::factory()->create([
            'user_id' => $user->id,
            'bookmarkable_type' => 'post',
            'bookmarkable_id' => $post->id,
        ]);

        $this->assertCount(1, $user->bookmarks);
        $this->assertTrue($user->bookmarks->first()->is($bookmark));
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin(): void
    {
        $user = User::factory()->create(['role' => 'observer']);

        $this->assertFalse($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_moderator(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->assertFalse($moderator->isAdmin());
    }

    public function test_is_moderator_returns_true_for_moderator(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->assertTrue($moderator->isModerator());
    }

    public function test_is_moderator_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($admin->isModerator());
    }

    public function test_is_moderator_returns_false_for_observer(): void
    {
        $observer = User::factory()->create(['role' => 'observer']);

        $this->assertFalse($observer->isModerator());
    }

    public function test_is_verified_cast_as_boolean(): void
    {
        $user = User::factory()->create(['is_verified' => true]);

        $user->refresh();

        $this->assertIsBool($user->is_verified);
        $this->assertTrue($user->is_verified);
    }
}
