<?php

declare(strict_types=1);

namespace AICore\WP\Security;

use AICore\WP\Database\Repository;

final class AuditLogger
{
    public function __construct(private readonly Repository $repository)
    {
    }

    /** @param array<string,mixed> $context */
    public function log(string $event, array $context = []): void
    {
        $this->repository->event('audit.' . sanitize_key($event), [
            'actor_id' => get_current_user_id() ?: null,
            'properties' => $context,
        ]);
    }
}
