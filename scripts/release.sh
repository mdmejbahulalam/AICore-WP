#!/usr/bin/env bash
set -euo pipefail

npm ci
npm run build
composer install --no-dev --optimize-autoloader
npm run verify:release-assets
npm run package
