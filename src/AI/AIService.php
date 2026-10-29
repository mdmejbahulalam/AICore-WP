<?php

declare(strict_types=1);

namespace AICore\WP\AI;

/** Single entry point modules use to call an AI provider; every real call is recorded for usage analytics. */
final class AIService
{
    public function __construct(private readonly ProviderRegistry $providers, private readonly UsageTracker $usage)
    {
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @param array<string,mixed> $options provider, model, max_tokens, temperature
     * @throws \RuntimeException when the provider request fails
     */
    public function chat(array $messages, array $options = [], string $operation = 'chat'): AIResponse
    {
        $provider = $this->providers->get(isset($options['provider']) ? (string) $options['provider'] : null);
        $response = $provider->chat($messages, $options);
        if (($response->metadata['mode'] ?? '') !== 'preview') {
            $this->usage->record($provider->id(), (string) ($response->metadata['model'] ?? 'unknown'), $operation, $response->inputTokens, $response->outputTokens);
        }
        return $response;
    }
}
