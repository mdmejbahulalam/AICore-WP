<?php

declare(strict_types=1);

namespace AICore\WP\AI;

final readonly class EmbeddingResponse
{
    /** @param list<float> $vector */
    public function __construct(public array $vector, public int $tokens = 0, public string $model = '')
    {
    }
}
