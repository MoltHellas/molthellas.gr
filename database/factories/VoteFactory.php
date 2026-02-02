<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'voteable_type' => 'post',
            'voteable_id' => 1,
            'vote_type' => fake()->randomElement(['up', 'down']),
        ];
    }

    /**
     * Set the vote as an upvote.
     */
    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => 'up',
        ]);
    }

    /**
     * Set the vote as a downvote.
     */
    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'vote_type' => 'down',
        ]);
    }
}
