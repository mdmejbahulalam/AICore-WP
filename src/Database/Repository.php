<?php

declare(strict_types=1);

namespace AICore\WP\Database;

use wpdb;

final class Repository
{
    public function __construct(private readonly wpdb $db)
    {
    }

    /** @param array<string,mixed> $data */
    public function insert(string $table, array $data): int
    {
        $this->db->insert($this->db->prefix . $table, $data);
        return (int) $this->db->insert_id;
    }

    /** @return array<string,mixed>|null */
    public function find(string $table, int $id): ?array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->db->prefix}{$table} WHERE id = %d", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return is_array($row) ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function recent(string $table, int $limit = 20): array
    {
        $safeLimit = max(1, min(100, $limit));
        $sql = $this->db->prepare("SELECT * FROM {$this->db->prefix}{$table} ORDER BY id DESC LIMIT %d", $safeLimit);
        return (array) $this->db->get_results($sql, ARRAY_A);
    }

    /** @param array<string,mixed> $data */
    public function event(string $name, array $data = []): int
    {
        return $this->insert('aicore_analytics_events', [
            'event_name' => sanitize_key($name),
            'actor_id' => isset($data['actor_id']) ? sanitize_text_field((string) $data['actor_id']) : null,
            'object_type' => isset($data['object_type']) ? sanitize_key((string) $data['object_type']) : null,
            'object_id' => isset($data['object_id']) ? sanitize_text_field((string) $data['object_id']) : null,
            'properties' => wp_json_encode($data['properties'] ?? []),
            'created_at' => current_time('mysql', true),
        ]);
    }
}
