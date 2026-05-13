<?php

declare(strict_types=1);

namespace AICore\WP\AI;

interface ProviderInterface
{
    public function id(): string;
    /** @param list<array{role:string,content:string}> $messages @param array<string,mixed> $options */
    public function chat(array $messages, array $options = []): AIResponse;
    /** @return list<float> */
    public function embed(string $input): array;
}
