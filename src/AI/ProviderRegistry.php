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

    /** @return list<string> */
    public function ids(): array
    {
        return array_values(array_keys($this->providers));
    }
}
