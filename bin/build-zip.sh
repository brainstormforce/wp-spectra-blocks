#!/usr/bin/env bash
# Creates a production-ready zip for CI use.
# Assumes assets are already built (npm run build + admin build).
# Assumes composer install --no-dev has already been run.

set -e

PLUGIN_SLUG="spectra-blocks"
PLUGIN_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARTIFACT_DIR="${PLUGIN_ROOT}/artifact"

mkdir -p "$ARTIFACT_DIR"
ZIP_FILE="${ARTIFACT_DIR}/${PLUGIN_SLUG}.zip"

[ -f "$ZIP_FILE" ] && rm "$ZIP_FILE"

cd "$PLUGIN_ROOT"

# Note: zip's --exclude uses fnmatch where * matches path separators
# when used as a glob (e.g., *.map), but ** prefixes do NOT work
# reliably across platforms. Use explicit paths for directories.

zip -r "$ZIP_FILE" . \
  --exclude "node_modules/*" \
  --exclude "admin/node_modules/*" \
  --exclude ".git/*" \
  --exclude ".github/*" \
  --exclude ".wordpress-org/*" \
  --exclude ".claude/*" \
  --exclude ".vscode/*" \
  --exclude "src/*" \
  --exclude "admin/assets/src/*" \
  --exclude "admin/src/*" \
  --exclude "tests/*" \
  --exclude "e2e/*" \
  --exclude "bin/*" \
  --exclude "artifact/*" \
  --exclude "composer.json" \
  --exclude "composer.lock" \
  --exclude "package.json" \
  --exclude "package-lock.json" \
  --exclude "webpack.config.js" \
  --exclude "phpcs.xml" \
  --exclude "phpunit.xml.dist" \
  --exclude "CLAUDE.md" \
  --exclude "Claude.md" \
  --exclude "README.md" \
  --exclude ".gitignore" \
  --exclude ".gitattributes" \
  --exclude ".distignore" \
  --exclude ".editorconfig" \
  --exclude "admin/package.json" \
  --exclude "admin/package-lock.json" \
  --exclude "admin/webpack.config.js" \
  --exclude "admin/tailwind.config.js" \
  --exclude "admin/postcss.config.js" \
  --exclude "lib/*/package.json" \
  --exclude "lib/*/package-lock.json" \
  --exclude "lib/*/node_modules/*" \
  --exclude "lib/*/tests/*" \
  --exclude "*.DS_Store" \
  --exclude "*.log" \
  --exclude "*.map" \
  --exclude "*.zip" \
  --exclude "auth.json"

echo "Created: ${ZIP_FILE}"
