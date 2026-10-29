<?php

declare(strict_types=1);

namespace AICore\WP\AI;

interface ProviderInterface
{
    public function id(): string;

    /** @param list<array{role:string,content:string}> $messages @param array<string,mixed> $options */
    public function chat(array $messages, array $options = []): AIResponse;

    /**
     * Same as chat() but delivers text incrementally to $onDelta while the reply is generated.
     *
     * @param list<array{role:string,content:string}> $messages
     * @param array<string,mixed> $options
     * @param callable(string):void $onDelta
     */
    public function stream(array $messages, array $options, callable $onDelta): AIResponse;

    /** @param array<string,mixed> $options @throws \RuntimeException when unsupported or the request fails */
    public function embed(string $input, array $options = []): EmbeddingResponse;

    public function supportsEmbeddings(): bool;
}
