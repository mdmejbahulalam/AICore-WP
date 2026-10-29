<?php

declare(strict_types=1);

namespace AICore\WP\AI;

/** Static description of the supported AI providers (labels, suggested models, key requirement). */
final class ProviderCatalog
{
    /** @return array<string,array{label:string,default_model:string,needs_key:bool}> */
    public static function all(): array
    {
        return [
            'openai' => ['label' => 'OpenAI', 'default_model' => 'gpt-4o-mini', 'needs_key' => true],
            'claude' => ['label' => 'Anthropic Claude', 'default_model' => 'claude-sonnet-5-5', 'needs_key' => true],
            'gemini' => ['label' => 'Google Gemini', 'default_model' => 'gemini-2.5-flash', 'needs_key' => true],
            'deepseek' => ['label' => 'DeepSeek', 'default_model' => 'deepseek-chat', 'needs_key' => true],
            'openrouter' => ['label' => 'OpenRouter', 'default_model' => 'openai/gpt-4o-mini', 'needs_key' => true],
            'groq' => ['label' => 'Groq', 'default_model' => 'llama-3.3-70b-versatile', 'needs_key' => true],
            'ollama' => ['label' => 'Ollama (local)', 'default_model' => 'llama3.2', 'needs_key' => false],
        ];
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $id): bool
    {
        return isset(self::all()[$id]);
    }

    public static function defaultModel(string $id): string
    {
        return self::all()[$id]['default_model'] ?? 'default';
    }
}
