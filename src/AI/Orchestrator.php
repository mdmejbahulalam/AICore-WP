<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use AICore\WP\Database\Repository;
use AICore\WP\Security\AuditLogger;

final class Orchestrator
{
    public function __construct(private readonly ProviderRegistry $providers, private readonly Repository $repository, private readonly AuditLogger $auditLogger)
    {
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    public function command(string $prompt, array $context = []): array
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
        $response = $this->providers->get($context['provider'] ?? null)->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], $context);
        $this->repository->insert('aicore_ai_messages', [
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'provider' => (string) ($context['provider'] ?? get_option('aicore_wp_default_provider', 'openai')),
            'model' => (string) ($context['model'] ?? 'default'),
            'content' => $response->content,
            'tool_calls' => wp_json_encode([]),
            'tokens' => $response->inputTokens + $response->outputTokens,
            'created_at' => current_time('mysql', true),
        ]);
        $this->auditLogger->log('command_executed', ['conversation_id' => $conversationId]);
        return ['conversation_id' => $conversationId, 'message' => $response->content, 'usage' => ['input' => $response->inputTokens, 'output' => $response->outputTokens]];
    }
}
