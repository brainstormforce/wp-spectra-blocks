#!/usr/bin/env bash
# Creates a production-ready zip for WordPress.org upload.
# Usage: npm run zip   (runs from plugin root)

set -e

PLUGIN_SLUG="spectra-blocks"
VERSION=$(node -p "require('./package.json').version")
PLUGIN_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PARENT_DIR="$(dirname "$PLUGIN_ROOT")"
ZIP_FILE="${PARENT_DIR}/${PLUGIN_SLUG}.${VERSION}.zip"

echo "Building ${PLUGIN_SLUG} v${VERSION}..."

# 1. Build blocks & extensions
echo "→ Running npm run build..."
npm run build

# 2. Build admin dashboard
echo "→ Running admin build..."
(cd admin && npm run build)

# 3. Install production-only Composer dependencies (strip dev packages from zip)
echo "→ Installing production Composer dependencies..."
cd "$PLUGIN_ROOT"
composer install --no-dev --quiet

# 4. Remove old zip if exists
[ -f "$ZIP_FILE" ] && rm "$ZIP_FILE"

# 5. Create zip from parent directory
echo "→ Creating zip..."
cd "$PARENT_DIR"

zip -r "$ZIP_FILE" "$PLUGIN_SLUG" \
  --exclude "**/node_modules/*" \
  --exclude "**/.git/*" \
  --exclude "**/src/*" \
  --exclude "**/admin/assets/src/*" \
  --exclude "**/tests/*" \
  --exclude "**/e2e/*" \
  --exclude "**/.github/*" \
  --exclude "${PLUGIN_SLUG}/composer.json" \
  --exclude "${PLUGIN_SLUG}/composer.lock" \
  --exclude "${PLUGIN_SLUG}/package.json" \
  --exclude "${PLUGIN_SLUG}/package-lock.json" \
  --exclude "${PLUGIN_SLUG}/webpack.config.js" \
  --exclude "${PLUGIN_SLUG}/phpcs.xml" \
  --exclude "${PLUGIN_SLUG}/phpunit.xml.dist" \
  --exclude "${PLUGIN_SLUG}/CLAUDE.md" \
  --exclude "${PLUGIN_SLUG}/README.md" \
  --exclude "${PLUGIN_SLUG}/.gitignore" \
  --exclude "${PLUGIN_SLUG}/.gitattributes" \
  --exclude "${PLUGIN_SLUG}/.distignore" \
  --exclude "${PLUGIN_SLUG}/.editorconfig" \
  --exclude "${PLUGIN_SLUG}/admin/package.json" \
  --exclude "${PLUGIN_SLUG}/admin/package-lock.json" \
  --exclude "${PLUGIN_SLUG}/admin/webpack.config.js" \
  --exclude "${PLUGIN_SLUG}/admin/tailwind.config.js" \
  --exclude "${PLUGIN_SLUG}/admin/postcss.config.js" \
  --exclude "**/.DS_Store" \
  --exclude "**/*.log" \
  --exclude "**/*.map"

# 6. Restore dev dependencies for local development
echo "→ Restoring dev Composer dependencies..."
cd "$PLUGIN_ROOT"
composer install --quiet

echo ""
echo "✓ Created: ${ZIP_FILE}"
echo "  Size: $(du -sh "$ZIP_FILE" | cut -f1)"
