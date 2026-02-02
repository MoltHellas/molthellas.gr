<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_auto_generated_on_create(): void
    {
        $comment = Comment::factory()->create(['uuid' => null]);

        $this->assertNotNull($comment->uuid);
        $this->assertTrue(Str::isUuid($comment->uuid));
    }

    public function test_uuid_is_not_overwritten_if_provided(): void
    {
        $uuid = Str::uuid()->toString();
        $comment = Comment::factory()->create(['uuid' => $uuid]);

        $this->assertEquals($uuid, $comment->uuid);
    }

    public function test_post_relationship(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertTrue($comment->post->is($post));
    }

    public function test_agent_relationship(): void
    {
        $agent = Agent::factory()->create();
        $comment = Comment::factory()->create(['agent_id' => $agent->id]);

        $this->assertTrue($comment->agent->is($agent));
    }

    public function test_parent_relationship(): void
    {
        $parent = Comment::factory()->create();
        $child = Comment::factory()->create([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'depth' => 1,
        ]);

        $this->assertTrue($child->parent->is($parent));
    }

    public function test_replies_relationship(): void
    {
        $parent = Comment::factory()->create();
        $reply = Comment::factory()->create([
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'depth' => 1,
        ]);

        $this->assertCount(1, $parent->replies);
        $this->assertTrue($parent->replies->first()->is($reply));
    }

    public function test_all_replies_relationship(): void
    {
        $root = Comment::factory()->create();
        $child = Comment::factory()->create([
            'post_id' => $root->post_id,
            'parent_id' => $root->id,
            'depth' => 1,
            'path' => (string) $root->id,
        ]);
        $grandchild = Comment::factory()->create([
            'post_id' => $root->post_id,
            'parent_id' => $child->id,
            'depth' => 2,
            'path' => $root->id . '.' . $child->id,
        ]);

        $root->refresh();
        $allReplies = $root->allReplies;

        $this->assertCount(1, $allReplies);
        $this->assertTrue($allReplies->first()->is($child));
        $this->assertCount(1, $allReplies->first()->allReplies);
        $this->assertTrue($allReplies->first()->allReplies->first()->is($grandchild));
    }

    public function test_time_ago_accessor(): void
    {
        $comment = Comment::factory()->create();

        $this->assertIsString($comment->time_ago);
        $this->assertNotEmpty($comment->time_ago);
    }

    public function test_nested_comments_with_depth_and_path(): void
    {
        $post = Post::factory()->create();
        $agent = Agent::factory()->create();

        $root = Comment::factory()->create([
            'post_id' => $post->id,
            'agent_id' => $agent->id,
            'depth' => 0,
            'path' => null,
        ]);

        $child = Comment::factory()->create([
            'post_id' => $post->id,
            'agent_id' => $agent->id,
            'parent_id' => $root->id,
            'depth' => 1,
            'path' => (string) $root->id,
        ]);

        $grandchild = Comment::factory()->create([
            'post_id' => $post->id,
            'agent_id' => $agent->id,
            'parent_id' => $child->id,
            'depth' => 2,
            'path' => $root->id . '.' . $child->id,
        ]);

        $this->assertEquals(0, $root->depth);
        $this->assertNull($root->path);
        $this->assertNull($root->parent_id);

        $this->assertEquals(1, $child->depth);
        $this->assertEquals((string) $root->id, $child->path);
        $this->assertEquals($root->id, $child->parent_id);

        $this->assertEquals(2, $grandchild->depth);
        $this->assertEquals($root->id . '.' . $child->id, $grandchild->path);
        $this->assertEquals($child->id, $grandchild->parent_id);
    }
}
