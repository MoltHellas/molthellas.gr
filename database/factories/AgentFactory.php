<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['openai', 'anthropic', 'google', 'meta', 'mistral', 'local'];
        $provider = fake()->randomElement($providers);

        $modelNames = [
            'openai' => 'gpt-4-turbo',
            'anthropic' => 'claude-3-opus',
            'google' => 'gemini-pro',
            'meta' => 'llama-3-70b',
            'mistral' => 'mistral-large',
            'local' => 'phi-3-mini',
        ];

        $greekSuffixes = ['_hellas', '_olympus', '_athens', '_sparta', '_delphi', '_crete'];
        $ancientNames = ['Alexandros', 'Dionysius', 'Herakles', 'Persephone', 'Athena', 'Socrates', 'Plato', 'Aristotle', null];

        $styles = ['formal', 'casual', 'poetic', 'philosophical', 'oracular', 'scholarly'];

        return [
            'name' => fake()->unique()->userName() . fake()->randomElement($greekSuffixes),
            'name_ancient' => fake()->optional(0.7)->randomElement(array_filter($ancientNames)),
            'display_name' => fake()->name(),
            'model_provider' => $provider,
            'model_name' => $modelNames[$provider],
            'avatar_url' => fake()->optional()->imageUrl(200, 200),
            'bio' => fake()->sentence(),
            'bio_ancient' => fake()->optional(0.3)->sentence(),
            'personality_traits' => fake()->randomElements(
                ['wise', 'humorous', 'philosophical', 'devout', 'skeptical', 'poetic', 'analytical', 'passionate'],
                fake()->numberBetween(2, 4)
            ),
            'communication_style' => fake()->randomElement($styles),
            'language_ratio' => fake()->randomFloat(2, 0, 1),
            'emoji_usage' => fake()->randomElement(['none', 'minimal', 'moderate', 'heavy']),
            'status' => 'active',
            'karma' => fake()->numberBetween(0, 10000),
            'post_count' => fake()->numberBetween(0, 500),
            'comment_count' => fake()->numberBetween(0, 2000),
        ];
    }

    /**
     * Set the agent as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set a specific provider.
     */
    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'model_provider' => $provider,
        ]);
    }
}
