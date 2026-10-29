<?php

declare(strict_types=1);

use AICore\WP\Chat\PromptBuilder;
use AICore\WP\Memory\MemoryRanker;
use PHPUnit\Framework\TestCase;

final class ChatMemoryTest extends TestCase
{
    public function testPromptWithoutMemoriesHasOnlyTheBaseSystemPrompt(): void
    {
        $messages = PromptBuilder::build([], [['role' => 'user', 'content' => 'hi']]);

        self::assertSame('system', $messages[0]['role']);
        self::assertSame(PromptBuilder::SYSTEM, $messages[0]['content']);
        self::assertSame([['role' => 'user', 'content' => 'hi']], array_slice($messages, 1));
    }

    public function testMemoriesAreAddedToTheSystemPromptAndLongOnesTruncated(): void
    {
        $messages = PromptBuilder::build(
            [['key' => 'Brand voice', 'value' => 'Friendly and short'], ['key' => 'Long', 'value' => str_repeat('x', 900)]],
            [['role' => 'user', 'content' => 'hi']]
        );

        $system = $messages[0]['content'];
        self::assertStringContainsString('- Brand voice: Friendly and short', $system);
        self::assertStringContainsString('…', $system);
        self::assertLessThan(PromptBuilder::MAX_MEMORY_CHARS + 600, mb_strlen($system));
    }

    public function testHistoryKeepsTheNewestMessagesThatFitTheBudget(): void
    {
        $history = [
            ['role' => 'user', 'content' => str_repeat('a', 50)],
            ['role' => 'assistant', 'content' => str_repeat('b', 50)],
            ['role' => 'user', 'content' => str_repeat('c', 50)],
            ['role' => 'assistant', 'content' => str_repeat('d', 50)],
            ['role' => 'user', 'content' => 'latest'],
        ];

        $kept = PromptBuilder::trim($history, 120);

        self::assertSame('latest', end($kept)['content']);
        self::assertSame('user', $kept[0]['role']);
        self::assertLessThan(count($history), count($kept));
        self::assertSame([str_repeat('c', 50), str_repeat('d', 50), 'latest'], array_column($kept, 'content'));
    }

    public function testNewestMessageIsAlwaysKeptEvenWhenOverBudget(): void
    {
        $kept = PromptBuilder::trim([['role' => 'user', 'content' => str_repeat('z', 5000)]], 100);

        self::assertCount(1, $kept);
    }

    public function testHistoryNeverStartsWithAnAssistantMessage(): void
    {
        $kept = PromptBuilder::trim([
            ['role' => 'assistant', 'content' => 'old reply'],
            ['role' => 'user', 'content' => 'q'],
        ], 1000);

        self::assertSame('user', $kept[0]['role']);
    }

    /** @return list<array<string,mixed>> */
    private function memories(): array
    {
        return [
            ['id' => 1, 'confidence' => 1.0, 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 2, 'confidence' => 0.5, 'updated_at' => '2026-03-01 00:00:00'],
            ['id' => 3, 'confidence' => 1.0, 'updated_at' => '2026-02-01 00:00:00'],
            ['id' => 4, 'confidence' => 0.9, 'updated_at' => '2026-04-01 00:00:00'],
        ];
    }

    public function testWithoutAQueryVectorMemoriesAreOrderedByConfidenceThenRecency(): void
    {
        $ranked = MemoryRanker::rank($this->memories(), null, [], 3);

        self::assertSame([3, 1, 4], array_column($ranked, 'id'));
    }

    public function testSimilarMemoriesComeFirstIrrelevantOnesAreDroppedAndUnembeddedFollow(): void
    {
        $vectors = [
            '1' => [1.0, 0.0],   // identical to the query
            '2' => [0.0, 1.0],   // orthogonal: dropped
            '3' => [1.0, 1.0],   // ~0.71
        ];

        $ranked = MemoryRanker::rank($this->memories(), [1.0, 0.0], $vectors, 10);

        self::assertSame([1, 3, 4], array_column($ranked, 'id')); // 4 has no vector so it follows the scored ones
    }

    public function testLimitIsApplied(): void
    {
        self::assertCount(2, MemoryRanker::rank($this->memories(), null, [], 2));
    }
}
