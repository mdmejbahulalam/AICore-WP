<?php

declare(strict_types=1);

namespace AICoreWP\Admin;

/**
 * Registers the WordPress admin application assets.
 */
final class AdminApp
{
    private const ADMIN_SCRIPT_HANDLE = 'aicore-wp-admin';
    private const ADMIN_STYLE_HANDLE = 'aicore-wp-admin';
    private const MANIFEST_RELATIVE_PATH = 'assets/admin/dist/.vite/manifest.json';
    private const DIST_RELATIVE_PATH = 'assets/admin/dist/';
    private const ADMIN_ENTRY = 'assets/admin/src/main.tsx';
    private const ADMIN_STYLES_ENTRY = 'assets/admin/src/styles.css';
    private const MISSING_ASSETS_MESSAGE = 'AICore WP admin assets are missing. Please run npm install && npm run build.';

    private string $pluginFile;

    /**
     * @param string $pluginFile Absolute path to the plugin bootstrap file.
     */
    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    /**
     * Attach WordPress hooks for the admin application.
     */
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue the compiled Vite assets for the admin application.
     */
    public function enqueueAssets(): void
    {
        $manifestPath = $this->getManifestPath();

        if (! is_readable($manifestPath)) {
            $this->registerMissingAssetsNotice();
            return;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || empty($manifest[self::ADMIN_ENTRY]['file'])) {
            $this->registerMissingAssetsNotice();
            return;
        }

        $assetBaseUrl = plugin_dir_url($this->pluginFile) . self::DIST_RELATIVE_PATH;
        $version = (string) filemtime($manifestPath);
        $scriptFile = $manifest[self::ADMIN_ENTRY]['file'];

        wp_enqueue_script(
            self::ADMIN_SCRIPT_HANDLE,
            $assetBaseUrl . $scriptFile,
            ['wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n'],
            $version,
            true
        );

        foreach ($this->getStylesheetFiles($manifest) as $index => $stylesheetFile) {
            wp_enqueue_style(
                self::ADMIN_STYLE_HANDLE . '-' . ($index + 1),
                $assetBaseUrl . $stylesheetFile,
                [],
                $version
            );
        }
    }

    /**
     * Render an admin error when compiled assets are unavailable.
     */
    public function renderMissingAssetsNotice(): void
    {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(self::MISSING_ASSETS_MESSAGE)
        );
    }

    private function registerMissingAssetsNotice(): void
    {
        add_action('admin_notices', [$this, 'renderMissingAssetsNotice']);
    }

    private function getManifestPath(): string
    {
        return plugin_dir_path($this->pluginFile) . self::MANIFEST_RELATIVE_PATH;
    }

    /**
     * @param array<string, mixed> $manifest
     * @return list<string>
     */
    private function getStylesheetFiles(array $manifest): array
    {
        $stylesheets = [];

        if (isset($manifest[self::ADMIN_ENTRY]['css']) && is_array($manifest[self::ADMIN_ENTRY]['css'])) {
            $stylesheets = array_merge($stylesheets, $manifest[self::ADMIN_ENTRY]['css']);
        }

        if (! empty($manifest[self::ADMIN_STYLES_ENTRY]['file'])) {
            $stylesheets[] = $manifest[self::ADMIN_STYLES_ENTRY]['file'];
        }

        if (isset($manifest[self::ADMIN_STYLES_ENTRY]['css']) && is_array($manifest[self::ADMIN_STYLES_ENTRY]['css'])) {
            $stylesheets = array_merge($stylesheets, $manifest[self::ADMIN_STYLES_ENTRY]['css']);
        }

        return array_values(array_unique(array_filter($stylesheets, 'is_string')));
    }
}
