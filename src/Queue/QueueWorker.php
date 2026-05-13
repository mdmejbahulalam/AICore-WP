<?php

declare(strict_types=1);

namespace AICore\WP\Queue;

use AICore\WP\Database\Repository;
use AICore\WP\Security\AuditLogger;

final class QueueWorker
{
    public function __construct(private readonly Repository $repository, private readonly AuditLogger $auditLogger)
    {
    }

    public function run(): void
    {
        $this->auditLogger->log('queue_tick', ['memory' => memory_get_usage(true)]);
    }
}
