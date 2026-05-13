<?php

declare(strict_types=1);

namespace AICore\WP\Database;

final class Migrator
{
    public function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $tables = $this->schema($wpdb->prefix, $charset);
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /** @return list<string> */
    public function schema(string $prefix, string $charset): array
    {
        return [
            "CREATE TABLE {$prefix}aicore_ai_conversations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NULL, title VARCHAR(255) NOT NULL DEFAULT '', status VARCHAR(32) NOT NULL DEFAULT 'active', context LONGTEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), KEY user_status (user_id,status), KEY updated_at (updated_at)) {$charset};",
            "CREATE TABLE {$prefix}aicore_ai_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, conversation_id BIGINT UNSIGNED NOT NULL, role VARCHAR(32) NOT NULL, provider VARCHAR(64) NULL, model VARCHAR(128) NULL, content LONGTEXT NOT NULL, tool_calls LONGTEXT NULL, tokens INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY conversation_created (conversation_id,created_at)) {$charset};",
            "CREATE TABLE {$prefix}aicore_ai_embeddings (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, source_type VARCHAR(64) NOT NULL, source_id VARCHAR(128) NOT NULL, vector_id VARCHAR(191) NOT NULL, provider VARCHAR(64) NOT NULL, dimensions SMALLINT UNSIGNED NOT NULL, chunk_hash CHAR(64) NOT NULL, content LONGTEXT NOT NULL, metadata LONGTEXT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY chunk_hash (chunk_hash), KEY source_lookup (source_type,source_id), KEY vector_id (vector_id)) {$charset};",
            "CREATE TABLE {$prefix}aicore_ai_memories (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NULL, scope VARCHAR(64) NOT NULL DEFAULT 'site', memory_key VARCHAR(191) NOT NULL, memory_value LONGTEXT NOT NULL, confidence DECIMAL(5,4) NOT NULL DEFAULT 1.0000, expires_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), KEY user_scope (user_id,scope), KEY memory_key (memory_key)) {$charset};",
            "CREATE TABLE {$prefix}aicore_workflows (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(191) NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'draft', trigger_type VARCHAR(96) NOT NULL, definition LONGTEXT NOT NULL, created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), KEY status_trigger (status,trigger_type)) {$charset};",
            "CREATE TABLE {$prefix}aicore_workflow_runs (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, workflow_id BIGINT UNSIGNED NOT NULL, status VARCHAR(32) NOT NULL, payload LONGTEXT NULL, result LONGTEXT NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, run_at DATETIME NOT NULL, finished_at DATETIME NULL, PRIMARY KEY (id), KEY workflow_status (workflow_id,status), KEY run_at (run_at)) {$charset};",
            "CREATE TABLE {$prefix}aicore_chatbot_sessions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, chatbot_id VARCHAR(96) NOT NULL, visitor_id VARCHAR(128) NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'open', metadata LONGTEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), KEY chatbot_visitor (chatbot_id,visitor_id), KEY status (status)) {$charset};",
            "CREATE TABLE {$prefix}aicore_chatbot_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, session_id BIGINT UNSIGNED NOT NULL, role VARCHAR(32) NOT NULL, content LONGTEXT NOT NULL, sentiment VARCHAR(32) NULL, intent VARCHAR(96) NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY session_created (session_id,created_at)) {$charset};",
            "CREATE TABLE {$prefix}aicore_ai_generations (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, type VARCHAR(64) NOT NULL, status VARCHAR(32) NOT NULL, prompt LONGTEXT NOT NULL, output LONGTEXT NULL, metadata LONGTEXT NULL, created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY type_status (type,status), KEY created_by (created_by)) {$charset};",
            "CREATE TABLE {$prefix}aicore_analytics_events (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, event_name VARCHAR(128) NOT NULL, actor_id VARCHAR(128) NULL, object_type VARCHAR(64) NULL, object_id VARCHAR(128) NULL, properties LONGTEXT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY event_created (event_name,created_at), KEY object_lookup (object_type,object_id)) {$charset};",
            "CREATE TABLE {$prefix}aicore_prompts (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, slug VARCHAR(128) NOT NULL, title VARCHAR(191) NOT NULL, module VARCHAR(64) NOT NULL, prompt LONGTEXT NOT NULL, variables LONGTEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY slug (slug), KEY module (module)) {$charset};",
            "CREATE TABLE {$prefix}aicore_api_keys (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(191) NOT NULL, key_hash CHAR(64) NOT NULL, scopes LONGTEXT NOT NULL, last_used_at DATETIME NULL, expires_at DATETIME NULL, created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY key_hash (key_hash), KEY expires_at (expires_at)) {$charset};",
            "CREATE TABLE {$prefix}aicore_mcp_tools (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(128) NOT NULL, description TEXT NOT NULL, schema_json LONGTEXT NOT NULL, scopes LONGTEXT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, PRIMARY KEY (id), UNIQUE KEY name (name), KEY enabled (enabled)) {$charset};",
            "CREATE TABLE {$prefix}aicore_subscriptions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, account_id VARCHAR(128) NOT NULL, provider VARCHAR(32) NOT NULL, plan VARCHAR(64) NOT NULL, status VARCHAR(32) NOT NULL, renews_at DATETIME NULL, metadata LONGTEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id), KEY account_status (account_id,status)) {$charset};",
            "CREATE TABLE {$prefix}aicore_usage_logs (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id BIGINT UNSIGNED NULL, provider VARCHAR(64) NOT NULL, model VARCHAR(128) NOT NULL, operation VARCHAR(96) NOT NULL, input_tokens INT UNSIGNED NOT NULL DEFAULT 0, output_tokens INT UNSIGNED NOT NULL DEFAULT 0, cost DECIMAL(12,6) NOT NULL DEFAULT 0.000000, created_at DATETIME NOT NULL, PRIMARY KEY (id), KEY user_created (user_id,created_at), KEY provider_model (provider,model)) {$charset};"
        ];
    }
}
