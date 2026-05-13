<?php

declare(strict_types=1);

namespace AICore\WP\Core;

use RuntimeException;

final class Container
{
    /** @var array<string, callable(self): object> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /** @template T of object @param class-string<T>|string $id @return T */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (! isset($this->bindings[$id])) {
            if (class_exists($id)) {
                $this->instances[$id] = new $id();
                return $this->instances[$id];
            }
            throw new RuntimeException("Service {$id} is not registered.");
        }
        $this->instances[$id] = ($this->bindings[$id])($this);
        return $this->instances[$id];
    }
}
