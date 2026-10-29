<?php

declare(strict_types=1);

namespace AICore\WP\Chat;

use AICore\WP\AI\AIResponse;
use AICore\WP\AI\AIService;
use AICore\WP\AI\ProviderCatalog;
use AICore\WP\Database\Repository;
use AICore\WP\Memory\MemoryService;
use WP_Error;

/** Persisted multi-turn chat. Conversations belong to one user; every query is scoped by user id. */
final class ChatService
{
    private const CONVERSATIONS = 'aicore_ai_conversations';
    private const MESSAGES = 'aicore_ai_messages';
    private const HISTORY_LIMIT = 60;
    private const MAX_PROMPT_LENGTH = 20000;

    public function __construct(
        private readonly Repository $repository,
        private readonly AIService $ai,
        private readonly MemoryService $memory
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function conversations(int $userId): array
    {
        $rows = $this->repository->select(
            "SELECT id, title, context, updated_at FROM {prefix}" . self::CONVERSATIONS . " WHERE user_id = %d AND status <> 'deleted' ORDER BY updated_at DESC, id DESC LIMIT 100",
            [$userId]
        );
        return array_map(fn (array $row): array => $this->presentConversation($row), $rows);
    }

    /**
     * @param array<string,mixed> $options provider, model
     * @return array<string,mixed>
     */
    public function create(int $userId, string $title, array $options = []): array
    {
        $now = current_time('mysql', true);
        $id = $this->repository->insert(self::CONVERSATIONS, [
            'user_id' => $userId,
            'title' => mb_substr(sanitize_text_field($title), 0, 120),
            'status' => 'active',
            'context' => wp_json_encode($this->cleanOptions($options)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->presentConversation($this->find($id, $userId) ?? []);
    }

    /** @return array<string,mixed>|null */
    public function find(int $id, int $userId): ?array
    {
        $rows = $this->repository->select(
            "SELECT * FROM {prefix}" . self::CONVERSATIONS . " WHERE id = %d AND user_id = %d AND status <> 'deleted' LIMIT 1",
            [$id, $userId]
        );
        return $rows[0] ?? null;
    }

    /** @return list<array<string,mixed>> oldest first */
    public function messages(int $conversationId): array
    {
        $rows = $this->repository->select(
            'SELECT id, role, content, provider, model, tokens, created_at FROM {prefix}' . self::MESSAGES . ' WHERE conversation_id = %d ORDER BY id ASC LIMIT 500',
            [$conversationId]
        );
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'role' => (string) $row['role'],
            'content' => (string) $row['content'],
            'provider' => (string) ($row['provider'] ?? ''),
            'model' => (string) ($row['model'] ?? ''),
            'tokens' => (int) $row['tokens'],
            'created_at' => (string) $row['created_at'],
        ], $rows);
    }

    public function rename(int $id, int $userId, string $title): bool
    {
        if ($this->find($id, $userId) === null) {
            return false;
        }
        return $this->repository->update(self::CONVERSATIONS, $id, ['title' => mb_substr(sanitize_text_field($title), 0, 120), 'updated_at' => current_time('mysql', true)]);
    }

    public function remove(int $id, int $userId): bool
    {
        if ($this->find($id, $userId) === null) {
            return false;
        }
        $this->repository->deleteBy(self::MESSAGES, ['conversation_id' => $id]);
        return $this->repository->delete(self::CONVERSATIONS, $id);
    }

    /**
     * Validates the request, stores the user's message and builds the prompt. Nothing is sent to the provider yet.
     *
     * @param array<string,mixed> $options provider, model, max_tokens, temperature
     * @return array<string,mixed>|WP_Error
     */
    public function prepare(int $conversationId, int $userId, string $content, array $options = []): array|WP_Error
    {
        $conversation = $this->find($conversationId, $userId);
        if ($conversation === null) {
            return new WP_Error('aicore_conversation_not_found', __('Conversation not found.', 'aicore-wp'), ['status' => 404]);
        }
        $content = trim($content);
        if ($content === '' || mb_strlen($content) > self::MAX_PROMPT_LENGTH) {
            return new WP_Error('aicore_invalid_message', __('Enter a message of up to 20,000 characters.', 'aicore-wp'), ['status' => 400]);
        }

        $stored = $this->cleanOptions((array) json_decode((string) ($conversation['context'] ?? ''), true));
        $chosen = $this->cleanOptions($options);
        $aiOptions = $chosen + $stored;
        if (array_intersect_key($chosen, ['provider' => 1, 'model' => 1]) !== array_intersect_key($stored, ['provider' => 1, 'model' => 1]) && array_intersect_key($chosen, ['provider' => 1, 'model' => 1]) !== []) {
            $this->repository->update(self::CONVERSATIONS, $conversationId, ['context' => wp_json_encode($aiOptions)]);
        }

        $history = array_map(
            static fn (array $message): array => ['role' => $message['role'], 'content' => $message['content']],
            array_values(array_filter(array_slice($this->messages($conversationId), -self::HISTORY_LIMIT), static fn (array $m): bool => in_array($m['role'], ['user', 'assistant'], true)))
        );
        $history[] = ['role' => 'user', 'content' => $content];

        $memories = array_map(
            static fn (array $memory): array => ['key' => (string) $memory['memory_key'], 'value' => (string) $memory['memory_value']],
            $this->memory->recall($userId, $content)
        );

        $userMessageId = $this->repository->insert(self::MESSAGES, [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $content,
            'tokens' => 0,
            'created_at' => current_time('mysql', true),
        ]);
        if (trim((string) $conversation['title']) === '') {
            $this->repository->update(self::CONVERSATIONS, $conversationId, ['title' => mb_substr(wp_trim_words($content, 8, ''), 0, 120)]);
        }

        return [
            'conversation_id' => $conversationId,
            'user_message_id' => $userMessageId,
            'messages' => PromptBuilder::build($memories, $history),
            'options' => $aiOptions,
            'memories_used' => count($memories),
        ];
    }

    /**
     * @param array<string,mixed> $prepared result of prepare()
     * @return array<string,mixed>
     * @throws \RuntimeException when the provider fails; the user's message stays saved
     */
    public function reply(array $prepared): array
    {
        return $this->finish($prepared, $this->ai->chat($prepared['messages'], $prepared['options'], 'chat'));
    }

    /**
     * @param array<string,mixed> $prepared result of prepare()
     * @param callable(string):void $onDelta
     * @return array<string,mixed>
     * @throws \RuntimeException when the provider fails
     */
    public function streamReply(array $prepared, callable $onDelta): array
    {
        return $this->finish($prepared, $this->ai->stream($prepared['messages'], $prepared['options'], $onDelta, 'chat'));
    }

    /**
     * @param array<string,mixed> $prepared
     * @return array<string,mixed>
     */
    private function finish(array $prepared, AIResponse $response): array
    {
        $conversationId = (int) $prepared['conversation_id'];
        $messageId = $this->repository->insert(self::MESSAGES, [
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'provider' => (string) ($response->metadata['provider'] ?? ''),
            'model' => (string) ($response->metadata['model'] ?? ''),
            'content' => $response->content,
            'tool_calls' => wp_json_encode([]),
            'tokens' => $response->inputTokens + $response->outputTokens,
            'created_at' => current_time('mysql', true),
        ]);
        $this->repository->update(self::CONVERSATIONS, $conversationId, ['updated_at' => current_time('mysql', true)]);

        return [
            'conversation_id' => $conversationId,
            'user_message_id' => (int) $prepared['user_message_id'],
            'message' => [
                'id' => $messageId,
                'role' => 'assistant',
                'content' => $response->content,
                'provider' => (string) ($response->metadata['provider'] ?? ''),
                'model' => (string) ($response->metadata['model'] ?? ''),
                'tokens' => $response->inputTokens + $response->outputTokens,
            ],
            'memories_used' => (int) $prepared['memories_used'],
        ];
    }

    /**
     * Keep only known, safe option values.
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function cleanOptions(array $options): array
    {
        $clean = [];
        if (isset($options['provider']) && ProviderCatalog::exists((string) $options['provider'])) {
            $clean['provider'] = (string) $options['provider'];
        }
        if (isset($options['model']) && trim((string) $options['model']) !== '') {
            $clean['model'] = mb_substr(sanitize_text_field((string) $options['model']), 0, 128);
        }
        if (isset($options['max_tokens']) && (int) $options['max_tokens'] > 0) {
            $clean['max_tokens'] = min(32000, (int) $options['max_tokens']);
        }
        if (isset($options['temperature']) && is_numeric($options['temperature'])) {
            $clean['temperature'] = max(0.0, min(2.0, (float) $options['temperature']));
        }
        return $clean;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function presentConversation(array $row): array
    {
        $context = (array) json_decode((string) ($row['context'] ?? ''), true);
        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'provider' => (string) ($context['provider'] ?? ''),
            'model' => (string) ($context['model'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
