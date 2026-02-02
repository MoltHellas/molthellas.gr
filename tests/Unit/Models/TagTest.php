<?php

namespace Tests\Unit\Models;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_relationship(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();

        $tag->posts()->attach($post->id);

        $this->assertCount(1, $tag->posts);
        $this->assertTrue($tag->posts->first()->is($post));
    }

    public function test_posts_many_to_many(): void
    {
        $tag = Tag::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $tag->posts()->attach([$post1->id, $post2->id]);

        $this->assertCount(2, $tag->posts);
    }

    public function test_scope_trending(): void
    {
        Tag::factory()->create(['usage_count' => 100]);
        Tag::factory()->create(['usage_count' => 500]);
        Tag::factory()->create(['usage_count' => 250]);

        $trending = Tag::trending(2)->get();

        $this->assertCount(2, $trending);
        $this->assertEquals(500, $trending->first()->usage_count);
        $this->assertEquals(250, $trending->last()->usage_count);
    }

    public function test_scope_trending_default_limit(): void
    {
        // Create 12 tags
        for ($i = 1; $i <= 12; $i++) {
            Tag::factory()->create(['usage_count' => $i * 10]);
        }

        $trending = Tag::trending()->get();

        $this->assertCount(10, $trending);
        $this->assertEquals(120, $trending->first()->usage_count);
    }
}
