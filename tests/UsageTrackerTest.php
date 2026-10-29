<?php

declare(strict_types=1);

use AICore\WP\AI\UsageTracker;
use AICore\WP\Database\Repository;
use PHPUnit\Framework\TestCase;

final class UsageTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        WpStub::reset();
    }

    private function tracker(): UsageTracker
    {
        // estimateCost() does not touch the database, so the repository is never used.
        return new UsageTracker((new ReflectionClass(Repository::class))->newInstanceWithoutConstructor());
    }

    public function testUnknownModelsCostNothing(): void
    {
        self::assertSame(0.0, $this->tracker()->estimateCost('openai', 'mystery', 1000, 1000));
    }

    public function testCostUsesPerMillionTokenPrices(): void
    {
        WpStub::$options[UsageTracker::PRICES_OPTION] = ['openai:gpt-x' => ['input' => 2.0, 'output' => 8.0]];

        // 500k in * $2/M + 250k out * $8/M = 1 + 2
        self::assertSame(3.0, $this->tracker()->estimateCost('openai', 'gpt-x', 500_000, 250_000));
    }
}
