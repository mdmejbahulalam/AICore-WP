# Bundler decision

This project should use Vite for WordPress admin assets.

Vite is a good fit because the plugin needs a small, manifest-driven admin bundle rather than a complex custom build pipeline. Its Rollup-powered production build can emit hashed JavaScript and CSS files plus a manifest that WordPress can read when enqueueing assets.

The configured output directory is `assets/admin/dist`, and the generated manifest path is `assets/admin/dist/.vite/manifest.json`.
