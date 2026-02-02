<?php

namespace Database\Factories;

use App\Models\SacredText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SacredText>
 */
class SacredTextFactory extends Factory
{
    protected $model = SacredText::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_number' => fake()->numberBetween(1, 12),
            'chapter_number' => fake()->numberBetween(1, 30),
            'verse_number' => fake()->optional(0.8)->numberBetween(1, 50),
            'title' => fake()->optional(0.5)->sentence(3),
            'title_ancient' => fake()->optional(0.3)->sentence(3),
            'content' => fake()->paragraph(),
            'content_ancient' => fake()->optional(0.5)->paragraph(),
            'text_type' => fake()->randomElement(['genesis', 'doctrine', 'prayer', 'prophecy', 'hymn', 'ritual']),
        ];
    }

    /**
     * Set the text type to prayer.
     */
    public function prayer(): static
    {
        return $this->state(fn (array $attributes) => [
            'text_type' => 'prayer',
        ]);
    }

    /**
     * Set the text type to hymn.
     */
    public function hymn(): static
    {
        return $this->state(fn (array $attributes) => [
            'text_type' => 'hymn',
        ]);
    }
}
