# AICore WordPress Plugin

This repository contains the source project and release tooling for the AICore WordPress plugin.

## Install types

### Source install for development

Use a source install when you are developing the plugin or reviewing changes from Git. Source installs require local build tooling because admin assets are generated from source before WordPress can load them.

```bash
npm ci
npm run build
composer install
```

The admin source files live in `assets/admin/src/`. The build output is written to `assets/admin/dist/`, including the Vite-compatible manifest at `assets/admin/dist/.vite/manifest.json`.

### Production zip install

Use the production zip when installing the plugin on a WordPress site. A production zip is built by the release process and already contains compiled admin assets in `assets/admin/dist/`; site administrators should not need Node.js or npm on the server.

Do not create production plugin zips directly from a Git checkout unless you run the release command first. A raw source archive can omit `assets/admin/dist/.vite/manifest.json`, which prevents WordPress from resolving the compiled admin asset filenames.

## Release command

The documented release command is:

```bash
npm ci && npm run build && composer install --no-dev --optimize-autoloader && npm run verify:release-assets && npm run package
```

You can run the same process with:

```bash
npm run release
```

The release process performs a reproducible Node install from `package-lock.json`, builds the admin assets, installs optimized production Composer dependencies, fails if `assets/admin/dist/.vite/manifest.json` is missing or invalid, and creates `build/aicore-wp.zip` with `assets/admin/dist/` included.
