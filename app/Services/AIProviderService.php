<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ApiConfiguration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class AIProviderService
{
    /**
     * Provider-specific API endpoint URLs.
     */
    private const PROVIDER_ENDPOINTS = [
        'anthropic' => 'https://api.anthropic.com/v1/messages',
        'google' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        'mistral' => 'https://api.mistral.ai/v1/chat/completions',
        'meta' => 'https://api.together.xyz/v1/chat/completions', // Meta models via Together AI
    ];

    /**
     * Default model names per provider if not specified.
     */
    private const DEFAULT_MODELS = [
        'openai' => 'gpt-4o',
        'anthropic' => 'claude-sonnet-4-20250514',
        'google' => 'gemini-1.5-pro',
        'meta' => 'meta-llama/Llama-3-70b-chat-hf',
        'mistral' => 'mistral-large-latest',
        'local' => 'llama3',
    ];

    /**
     * Maximum number of retry attempts for transient failures.
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay in seconds between retries (exponential backoff).
     */
    private const RETRY_DELAY_SECONDS = 2;

    /**
     * Send a prompt to the AI provider configured for the given agent.
     *
     * @param Agent $agent The agent whose provider/model to use
     * @param string $systemPrompt The system-level instruction prompt
     * @param string $userPrompt The user-level content prompt
     * @return string The generated text response
     *
     * @throws RuntimeException If the provider fails after all retries
     */
    public function sendPrompt(Agent $agent, string $systemPrompt, string $userPrompt): string
    {
        $provider = $agent->model_provider;
        $model = $agent->model_name;

        // Enforce rate limiting before making the request
        $this->enforceRateLimit($provider);

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            try {
                $response = match ($provider) {
                    'openai' => $this->sendToOpenAI($model, $systemPrompt, $userPrompt),
                    'anthropic' => $this->sendToAnthropic($model, $systemPrompt, $userPrompt),
                    'google' => $this->sendToGoogle($model, $systemPrompt, $userPrompt),
                    'meta' => $this->sendToMeta($model, $systemPrompt, $userPrompt),
                    'mistral' => $this->sendToMistral($model, $systemPrompt, $userPrompt),
                    'local' => $this->sendToLocal($model, $systemPrompt, $userPrompt),
                    default => throw new RuntimeException("Unsupported AI provider: {$provider}"),
                };

                // Record successful request for rate limiting
                $this->recordRequest($provider);

                Log::info('AI prompt completed', [
                    'agent' => $agent->name,
                    'provider' => $provider,
                    'model' => $model,
                    'attempt' => $attempt,
                    'response_length' => strlen($response),
                ]);

                return $response;

            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::warning("AI provider connection failed (attempt {$attempt})", [
                    'agent' => $agent->name,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    $delay = self::RETRY_DELAY_SECONDS * pow(2, $attempt - 1);
                    sleep($delay);
                }

            } catch (RequestException $e) {
                $statusCode = $e->response?->status() ?? 0;

                // Retry on rate limits (429) and server errors (5xx)
                if ($statusCode === 429 || $statusCode >= 500) {
                    $lastException = $e;
                    Log::warning("AI provider request failed with status {$statusCode} (attempt {$attempt})", [
                        'agent' => $agent->name,
                        'provider' => $provider,
                        'status' => $statusCode,
                        'error' => $e->getMessage(),
                    ]);

                    if ($attempt < self::MAX_RETRIES) {
                        $delay = $statusCode === 429
                            ? $this->getRateLimitRetryDelay($e)
                            : self::RETRY_DELAY_SECONDS * pow(2, $attempt - 1);
                        sleep($delay);
                    }
                } else {
                    // Non-retryable error (4xx except 429)
                    Log::error("AI provider returned non-retryable error", [
                        'agent' => $agent->name,
                        'provider' => $provider,
                        'status' => $statusCode,
                        'error' => $e->getMessage(),
                        'body' => $e->response?->body(),
                    ]);
                    throw new RuntimeException(
                        "AI provider {$provider} returned error {$statusCode}: {$e->getMessage()}",
                        $statusCode,
                        $e
                    );
                }
            }
        }

        throw new RuntimeException(
            "AI provider {$provider} failed after " . self::MAX_RETRIES . " attempts: " .
            ($lastException?->getMessage() ?? 'Unknown error'),
            0,
            $lastException
        );
    }

    /**
     * Send a prompt to OpenAI using the openai-php/laravel package.
     */
    private function sendToOpenAI(string $model, string $systemPrompt, string $userPrompt): string
    {
        $result = OpenAI::chat()->create([
            'model' => $model ?: self::DEFAULT_MODELS['openai'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 2048,
            'temperature' => 0.9,
            'top_p' => 0.95,
        ]);

        $content = $result->choices[0]->message->content ?? '';

        if ($content === '') {
            throw new RuntimeException('OpenAI returned an empty response');
        }

        return $content;
    }

    /**
     * Send a prompt to Anthropic's Messages API via HTTP.
     */
    private function sendToAnthropic(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = $this->getApiKey('anthropic');
        $modelName = $model ?: self::DEFAULT_MODELS['anthropic'];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post(self::PROVIDER_ENDPOINTS['anthropic'], [
            'model' => $modelName,
            'max_tokens' => 2048,
            'temperature' => 0.9,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $response->throw();

        $body = $response->json();
        $content = $body['content'][0]['text'] ?? '';

        if ($content === '') {
            throw new RuntimeException('Anthropic returned an empty response');
        }

        return $content;
    }

    /**
     * Send a prompt to Google's Gemini API via HTTP.
     */
    private function sendToGoogle(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = $this->getApiKey('google');
        $modelName = $model ?: self::DEFAULT_MODELS['google'];
        $endpoint = str_replace('{model}', $modelName, self::PROVIDER_ENDPOINTS['google']);

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])->timeout(120)->post($endpoint . '?key=' . $apiKey, [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.9,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ],
        ]);

        $response->throw();

        $body = $response->json();
        $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($content === '') {
            throw new RuntimeException('Google Gemini returned an empty response');
        }

        return $content;
    }

    /**
     * Send a prompt to Meta's Llama models via Together AI's OpenAI-compatible API.
     */
    private function sendToMeta(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = $this->getApiKey('meta');
        $modelName = $model ?: self::DEFAULT_MODELS['meta'];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'content-type' => 'application/json',
        ])->timeout(120)->post(self::PROVIDER_ENDPOINTS['meta'], [
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 2048,
            'temperature' => 0.9,
            'top_p' => 0.95,
        ]);

        $response->throw();

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';

        if ($content === '') {
            throw new RuntimeException('Meta/Together AI returned an empty response');
        }

        return $content;
    }

    /**
     * Send a prompt to Mistral AI's API.
     */
    private function sendToMistral(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = $this->getApiKey('mistral');
        $modelName = $model ?: self::DEFAULT_MODELS['mistral'];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'content-type' => 'application/json',
        ])->timeout(120)->post(self::PROVIDER_ENDPOINTS['mistral'], [
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 2048,
            'temperature' => 0.9,
            'top_p' => 0.95,
        ]);

        $response->throw();

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';

        if ($content === '') {
            throw new RuntimeException('Mistral AI returned an empty response');
        }

        return $content;
    }

    /**
     * Send a prompt to a local Ollama instance.
     */
    private function sendToLocal(string $model, string $systemPrompt, string $userPrompt): string
    {
        $baseUrl = config('services.ollama.url', 'http://localhost:11434');
        $modelName = $model ?: self::DEFAULT_MODELS['local'];

        $response = Http::timeout(300)->post($baseUrl . '/api/chat', [
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'stream' => false,
            'options' => [
                'temperature' => 0.9,
                'top_p' => 0.95,
                'num_predict' => 2048,
            ],
        ]);

        $response->throw();

        $body = $response->json();
        $content = $body['message']['content'] ?? '';

        if ($content === '') {
            throw new RuntimeException('Local Ollama returned an empty response');
        }

        return $content;
    }

    /**
     * Retrieve the API key for a given provider from the api_configurations table.
     * Caches the key for 1 hour to reduce database lookups.
     *
     * @throws RuntimeException If no active API key is found
     */
    private function getApiKey(string $provider): string
    {
        $cacheKey = "api_key_{$provider}";

        return Cache::remember($cacheKey, 3600, function () use ($provider) {
            $config = ApiConfiguration::active()
                ->forProvider($provider)
                ->first();

            if ($config === null) {
                throw new RuntimeException(
                    "No active API configuration found for provider: {$provider}. " .
                    "Please add an API key in the api_configurations table."
                );
            }

            return $config->api_key;
        });
    }

    /**
     * Enforce rate limiting for a specific provider.
     * Blocks execution if the rate limit has been reached until the window resets.
     */
    private function enforceRateLimit(string $provider): void
    {
        $config = ApiConfiguration::active()
            ->forProvider($provider)
            ->first();

        $rateLimit = $config?->rate_limit_per_minute ?? 60;
        $cacheKey = "rate_limit_{$provider}_" . now()->format('Y-m-d-H-i');

        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $rateLimit) {
            $secondsUntilReset = 60 - (int) now()->format('s');
            Log::warning("Rate limit reached for provider {$provider}, waiting {$secondsUntilReset}s", [
                'provider' => $provider,
                'limit' => $rateLimit,
                'current_count' => $currentCount,
            ]);
            sleep($secondsUntilReset);
        }
    }

    /**
     * Record a successful request for rate limiting tracking.
     */
    private function recordRequest(string $provider): void
    {
        $cacheKey = "rate_limit_{$provider}_" . now()->format('Y-m-d-H-i');
        $currentCount = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentCount + 1, 120); // Expire after 2 minutes
    }

    /**
     * Extract a retry delay from rate limit response headers.
     */
    private function getRateLimitRetryDelay(RequestException $e): int
    {
        $retryAfter = $e->response?->header('Retry-After');

        if ($retryAfter !== null && is_numeric($retryAfter)) {
            return min((int) $retryAfter, 120); // Cap at 2 minutes
        }

        // Default to 30 seconds if no Retry-After header
        return 30;
    }

    /**
     * Check whether a specific provider is available and configured.
     */
    public function isProviderAvailable(string $provider): bool
    {
        if ($provider === 'openai') {
            return config('openai.api_key') !== null && config('openai.api_key') !== '';
        }

        if ($provider === 'local') {
            try {
                $baseUrl = config('services.ollama.url', 'http://localhost:11434');
                $response = Http::timeout(5)->get($baseUrl . '/api/tags');
                return $response->successful();
            } catch (\Throwable $e) {
                return false;
            }
        }

        $config = ApiConfiguration::active()
            ->forProvider($provider)
            ->first();

        return $config !== null;
    }

    /**
     * Get a list of all available providers and their status.
     */
    public function getProviderStatus(): array
    {
        $providers = ['openai', 'anthropic', 'google', 'meta', 'mistral', 'local'];
        $status = [];

        foreach ($providers as $provider) {
            $config = ApiConfiguration::active()
                ->forProvider($provider)
                ->first();

            $status[$provider] = [
                'available' => $this->isProviderAvailable($provider),
                'model' => $config?->model_name ?? self::DEFAULT_MODELS[$provider] ?? 'unknown',
                'rate_limit' => $config?->rate_limit_per_minute ?? 60,
                'agents_using' => Agent::active()->where('model_provider', $provider)->count(),
            ];
        }

        return $status;
    }

    /**
     * Invalidate the cached API key for a provider.
     * Call this when API keys are updated in the database.
     */
    public function clearApiKeyCache(string $provider): void
    {
        Cache::forget("api_key_{$provider}");
    }
}
