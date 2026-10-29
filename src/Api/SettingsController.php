<?php

declare(strict_types=1);

namespace AICore\WP\Api;

use AICore\WP\AI\AIService;
use AICore\WP\AI\ProviderCatalog;
use AICore\WP\AI\ProviderSettings;
use AICore\WP\AI\UsageTracker;
use AICore\WP\Core\Container;
use AICore\WP\Modules\ModuleRegistry;
use AICore\WP\Security\AuditLogger;
use AICore\WP\Security\PermissionGuard;
use AICore\WP\Security\RateLimiter;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/** Admin endpoints for provider settings, module toggles and usage summaries. All require manage_options. */
final class SettingsController
{
    private const ID = '(?P<id>[a-z0-9_-]+)';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(string $namespace): void
    {
        $admin = fn () => $this->container->get(PermissionGuard::class)->requireCapability('manage_options');

        register_rest_route($namespace, '/settings', ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => [$this, 'settings']]);
        register_rest_route($namespace, '/settings/providers/' . self::ID, ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => [$this, 'saveProvider']]);
        register_rest_route($namespace, '/settings/providers/' . self::ID . '/test', ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => [$this, 'testProvider']]);
        register_rest_route($namespace, '/settings/default-provider', ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => [$this, 'setDefaultProvider']]);
        register_rest_route($namespace, '/modules', ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => [$this, 'modules']]);
        register_rest_route($namespace, '/modules/' . self::ID, ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => [$this, 'toggleModule']]);
        register_rest_route($namespace, '/usage/summary', ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => [$this, 'usage']]);
    }

    public function settings(): WP_REST_Response
    {
        $settings = $this->container->get(ProviderSettings::class);
        $providers = [];
        foreach (ProviderCatalog::all() as $id => $meta) {
            $providers[] = ['id' => $id, 'label' => $meta['label'], 'default_model' => $meta['default_model'], 'needs_key' => $meta['needs_key'], 'default_embedding_model' => ProviderCatalog::embeddingModel($id)] + $settings->public($id);
        }
        return rest_ensure_response(['providers' => $providers, 'default_provider' => $settings->defaultProvider()]);
    }

    public function saveProvider(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (string) $request['id'];
        if (! ProviderCatalog::exists($id)) {
            return new WP_Error('aicore_unknown_provider', __('Unknown AI provider.', 'aicore-wp'), ['status' => 404]);
        }
        $input = array_intersect_key((array) $request->get_json_params(), array_flip(['api_key', 'clear_key', 'model', 'embedding_model', 'max_tokens', 'base_url']));
        $this->container->get(ProviderSettings::class)->save($id, $input);
        $this->container->get(AuditLogger::class)->log('provider_settings_updated', ['provider' => $id, 'fields' => array_keys($input)]);
        return rest_ensure_response($this->container->get(ProviderSettings::class)->public($id));
    }

    public function setDefaultProvider(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (string) $request->get_param('provider');
        if (! ProviderCatalog::exists($id)) {
            return new WP_Error('aicore_unknown_provider', __('Unknown AI provider.', 'aicore-wp'), ['status' => 404]);
        }
        $this->container->get(ProviderSettings::class)->setDefaultProvider($id);
        $this->container->get(AuditLogger::class)->log('default_provider_changed', ['provider' => $id]);
        return rest_ensure_response(['default_provider' => $id]);
    }

    /** Sends a tiny prompt to the provider. Provider failures are reported as ok=false (HTTP 200) so the UI can show them. */
    public function testProvider(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (string) $request['id'];
        if (! ProviderCatalog::exists($id)) {
            return new WP_Error('aicore_unknown_provider', __('Unknown AI provider.', 'aicore-wp'), ['status' => 404]);
        }
        $limited = $this->container->get(RateLimiter::class)->hit('provider_test', 20, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) {
            return $limited;
        }
        $started = microtime(true);
        try {
            $response = $this->container->get(AIService::class)->chat(
                [['role' => 'user', 'content' => 'Reply with the single word: OK']],
                ['provider' => $id, 'max_tokens' => 16],
                'provider_test'
            );
        } catch (Throwable $e) {
            return rest_ensure_response(['ok' => false, 'error' => $e->getMessage()]);
        }
        if (($response->metadata['mode'] ?? '') === 'preview') {
            return rest_ensure_response(['ok' => false, 'error' => __('No API key saved for this provider.', 'aicore-wp')]);
        }
        return rest_ensure_response([
            'ok' => true,
            'model' => (string) ($response->metadata['model'] ?? ''),
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'reply' => mb_substr(trim($response->content), 0, 80),
        ]);
    }

    public function modules(): WP_REST_Response
    {
        return rest_ensure_response($this->container->get(ModuleRegistry::class)->manifest());
    }

    public function toggleModule(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (string) $request['id'];
        $enabled = rest_sanitize_boolean($request->get_param('enabled'));
        $result = $this->container->get(ModuleRegistry::class)->setEnabled($id, $enabled);
        if (is_wp_error($result)) {
            return $result;
        }
        $this->container->get(AuditLogger::class)->log('module_toggled', ['module' => $id, 'enabled' => $enabled]);
        return rest_ensure_response(['id' => $id, 'enabled' => $enabled]);
    }

    public function usage(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->container->get(UsageTracker::class)->summary((int) ($request->get_param('days') ?: 30)));
    }
}
