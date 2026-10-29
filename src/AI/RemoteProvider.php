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
        if ($this->needsKey($config)) {
            return $this->previewResponse($messages);
        }

        $request = $this->buildRequest($config, $messages, $options, false);
        $response = wp_remote_post($request['endpoint'], ['timeout' => 60, 'headers' => $request['headers'], 'body' => wp_json_encode($request['body'])]);
        if (is_wp_error($response)) {
            throw new RuntimeException(sprintf('%s request failed: %s', $this->id, $response->get_error_message()));
        }
        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        $json = is_array($json) ? $json : [];
        $this->assertSuccess((int) wp_remote_retrieve_response_code($response), $json);

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

        return new AIResponse($content, $input, $output, ['model' => (string) ($json['model'] ?? $request['model']), 'provider' => $this->id, 'raw' => $json]);
    }

    public function stream(array $messages, array $options, callable $onDelta): AIResponse
    {
        $config = $this->settings->config($this->id);
        if ($this->needsKey($config)) {
            $preview = $this->previewResponse($messages);
            $onDelta($preview->content);
            return $preview;
        }

        $request = $this->buildRequest($config, $messages, $options, true);
        $decoder = new StreamDecoder($this->id === 'claude' ? 'anthropic' : 'openai', $onDelta);
        $errorBody = '';

        // WordPress buffers whole responses, so hook into its cURL handle to receive the body as it arrives.
        $hook = static function ($handle) use ($decoder, &$errorBody): void {
            curl_setopt($handle, CURLOPT_WRITEFUNCTION, static function ($curl, string $data) use ($decoder, &$errorBody): int {
                if ((int) curl_getinfo($curl, CURLINFO_HTTP_CODE) >= 400) {
                    $errorBody .= $data; // a JSON error body, not an event stream
                } else {
                    $decoder->feed($data);
                }
                return strlen($data);
            });
        };
        add_action('http_api_curl', $hook, 10, 1);
        try {
            $response = wp_remote_post($request['endpoint'], ['timeout' => 120, 'headers' => $request['headers'], 'body' => wp_json_encode($request['body'])]);
        } finally {
            remove_action('http_api_curl', $hook, 10);
        }
        if (is_wp_error($response)) {
            throw new RuntimeException(sprintf('%s request failed: %s', $this->id, $response->get_error_message()));
        }
        $json = json_decode($errorBody, true);
        $this->assertSuccess((int) wp_remote_retrieve_response_code($response), is_array($json) ? $json : []);
        $decoder->finish();

        $content = $decoder->content();
        $estimated = $decoder->inputTokens() === 0 && $decoder->outputTokens() === 0;
        $input = $estimated ? $this->estimateTokens(implode("\n", array_column($messages, 'content'))) : $decoder->inputTokens();
        $output = $estimated ? $this->estimateTokens($content) : $decoder->outputTokens();

        return new AIResponse($content, $input, $output, [
            'model' => $decoder->model() !== '' ? $decoder->model() : $request['model'],
            'provider' => $this->id,
            'usage_estimated' => $estimated,
        ]);
    }

    public function supportsEmbeddings(): bool
    {
        return ProviderCatalog::embeddingModel($this->id) !== '';
    }

    public function embed(string $input, array $options = []): EmbeddingResponse
    {
        $config = $this->settings->config($this->id);
        $model = (string) ($options['model'] ?? ($config['embedding_model'] !== '' ? $config['embedding_model'] : ProviderCatalog::embeddingModel($this->id)));
        if ($model === '') {
            throw new RuntimeException(sprintf('%s does not support embeddings.', $this->id));
        }
        if ($this->needsKey($config)) {
            throw new RuntimeException(sprintf('%s is not configured: no API key saved.', $this->id));
        }

        $chatUrl = $this->endpoint($config['base_url']);
        if (! str_contains($chatUrl, '/chat/completions')) {
            throw new RuntimeException('The custom endpoint must end with /chat/completions so the embeddings URL can be derived.');
        }
        $headers = ['Content-Type' => 'application/json'];
        if ($config['api_key'] !== '') {
            $headers['Authorization'] = 'Bearer ' . $config['api_key'];
        }
        $response = wp_remote_post(str_replace('/chat/completions', '/embeddings', $chatUrl), [
            'timeout' => 60,
            'headers' => $headers,
            'body' => wp_json_encode(['model' => $model, 'input' => $input]),
        ]);
        if (is_wp_error($response)) {
            throw new RuntimeException(sprintf('%s request failed: %s', $this->id, $response->get_error_message()));
        }
        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        $json = is_array($json) ? $json : [];
        $this->assertSuccess((int) wp_remote_retrieve_response_code($response), $json);

        $vector = $json['data'][0]['embedding'] ?? null;
        if (! is_array($vector) || $vector === []) {
            throw new RuntimeException(sprintf('%s returned no embedding.', $this->id));
        }
        return new EmbeddingResponse(array_map('floatval', $vector), (int) ($json['usage']['prompt_tokens'] ?? $json['usage']['total_tokens'] ?? 0), (string) ($json['model'] ?? $model));
    }

    /** @param array{api_key:string,model:string,max_tokens:int,base_url:string,embedding_model:string} $config */
    private function needsKey(array $config): bool
    {
        return $config['api_key'] === '' && (ProviderCatalog::all()[$this->id]['needs_key'] ?? true);
    }

    /** @param list<array{role:string,content:string}> $messages */
    private function previewResponse(array $messages): AIResponse
    {
        $last = end($messages);
        return new AIResponse('Provider not configured. Preview response for: ' . sanitize_text_field((string) ($last['content'] ?? '')), 0, 0, ['mode' => 'preview', 'model' => 'preview', 'provider' => $this->id]);
    }

    /**
     * @param array{api_key:string,model:string,max_tokens:int,base_url:string,embedding_model:string} $config
     * @param list<array{role:string,content:string}> $messages
     * @param array<string,mixed> $options
     * @return array{endpoint:string,headers:array<string,string>,body:array<string,mixed>,model:string}
     */
    private function buildRequest(array $config, array $messages, array $options, bool $stream): array
    {
        $model = (string) ($options['model'] ?? ($config['model'] !== '' ? $config['model'] : ProviderCatalog::defaultModel($this->id)));
        $maxTokens = (int) ($options['max_tokens'] ?? ($config['max_tokens'] > 0 ? $config['max_tokens'] : 1024));
        $temperature = $options['temperature'] ?? 0.4;
        $endpoint = $this->endpoint($config['base_url']);

        if ($this->id === 'claude') {
            [$headers, $body] = $this->anthropicRequest($config['api_key'], $model, $messages, $maxTokens, $temperature);
            if ($stream) {
                $body['stream'] = true;
            }
        } else {
            $headers = ['Content-Type' => 'application/json'];
            if ($config['api_key'] !== '') {
                $headers['Authorization'] = 'Bearer ' . $config['api_key'];
            }
            $body = ['model' => $model, 'messages' => $messages, 'stream' => $stream, 'temperature' => $temperature, 'max_tokens' => $maxTokens];
            if ($stream) {
                $body['stream_options'] = ['include_usage' => true];
            }
        }
        return ['endpoint' => $endpoint, 'headers' => $headers, 'body' => $body, 'model' => $model];
    }

    /** @param array<string,mixed> $json */
    private function assertSuccess(int $status, array $json): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }
        $detail = $json['error']['message'] ?? $json['error'] ?? $json[0]['error']['message'] ?? 'Unknown error';
        throw new RuntimeException(sprintf('%s API error (%d): %s', $this->id, $status, is_string($detail) ? $detail : wp_json_encode($detail)));
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
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
