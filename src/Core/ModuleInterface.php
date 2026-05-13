<?php

declare(strict_types=1);

namespace AICore\WP\Core;

interface ModuleInterface
{
    public function id(): string;
    public function name(): string;
    public function register(Container $container): void;
    public function routes(Container $container): array;
}
