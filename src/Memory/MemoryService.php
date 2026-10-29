<?php

declare(strict_types=1);

namespace AICore\WP\Memory;

use AICore\WP\AI\AIService;
use AICore\WP\AI\EmbeddingStore;
use AICore\WP\Database\Repository;
use Throwable;
use WP_Error;

/**
 * Long-term memories: short facts the assistant should remember. Scope "site" is shared by every admin;
 * scope "user" belongs to one user. Memories are embedded when a provider supports it so that only the
 * relevant ones are added to a prompt; without embeddings the most confident/recent ones are used.
 */
final class MemoryService
{
    private const TABLE = 'aicore_ai_memories';
    private const SOURCE = 'memory';
    private const MAX_VALUE = 4000;

    public function __construct(
        private readonly Repository $repository,
        private readonly AIService $ai,
        private readonly EmbeddingStore $embeddings
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function list(int $userId): array
    {
        $rows = $this->activeRows($userId);
        $vectors = $this->embeddings->vectors(self::SOURCE, array_map(static fn (array $row): string => (string) $row['id'], $rows));
        return array_map(fn (array $row): array => $this->present($row, isset($vectors[(string) $row['id']])), $rows);
    }

    /**
     * @param array<string,mixed> $input scope (site|user), key, value, confidence (0-1), expires_at
     * @return array<string,mixed>|WP_Error
     */
    public function create(int $userId, array $input): array|WP_Error
    {
        $fields = $this->validate($input, true);
        if ($fields instanceof WP_Error) {
            return $fields;
        }
        $now = current_time('mysql', true);
        $scope = (string) ($input['scope'] ?? 'site');
        $id = $this->repository->insert(self::TABLE, [
            'user_id' => $scope === 'user' ? $userId : null,
            'scope' => $scope === 'user' ? 'user' : 'site',
            'memory_key' => $fields['memory_key'],
            'memory_value' => $fields['memory_value'],
            'confidence' => $fields['confidence'] ?? 1.0,
            'expires_at' => $fields['expires_at'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->present($this->find($id, $userId) ?? [], $this->embed($id));
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>|WP_Error
     */
    public function update(int $id, int $userId, array $input): array|WP_Error
    {
        if ($this->find($id, $userId) === null) {
            return $this->notFound();
        }
        $fields = $this->validate($input, false);
        if ($fields instanceof WP_Error) {
            return $fields;
        }
        $this->repository->update(self::TABLE, $id, $fields + ['updated_at' => current_time('mysql', true)]);
        $embedded = $this->embed($id);
        return $this->present($this->find($id, $userId) ?? [], $embedded);
    }

    public function delete(int $id, int $userId): bool|WP_Error
    {
        if ($this->find($id, $userId) === null) {
            return $this->notFound();
        }
        $this->embeddings->delete(self::SOURCE, (string) $id);
        return $this->repository->delete(self::TABLE, $id);
    }

    /**
     * Memories worth adding to a prompt for this query, best first. With more memories than $limit, the most
     * relevant ones (by embedding similarity when available) are chosen; otherwise all of them are returned.
     *
     * @return list<array<string,mixed>>
     */
    public function recall(int $userId, string $query, int $limit = 8): array
    {
        $rows = $this->activeRows($userId);
        if (count($rows) <= $limit) {
            // Everything fits in the prompt, so skip relevance filtering (and the embedding call): general
            // instructions such as "reply in a friendly tone" apply to every question.
            return MemoryRanker::rank($rows, null, [], $limit);
        }
        $vectors = $this->embeddings->vectors(self::SOURCE, array_map(static fn (array $row): string => (string) $row['id'], $rows));
        $queryVector = null;
        if ($vectors !== [] && trim($query) !== '') {
            try {
                $queryVector = $this->ai->embed($query, [], 'memory_recall')->vector;
            } catch (Throwable) {
                $queryVector = null; // embeddings unavailable: fall back to confidence/recency
            }
        }
        return MemoryRanker::rank($rows, $queryVector, $vectors, $limit);
    }

    /** Embed every active memory that has no vector yet (e.g. after adding an embeddings-capable provider key). */
    public function reindex(int $userId): array
    {
        $rows = $this->activeRows($userId);
        $existing = $this->embeddings->vectors(self::SOURCE, array_map(static fn (array $row): string => (string) $row['id'], $rows));
        $embedded = 0;
        $failed = 0;
        $error = '';
        foreach ($rows as $row) {
            if (isset($existing[(string) $row['id']])) {
                continue;
            }
            try {
                $this->embedOrThrow((int) $row['id'], $row);
                $embedded++;
            } catch (Throwable $e) {
                $failed++;
                $error = $error !== '' ? $error : $e->getMessage();
            }
        }
        return ['embedded' => $embedded, 'failed' => $failed, 'error' => $error];
    }

    /** @return list<array<string,mixed>> */
    private function activeRows(int $userId): array
    {
        return $this->repository->select(
            'SELECT * FROM {prefix}' . self::TABLE . ' WHERE (user_id IS NULL OR user_id = %d) AND (expires_at IS NULL OR expires_at > %s) ORDER BY updated_at DESC, id DESC LIMIT 500',
            [$userId, current_time('mysql', true)]
        );
    }

    /** @return array<string,mixed>|null */
    private function find(int $id, int $userId): ?array
    {
        $rows = $this->repository->select('SELECT * FROM {prefix}' . self::TABLE . ' WHERE id = %d AND (user_id IS NULL OR user_id = %d) LIMIT 1', [$id, $userId]);
        return $rows[0] ?? null;
    }

    private function embed(int $id): bool
    {
        $rows = $this->repository->select('SELECT * FROM {prefix}' . self::TABLE . ' WHERE id = %d LIMIT 1', [$id]);
        if ($rows === []) {
            return false;
        }
        try {
            $this->embedOrThrow($id, $rows[0]);
            return true;
        } catch (Throwable) {
            return false; // saving the memory must not fail because embeddings are unavailable
        }
    }

    /** @param array<string,mixed> $row @throws \RuntimeException */
    private function embedOrThrow(int $id, array $row): void
    {
        $text = $row['memory_key'] . ': ' . $row['memory_value'];
        $response = $this->ai->embed($text, [], 'memory_embedding');
        $provider = $this->ai->embeddingProviderId() ?? '';
        $this->embeddings->save(self::SOURCE, (string) $id, $text, $response->vector, $provider, $response->model);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>|WP_Error only the fields present in $input (all required ones when $creating)
     */
    private function validate(array $input, bool $creating): array|WP_Error
    {
        $fields = [];
        if ($creating || array_key_exists('key', $input)) {
            $key = trim(sanitize_text_field((string) ($input['key'] ?? '')));
            if ($key === '' || mb_strlen($key) > 191) {
                return new WP_Error('aicore_invalid_memory', 'A memory needs a short key (up to 191 characters).', ['status' => 400]);
            }
            $fields['memory_key'] = $key;
        }
        if ($creating || array_key_exists('value', $input)) {
            $value = trim(sanitize_textarea_field((string) ($input['value'] ?? '')));
            if ($value === '' || mb_strlen($value) > self::MAX_VALUE) {
                return new WP_Error('aicore_invalid_memory', 'A memory needs a value (up to ' . self::MAX_VALUE . ' characters).', ['status' => 400]);
            }
            $fields['memory_value'] = $value;
        }
        if (array_key_exists('confidence', $input)) {
            $fields['confidence'] = max(0.0, min(1.0, (float) $input['confidence']));
        }
        if (array_key_exists('expires_at', $input)) {
            $expires = trim((string) $input['expires_at']);
            $time = $expires === '' ? false : strtotime($expires . ' UTC');
            $fields['expires_at'] = $time === false ? null : gmdate('Y-m-d H:i:s', $time);
        }
        return $fields;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function present(array $row, bool $embedded): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'scope' => (string) ($row['scope'] ?? 'site'),
            'key' => (string) ($row['memory_key'] ?? ''),
            'value' => (string) ($row['memory_value'] ?? ''),
            'confidence' => (float) ($row['confidence'] ?? 1),
            'expires_at' => $row['expires_at'] ?? null,
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'embedded' => $embedded,
        ];
    }

    private function notFound(): WP_Error
    {
        return new WP_Error('aicore_memory_not_found', 'Memory not found.', ['status' => 404]);
    }
}
