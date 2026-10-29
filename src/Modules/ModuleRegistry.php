<?php

declare(strict_types=1);

namespace AICore\WP\Modules;

use AICore\WP\Core\Container;
use AICore\WP\Core\ModuleInterface;
use WP_Error;

/**
 * Discovers modules from modules/<id>/module.json, tracks which are enabled and instantiates the enabled ones.
 * A module may ship a class AICore\WP\Modules\<Id>Module; otherwise a GenericModule (manifest endpoint only) is used.
 */
final class ModuleRegistry
{
    public const OPTION = 'aicore_wp_modules';

    /** @var array<string,array{id:string,name:string,description:string,enabledByDefault:bool,requires:list<string>,capabilities:list<string>}> */
    private array $definitions = [];
    /** @var array<string,ModuleInterface> */
    private array $instances = [];

    public function __construct(?string $modulesDir = null)
    {
        $dir = $modulesDir ?? dirname(__DIR__, 2) . '/modules';
        $files = glob($dir . '/*/module.json') ?: [];
        sort($files);
        foreach ($files as $file) {
            $json = json_decode((string) file_get_contents($file), true);
            if (! is_array($json) || ! isset($json['id'])) {
                continue;
            }
            $id = (string) $json['id'];
            $this->definitions[$id] = [
                'id' => $id,
                'name' => (string) ($json['name'] ?? $id),
                'description' => (string) ($json['description'] ?? ''),
                'enabledByDefault' => (bool) ($json['enabledByDefault'] ?? true),
                'requires' => array_values(array_map('strval', (array) ($json['requires'] ?? []))),
                'capabilities' => array_values(array_map('strval', (array) ($json['capabilities'] ?? []))),
            ];
        }
    }

    public function exists(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    public function isEnabled(string $id): bool
    {
        if (! isset($this->definitions[$id])) {
            return false;
        }
        $state = (array) get_option(self::OPTION, []);
        return array_key_exists($id, $state) ? (bool) $state[$id] : $this->definitions[$id]['enabledByDefault'];
    }

    /** @return true|WP_Error */
    public function setEnabled(string $id, bool $enabled)
    {
        if (! $this->exists($id)) {
            return new WP_Error('aicore_unknown_module', 'Unknown module.', ['status' => 404]);
        }
        if ($enabled) {
            $missing = array_values(array_filter($this->definitions[$id]['requires'], fn (string $dep): bool => ! $this->isEnabled($dep)));
            if ($missing !== []) {
                return new WP_Error('aicore_module_requires', 'Enable these modules first: ' . implode(', ', $missing) . '.', ['status' => 409, 'missing' => $missing]);
            }
        } else {
            $dependents = $this->enabledDependents($id);
            if ($dependents !== []) {
                return new WP_Error('aicore_module_required_by', 'Disable these modules first: ' . implode(', ', $dependents) . '.', ['status' => 409, 'dependents' => $dependents]);
            }
        }
        $state = (array) get_option(self::OPTION, []);
        $state[$id] = $enabled;
        update_option(self::OPTION, $state);
        return true;
    }

    public function registerModules(Container $container): void
    {
        foreach ($this->enabledModules() as $module) {
            $module->register($container);
            do_action('aicore_wp_module_registered', $module->id(), $module);
        }
    }

    /** @return list<array<string,mixed>> */
    public function routes(Container $container): array
    {
        $routes = [];
        foreach ($this->enabledModules() as $module) {
            array_push($routes, ...$module->routes($container));
        }
        return $routes;
    }

    /** @return list<array<string,mixed>> */
    public function manifest(): array
    {
        $manifest = [];
        foreach ($this->definitions as $id => $definition) {
            $manifest[] = [
                'id' => $id,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'enabled' => $this->isEnabled($id),
                'requires' => $definition['requires'],
                'required_by' => $this->dependents($id),
                'capabilities' => $definition['capabilities'],
            ];
        }
        return $manifest;
    }

    /** @return list<ModuleInterface> */
    private function enabledModules(): array
    {
        $modules = [];
        foreach ($this->definitions as $id => $definition) {
            if ($this->isEnabled($id) && $this->dependenciesEnabled($id)) {
                $modules[] = $this->instances[$id] ??= $this->instantiate($definition);
            }
        }
        return $modules;
    }

    private function dependenciesEnabled(string $id): bool
    {
        foreach ($this->definitions[$id]['requires'] as $dep) {
            if (! $this->isEnabled($dep) || ! $this->dependenciesEnabled($dep)) {
                return false;
            }
        }
        return true;
    }

    /** @return list<string> */
    private function dependents(string $id): array
    {
        return array_values(array_keys(array_filter($this->definitions, fn (array $d): bool => in_array($id, $d['requires'], true))));
    }

    /** @return list<string> */
    private function enabledDependents(string $id): array
    {
        return array_values(array_filter($this->dependents($id), fn (string $dep): bool => $this->isEnabled($dep)));
    }

    /** @param array{id:string,name:string,capabilities:list<string>} $definition */
    private function instantiate(array $definition): ModuleInterface
    {
        $class = __NAMESPACE__ . '\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $definition['id']))) . 'Module';
        if (class_exists($class) && is_subclass_of($class, ModuleInterface::class)) {
            return new $class();
        }
        return new GenericModule($definition['id'], $definition['name'], $definition['capabilities']);
    }
}
