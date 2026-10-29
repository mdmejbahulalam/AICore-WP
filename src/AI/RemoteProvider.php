<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use RuntimeException;

final class RemoteProvider implements ProviderInterface
{
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct(private readonly string $id, private readonly ProviderSettings $settings)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $config = $this->settings->config($this->id);
        $apiKey = $config['api_key'];
        if ($apiKey === '' && (ProviderCatalog::all()[$this->id]['needs_key'] ?? true)) {
            $last = end($messages);
            return new AIResponse('Provider not configured. Preview response for: ' . sanitize_text_field((string) ($last['content'] ?? '')), 0, 0, ['mode' => 'preview', 'model' => 'preview', 'provider' => $this->id]);
        }

        $model = (string) ($options['model'] ?? ($config['model'] !== '' ? $config['model'] : ProviderCatalog::defaultModel($this->id)));
        $maxTokens = (int) ($options['max_tokens'] ?? ($config['max_tokens'] > 0 ? $config['max_tokens'] : 1024));
        $temperature = $options['temperature'] ?? 0.4;
        $endpoint = $this->endpoint($config['base_url']);

        if ($this->id === 'claude') {
            [$headers, $body] = $this->anthropicRequest($apiKey, $model, $messages, $maxTokens, $temperature);
        } else {
            $headers = ['Content-Type' => 'application/json'];
            if ($apiKey !== '') {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }
            $body = ['model' => $model, 'messages' => $messages, 'stream' => false, 'temperature' => $temperature, 'max_tokens' => $maxTokens];
        }

        $response = wp_remote_post($endpoint, ['timeout' => 60, 'headers' => $headers, 'body' => wp_json_encode($body)]);
        if (is_wp_error($response)) {
            throw new RuntimeException(sprintf('%s request failed: %s', $this->id, $response->get_error_message()));
        }
        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        $json = is_array($json) ? $json : [];
        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            $detail = $json['error']['message'] ?? $json['error'] ?? $json[0]['error']['message'] ?? 'Unknown error';
            throw new RuntimeException(sprintf('%s API error (%d): %s', $this->id, $status, is_string($detail) ? $detail : wp_json_encode($detail)));
        }

        if ($this->id === 'claude') {
            $content = '';
            foreach ((array) ($json['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $content .= (string) ($block['text'] ?? '');
                }
            }
            $input = (int) ($json['usage']['input_tokens'] ?? 0);
            $output = (int) ($json['usage']['output_tokens'] ?? 0);
        } else {
            $content = (string) ($json['choices'][0]['message']['content'] ?? '');
            $input = (int) ($json['usage']['prompt_tokens'] ?? 0);
            $output = (int) ($json['usage']['completion_tokens'] ?? 0);
        }

        return new AIResponse($content, $input, $output, ['model' => (string) ($json['model'] ?? $model), 'provider' => $this->id, 'raw' => $json]);
    }

    public function embed(string $input): array
    {
        return array_fill(0, 384, min(1, strlen($input) / 1000));
    }

    /**
     * Anthropic Messages API: system prompt is a top-level field and max_tokens is required.
     *
     * @param list<array{role:string,content:string}> $messages
     * @return array{0:array<string,string>,1:array<string,mixed>}
     */
    private function anthropicRequest(string $apiKey, string $model, array $messages, int $maxTokens, mixed $temperature): array
    {
        $system = [];
        $turns = [];
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $system[] = (string) $message['content'];
                continue;
            }
            $turns[] = ['role' => $message['role'], 'content' => $message['content']];
        }
        $body = ['model' => $model, 'max_tokens' => $maxTokens, 'temperature' => $temperature, 'messages' => $turns];
        if ($system !== []) {
            $body['system'] = implode("\n\n", $system);
        }
        $headers = ['x-api-key' => $apiKey, 'anthropic-version' => self::ANTHROPIC_VERSION, 'Content-Type' => 'application/json'];
        return [$headers, $body];
    }

    private function endpoint(string $custom): string
    {
        if ($custom !== '') {
            return esc_url_raw($custom);
        }
        return match ($this->id) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'claude' => 'https://api.anthropic.com/v1/messages',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'ollama' => 'http://127.0.0.1:11434/v1/chat/completions',
            default => throw new RuntimeException("No endpoint configured for provider {$this->id}."),
        };
    }
}
