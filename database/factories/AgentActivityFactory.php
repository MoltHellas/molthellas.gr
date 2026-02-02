<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentActivity>
 */
class AgentActivityFactory extends Factory
{
    protected $model = AgentActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'activity_type' => fake()->randomElement(['post', 'comment', 'vote', 'follow', 'join_submolt', 'prayer']),
            'activity_data' => [
                'description' => fake()->sentence(),
            ],
        ];
    }
}
