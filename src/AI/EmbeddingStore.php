<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use AICore\WP\Database\Repository;

/**
 * Persists embedding vectors in aicore_ai_embeddings (the vector itself lives in the metadata JSON).
 * One row per (source_type, source_id); saving again replaces the previous vector.
 */
final class EmbeddingStore
{
    private const TABLE = 'aicore_ai_embeddings';

    public function __construct(private readonly Repository $repository)
    {
    }

    /** @param list<float> $vector */
    public function save(string $sourceType, string $sourceId, string $text, array $vector, string $provider, string $model): void
    {
        $this->delete($sourceType, $sourceId);
        $this->repository->insert(self::TABLE, [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'vector_id' => $sourceType . ':' . $sourceId,
            'provider' => $provider,
            'dimensions' => count($vector),
            'chunk_hash' => hash('sha256', implode('|', [$sourceType, $sourceId, $provider, $model])),
            'content' => $text,
            'metadata' => wp_json_encode(['model' => $model, 'vector' => $vector]),
            'created_at' => current_time('mysql', true),
        ]);
    }

    public function delete(string $sourceType, string $sourceId): void
    {
        $this->repository->deleteBy(self::TABLE, ['source_type' => $sourceType, 'source_id' => $sourceId]);
    }

    /**
     * @param list<string> $sourceIds
     * @return array<string,list<float>> source_id => vector
     */
    public function vectors(string $sourceType, array $sourceIds): array
    {
        if ($sourceIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($sourceIds), '%s'));
        $rows = $this->repository->select(
            "SELECT source_id, metadata FROM {prefix}" . self::TABLE . " WHERE source_type = %s AND source_id IN ($placeholders)",
            [$sourceType, ...$sourceIds]
        );
        $vectors = [];
        foreach ($rows as $row) {
            $meta = json_decode((string) $row['metadata'], true);
            if (is_array($meta) && is_array($meta['vector'] ?? null)) {
                $vectors[(string) $row['source_id']] = array_map('floatval', $meta['vector']);
            }
        }
        return $vectors;
    }
}
