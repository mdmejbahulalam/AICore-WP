<?php
/**
 * Plugin Name: AICore WP
 * Description: WordPress integration for AICore admin assets.
 * Version: 0.1.0
 * Author: AICore
 * License: GPL-2.0-or-later
 * Text Domain: aicore-wp
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

const AICORE_WP_VERSION = '0.1.0';
const AICORE_WP_ADMIN_ENTRY = 'assets/admin/src/admin.js';
const AICORE_WP_ADMIN_MANIFEST = 'assets/admin/dist/.vite/manifest.json';

add_action('admin_enqueue_scripts', 'aicore_wp_enqueue_admin_assets');

/**
 * Enqueue the Vite-built admin bundle by reading the generated manifest.
 */
function aicore_wp_enqueue_admin_assets(): void
{
    $manifest_path = plugin_dir_path(__FILE__) . AICORE_WP_ADMIN_MANIFEST;

    if (! is_readable($manifest_path)) {
        return;
    }

    $manifest = json_decode((string) file_get_contents($manifest_path), true);

    if (! is_array($manifest) || empty($manifest[AICORE_WP_ADMIN_ENTRY]['file'])) {
        return;
    }

    $entry = $manifest[AICORE_WP_ADMIN_ENTRY];
    $dist_url = plugin_dir_url(__FILE__) . 'assets/admin/dist/';
    $dist_path = plugin_dir_path(__FILE__) . 'assets/admin/dist/';
    $script_file = (string) $entry['file'];
    $script_path = $dist_path . $script_file;
    $script_version = is_readable($script_path) ? (string) filemtime($script_path) : AICORE_WP_VERSION;

    wp_enqueue_script(
        'aicore-wp-admin',
        $dist_url . $script_file,
        array(),
        $script_version,
        true
    );
    wp_script_add_data('aicore-wp-admin', 'type', 'module');

    if (empty($entry['css']) || ! is_array($entry['css'])) {
        return;
    }

    foreach ($entry['css'] as $index => $css_file) {
        if (! is_string($css_file)) {
            continue;
        }

        $css_path = $dist_path . $css_file;
        $css_version = is_readable($css_path) ? (string) filemtime($css_path) : AICORE_WP_VERSION;

        wp_enqueue_style(
            'aicore-wp-admin-' . (int) $index,
            $dist_url . $css_file,
            array(),
            $css_version
        );
    }
}
