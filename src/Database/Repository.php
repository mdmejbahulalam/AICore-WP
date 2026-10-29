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

    /** @param array<string,mixed> $data */
    public function update(string $table, int $id, array $data): bool
    {
        return $this->db->update($this->db->prefix . $table, $data, ['id' => $id]) !== false;
    }

    public function delete(string $table, int $id): bool
    {
        return $this->db->delete($this->db->prefix . $table, ['id' => $id]) !== false;
    }

    /** @param array<string,scalar> $where Column => value pairs, combined with AND. */
    public function deleteBy(string $table, array $where): bool
    {
        return $this->db->delete($this->db->prefix . $table, $where) !== false;
    }

    /** @return array<string,mixed>|null */
    public function find(string $table, int $id): ?array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->db->prefix}{$table} WHERE id = %d", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return is_array($row) ? $row : null;
    }

    /**
     * Run a read query. `{prefix}` in the SQL is replaced with the table prefix; values go through $args (wpdb::prepare).
     *
     * @param list<mixed> $args
     * @return list<array<string,mixed>>
     */
    public function select(string $sql, array $args = []): array
    {
        $sql = str_replace('{prefix}', $this->db->prefix, $sql);
        $prepared = $args === [] ? $sql : $this->db->prepare($sql, ...$args);
        return (array) $this->db->get_results($prepared, ARRAY_A);
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
