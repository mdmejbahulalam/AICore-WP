<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use AICore\WP\Database\Repository;

/** Records every billable AI call in aicore_usage_logs and summarises usage. */
final class UsageTracker
{
    public const PRICES_OPTION = 'aicore_wp_model_prices';

    public function __construct(private readonly Repository $repository)
    {
    }

    public function record(string $provider, string $model, string $operation, int $inputTokens, int $outputTokens): void
    {
        $this->repository->insert('aicore_usage_logs', [
            'user_id' => get_current_user_id() ?: null,
            'provider' => $provider,
            'model' => $model,
            'operation' => $operation,
            'input_tokens' => max(0, $inputTokens),
            'output_tokens' => max(0, $outputTokens),
            'cost' => $this->estimateCost($provider, $model, $inputTokens, $outputTokens),
            'created_at' => current_time('mysql', true),
        ]);
    }

    /**
     * Estimated cost in USD. Prices are per 1M tokens and come from the `aicore_wp_model_prices` option or the
     * `aicore_wp_model_prices` filter, keyed "provider:model" => ['input' => x, 'output' => y]. Unknown models cost 0.
     */
    public function estimateCost(string $provider, string $model, int $inputTokens, int $outputTokens): float
    {
        $prices = (array) apply_filters('aicore_wp_model_prices', (array) get_option(self::PRICES_OPTION, []));
        $price = (array) ($prices[$provider . ':' . $model] ?? []);
        return round(((float) ($price['input'] ?? 0) * $inputTokens + (float) ($price['output'] ?? 0) * $outputTokens) / 1_000_000, 6);
    }

    /** @return array{days:int,totals:array<string,int|float>,by_model:list<array<string,mixed>>} */
    public function summary(int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $since = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
        $rows = $this->repository->select(
            'SELECT provider, model, COUNT(*) AS calls, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(cost) AS cost FROM {prefix}aicore_usage_logs WHERE created_at >= %s GROUP BY provider, model ORDER BY calls DESC LIMIT 100',
            [$since]
        );
        $byModel = [];
        $totals = ['calls' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0];
        foreach ($rows as $row) {
            $entry = [
                'provider' => (string) $row['provider'],
                'model' => (string) $row['model'],
                'calls' => (int) $row['calls'],
                'input_tokens' => (int) $row['input_tokens'],
                'output_tokens' => (int) $row['output_tokens'],
                'cost' => round((float) $row['cost'], 6),
            ];
            $byModel[] = $entry;
            $totals['calls'] += $entry['calls'];
            $totals['input_tokens'] += $entry['input_tokens'];
            $totals['output_tokens'] += $entry['output_tokens'];
            $totals['cost'] += $entry['cost'];
        }
        $totals['cost'] = round($totals['cost'], 6);
        return ['days' => $days, 'totals' => $totals, 'by_model' => $byModel];
    }
}
