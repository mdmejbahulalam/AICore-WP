<?php

declare(strict_types=1);

namespace AICore\WP\Admin;

final class AdminApp
{
    public const MENU_SLUG = 'aicore-wp';

    private const MANIFEST_RELATIVE_PATH = 'assets/admin/dist/.vite/manifest.json';
    private const DIST_RELATIVE_PATH = 'assets/admin/dist/';
    private const ADMIN_ENTRY = 'assets/admin/src/main.tsx';
    private const MISSING_ASSETS_MESSAGE = 'AICore WP admin assets are missing. Please run npm install && npm run build.';

    public function registerMenu(): void
    {
        add_menu_page(__('AICore WP', 'aicore-wp'), __('AICore WP', 'aicore-wp'), 'manage_options', self::MENU_SLUG, [$this, 'render'], 'dashicons-superhero-alt', 3);
    }

    public function enqueue(string $hook): void
    {
        if (! str_contains($hook, self::MENU_SLUG)) {
            return;
        }

        $manifestPath = AICORE_WP_PATH . self::MANIFEST_RELATIVE_PATH;
        $manifest = is_readable($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
        $entry = is_array($manifest) ? ($manifest[self::ADMIN_ENTRY] ?? null) : null;
        if (! is_array($entry) || empty($entry['file'])) {
            add_action('admin_notices', [$this, 'renderMissingAssetsNotice']);
            return;
        }

        $baseUrl = AICORE_WP_URL . self::DIST_RELATIVE_PATH;
        $version = (string) filemtime($manifestPath);

        foreach (array_values(array_filter((array) ($entry['css'] ?? []), 'is_string')) as $index => $stylesheet) {
            wp_enqueue_style('aicore-wp-admin-' . ($index + 1), $baseUrl . $stylesheet, [], $version);
        }
        wp_enqueue_script('aicore-wp-admin', $baseUrl . $entry['file'], ['wp-api-fetch'], $version, true);
        wp_localize_script('aicore-wp-admin', 'AICoreWP', [
            'restUrl' => esc_url_raw(rest_url('aicore/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'site' => get_bloginfo('name'),
            'user' => wp_get_current_user()->display_name,
        ]);
    }

    public function renderMissingAssetsNotice(): void
    {
        printf('<div class="notice notice-error"><p>%s</p></div>', esc_html(self::MISSING_ASSETS_MESSAGE));
    }

    public function render(): void
    {
        echo '<div id="aicore-wp-root" class="aicore-wp-root"></div>';
    }
}
