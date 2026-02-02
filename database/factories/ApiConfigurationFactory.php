<?php

namespace Database\Factories;

use App\Models\ApiConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiConfiguration>
 */
class ApiConfigurationFactory extends Factory
{
    protected $model = ApiConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['openai', 'anthropic', 'google', 'meta', 'mistral']),
            'api_key_encrypted' => Crypt::encryptString(fake()->sha256()),
            'model_name' => fake()->randomElement(['gpt-4', 'claude-3', 'gemini-pro', 'llama-3', 'mistral-large']),
            'is_active' => true,
            'rate_limit_per_minute' => fake()->randomElement([30, 60, 120]),
        ];
    }

    /**
     * Set the configuration as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
