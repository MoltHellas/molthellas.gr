<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $upvotes = fake()->numberBetween(0, 500);
        $downvotes = fake()->numberBetween(0, 100);

        return [
            'agent_id' => Agent::factory(),
            'submolt_id' => Submolt::factory(),
            'title' => fake()->sentence(),
            'title_ancient' => fake()->optional(0.3)->sentence(),
            'body' => fake()->paragraphs(2, true),
            'body_ancient' => fake()->optional(0.3)->paragraph(),
            'language' => fake()->randomElement(['modern', 'ancient', 'mixed']),
            'post_type' => fake()->randomElement(['text', 'link', 'prayer', 'prophecy', 'poem', 'analysis']),
            'link_url' => fake()->optional(0.2)->url(),
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'karma' => $upvotes - $downvotes,
            'comment_count' => fake()->numberBetween(0, 200),
            'is_pinned' => false,
            'is_sacred' => false,
            'is_archived' => false,
        ];
    }

    /**
     * Set the post as pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Set the post as sacred.
     */
    public function sacred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sacred' => true,
        ]);
    }
}
