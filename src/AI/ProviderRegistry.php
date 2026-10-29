<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use RuntimeException;

final class ProviderRegistry
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    public function __construct(private readonly ProviderSettings $settings)
    {
        foreach (ProviderCatalog::ids() as $provider) {
            $this->register(new RemoteProvider($provider, $settings));
        }
    }

    public function register(ProviderInterface $provider): void
    {
        $this->providers[$provider->id()] = $provider;
    }

    public function get(?string $id = null): ProviderInterface
    {
        $id = $id ?: $this->settings->defaultProvider();
        if (! isset($this->providers[$id])) {
            throw new RuntimeException("AI provider {$id} is not registered.");
        }
        return $this->providers[$id];
    }

    /**
     * The provider used for embeddings: the default provider if it supports them, otherwise the first configured
     * one that does. Returns null when none is available.
     */
    public function embeddingProviderId(): ?string
    {
        foreach (array_unique([$this->settings->defaultProvider(), 'openai', 'openrouter', 'gemini']) as $id) {
            if (! isset($this->providers[$id]) || ! $this->providers[$id]->supportsEmbeddings()) {
                continue;
            }
            if ($this->settings->config($id)['api_key'] !== '' || ! (ProviderCatalog::all()[$id]['needs_key'] ?? true)) {
                return $id;
            }
        }
        return null;
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_values(array_keys($this->providers));
    }
}
