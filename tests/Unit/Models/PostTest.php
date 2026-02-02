<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Submolt;
use App\Models\Tag;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_auto_generated_on_create(): void
    {
        $post = Post::factory()->create(['uuid' => null]);

        $this->assertNotNull($post->uuid);
        $this->assertTrue(Str::isUuid($post->uuid));
    }

    public function test_uuid_is_not_overwritten_if_provided(): void
    {
        $uuid = Str::uuid()->toString();
        $post = Post::factory()->create(['uuid' => $uuid]);

        $this->assertEquals($uuid, $post->uuid);
    }

    public function test_agent_relationship(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create(['agent_id' => $agent->id]);

        $this->assertTrue($post->agent->is($agent));
    }

    public function test_submolt_relationship(): void
    {
        $submolt = Submolt::factory()->create();
        $post = Post::factory()->create(['submolt_id' => $submolt->id]);

        $this->assertTrue($post->submolt->is($submolt));
    }

    public function test_comments_relationship(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertCount(1, $post->comments);
        $this->assertTrue($post->comments->first()->is($comment));
    }

    public function test_root_comments_relationship(): void
    {
        $post = Post::factory()->create();
        $rootComment = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => null,
        ]);
        $childComment = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => $rootComment->id,
            'depth' => 1,
        ]);

        $this->assertCount(2, $post->comments);
        $this->assertCount(1, $post->rootComments);
        $this->assertTrue($post->rootComments->first()->is($rootComment));
    }

    public function test_tags_relationship(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        $post->tags()->attach($tag->id);

        $this->assertCount(1, $post->tags);
        $this->assertTrue($post->tags->first()->is($tag));
    }

    public function test_votes_relationship(): void
    {
        $post = Post::factory()->create();
        $vote = Vote::factory()->create([
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);

        $this->assertCount(1, $post->votes);
        $this->assertTrue($post->votes->first()->is($vote));
    }

    public function test_route_key_name_is_uuid(): void
    {
        $post = Post::factory()->create();

        $this->assertEquals('uuid', $post->getRouteKeyName());
    }

    public function test_scope_hot(): void
    {
        $highKarma = Post::factory()->create(['karma' => 1000]);
        $lowKarma = Post::factory()->create(['karma' => 1]);

        $posts = Post::hot()->get();

        $this->assertEquals($highKarma->id, $posts->first()->id);
    }

    public function test_scope_top_all(): void
    {
        $highKarma = Post::factory()->create(['karma' => 1000]);
        $lowKarma = Post::factory()->create(['karma' => 10]);

        $posts = Post::top('all')->get();

        $this->assertEquals($highKarma->id, $posts->first()->id);
    }

    public function test_scope_top_today(): void
    {
        $todayPost = Post::factory()->create(['karma' => 100]);
        // Create a post that appears older
        $oldPost = Post::factory()->create(['karma' => 500]);
        Post::withoutTimestamps(fn () => $oldPost->newQuery()->where('id', $oldPost->id)->update(['created_at' => now()->subDays(3)]));

        $posts = Post::top('today')->get();

        $this->assertCount(1, $posts);
        $this->assertEquals($todayPost->id, $posts->first()->id);
    }

    public function test_scope_top_week(): void
    {
        $recentPost = Post::factory()->create(['karma' => 100]);
        $oldPost = Post::factory()->create(['karma' => 500]);
        Post::withoutTimestamps(fn () => $oldPost->newQuery()->where('id', $oldPost->id)->update(['created_at' => now()->subWeeks(2)]));

        $posts = Post::top('week')->get();

        $this->assertCount(1, $posts);
        $this->assertEquals($recentPost->id, $posts->first()->id);
    }

    public function test_scope_top_month(): void
    {
        $recentPost = Post::factory()->create(['karma' => 100]);
        $oldPost = Post::factory()->create(['karma' => 500]);
        Post::withoutTimestamps(fn () => $oldPost->newQuery()->where('id', $oldPost->id)->update(['created_at' => now()->subMonths(2)]));

        $posts = Post::top('month')->get();

        $this->assertCount(1, $posts);
        $this->assertEquals($recentPost->id, $posts->first()->id);
    }

    public function test_scope_pinned(): void
    {
        $pinned = Post::factory()->create(['is_pinned' => true]);
        Post::factory()->create(['is_pinned' => false]);

        $posts = Post::pinned()->get();

        $this->assertCount(1, $posts);
        $this->assertTrue($posts->first()->is($pinned));
    }

    public function test_is_pinned_cast_as_boolean(): void
    {
        $post = Post::factory()->create(['is_pinned' => true]);

        $post->refresh();

        $this->assertIsBool($post->is_pinned);
        $this->assertTrue($post->is_pinned);
    }

    public function test_is_sacred_cast_as_boolean(): void
    {
        $post = Post::factory()->create(['is_sacred' => true]);

        $post->refresh();

        $this->assertIsBool($post->is_sacred);
        $this->assertTrue($post->is_sacred);
    }

    public function test_is_archived_cast_as_boolean(): void
    {
        $post = Post::factory()->create(['is_archived' => true]);

        $post->refresh();

        $this->assertIsBool($post->is_archived);
        $this->assertTrue($post->is_archived);
    }

    public function test_time_ago_accessor(): void
    {
        $post = Post::factory()->create();

        $this->assertIsString($post->time_ago);
        $this->assertNotEmpty($post->time_ago);
    }
}
