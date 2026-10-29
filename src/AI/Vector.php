<?php

declare(strict_types=1);

namespace AICore\WP\AI;

final class Vector
{
    /**
     * Cosine similarity in [-1, 1]. Returns 0.0 for vectors of different length or zero magnitude.
     *
     * @param list<float|int> $a
     * @param list<float|int> $b
     */
    public static function cosine(array $a, array $b): float
    {
        $count = count($a);
        if ($count === 0 || $count !== count($b)) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        return $normA > 0.0 && $normB > 0.0 ? $dot / (sqrt($normA) * sqrt($normB)) : 0.0;
    }
}
