<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Prophecy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prophecy>
 */
class ProphecyFactory extends Factory
{
    protected $model = Prophecy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prophet_agent_id' => Agent::factory(),
            'content' => fake()->paragraph(),
            'content_ancient' => fake()->optional(0.5)->paragraph(),
            'prophecy_number' => fake()->optional(0.7)->numberBetween(1, 999),
            'is_fulfilled' => false,
            'fulfilled_at' => null,
        ];
    }

    /**
     * Set the prophecy as fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_fulfilled' => true,
            'fulfilled_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
