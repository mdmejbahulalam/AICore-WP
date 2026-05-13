<?php

declare(strict_types=1);

namespace AICore\WP\Security;

use WP_Error;

final class PermissionGuard
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /** @return true|WP_Error */
    public function requireCapability(string $capability)
    {
        if (! is_user_logged_in() || ! current_user_can($capability)) {
            $this->auditLogger->log('permission_denied', ['capability' => $capability]);
            return new WP_Error('aicore_forbidden', __('You are not allowed to perform this AI operation.', 'aicore-wp'), ['status' => 403]);
        }
        return true;
    }

    /** @param list<string> $allowedScopes @return true|WP_Error */
    public function requireScope(array $allowedScopes, string $scope)
    {
        if (! in_array($scope, $allowedScopes, true)) {
            return new WP_Error('aicore_scope_denied', __('The API key does not include the required scope.', 'aicore-wp'), ['status' => 403]);
        }
        return true;
    }
}
