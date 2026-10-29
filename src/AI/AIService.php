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
        $provider = $this->provider($options);
        return $this->record($provider, $provider->chat($messages, $options), $operation);
    }

    /**
     * Like chat(), but $onDelta receives the reply text as it is generated.
     *
     * @param list<array{role:string,content:string}> $messages
     * @param array<string,mixed> $options
     * @param callable(string):void $onDelta
     * @throws \RuntimeException when the provider request fails
     */
    public function stream(array $messages, array $options, callable $onDelta, string $operation = 'chat'): AIResponse
    {
        $provider = $this->provider($options);
        return $this->record($provider, $provider->stream($messages, $options, $onDelta), $operation);
    }

    /**
     * @param array<string,mixed> $options provider, model
     * @throws \RuntimeException when the provider does not support embeddings or the request fails
     */
    public function embed(string $input, array $options = [], string $operation = 'embedding'): EmbeddingResponse
    {
        $providerId = isset($options['provider']) && $options['provider'] !== '' ? (string) $options['provider'] : $this->providers->embeddingProviderId();
        if ($providerId === null) {
            throw new \RuntimeException('No configured AI provider supports embeddings. Add an OpenAI, OpenRouter or Gemini key.');
        }
        $provider = $this->providers->get($providerId);
        $response = $provider->embed($input, $options);
        $this->usage->record($provider->id(), $response->model, $operation, $response->tokens, 0);
        return $response;
    }

    public function supportsEmbeddings(): bool
    {
        return $this->embeddingProviderId() !== null;
    }

    /** The provider embed() uses when none is given, or null when no configured provider supports embeddings. */
    public function embeddingProviderId(): ?string
    {
        return $this->providers->embeddingProviderId();
    }

    /** @param array<string,mixed> $options */
    private function provider(array $options): ProviderInterface
    {
        return $this->providers->get(isset($options['provider']) && $options['provider'] !== '' ? (string) $options['provider'] : null);
    }

    private function record(ProviderInterface $provider, AIResponse $response, string $operation): AIResponse
    {
        if (($response->metadata['mode'] ?? '') !== 'preview') {
            $this->usage->record($provider->id(), (string) ($response->metadata['model'] ?? 'unknown'), $operation, $response->inputTokens, $response->outputTokens);
        }
        return $response;
    }
}
