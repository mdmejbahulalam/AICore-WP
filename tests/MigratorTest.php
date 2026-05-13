<?php

declare(strict_types=1);

use AICore\WP\Database\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    public function testSchemaContainsRequiredTables(): void
    {
        $schema = (new Migrator())->schema('wp_', 'DEFAULT CHARSET=utf8mb4');
        $joined = implode("\n", $schema);
        self::assertStringContainsString('wp_aicore_ai_conversations', $joined);
        self::assertStringContainsString('wp_aicore_mcp_tools', $joined);
        self::assertStringContainsString('wp_aicore_usage_logs', $joined);
    }
}
