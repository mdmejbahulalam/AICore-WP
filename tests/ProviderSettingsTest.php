<?php

declare(strict_types=1);

use AICore\WP\AI\ProviderSettings;
use AICore\WP\Security\Crypto;
use PHPUnit\Framework\TestCase;

final class ProviderSettingsTest extends TestCase
{
    private ProviderSettings $settings;

    protected function setUp(): void
    {
        WpStub::reset();
        $this->settings = new ProviderSettings(new Crypto());
    }

    public function testCryptoRoundTripAndTamperDetection(): void
    {
        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('sk-secret');

        self::assertNotSame('sk-secret', $encrypted);
        self::assertTrue($crypto->isEncrypted($encrypted));
        self::assertSame('sk-secret', $crypto->decrypt($encrypted));
        self::assertSame('', $crypto->decrypt(substr($encrypted, 0, -4) . 'AAAA'));
        self::assertSame('legacy-plain', $crypto->decrypt('legacy-plain'));
        self::assertSame('', $crypto->encrypt(''));
    }

    public function testKeyIsStoredEncryptedAndNeverExposedPublicly(): void
    {
        $this->settings->save('openai', ['api_key' => ' sk-abcdef1234 ', 'model' => 'gpt-x', 'max_tokens' => 500]);

        $stored = WpStub::$options[ProviderSettings::OPTION]['openai'];
        self::assertStringStartsWith('enc:v1:', $stored['api_key']);
        self::assertStringNotContainsString('abcdef', $stored['api_key']);
        self::assertSame('sk-abcdef1234', $this->settings->config('openai')['api_key']);

        $public = $this->settings->public('openai');
        self::assertTrue($public['has_key']);
        self::assertSame('…1234', $public['key_hint']);
        self::assertSame('gpt-x', $public['model']);
        self::assertStringNotContainsString('abcdef', json_encode($public));
    }

    public function testOmittedOrBlankKeyKeepsExistingKeyAndClearRemovesIt(): void
    {
        $this->settings->save('claude', ['api_key' => 'k-one']);
        $this->settings->save('claude', ['model' => 'm', 'api_key' => '']);
        self::assertSame('k-one', $this->settings->config('claude')['api_key']);

        $this->settings->save('claude', ['clear_key' => true]);
        self::assertFalse($this->settings->public('claude')['has_key']);
    }

    public function testLegacyPlainTextKeyIsMigratedOnNextSave(): void
    {
        WpStub::$options[ProviderSettings::OPTION] = ['groq' => ['api_key' => 'plain-key']];
        self::assertSame('plain-key', $this->settings->config('groq')['api_key']);

        $this->settings->save('groq', ['model' => 'llama']);

        self::assertStringStartsWith('enc:v1:', WpStub::$options[ProviderSettings::OPTION]['groq']['api_key']);
        self::assertSame('plain-key', $this->settings->config('groq')['api_key']);
    }

    public function testInputIsSanitised(): void
    {
        $this->settings->save('openai', ['max_tokens' => 9999999, 'base_url' => 'javascript:alert(1)', 'model' => '<b>gpt</b>']);

        $config = $this->settings->config('openai');
        self::assertSame(200000, $config['max_tokens']);
        self::assertSame('', $config['base_url']);
        self::assertSame('gpt', $config['model']);
    }

    public function testDefaultProviderFallsBackToOpenAiWhenInvalid(): void
    {
        self::assertSame('openai', $this->settings->defaultProvider());
        $this->settings->setDefaultProvider('claude');
        self::assertSame('claude', $this->settings->defaultProvider());
        WpStub::$options[ProviderSettings::DEFAULT_OPTION] = 'nonsense';
        self::assertSame('openai', $this->settings->defaultProvider());
    }
}
