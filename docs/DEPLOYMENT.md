# Deployment

Deploy the AICore WordPress plugin from a production plugin zip, not from an unbuilt source checkout.

## Source checkout

A source checkout is for development and continuous integration. It contains the admin asset source files and release scripts, but it is not guaranteed to contain built files until the build step runs.

Prepare a source checkout with:

```bash
npm ci
npm run build
composer install
```

After the build finishes, `assets/admin/dist/.vite/manifest.json` must exist. The release verification command checks that the manifest is valid and that each referenced compiled asset exists:

```bash
npm run verify:release-assets
```

## Production plugin zip

A production plugin zip is the deployable artifact for WordPress. It must include `assets/admin/dist/`, especially `assets/admin/dist/.vite/manifest.json`, so the plugin can enqueue compiled admin JavaScript and CSS without requiring a build on the WordPress server.

Build the production package with:

```bash
npm ci && npm run build && composer install --no-dev --optimize-autoloader && npm run verify:release-assets && npm run package
```

The package script writes `build/aicore-wp.zip` and fails if the manifest is missing from either the workspace or the final zip. The CI release workflow runs the same checks on pull requests, pushes to `main`, and manual workflow dispatches.

## Deployment checklist

1. Run the release command from a clean checkout.
2. Confirm `build/aicore-wp.zip` exists.
3. Confirm the zip contains `aicore-wp/assets/admin/dist/.vite/manifest.json`.
4. Upload `build/aicore-wp.zip` through the WordPress plugin installer or deploy the extracted plugin directory through your normal release system.
