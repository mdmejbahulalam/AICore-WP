<?php

declare(strict_types=1);

namespace AICore\WP\Modules;

use AICore\WP\Chat\ChatService;
use AICore\WP\Core\AbstractModule;
use AICore\WP\Core\Container;
use AICore\WP\Security\PermissionGuard;
use AICore\WP\Security\RateLimiter;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/** Persisted chat with optional streaming (Server-Sent Events). Admin-only for now. */
final class ChatModule extends AbstractModule
{
    public function id(): string
    {
        return 'chat';
    }

    public function name(): string
    {
        return 'AI Chat Center';
    }

    public function routes(Container $container): array
    {
        $admin = fn () => $container->get(PermissionGuard::class)->requireCapability('manage_options');
        $chat = fn (): ChatService => $container->get(ChatService::class);
        $id = '(?P<id>\d+)';

        return [
            ['path' => '/chat/conversations', 'args' => [
                ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => fn () => rest_ensure_response($chat()->conversations(get_current_user_id()))],
                ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => fn (WP_REST_Request $r) => rest_ensure_response(
                    $chat()->create(get_current_user_id(), (string) $r->get_param('title'), (array) $r->get_json_params())
                )],
            ]],
            ['path' => "/chat/conversations/$id", 'args' => [
                ['methods' => 'GET', 'permission_callback' => $admin, 'callback' => function (WP_REST_Request $r) use ($chat) {
                    $conversation = $chat()->find((int) $r['id'], get_current_user_id());
                    return $conversation === null ? $this->notFound() : rest_ensure_response(['messages' => $chat()->messages((int) $r['id'])]);
                }],
                ['methods' => 'POST', 'permission_callback' => $admin, 'callback' => fn (WP_REST_Request $r) => $chat()->rename((int) $r['id'], get_current_user_id(), (string) $r->get_param('title'))
                    ? rest_ensure_response(['id' => (int) $r['id'], 'title' => sanitize_text_field((string) $r->get_param('title'))])
                    : $this->notFound()],
                ['methods' => 'DELETE', 'permission_callback' => $admin, 'callback' => fn (WP_REST_Request $r) => $chat()->remove((int) $r['id'], get_current_user_id())
                    ? rest_ensure_response(['deleted' => true])
                    : $this->notFound()],
            ]],
            ['path' => "/chat/conversations/$id/messages", 'args' => [
                'methods' => 'POST',
                'permission_callback' => $admin,
                'callback' => fn (WP_REST_Request $r) => $this->send($container, $r),
            ]],
            ['path' => "/chat/conversations/$id/stream", 'args' => [
                'methods' => 'POST',
                'permission_callback' => $admin,
                'callback' => fn (WP_REST_Request $r) => $this->stream($container, $r),
            ]],
        ];
    }

    private function send(Container $container, WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $prepared = $this->prepare($container, $request);
        if ($prepared instanceof WP_Error) {
            return $prepared;
        }
        try {
            return rest_ensure_response($container->get(ChatService::class)->reply($prepared));
        } catch (Throwable $e) {
            return new WP_Error('aicore_provider_error', $e->getMessage(), ['status' => 502, 'user_message_id' => $prepared['user_message_id']]);
        }
    }

    /** Sends the reply as Server-Sent Events: `start`, many `delta`, then `done` (or `error`). Ends the request. */
    private function stream(Container $container, WP_REST_Request $request): WP_Error
    {
        $prepared = $this->prepare($container, $request);
        if ($prepared instanceof WP_Error) {
            return $prepared; // validation errors are ordinary JSON responses
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', '0');
        }
        @set_time_limit(0);
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');

        $emit = static function (string $event, array $data): void {
            echo "event: $event\ndata: " . wp_json_encode($data) . "\n\n";
            @flush();
        };

        $emit('start', ['conversation_id' => $prepared['conversation_id'], 'user_message_id' => $prepared['user_message_id'], 'memories_used' => $prepared['memories_used']]);
        try {
            $result = $container->get(ChatService::class)->streamReply($prepared, static fn (string $text) => $emit('delta', ['text' => $text]));
            $emit('done', $result);
        } catch (Throwable $e) {
            $emit('error', ['message' => $e->getMessage()]);
        }
        exit;
    }

    /** @return array<string,mixed>|WP_Error */
    private function prepare(Container $container, WP_REST_Request $request): array|WP_Error
    {
        $limited = $container->get(RateLimiter::class)->hit('chat', 120, HOUR_IN_SECONDS);
        if (is_wp_error($limited)) {
            return $limited;
        }
        $body = (array) $request->get_json_params();
        return $container->get(ChatService::class)->prepare((int) $request['id'], get_current_user_id(), (string) ($body['content'] ?? ''), $body);
    }

    private function notFound(): WP_Error
    {
        return new WP_Error('aicore_conversation_not_found', __('Conversation not found.', 'aicore-wp'), ['status' => 404]);
    }
}
