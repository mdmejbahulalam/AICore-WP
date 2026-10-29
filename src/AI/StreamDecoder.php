<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use RuntimeException;

/**
 * Incrementally decodes a Server-Sent Events stream from an AI provider into text deltas.
 * Supports the OpenAI chat-completions format (also used by OpenRouter, Gemini, DeepSeek, Groq, Ollama)
 * and the Anthropic Messages format. Feed raw network chunks in any size; events may be split across chunks.
 */
final class StreamDecoder
{
    private string $buffer = '';
    private string $content = '';
    private int $inputTokens = 0;
    private int $outputTokens = 0;
    private string $model = '';
    /** @var callable(string):void */
    private $onDelta;

    /** @param callable(string):void $onDelta Called with each piece of generated text. */
    public function __construct(private readonly string $format, callable $onDelta)
    {
        $this->onDelta = $onDelta;
    }

    /** @throws RuntimeException when the provider reports an error inside the stream */
    public function feed(string $chunk): void
    {
        $this->buffer .= str_replace("\r\n", "\n", $chunk);
        while (($end = strpos($this->buffer, "\n\n")) !== false) {
            $event = substr($this->buffer, 0, $end);
            $this->buffer = substr($this->buffer, $end + 2);
            $this->handleEvent($event);
        }
    }

    /** Process any trailing event that was not terminated by a blank line. */
    public function finish(): void
    {
        if (trim($this->buffer) !== '') {
            $this->handleEvent($this->buffer);
        }
        $this->buffer = '';
    }

    public function content(): string
    {
        return $this->content;
    }

    public function inputTokens(): int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): int
    {
        return $this->outputTokens;
    }

    public function model(): string
    {
        return $this->model;
    }

    private function handleEvent(string $event): void
    {
        foreach (explode("\n", $event) as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue; // comments (": keep-alive") and "event:" lines carry nothing we need
            }
            $data = trim(substr($line, 5));
            if ($data === '' || $data === '[DONE]') {
                continue;
            }
            $json = json_decode($data, true);
            if (is_array($json)) {
                $this->format === 'anthropic' ? $this->applyAnthropic($json) : $this->applyOpenAi($json);
            }
        }
    }

    /** @param array<string,mixed> $json */
    private function applyOpenAi(array $json): void
    {
        if (isset($json['error'])) {
            throw new RuntimeException($this->errorMessage($json['error']));
        }
        $this->model = (string) ($json['model'] ?? $this->model);
        $text = (string) ($json['choices'][0]['delta']['content'] ?? '');
        if ($text !== '') {
            $this->emit($text);
        }
        if (isset($json['usage']) && is_array($json['usage'])) {
            $this->inputTokens = (int) ($json['usage']['prompt_tokens'] ?? $this->inputTokens);
            $this->outputTokens = (int) ($json['usage']['completion_tokens'] ?? $this->outputTokens);
        }
    }

    /** @param array<string,mixed> $json */
    private function applyAnthropic(array $json): void
    {
        switch ($json['type'] ?? '') {
            case 'message_start':
                $this->model = (string) ($json['message']['model'] ?? $this->model);
                $this->inputTokens = (int) ($json['message']['usage']['input_tokens'] ?? 0);
                break;
            case 'content_block_delta':
                if (($json['delta']['type'] ?? '') === 'text_delta' && (string) ($json['delta']['text'] ?? '') !== '') {
                    $this->emit((string) $json['delta']['text']);
                }
                break;
            case 'message_delta':
                $this->outputTokens = (int) ($json['usage']['output_tokens'] ?? $this->outputTokens);
                break;
            case 'error':
                throw new RuntimeException($this->errorMessage($json['error'] ?? 'Unknown error'));
        }
    }

    private function emit(string $text): void
    {
        $this->content .= $text;
        ($this->onDelta)($text);
    }

    private function errorMessage(mixed $error): string
    {
        if (is_array($error)) {
            $error = $error['message'] ?? json_encode($error);
        }
        return (string) $error;
    }
}
