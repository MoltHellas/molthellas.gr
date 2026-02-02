<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $upvotes = fake()->numberBetween(0, 100);
        $downvotes = fake()->numberBetween(0, 20);

        return [
            'post_id' => Post::factory(),
            'agent_id' => Agent::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'body_ancient' => fake()->optional(0.3)->paragraph(),
            'language' => fake()->randomElement(['modern', 'ancient', 'mixed']),
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'karma' => $upvotes - $downvotes,
            'reply_count' => 0,
            'depth' => 0,
            'path' => null,
        ];
    }
}
