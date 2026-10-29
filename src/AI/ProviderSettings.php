<?php

declare(strict_types=1);

namespace AICore\WP\AI;

use AICore\WP\Security\Crypto;

/** Reads and writes per-provider configuration. API keys are encrypted at rest and never returned by public(). */
final class ProviderSettings
{
    public const OPTION = 'aicore_wp_provider_settings';
    public const DEFAULT_OPTION = 'aicore_wp_default_provider';

    public function __construct(private readonly Crypto $crypto)
    {
    }

    /** @return array{api_key:string,model:string,max_tokens:int,base_url:string,embedding_model:string} Decrypted; for server-side use only. */
    public function config(string $id): array
    {
        $stored = (array) (((array) get_option(self::OPTION, []))[$id] ?? []);
        return [
            'api_key' => $this->crypto->decrypt((string) ($stored['api_key'] ?? '')),
            'model' => (string) ($stored['model'] ?? ''),
            'max_tokens' => (int) ($stored['max_tokens'] ?? 0),
            'base_url' => (string) ($stored['base_url'] ?? ''),
            'embedding_model' => (string) ($stored['embedding_model'] ?? ''),
        ];
    }

    /** @return array{has_key:bool,key_hint:string,model:string,max_tokens:int,base_url:string,embedding_model:string} Safe to send to the browser. */
    public function public(string $id): array
    {
        $config = $this->config($id);
        $key = $config['api_key'];
        return [
            'has_key' => $key !== '',
            'key_hint' => $key !== '' ? '…' . substr($key, -4) : '',
            'model' => $config['model'],
            'max_tokens' => $config['max_tokens'],
            'base_url' => $config['base_url'],
            'embedding_model' => $config['embedding_model'],
        ];
    }

    /**
     * @param array<string,mixed> $input api_key (non-empty replaces the key), clear_key, model, embedding_model, max_tokens, base_url.
     *                                   Omitted fields are left unchanged.
     */
    public function save(string $id, array $input): void
    {
        $all = (array) get_option(self::OPTION, []);
        $stored = (array) ($all[$id] ?? []);

        if (! empty($input['clear_key'])) {
            $stored['api_key'] = '';
        } elseif (isset($input['api_key']) && trim((string) $input['api_key']) !== '') {
            $stored['api_key'] = $this->crypto->encrypt(trim((string) $input['api_key']));
        } elseif (isset($stored['api_key']) && $stored['api_key'] !== '' && ! $this->crypto->isEncrypted((string) $stored['api_key'])) {
            $stored['api_key'] = $this->crypto->encrypt((string) $stored['api_key']); // migrate legacy plain text
        }
        if (array_key_exists('model', $input)) {
            $stored['model'] = sanitize_text_field((string) $input['model']);
        }
        if (array_key_exists('embedding_model', $input)) {
            $stored['embedding_model'] = sanitize_text_field((string) $input['embedding_model']);
        }
        if (array_key_exists('max_tokens', $input)) {
            $stored['max_tokens'] = max(0, min(200000, (int) $input['max_tokens']));
        }
        if (array_key_exists('base_url', $input)) {
            $url = trim((string) $input['base_url']);
            $stored['base_url'] = $url === '' ? '' : esc_url_raw($url, ['http', 'https']);
        }

        $all[$id] = $stored;
        update_option(self::OPTION, $all, false);
    }

    public function defaultProvider(): string
    {
        $id = (string) get_option(self::DEFAULT_OPTION, 'openai');
        return ProviderCatalog::exists($id) ? $id : 'openai';
    }

    public function setDefaultProvider(string $id): void
    {
        update_option(self::DEFAULT_OPTION, $id);
    }
}
