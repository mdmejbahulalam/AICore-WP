<?php
/**
 * Plugin Name: AICore WP
 * Plugin URI: https://example.com/aicore-wp
 * Description: Enterprise-grade AI Operating System for WordPress: agents, chatbots, workflows, RAG, MCP, analytics, billing, and AI automation.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: AICore WP
 * License: GPL-2.0-or-later
 * Text Domain: aicore-wp
 */

declare(strict_types=1);

use AICore\WP\Core\Plugin;
use AICore\WP\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

define('AICORE_WP_VERSION', '0.1.0');
define('AICORE_WP_FILE', __FILE__);
define('AICORE_WP_PATH', plugin_dir_path(__FILE__));
define('AICORE_WP_URL', plugin_dir_url(__FILE__));

$autoload = AICORE_WP_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'AICore\\WP\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = AICORE_WP_PATH . 'src/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

register_activation_hook(__FILE__, static function (): void {
    (new Migrator())->install();
    update_option('aicore_wp_version', AICORE_WP_VERSION, false);
});

register_deactivation_hook(__FILE__, static function (): void {
    wp_clear_scheduled_hook('aicore_wp_queue_tick');
});

add_action('plugins_loaded', static function (): void {
    Plugin::boot(AICORE_WP_FILE)->register();
});
