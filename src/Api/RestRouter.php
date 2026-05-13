<?php

declare(strict_types=1);

namespace AICore\WP\Api;

use AICore\WP\AI\Orchestrator;
use AICore\WP\AI\ToolRegistry;
use AICore\WP\Core\Container;
use AICore\WP\Modules\ModuleRegistry;
use AICore\WP\Security\PermissionGuard;
use AICore\WP\Security\RateLimiter;
use WP_REST_Request;
use WP_REST_Response;

final class RestRouter
{
    public const NAMESPACE = 'aicore/v1';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        register_rest_route(self::NAMESPACE, '/command', [
            'methods' => 'POST',
            'permission_callback' => fn () => $this->container->get(PermissionGuard::class)->requireCapability('manage_options'),
            'callback' => [$this, 'command'],
            'args' => ['prompt' => ['type' => 'string', 'required' => true]],
        ]);
        register_rest_route(self::NAMESPACE, '/modules', [
            'methods' => 'GET',
            'permission_callback' => fn () => $this->container->get(PermissionGuard::class)->requireCapability('manage_options'),
            'callback' => fn () => rest_ensure_response($this->container->get(ModuleRegistry::class)->manifest()),
        ]);
        register_rest_route(self::NAMESPACE, '/mcp/tools', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => fn () => rest_ensure_response((new ToolRegistry())->tools()),
        ]);
        register_rest_route(self::NAMESPACE, '/mcp/execute', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'mcpPermission'],
            'callback' => [$this, 'mcpExecute'],
        ]);
        foreach ($this->container->get(ModuleRegistry::class)->routes($this->container) as $route) {
            register_rest_route(self::NAMESPACE, $route['path'], $route['args']);
        }
    }

    public function command(WP_REST_Request $request): WP_REST_Response
    {
        $limited = $this->container->get(RateLimiter::class)->hit('command', 60, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) {
            return rest_ensure_response($limited);
        }
        $prompt = sanitize_textarea_field((string) $request->get_param('prompt'));
        $context = (array) $request->get_param('context');
        return rest_ensure_response($this->container->get(Orchestrator::class)->command($prompt, $context));
    }

    /** @return bool */
    public function mcpPermission(WP_REST_Request $request): bool
    {
        $header = (string) $request->get_header('x-aicore-api-key');
        if ($header === '') {
            return false;
        }
        $keys = (array) get_option('aicore_wp_api_keys', []);
        return in_array(hash('sha256', $header), $keys, true);
    }

    public function mcpExecute(WP_REST_Request $request): WP_REST_Response
    {
        $tool = sanitize_key((string) $request->get_param('tool'));
        $args = (array) $request->get_param('arguments');
        return rest_ensure_response((new ToolRegistry())->execute($tool, $args));
    }
}
