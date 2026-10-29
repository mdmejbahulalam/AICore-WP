<?php

declare(strict_types=1);

use AICore\WP\AI\ProviderSettings;
use AICore\WP\AI\RemoteProvider;
use AICore\WP\Security\Crypto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RemoteProviderTest extends TestCase
{
    private const MESSAGES = [
        ['role' => 'system', 'content' => 'SYS'],
        ['role' => 'user', 'content' => 'hello'],
    ];

    protected function setUp(): void
    {
        WpStub::reset();
    }

    private function provider(string $id): RemoteProvider
    {
        return new RemoteProvider($id, new ProviderSettings(new Crypto()));
    }

    public function testFallsBackToCatalogModelWhenNoneConfigured(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['deepseek' => ['api_key' => 'k']];
        $this->respondWith(200, ['choices' => [['message' => ['content' => 'ok']]]]);

        $response = $this->provider('deepseek')->chat(self::MESSAGES);

        self::assertSame('deepseek-chat', $this->sentBody()['model']);
        self::assertSame('deepseek', $response->metadata['provider']);
    }

    public function testOllamaWorksWithoutAKey(): void
    {
        $this->respondWith(200, ['choices' => [['message' => ['content' => 'local']]]]);

        $response = $this->provider('ollama')->chat(self::MESSAGES);

        self::assertSame('local', $response->content);
        self::assertArrayNotHasKey('Authorization', WpStub::$requests[0]['args']['headers']);
    }

    public function testEmbeddingsUseTheEmbeddingsEndpointAndCatalogModel(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['openrouter' => ['api_key' => 'k']];
        $this->respondWith(200, ['model' => 'openai/text-embedding-3-small', 'data' => [['embedding' => [0.5, 0.25]]], 'usage' => ['prompt_tokens' => 4]]);

        $result = $this->provider('openrouter')->embed('hello');

        self::assertSame('https://openrouter.ai/api/v1/embeddings', WpStub::$requests[0]['url']);
        self::assertSame('openai/text-embedding-3-small', $this->sentBody()['model']);
        self::assertSame('hello', $this->sentBody()['input']);
        self::assertSame([0.5, 0.25], $result->vector);
        self::assertSame(4, $result->tokens);
    }

    public function testEmbeddingsRejectedForProvidersWithoutSupport(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['claude' => ['api_key' => 'k']];

        self::assertFalse($this->provider('claude')->supportsEmbeddings());
        self::assertTrue($this->provider('openai')->supportsEmbeddings());
        $this->expectExceptionMessage('claude does not support embeddings');
        $this->provider('claude')->embed('x');
    }

    public function testEncryptedKeyIsDecryptedForTheRequest(): void
    {
        $settings = new ProviderSettings(new Crypto());
        $settings->save('openai', ['api_key' => 'sk-secret-1234']);
        $this->respondWith(200, ['choices' => [['message' => ['content' => 'ok']]]]);

        (new RemoteProvider('openai', $settings))->chat(self::MESSAGES);

        self::assertSame('Bearer sk-secret-1234', WpStub::$requests[0]['args']['headers']['Authorization']);
    }

    /** @param array<string,mixed> $body */
    private function respondWith(int $status, array $body): void
    {
        WpStub::$responder = static fn () => ['response' => ['code' => $status], 'body' => json_encode($body)];
    }

    /** @return array<string,mixed> */
    private function sentBody(): array
    {
        return json_decode(WpStub::$requests[0]['args']['body'], true);
    }

    public function testUnconfiguredProviderReturnsPreview(): void
    {
        $response = $this->provider('openai')->chat(self::MESSAGES);

        self::assertStringContainsString('Provider not configured', $response->content);
        self::assertSame('preview', $response->metadata['mode']);
        self::assertSame([], WpStub::$requests);
    }

    public function testClaudeUsesMessagesApi(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['claude' => ['api_key' => 'k', 'model' => 'claude-x']];
        $this->respondWith(200, ['model' => 'claude-x', 'content' => [['type' => 'text', 'text' => 'hi']], 'usage' => ['input_tokens' => 11, 'output_tokens' => 3]]);

        $response = $this->provider('claude')->chat(self::MESSAGES);

        $request = WpStub::$requests[0];
        self::assertSame('https://api.anthropic.com/v1/messages', $request['url']);
        self::assertSame('k', $request['args']['headers']['x-api-key']);
        self::assertArrayHasKey('anthropic-version', $request['args']['headers']);
        self::assertArrayNotHasKey('Authorization', $request['args']['headers']);
        $body = $this->sentBody();
        self::assertSame('SYS', $body['system']);
        self::assertSame(['user'], array_column($body['messages'], 'role'));
        self::assertSame(1024, $body['max_tokens']);
        self::assertSame('hi', $response->content);
        self::assertSame([11, 3], [$response->inputTokens, $response->outputTokens]);
        self::assertSame('claude-x', $response->metadata['model']);
    }

    /** @return array<string,array{string,string}> */
    public static function openAiCompatible(): array
    {
        return [
            'gemini' => ['gemini', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'],
            'deepseek' => ['deepseek', 'https://api.deepseek.com/chat/completions'],
            'openrouter' => ['openrouter', 'https://openrouter.ai/api/v1/chat/completions'],
            'openai' => ['openai', 'https://api.openai.com/v1/chat/completions'],
        ];
    }

    #[DataProvider('openAiCompatible')]
    public function testOpenAiCompatibleProvidersUseOwnEndpoint(string $id, string $endpoint): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = [$id => ['api_key' => 'k', 'model' => 'm']];
        $this->respondWith(200, ['model' => 'm-real', 'choices' => [['message' => ['content' => 'ok']]], 'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 2]]);

        $response = $this->provider($id)->chat(self::MESSAGES, ['max_tokens' => 20]);

        self::assertSame($endpoint, WpStub::$requests[0]['url']);
        self::assertSame('Bearer k', WpStub::$requests[0]['args']['headers']['Authorization']);
        self::assertSame(20, $this->sentBody()['max_tokens']);
        self::assertSame(['system', 'user'], array_column($this->sentBody()['messages'], 'role'));
        self::assertSame('ok', $response->content);
        self::assertSame('m-real', $response->metadata['model']);
    }

    public function testHttpErrorThrowsDescriptiveException(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['openrouter' => ['api_key' => 'k']];
        $this->respondWith(402, ['error' => ['message' => 'need credits']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('openrouter API error (402): need credits');
        $this->provider('openrouter')->chat(self::MESSAGES);
    }

    public function testTransportErrorThrows(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['groq' => ['api_key' => 'k']];
        WpStub::$responder = static fn () => new WP_Error('http_request_failed', 'timeout');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('groq request failed: timeout');
        $this->provider('groq')->chat(self::MESSAGES);
    }

    public function testUnknownProviderIsRejected(): void
    {
        WpStub::$options['aicore_wp_provider_settings'] = ['mystery' => ['api_key' => 'k']];
        $this->respondWith(200, []);

        $this->expectException(RuntimeException::class);
        $this->provider('mystery')->chat(self::MESSAGES);
    }
}
