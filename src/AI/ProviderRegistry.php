<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use RuntimeException;

final class ProviderRegistry
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        foreach (['openai', 'claude', 'gemini', 'deepseek', 'openrouter', 'groq', 'ollama'] as $provider) {
            $this->register(new RemoteProvider($provider));
        }
    }

    public function register(ProviderInterface $provider): void
    {
        $this->providers[$provider->id()] = $provider;
    }

    public function get(?string $id = null): ProviderInterface
    {
        $id = $id ?: (string) get_option('aicore_wp_default_provider', 'openai');
        if (! isset($this->providers[$id])) {
            throw new RuntimeException("AI provider {$id} is not registered.");
        }
        return $this->providers[$id];
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_values(array_keys($this->providers));
    }
}
