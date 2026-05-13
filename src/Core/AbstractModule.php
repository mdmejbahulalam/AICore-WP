<?php

declare(strict_types=1);

namespace AICore\WP\Core;

abstract class AbstractModule implements ModuleInterface
{
    public function register(Container $container): void
    {
    }

    public function routes(Container $container): array
    {
        return [];
    }

    protected function enabled(): bool
    {
        $enabled = (array) get_option('aicore_wp_enabled_modules', []);
        return $enabled === [] || in_array($this->id(), $enabled, true);
    }
}
