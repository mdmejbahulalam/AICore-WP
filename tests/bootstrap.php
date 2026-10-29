<?php

declare(strict_types=1);

// Unit tests run without WordPress: stub the few WP functions the classes under test call.
define('ABSPATH', __DIR__ . '/');

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(public string $code = '', public string $message = '', public mixed $data = '')
        {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_data(): mixed
        {
            return $this->data;
        }
    }
}

/** In-memory stand-ins for WordPress options and the HTTP API. */
final class WpStub
{
    /** @var array<string,mixed> */
    public static array $options = [];
    /** @var list<array{url:string,args:array<string,mixed>}> */
    public static array $requests = [];
    /** @var callable|null */
    public static $responder = null;

    public static function reset(): void
    {
        self::$options = [];
        self::$requests = [];
        self::$responder = null;
    }
}

function get_option(string $name, mixed $default = false): mixed
{
    return WpStub::$options[$name] ?? $default;
}

function update_option(string $name, mixed $value, mixed $autoload = null): bool
{
    WpStub::$options[$name] = $value;
    return true;
}

function wp_salt(string $scheme = 'auth'): string
{
    return 'test-salt-' . $scheme;
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    return $value;
}

function do_action(string $hook, mixed ...$args): void
{
}

function sanitize_text_field(string $value): string
{
    return trim(strip_tags($value));
}

function esc_url_raw(string $url, ?array $protocols = null): string
{
    $scheme = (string) parse_url($url, PHP_URL_SCHEME);
    return in_array($scheme, $protocols ?? ['http', 'https'], true) ? $url : '';
}

function wp_json_encode(mixed $value): string|false
{
    return json_encode($value);
}

function is_wp_error(mixed $thing): bool
{
    return $thing instanceof WP_Error;
}

/** @param array<string,mixed> $args */
function wp_remote_post(string $url, array $args = []): mixed
{
    WpStub::$requests[] = ['url' => $url, 'args' => $args];
    return (WpStub::$responder)($url, $args);
}

function wp_remote_retrieve_body(array $response): string
{
    return (string) ($response['body'] ?? '');
}

function wp_remote_retrieve_response_code(array $response): int
{
    return (int) ($response['response']['code'] ?? 0);
}
