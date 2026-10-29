<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use AICore\WP\Database\Repository;
use AICore\WP\Security\AuditLogger;
use Throwable;
use WP_Error;

final class Orchestrator
{
    public function __construct(private readonly AIService $ai, private readonly Repository $repository, private readonly AuditLogger $auditLogger)
    {
    }

    /** @param array<string,mixed> $context @return array<string,mixed>|WP_Error */
    public function command(string $prompt, array $context = []): array|WP_Error
    {
        $conversationId = $this->repository->insert('aicore_ai_conversations', [
            'user_id' => get_current_user_id() ?: null,
            'title' => wp_trim_words($prompt, 8, ''),
            'status' => 'active',
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ]);
        $system = 'You are AICore WP, an AI operating system for WordPress. Reason step-by-step, ask for confirmation before destructive actions, and prefer drafts/previews.';
        try {
            $response = $this->ai->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ], $context, 'command');
        } catch (Throwable $e) {
            $this->repository->update('aicore_ai_conversations', $conversationId, ['status' => 'failed', 'updated_at' => current_time('mysql', true)]);
            $this->auditLogger->log('command_failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);
            return new WP_Error('aicore_provider_error', $e->getMessage(), ['status' => 502, 'conversation_id' => $conversationId]);
        }
        $this->repository->insert('aicore_ai_messages', [
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'provider' => (string) ($response->metadata['provider'] ?? $context['provider'] ?? ''),
            'model' => (string) ($response->metadata['model'] ?? $context['model'] ?? 'default'),
            'content' => $response->content,
            'tool_calls' => wp_json_encode([]),
            'tokens' => $response->inputTokens + $response->outputTokens,
            'created_at' => current_time('mysql', true),
        ]);
        $this->auditLogger->log('command_executed', ['conversation_id' => $conversationId]);
        return ['conversation_id' => $conversationId, 'message' => $response->content, 'usage' => ['input' => $response->inputTokens, 'output' => $response->outputTokens]];
    }
}
