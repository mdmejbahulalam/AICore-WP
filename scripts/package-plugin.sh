#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="${PLUGIN_SLUG:-aicore-wp}"
BUILD_DIR="$ROOT_DIR/build"
PACKAGE_DIR="$BUILD_DIR/package"
STAGING_DIR="$PACKAGE_DIR/$PLUGIN_SLUG"
ZIP_PATH="$BUILD_DIR/$PLUGIN_SLUG.zip"
MANIFEST_PATH="$ROOT_DIR/assets/admin/dist/.vite/manifest.json"

if [[ ! -f "$MANIFEST_PATH" ]]; then
  echo "Missing $MANIFEST_PATH. Run npm run build before packaging." >&2
  exit 1
fi

rm -rf "$PACKAGE_DIR" "$ZIP_PATH"
mkdir -p "$STAGING_DIR"

rsync -a "$ROOT_DIR/" "$STAGING_DIR/" \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude 'node_modules/' \
  --exclude 'build/' \
  --exclude 'assets/admin/src/' \
  --exclude 'scripts/' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude 'composer.lock' \
  --exclude '.gitignore'

if [[ ! -f "$STAGING_DIR/assets/admin/dist/.vite/manifest.json" ]]; then
  echo "Packaged plugin is missing assets/admin/dist/.vite/manifest.json." >&2
  exit 1
fi

(
  cd "$PACKAGE_DIR"
  zip -qr "$ZIP_PATH" "$PLUGIN_SLUG"
)

if ! zipinfo -1 "$ZIP_PATH" | grep -qx "$PLUGIN_SLUG/assets/admin/dist/.vite/manifest.json"; then
  echo "Final zip is missing assets/admin/dist/.vite/manifest.json." >&2
  exit 1
fi

echo "Created $ZIP_PATH"
