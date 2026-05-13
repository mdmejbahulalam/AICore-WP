<?php

declare(strict_types=1);

namespace AICore\WP\Modules;

use AICore\WP\Core\AbstractModule;
use AICore\WP\Core\Container;

final class GenericModule extends AbstractModule
{
    /** @param list<string> $capabilities */
    public function __construct(private readonly string $id, private readonly string $name, private readonly array $capabilities)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function routes(Container $container): array
    {
        return [[
            'path' => '/' . $this->id . '/manifest',
            'args' => [
                'methods' => 'GET',
                'permission_callback' => fn () => current_user_can('manage_options'),
                'callback' => fn () => rest_ensure_response(['id' => $this->id, 'name' => $this->name, 'capabilities' => $this->capabilities]),
            ],
        ]];
    }
}
