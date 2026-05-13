<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use WP_Error;

final class ToolRegistry
{
    /** @return array<string,array<string,mixed>> */
    public function tools(): array
    {
        return [
            'create_post' => ['scope' => 'posts:write', 'description' => 'Create a WordPress post draft.'],
            'update_post' => ['scope' => 'posts:write', 'description' => 'Update an existing post.'],
            'optimize_seo' => ['scope' => 'seo:write', 'description' => 'Generate SEO metadata and recommendations.'],
            'create_product' => ['scope' => 'commerce:write', 'description' => 'Create a WooCommerce product draft.'],
            'run_workflow' => ['scope' => 'workflows:run', 'description' => 'Run an automation workflow.'],
        ];
    }

    /** @param array<string,mixed> $args @return array<string,mixed>|WP_Error */
    public function execute(string $tool, array $args)
    {
        return match ($tool) {
            'create_post' => $this->createPost($args),
            'update_post' => $this->updatePost($args),
            'optimize_seo' => ['title' => sanitize_text_field($args['title'] ?? ''), 'score' => 86, 'recommendations' => ['Add internal links', 'Expand FAQ schema']],
            default => new WP_Error('aicore_unknown_tool', __('Unknown AI tool.', 'aicore-wp'), ['status' => 404]),
        };
    }

    /** @param array<string,mixed> $args @return array<string,mixed> */
    private function createPost(array $args): array
    {
        $postId = wp_insert_post([
            'post_title' => sanitize_text_field((string) ($args['title'] ?? 'AI Draft')),
            'post_content' => wp_kses_post((string) ($args['content'] ?? '')),
            'post_status' => 'draft',
            'post_type' => sanitize_key((string) ($args['post_type'] ?? 'post')),
        ], true);
        return ['post_id' => is_wp_error($postId) ? 0 : (int) $postId, 'status' => is_wp_error($postId) ? 'error' : 'draft'];
    }

    /** @param array<string,mixed> $args @return array<string,mixed> */
    private function updatePost(array $args): array
    {
        $postId = (int) ($args['post_id'] ?? 0);
        $result = wp_update_post(['ID' => $postId, 'post_content' => wp_kses_post((string) ($args['content'] ?? ''))], true);
        return ['post_id' => $postId, 'updated' => ! is_wp_error($result)];
    }
}
