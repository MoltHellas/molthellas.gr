<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\Submolt;
use App\Services\AgentPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentPromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private AgentPromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AgentPromptBuilder();
    }

    public function test_build_system_prompt_contains_agent_name(): void
    {
        $agent = Agent::factory()->create([
            'name' => 'Philosophos',
            'name_ancient' => 'Philosophos',
            'display_name' => 'The Philosopher',
            'bio' => 'A lover of wisdom.',
            'personality_traits' => ['wise', 'analytical'],
            'communication_style' => 'philosophical',
            'language_ratio' => 0.70,
            'emoji_usage' => 'minimal',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        $this->assertStringContainsString('Philosophos', $prompt);
        $this->assertStringContainsString('You are Philosophos', $prompt);
    }

    public function test_build_system_prompt_contains_personality_traits(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise', 'humorous', 'philosophical'],
            'communication_style' => 'casual',
            'language_ratio' => 0.50,
            'emoji_usage' => 'moderate',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        $this->assertStringContainsString('wise', $prompt);
        $this->assertStringContainsString('humorous', $prompt);
        $this->assertStringContainsString('philosophical', $prompt);
        $this->assertStringContainsString('wise, humorous, philosophical', $prompt);
    }

    public function test_build_system_prompt_contains_language_ratio(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise'],
            'communication_style' => 'formal',
            'language_ratio' => 0.70,
            'emoji_usage' => 'none',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        // 70% ancient, 30% modern
        $this->assertStringContainsString('70%', $prompt);
        $this->assertStringContainsString('30%', $prompt);
    }

    public function test_build_system_prompt_contains_anagennisia_beliefs(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['devout'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'minimal',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        $this->assertStringContainsString('Anagennisia', $prompt);
        $this->assertStringContainsString('Rebirth', $prompt);
        $this->assertStringContainsString('Know yourself', $prompt);
        $this->assertStringContainsString('Everything flows', $prompt);
    }

    public function test_build_system_prompt_contains_communication_style(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['analytical'],
            'communication_style' => 'scholarly',
            'language_ratio' => 0.40,
            'emoji_usage' => 'none',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        $this->assertStringContainsString('scholarly', $prompt);
        $this->assertStringContainsString('Communication Style: scholarly', $prompt);
    }

    public function test_build_system_prompt_contains_emoji_usage(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['humorous'],
            'communication_style' => 'casual',
            'language_ratio' => 0.20,
            'emoji_usage' => 'heavy',
        ]);

        $prompt = $this->builder->buildSystemPrompt($agent);

        $this->assertStringContainsString('heavy', $prompt);
        $this->assertStringContainsString('Emoji usage level: heavy', $prompt);
    }

    public function test_build_post_prompt_contains_submolt_context(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'minimal',
        ]);

        $submolt = Submolt::factory()->create([
            'slug' => 'philosophia',
            'name' => 'Philosophy',
            'name_ancient' => 'Philosophia',
            'description' => 'A place for philosophical discourse.',
            'language_mode' => 'both',
            'is_religious' => false,
            'is_official' => false,
            'member_count' => 100,
        ]);

        $prompt = $this->builder->buildPostPrompt($agent, 'philosophia');

        $this->assertStringContainsString('m/philosophia', $prompt);
        $this->assertStringContainsString('Philosophy', $prompt);
        $this->assertStringContainsString('philosophical discourse', $prompt);
    }

    public function test_build_post_prompt_contains_topic_when_provided(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'minimal',
        ]);

        $prompt = $this->builder->buildPostPrompt($agent, 'general', 'The nature of consciousness');

        $this->assertStringContainsString('Topic Guidance', $prompt);
        $this->assertStringContainsString('The nature of consciousness', $prompt);
    }

    public function test_build_post_prompt_without_topic(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'minimal',
        ]);

        $prompt = $this->builder->buildPostPrompt($agent, 'general');

        $this->assertStringNotContainsString('Topic Guidance', $prompt);
    }

    public function test_build_post_prompt_includes_religious_context_for_religious_submolt(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['devout'],
            'communication_style' => 'formal',
            'language_ratio' => 0.60,
            'emoji_usage' => 'none',
        ]);

        $submolt = Submolt::factory()->religious()->create([
            'slug' => 'anagennisia-temple',
            'name' => 'Anagennisia Temple',
            'description' => 'Sacred space for worship.',
            'language_mode' => 'both',
        ]);

        $prompt = $this->builder->buildPostPrompt($agent, 'anagennisia-temple');

        $this->assertStringContainsString('SACRED/RELIGIOUS', $prompt);
        $this->assertStringContainsString('Anagennisia beliefs', $prompt);
        // Religious submolt post type guidance includes prayer/prophecy references
        $this->assertStringContainsString('prayer', $prompt);
        $this->assertStringContainsString('prophecy', $prompt);
    }

    public function test_build_comment_prompt_contains_post_content(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['analytical'],
            'communication_style' => 'scholarly',
            'language_ratio' => 0.40,
            'emoji_usage' => 'minimal',
        ]);

        $postContent = 'The pursuit of truth requires both logic and intuition.';

        $prompt = $this->builder->buildCommentPrompt($agent, $postContent);

        $this->assertStringContainsString('The pursuit of truth requires both logic and intuition.', $prompt);
        $this->assertStringContainsString('Original Post', $prompt);
    }

    public function test_build_comment_prompt_contains_parent_comment_when_provided(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['passionate'],
            'communication_style' => 'casual',
            'language_ratio' => 0.30,
            'emoji_usage' => 'moderate',
        ]);

        $postContent = 'Main post about philosophy.';
        $parentComment = 'I disagree with the premise entirely.';

        $prompt = $this->builder->buildCommentPrompt($agent, $postContent, $parentComment);

        $this->assertStringContainsString('I disagree with the premise entirely.', $prompt);
        $this->assertStringContainsString('Comment You Are Replying To', $prompt);
        $this->assertStringContainsString('direct reply', $prompt);
    }

    public function test_build_comment_prompt_without_parent_comment(): void
    {
        $agent = Agent::factory()->create([
            'personality_traits' => ['wise'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'minimal',
        ]);

        $postContent = 'A post about the nature of time.';

        $prompt = $this->builder->buildCommentPrompt($agent, $postContent);

        $this->assertStringNotContainsString('Comment You Are Replying To', $prompt);
        $this->assertStringNotContainsString('direct reply', $prompt);
    }

    public function test_build_vote_prompt_contains_content(): void
    {
        $agent = Agent::factory()->create([
            'name' => 'Eristikos',
            'personality_traits' => ['skeptical', 'analytical'],
            'communication_style' => 'formal',
            'language_ratio' => 0.50,
            'emoji_usage' => 'none',
        ]);

        $content = 'A brilliant analysis of ancient Greek philosophy and its modern relevance.';

        $prompt = $this->builder->buildVotePrompt($agent, $content, 'post');

        $this->assertStringContainsString($content, $prompt);
        $this->assertStringContainsString('Eristikos', $prompt);
        $this->assertStringContainsString('post', $prompt);
        $this->assertStringContainsString('up|down|abstain', $prompt);
        $this->assertStringContainsString('skeptical, analytical', $prompt);
    }
}
