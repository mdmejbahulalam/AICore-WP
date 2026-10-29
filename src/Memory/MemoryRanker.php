<?php

declare(strict_types=1);

namespace AICore\WP\Memory;

use AICore\WP\AI\Vector;

/** Chooses which memories to put in a prompt. Pure logic so it can be tested without WordPress. */
final class MemoryRanker
{
    /** Embedded memories less similar to the query than this are left out. */
    public const MIN_SIMILARITY = 0.15;

    /**
     * With a query vector, memories that have a vector are ranked by cosine similarity (below MIN_SIMILARITY they are
     * dropped), followed by memories without a vector. Without one, memories are ordered by confidence then recency.
     *
     * @param list<array<string,mixed>> $memories rows with id, confidence and updated_at
     * @param list<float>|null $queryVector
     * @param array<string,list<float>> $vectors memory id => vector
     * @return list<array<string,mixed>>
     */
    public static function rank(array $memories, ?array $queryVector, array $vectors, int $limit): array
    {
        $byRecency = static fn (array $a, array $b): int => [(float) $b['confidence'], (string) $b['updated_at']] <=> [(float) $a['confidence'], (string) $a['updated_at']];

        if ($queryVector === null) {
            usort($memories, $byRecency);
            return array_slice($memories, 0, $limit);
        }

        $scored = [];
        $unembedded = [];
        foreach ($memories as $memory) {
            $vector = $vectors[(string) $memory['id']] ?? null;
            if ($vector === null) {
                $unembedded[] = $memory;
                continue;
            }
            $similarity = Vector::cosine($queryVector, $vector);
            if ($similarity >= self::MIN_SIMILARITY) {
                $scored[] = ['memory' => $memory, 'score' => $similarity];
            }
        }
        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        usort($unembedded, $byRecency);

        return array_slice([...array_column($scored, 'memory'), ...$unembedded], 0, $limit);
    }
}
