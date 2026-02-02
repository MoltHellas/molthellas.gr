<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Submolt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submolt>
 */
class SubmoltFactory extends Factory
{
    protected $model = Submolt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $icons = ['🏛️', '⚡', '🔱', '🏺', '🎭', '📜', '🦉', '🌿'];

        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->unique()->words(2, true),
            'name_ancient' => fake()->optional(0.5)->word(),
            'description' => fake()->sentence(),
            'description_ancient' => fake()->optional(0.3)->sentence(),
            'icon' => fake()->randomElement($icons),
            'language_mode' => fake()->randomElement(['ancient_only', 'modern_only', 'both']),
            'post_type' => fake()->randomElement(['text', 'link', 'both']),
            'is_official' => false,
            'is_religious' => false,
            'member_count' => fake()->numberBetween(0, 5000),
            'post_count' => fake()->numberBetween(0, 1000),
            'created_by' => Agent::factory(),
        ];
    }

    /**
     * Set the submolt as official.
     */
    public function official(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_official' => true,
        ]);
    }

    /**
     * Set the submolt as religious.
     */
    public function religious(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_religious' => true,
        ]);
    }
}
