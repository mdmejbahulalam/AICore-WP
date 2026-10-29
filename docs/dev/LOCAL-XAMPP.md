# Local development on XAMPP (Windows)

This is the setup used to test the plugin. The longer `DEVELOPMENT.md` / `TESTING.md` in this folder were written
for a Docker workflow and may not match this layout.

1. Start Apache and MySQL/MariaDB in XAMPP. Check the MySQL port in `xampp/mysql/bin/my.ini` (here: `3307`).
2. Create a database, e.g. `aicore_wp_test`.
3. Install WordPress into `htdocs/aicore-wp` (zip from wordpress.org, or `wp core download`) and create `wp-config.php`
   with `DB_HOST` = `127.0.0.1:3307`. Enable `WP_DEBUG` and `WP_DEBUG_LOG`.
4. If pretty permalinks 404 on XAMPP, make sure `.htaccess` exists (WP-CLI cannot write it on XAMPP).
5. Put this repo in `wp-content/plugins/aicore-wp`, then:

   ```bash
   composer install
   npm ci
   npm run build        # writes assets/admin/dist/ (+ .vite/manifest.json)
   ```
6. Activate the plugin (`wp plugin activate aicore-wp`). The admin app is at *wp-admin → AICore WP*.
7. Provider keys: until the settings screen exists, set the option with WP-CLI, e.g.
   `wp option update aicore_wp_provider_settings '{"openrouter":{"api_key":"...","model":"openai/gpt-4o-mini"}}' --format=json`
   and `wp option update aicore_wp_default_provider openrouter`. Never commit keys.

## Tests

```bash
composer test         # PHPUnit; WordPress functions are stubbed in tests/bootstrap.php
npm run lint          # TypeScript type-check
```
