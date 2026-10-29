<?php

declare(strict_types=1);

namespace AICore\WP\Chat;

/** Assembles the message list sent to the model: system prompt + recalled memories + as much recent history as fits. */
final class PromptBuilder
{
    public const SYSTEM = 'You are AICore WP, an AI assistant built into a WordPress site. Be accurate and concise. Ask for confirmation before suggesting destructive changes, and prefer drafts and previews.';
    public const MAX_MEMORY_CHARS = 500;

    /**
     * @param list<array{key:string,value:string}> $memories
     * @param list<array{role:string,content:string}> $history oldest first, ending with the new user message
     * @param int $maxChars budget for the history (roughly 4 characters per token); the newest message is always kept
     * @return list<array{role:string,content:string}>
     */
    public static function build(array $memories, array $history, int $maxChars = 24000): array
    {
        $system = self::SYSTEM;
        if ($memories !== []) {
            $lines = array_map(
                static fn (array $memory): string => '- ' . $memory['key'] . ': ' . self::truncate($memory['value'], self::MAX_MEMORY_CHARS),
                $memories
            );
            $system .= "\n\nFacts to remember about this site and its user (use them when relevant, do not mention them unprompted):\n" . implode("\n", $lines);
        }
        return [['role' => 'system', 'content' => $system], ...self::trim($history, $maxChars)];
    }

    /**
     * Keep the newest messages that fit the budget. The result never starts with an assistant message,
     * because some providers (Anthropic) require the first turn to come from the user.
     *
     * @param list<array{role:string,content:string}> $history
     * @return list<array{role:string,content:string}>
     */
    public static function trim(array $history, int $maxChars): array
    {
        $kept = [];
        $used = 0;
        foreach (array_reverse($history) as $message) {
            $length = mb_strlen($message['content']);
            if ($kept !== [] && $used + $length > $maxChars) {
                break;
            }
            $kept[] = $message;
            $used += $length;
        }
        $kept = array_reverse($kept);
        while (count($kept) > 1 && $kept[0]['role'] !== 'user') {
            array_shift($kept);
        }
        return $kept;
    }

    private static function truncate(string $text, int $max): string
    {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }
}
