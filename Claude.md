# CLAUDE.md

This file provides guidance to Claude Code when working with spectra-blocks.

## Project Overview

**Spectra Blocks** is a fresh, standalone WordPress Gutenberg block plugin built on the V3 architecture using the WordPress Interactivity API.

- **Plugin slug:** `spectra-blocks`
- **Text domain:** `spectra-blocks`
- **PHP namespace:** `Spectra\` (V3 classes in includes/)
- **Block prefix:** `spectra/` (e.g., `spectra/container`)
- **Block categories:** `spectra-blocks`, `spectra-blocks-inner`
- **Option prefix:** `spectra_blocks_` (e.g., `spectra_blocks_file_generation`)
- **PHP class prefix:** `Spectra_Blocks_` (infrastructure classes in classes/)
- **Function prefix:** `spectra_blocks_` (global functions)
- **Author:** Brainstorm Force
- **Requires:** PHP 8.1+, WordPress 6.6+

## Tech Stack

| Layer | Technology |
|-------|------------|
| PHP Backend | WordPress plugin, PSR-4 autoloading (Composer), PHP >= 8.1 |
| JS Frontend | React, WordPress Interactivity API, @wordpress/scripts (webpack) |
| PHP Testing | PHPUnit |
| E2E Testing | Playwright |

## Directory Structure

```
spectra-blocks.php          # Main entry point
spectra-blocks-init.php     # V3 bootstrap (loads Spectra\ classes)
classes/                    # Infrastructure PHP classes (Spectra_Blocks_*)
includes/                   # V3 PHP classes (Spectra\ namespace, PSR-4)
  AssetLoader.php
  BlockManager.php
  ExtensionManager.php
  FontManager.php
  AnalyticsManager.php
  Blocks/                   # Block-specific PHP
  Extensions/               # Extension PHP
  Helpers/                  # Helper utilities
  Analytics/                # Analytics tracking
src/                        # JS/SCSS source (blocks, extensions, components)
  blocks/                   # 45+ block implementations
  extensions/               # Editor extensions
  helpers/                  # JS helpers including plugin-config.js
assets/                     # Static assets (images, fonts, icons, masks)
admin/                      # Admin dashboard (React SPA + PHP backend)
lib/                        # Third-party libraries
build/                      # Compiled assets (git-ignored, run npm run build)
vendor/                     # Composer dependencies (git-ignored, run composer install)
languages/                  # Translation files
```

## Development Commands

```bash
# Install dependencies
npm install
composer install           # Requires SSH access to github.com/brainstormforce private repos

# Build
npm run build              # Production build
npm run build:fresh        # Clean build
npm run start              # Dev watch mode

# Code quality
npm run lint:js
npm run lint:css
composer lint              # PHPCS

# Build admin dashboard (from admin/ directory)
cd admin && npm install && npm run build
```

### Library Management

BSF shared libraries (`lib/`) are managed via Composer with private VCS repositories:
- `brainstormforce/bsf-analytics` — Analytics opt-in UI
- `brainstormforce/zip-ai` — Zip AI assistant integration
- `brainstormforce/astra-notices` — Admin notices
- `brainstormforce/nps-survey` — NPS survey
- `brainstormforce/utm-analytics` — UTM tracking
- `brainstormforce/zipwp-images` — ZipWP image library

`composer install` requires SSH access to BSF private GitHub repos (same as UAG/Spectra).
These repos are NOT on Packagist — they use `"type": "vcs"` with `git@github.com:brainstormforce/` URLs.
Composer's `installer-paths` places them into `lib/{package-name}/` (not `vendor/`).

## Key Conventions

### Block Registration
- All blocks registered via `register_block_type_from_metadata()` from `build/blocks/**/block.json`
- Block names: `spectra/{block-name}` (e.g., `spectra/container`)
- Categories: `spectra-blocks` (main), `spectra-blocks-inner` (child blocks)

### Settings
Use `Spectra_Blocks_Settings::get/update/delete()` -- wraps `get_option()` with `spectra_blocks_` prefix.

### Backward Content Compatibility
The following block attribute names are intentionally kept with UAG prefix for backward compatibility with saved post content. Do NOT rename these:
- UAGDisplayConditions, UAGUserRole, UAGBrowser, UAGSystem, UAGDay
- UAGHideDesktop, UAGHideMob, UAGHideTab, UAGLoggedIn, UAGLoggedOut
- UAGAnimationType, UAGAnimationTime, UAGAnimationDelay, UAGAnimationEasing
- UAGAnimationRepeat, UAGAnimationDelayInterval, UAGAnimationDoNotApplyToContainer
- UAGPosition, UAGResponsiveConditions

### JS Globals
- `window.spectra_blocks_info` - Plugin configuration (url, rtl, etc.)
- `window.spectraBlocksSvgIcons` - SVG icon library
- `window.spectraBlocksIconCategoryList` - Icon categories
- Import from `@spectra-config` for typed accessors

### Admin Dashboard
- URL: `wp-admin/admin.php?page=spectra-blocks`
- REST namespace: `spectra-blocks/v1`
- AJAX prefix: `spectra_blocks_`
- Mount ID: `spectra-blocks-dashboard-app`

### Architecture Patterns

**Infrastructure classes** (`classes/`) use static `::init()` pattern:
```php
class Spectra_Blocks_Example {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'do_something' ) );
    }
}
```

**Core classes** (`includes/`) use the `Singleton` trait and PSR-4 autoloading:
```php
namespace Spectra\SomeNamespace;
use Spectra\Traits\Singleton;
class MyClass {
    use Singleton;
    public function init() { /* hooks */ }
}
```

### PHPDoc / JSDoc
- For the `@since` tag, always use `x.x.x` for new or modified code

### Dynamic CSS
- Use CSS variables instead of enqueueing additional CSS files
- Variables are scoped globally but can be overridden per block
- CSS Variables: `--spectra-{attribute-name}`
- CSS Classes: `spectra-{attribute-name}`

### Naming Reference

| Type | Convention | Example |
|------|-----------|---------|
| Block name | `spectra/{name}` | `spectra/container` |
| Option key | `spectra_blocks_{name}` | `spectra_blocks_file_generation` |
| Script handle | `spectra-blocks-{name}` | `spectra-blocks-aos-js` |
| AJAX action | `spectra_blocks_{name}` | `spectra_blocks_update_popup_status` |
| Nonce name | `spectra_blocks_{name}` | `spectra_blocks_popup_builder_admin_nonce` |
| Filter/action | `spectra_blocks_{name}` | `spectra_blocks_after_cache_purge` |
| Constants | `SPECTRA_BLOCKS_{NAME}` | `SPECTRA_BLOCKS_VER` |

## Testing

```bash
# PHP Unit Tests
./vendor/bin/phpunit                            # Full suite
./vendor/bin/phpunit --filter TestClassName     # Single test class

# E2E Tests (Playwright)
npx playwright test                             # Full suite
npx playwright test --ui                        # Interactive UI mode
npx playwright test tests/e2e/block-name        # Single test file
npx playwright codegen http://localhost:8888    # Record new test
```

## Environment Setup

> Local WP environment must be running before E2E tests. Recommended: `wp-env` or Local by Flywheel.
> Plugin requires WordPress 6.6+ and PHP 8.1+.
> SSH access to `github.com/brainstormforce` is required for `composer install`.

## Current Focus

> Update this section each sprint or feature cycle.
>
> - [ ] <!-- active task or feature being worked on -->
