<?php

declare(strict_types=1);

namespace AICore\WP\Admin;

final class AdminApp
{
    public function registerMenu(): void
    {
        add_menu_page(__('AICore WP', 'aicore-wp'), __('AICore WP', 'aicore-wp'), 'manage_options', 'aicore-wp', [$this, 'render'], 'dashicons-superhero-alt', 3);
    }

    public function enqueue(string $hook): void
    {
        if (! str_contains($hook, 'aicore-wp')) {
            return;
        }
        $asset = AICORE_WP_PATH . 'assets/admin/dist/.vite/manifest.json';
        $script = AICORE_WP_URL . 'assets/admin/src/main.tsx';
        $style = AICORE_WP_URL . 'assets/admin/src/styles.css';
        if (file_exists($asset)) {
            $manifest = json_decode((string) file_get_contents($asset), true);
            $entry = $manifest['assets/admin/src/main.tsx'] ?? [];
            $script = AICORE_WP_URL . 'assets/admin/dist/' . ($entry['file'] ?? 'aicore-admin.js');
            if (! empty($entry['css'][0])) {
                $style = AICORE_WP_URL . 'assets/admin/dist/' . $entry['css'][0];
            }
        }
        wp_enqueue_style('aicore-wp-admin', $style, [], AICORE_WP_VERSION);
        wp_enqueue_script('aicore-wp-admin', $script, ['wp-api-fetch'], AICORE_WP_VERSION, true);
        wp_localize_script('aicore-wp-admin', 'AICoreWP', [
            'restUrl' => esc_url_raw(rest_url('aicore/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'site' => get_bloginfo('name'),
            'user' => wp_get_current_user()->display_name,
        ]);
    }

    public function render(): void
    {
        echo '<div id="aicore-wp-root" class="aicore-wp-root"></div>';
    }
}
