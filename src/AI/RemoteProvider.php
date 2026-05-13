<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use RuntimeException;

final class RemoteProvider implements ProviderInterface
{
    public function __construct(private readonly string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $settings = (array) get_option('aicore_wp_provider_settings', []);
        $apiKey = (string) ($settings[$this->id]['api_key'] ?? '');
        if ($apiKey === '') {
            $last = end($messages);
            return new AIResponse('Provider not configured. Preview response for: ' . sanitize_text_field((string) ($last['content'] ?? '')), 0, 0, ['mode' => 'preview']);
        }
        $endpoint = $this->endpoint($settings[$this->id]['base_url'] ?? null);
        $body = [
            'model' => $options['model'] ?? $settings[$this->id]['model'] ?? 'default',
            'messages' => $messages,
            'stream' => false,
            'temperature' => $options['temperature'] ?? 0.4,
        ];
        $response = wp_remote_post($endpoint, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'],
            'body' => wp_json_encode($body),
        ]);
        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }
        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        $content = (string) ($json['choices'][0]['message']['content'] ?? $json['content'][0]['text'] ?? '');
        return new AIResponse($content, (int) ($json['usage']['prompt_tokens'] ?? 0), (int) ($json['usage']['completion_tokens'] ?? 0), ['raw' => $json]);
    }

    public function embed(string $input): array
    {
        return array_fill(0, 384, min(1, strlen($input) / 1000));
    }

    private function endpoint(?string $custom): string
    {
        if (is_string($custom) && $custom !== '') {
            return esc_url_raw($custom);
        }
        return match ($this->id) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'ollama' => 'http://127.0.0.1:11434/v1/chat/completions',
            default => 'https://api.openai.com/v1/chat/completions',
        };
    }
}
