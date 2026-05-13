<?php

declare(strict_types=1);

namespace AICore\WP\Security;

use WP_Error;

final class RateLimiter
{
    /** @return true|WP_Error */
    public function hit(string $bucket, int $limit, int $windowSeconds)
    {
        $key = 'aicore_rate_' . md5($bucket . '|' . (string) get_current_user_id());
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            return new WP_Error('aicore_rate_limited', __('AI rate limit exceeded. Please try again later.', 'aicore-wp'), ['status' => 429]);
        }
        set_transient($key, $count + 1, $windowSeconds);
        return true;
    }
}
