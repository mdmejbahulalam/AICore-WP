<?php

declare(strict_types=1);

namespace AICore\WP\AI;

final readonly class AIResponse
{
    /** @param array<string,mixed> $metadata */
    public function __construct(public string $content, public int $inputTokens = 0, public int $outputTokens = 0, public array $metadata = [])
    {
    }
}
