<?php

declare(strict_types=1);

namespace AICore\WP\Modules;

use AICore\WP\Core\AbstractModule;
use AICore\WP\Core\Container;
use AICore\WP\Memory\MemoryService;
use AICore\WP\Security\PermissionGuard;
use WP_REST_Request;

/** CRUD for long-term memories (site-wide and per-user facts the assistant should remember). */
final class MemoryModule extends AbstractModule
{
    public function id(): string
    {
        return 'memory';
    }

    public function name(): string
    {
        return 'AI Memory Manager';
    }

    public function routes(Container $container): array
    {
        $admin = fn () => $container->get(PermissionGuard::class)->requireCapability('manage_options');
        $memory = fn (): MemoryService => $container->get(MemoryService::class);
        $respond = static fn (mixed $result) => is_wp_error($result) ? $result : rest_ensure_response($result);

        return [
            ['path' => '/memories', 'args' => [
                ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => fn () => rest_ensure_response($memory()->list(get_current_user_id()))],
                ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => fn (WP_REST_Request $r) => $respond($memory()->create(get_current_user_id(), (array) $r->get_json_params()))],
            ]],
            ['path' => '/memories/reindex', 'args' => [
                'methods' => 'POST',
                'permission_callback' => $admin,
                'callback' => fn () => rest_ensure_response($memory()->reindex(get_current_user_id())),
            ]],
            ['path' => '/memories/(?P<id>\d+)', 'args' => [
                ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => fn (WP_REST_Request $r) => $respond($memory()->update((int) $r['id'], get_current_user_id(), (array) $r->get_json_params()))],
                ['methods' => 'DELETE', 'permission_callback' => $admin, 'callback' => function (WP_REST_Request $r) use ($memory, $respond) {
                    $result = $memory()->delete((int) $r['id'], get_current_user_id());
                    return $respond(is_wp_error($result) ? $result : ['deleted' => true]);
                }],
            ]],
        ];
    }
}
