<?php

declare(strict_types=1);

use AICore\WP\AI\StreamDecoder;
use AICore\WP\AI\Vector;
use PHPUnit\Framework\TestCase;

final class StreamDecoderTest extends TestCase
{
    /** @return array{StreamDecoder,\ArrayObject<int,string>} */
    private function decoder(string $format): array
    {
        $deltas = new ArrayObject();
        return [new StreamDecoder($format, static function (string $text) use ($deltas): void {
            $deltas[] = $text;
        }), $deltas];
    }

    public function testOpenAiStreamAcrossArbitraryChunkBoundaries(): void
    {
        [$decoder, $deltas] = $this->decoder('openai');
        $stream = ": OPENROUTER PROCESSING\n\n"
            . "data: {\"model\":\"m-1\",\"choices\":[{\"delta\":{\"role\":\"assistant\",\"content\":\"\"}}]}\n\n"
            . "data: {\"choices\":[{\"delta\":{\"content\":\"Hel\"}}]}\n\n"
            . "data: {\"choices\":[{\"delta\":{\"content\":\"lo\"}}]}\n\n"
            . "data: {\"choices\":[],\"usage\":{\"prompt_tokens\":12,\"completion_tokens\":2}}\n\n"
            . "data: [DONE]\n\n";

        foreach (str_split($stream, 7) as $chunk) {
            $decoder->feed($chunk);
        }
        $decoder->finish();

        self::assertSame(['Hel', 'lo'], $deltas->getArrayCopy());
        self::assertSame('Hello', $decoder->content());
        self::assertSame([12, 2, 'm-1'], [$decoder->inputTokens(), $decoder->outputTokens(), $decoder->model()]);
    }

    public function testHandlesCrLfLineEndingsAndUnterminatedFinalEvent(): void
    {
        [$decoder, $deltas] = $this->decoder('openai');
        $decoder->feed("data: {\"choices\":[{\"delta\":{\"content\":\"A\"}}]}\r\n\r\n");
        $decoder->feed('data: {"choices":[{"delta":{"content":"B"}}]}');
        self::assertSame(['A'], $deltas->getArrayCopy());

        $decoder->finish();
        self::assertSame('AB', $decoder->content());
    }

    public function testAnthropicStream(): void
    {
        [$decoder, $deltas] = $this->decoder('anthropic');
        $decoder->feed(
            "event: message_start\ndata: {\"type\":\"message_start\",\"message\":{\"model\":\"claude-x\",\"usage\":{\"input_tokens\":25}}}\n\n"
            . "event: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\"Hi\"}}\n\n"
            . "event: content_block_delta\ndata: {\"type\":\"content_block_delta\",\"delta\":{\"type\":\"text_delta\",\"text\":\" there\"}}\n\n"
            . "event: ping\ndata: {\"type\":\"ping\"}\n\n"
            . "event: message_delta\ndata: {\"type\":\"message_delta\",\"usage\":{\"output_tokens\":7}}\n\n"
        );

        self::assertSame(['Hi', ' there'], $deltas->getArrayCopy());
        self::assertSame([25, 7, 'claude-x'], [$decoder->inputTokens(), $decoder->outputTokens(), $decoder->model()]);
    }

    public function testInStreamErrorsThrow(): void
    {
        [$openai] = $this->decoder('openai');
        try {
            $openai->feed("data: {\"error\":{\"message\":\"rate limited\"}}\n\n");
            self::fail('Expected an exception');
        } catch (RuntimeException $e) {
            self::assertSame('rate limited', $e->getMessage());
        }

        [$anthropic] = $this->decoder('anthropic');
        $this->expectExceptionMessage('Overloaded');
        $anthropic->feed("data: {\"type\":\"error\",\"error\":{\"type\":\"overloaded_error\",\"message\":\"Overloaded\"}}\n\n");
    }

    public function testCosineSimilarity(): void
    {
        self::assertEqualsWithDelta(1.0, Vector::cosine([1, 2, 3], [2, 4, 6]), 1e-9);
        self::assertEqualsWithDelta(0.0, Vector::cosine([1, 0], [0, 1]), 1e-9);
        self::assertEqualsWithDelta(-1.0, Vector::cosine([1, 1], [-1, -1]), 1e-9);
        self::assertSame(0.0, Vector::cosine([1, 2], [1, 2, 3]));
        self::assertSame(0.0, Vector::cosine([0, 0], [1, 1]));
        self::assertSame(0.0, Vector::cosine([], []));
    }
}
