# Getting Started

## A Developer's Guide to Spectra Blocks

Welcome to the Spectra Blocks team! This documentation covers everything you need to know about developing for both **Spectra Blocks** (free) and **Spectra Blocks Pro**.

Spectra Blocks is the next-generation rewrite (V3) of the original Spectra/UAG plugin. It is built from the ground up using modern WordPress APIs — the Interactivity API, block.json API v3, CSS custom properties, and PSR-4 autoloading.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Working with Gutenberg](#working-with-gutenberg)
- [Working with Spectra Blocks](#working-with-spectra-blocks)
- [File Structure — Spectra Blocks (Free)](#file-structure--spectra-blocks-free)
- [File Structure — Spectra Blocks Pro](#file-structure--spectra-blocks-pro)
- [Architecture & Key Concepts](#architecture--key-concepts)
- [Block Anatomy (V3)](#block-anatomy-v3)
- [Creating a Spectra Block](#creating-a-spectra-block)
- [Extensions](#extensions)
- [Dynamic CSS & Interactivity API](#dynamic-css--interactivity-api)
- [Admin Dashboard](#admin-dashboard)
- [Working with Spectra Blocks Pro](#working-with-spectra-blocks-pro)
- [Coding Standards](#coding-standards)
- [Build System](#build-system)
- [Testing](#testing)
- [Spectra SDLC](#spectra-sdlc)
- [FAQs](#faqs)

---

## Prerequisites

### Development Environment

- **Node.js** — v23.5.0 (use a version manager like nvm)
- **PHP** — 8.1+ (spectra-blocks), 7.4+ (spectra-blocks-pro)
- **WordPress** — 6.6+ (required for Interactivity API and nested block variations)
- **Composer** — for PHP dependencies (requires SSH access to BSF private repos)
- **Code Editor** — VS Code recommended (with ESLint, Stylelint, PHP Intelephense)
- **Local** — Local by Flywheel for WordPress development
- **Git** — GitHub Desktop or CLI; repos at `github.com/brainstormforce/`

### Languages & Technologies

| Category | Technologies |
|----------|-------------|
| **Base** | HTML, CSS, JavaScript (ES6+), PHP 8.1+ |
| **Frontend** | React, WordPress Interactivity API, Swiper, CountUp.js |
| **Styling** | SCSS, Tailwind CSS (admin), CSS Custom Properties |
| **State** | `@wordpress/data`, React Redux (admin) |
| **Build** | `@wordpress/scripts` (webpack), Grunt (release tasks) |
| **PHP** | WordPress Plugin API, PSR-4 autoloading, WPCS |
| **Testing** | PHPUnit, Playwright (E2E), PHPStan, ESLint, Stylelint, PHPCS |

### Required Knowledge

- WordPress block development (Block Editor Handbook)
- WordPress Interactivity API (`data-wp-interactive`, `data-wp-bind`, etc.)
- `register_block_type_from_metadata()` and `block.json` API v3
- WordPress hooks and filters
- React functional components with hooks
- Git branching and PR workflow

---

## Working with Gutenberg

If you're new to Gutenberg block development, start here:

1. **Plugin Development** — Read the official WordPress Plugin Developer Handbook
2. **Block Development** — Work through the Block Editor Handbook tutorials
3. **Interactivity API** — Study the Interactivity API Reference (this is core to V3)

### Key WordPress Packages Used

| Package | Purpose |
|---------|---------|
| `@wordpress/blocks` | Block registration |
| `@wordpress/block-editor` | Editor components (InspectorControls, BlockControls) |
| `@wordpress/components` | UI components (PanelBody, TextControl, etc.) |
| `@wordpress/data` | Data stores and selectors |
| `@wordpress/interactivity` | Frontend interactivity (V3 core) |
| `@wordpress/element` | React wrapper |
| `@wordpress/i18n` | Internationalization |
| `@wordpress/compose` | Higher-order components and hooks |

### Task: Create Your First Block

Before working on Spectra, create a standalone Gutenberg block plugin using `@wordpress/create-block`. Build one of:
- A closeable message block with SVG icon, editable message, and dismiss button
- A price card with title, description, image, and variant selectors
- An interactive user profile with media library picture and social media icons

---

## Working with Spectra Blocks

### Overview

**Spectra Blocks** is a fresh, standalone WordPress Gutenberg block plugin built on the V3 architecture. It is NOT a continuation of the legacy UAG plugin — it's a clean rewrite.

| Property | Value |
|----------|-------|
| **Plugin slug** | `spectra-blocks` |
| **Text domain** | `spectra-blocks` |
| **Block prefix** | `spectra/` (e.g., `spectra/container`) |
| **PHP namespace** | `Spectra\` |
| **Option prefix** | `spectra_blocks_` |
| **Constant prefix** | `SPECTRA_BLOCKS_` |
| **Requires** | PHP 8.1+, WordPress 6.6+ |

### The Settings Page

The admin dashboard is at **Dashboard > Settings > Spectra Blocks** (`wp-admin/admin.php?page=spectra-blocks`).

It consists of:
- **Blocks** — Enable/disable individual blocks
- **Settings** — Global configuration organized in tabs:
  - **Editor Options** — Templates button, on-page CSS, block conditions, copy-paste, panel collapse, visibility mode, quick action sidebar
  - **Performance** — Google Fonts loading (local/preload), asset generation, Font Awesome 5 toggle
  - **Block Settings** — Container global padding, elements gap, button theme inheritance, editor spacing
  - **Extensions** — Animations, Global Block Styles (GBS), responsive controls, masonry gallery
  - **Integrations** — Social login (Facebook/Google), reCAPTCHA v2/v3 keys, analytics opt-in
  - **Font Management** — Global FSE fonts

### The Editor

When editing a post/page:
- Spectra blocks appear in the block inserter under "Spectra Blocks" category
- Each block's settings panel has 3 tabs: **General**, **Style**, **Advanced**
- The `spectra/container` block replaces `core/group`, `core/columns`, and `core/cover` with a flexbox/grid layout system
- The `spectra/content` block replaces `core/heading` and `core/paragraph` using a `tagName` attribute (h1-h6, p, div, span)

### Blocks List

**Spectra Blocks (Free)** — 46 blocks:

| Category | Blocks |
|----------|--------|
| **Layout** | container |
| **Content** | content, separator, list (+ list-child-icon, list-child-item), icon (+ icons) |
| **Interactive** | accordion (+ 5 child blocks), tabs (+ 4 child blocks), modal (+ 7 child blocks), slider (+ slider-child) |
| **Data** | counter (+ 3 child blocks), countdown (+ 7 child blocks), google-map |
| **Navigation** | button, buttons |
| **Popup** | popup-builder |

**Spectra Blocks Pro** — 35 blocks:

| Category | Blocks |
|----------|--------|
| **Loop Builder** | loop-builder (+ 12 child blocks: template, filter, filter-button, filter-checkbox, filter-select, pagination + 3 children, search, sort, no-results, reset-all-button) |
| **Forms** | form-wrapper (+ 10 child blocks: input-field, input-label, input-wrapper, field-wrapper, button, checkbox, icon, link, message, recaptcha) |
| **User Auth** | login, register (+ 7 child blocks: username, email, password, confirm-password, first-name, last-name, terms-and-conditions) |
| **Countdown** | countdown-child-expiry-wrapper (extends free countdown) |

### Extensions

**Free Extensions** (in `src/extensions/`):
- `animations` — AOS-based scroll animations
- `image-mask` — SVG mask shapes for images
- `responsive-conditions` — Show/hide blocks per device
- `responsive-controls` — Per-device padding/margin/typography
- `sticky-container` — Sticky positioning for containers
- `video-popup-nudge` — Video popup promotion
- `z-index` — Z-index control per block

**Pro Extensions** (in spectra-blocks-pro `src/extensions/`):
- `animations` — Extended animation options
- `countdown` — Countdown expiry actions
- `dynamic-content` — Dynamic field bindings
- `global-styles` — Global Block Styles system
- `modal` — Extended modal functionality
- `popup-builder` — Extended popup triggers/conditions
- `responsive-controls` — Extended responsive options
- `slider` — Extended slider settings

---

## File Structure — Spectra Blocks (Free)

```
spectra-blocks/
├── spectra-blocks.php              # Main entry point — constants, version checks
├── spectra-blocks-init.php         # V3 bootstrap — loads Spectra\ classes
├── classes/                        # Infrastructure PHP (Spectra_Blocks_* prefix)
│   ├── class-spectra-blocks-loader.php         # Plugin loader, hooks, autoloader
│   ├── class-spectra-blocks-admin-helper.php   # Admin settings get/update, localization
│   ├── class-spectra-blocks-helper.php         # General utilities
│   ├── class-spectra-blocks-rest-api.php       # REST API field registrations
│   ├── class-spectra-blocks-settings.php       # Settings wrapper (spectra_blocks_ prefix)
│   ├── class-spectra-blocks-filesystem.php     # WP_Filesystem wrapper
│   └── class-spectra-blocks-security-helper.php # Security utilities
├── includes/                       # V3 PHP (Spectra\ namespace, PSR-4)
│   ├── autoload.php                # Composer classmap autoloader
│   ├── class-block-manager.php     # Block registration from build/blocks/**/block.json
│   ├── class-extension-manager.php # Extension initialization
│   ├── class-asset-loader.php      # CSS/JS enqueuing, inline styles
│   ├── class-font-manager.php      # Google Fonts, local fonts, FSE fonts
│   ├── class-analytics-manager.php # Block/extension usage tracking
│   ├── class-pattern-css-refactored.php # Pattern CSS generation
│   ├── utils.php                   # Utility functions
│   ├── Blocks/                     # Block-specific PHP
│   │   ├── class-popup-builder.php     # Popup post type, AJAX, scripts
│   │   ├── class-modal.php             # Modal block coordination
│   │   └── class-countdown.php         # Countdown REST endpoint
│   ├── Extensions/                 # Extension PHP
│   │   ├── ResponsiveControls/         # Responsive CSS attribute handling
│   │   └── Animations/                 # Animation asset loading
│   ├── Helpers/                    # Utility classes
│   │   ├── class-core.php              # Core helpers (CSS generation, escaping)
│   │   ├── class-html-sanitizer.php    # SVG/HTML sanitization (enshrined/svg-sanitize)
│   │   ├── class-renderer.php          # Block render helpers
│   │   └── class-svg-sprite.php        # SVG sprite management
│   ├── Analytics/                  # BSF Analytics integration
│   └── Traits/
│       └── Singleton.php               # Singleton trait used across all managers
├── src/                            # JS/SCSS source
│   ├── blocks/                     # 46 block implementations
│   │   └── {block-name}/              # See "Block Anatomy" section
│   ├── extensions/                 # 7 editor extensions
│   ├── helpers/                    # JS helpers
│   │   ├── plugin-config.js            # Plugin URL, RTL detection
│   │   ├── block-icons.js              # Block SVG icons
│   │   └── ...
│   └── components/                 # Shared editor components
├── admin/                          # Admin dashboard (separate build)
│   ├── class-admin-loader.php          # PHP: enqueue, localize, autoload
│   ├── inc/class-admin-helper.php      # Admin settings localization
│   ├── ajax/                           # AJAX handlers
│   │   ├── class-ajax-init.php             # AJAX event registration
│   │   ├── class-ajax-base.php             # Base class (nonce verification)
│   │   └── class-common-settings.php       # Settings save handlers
│   ├── api/                            # REST API endpoints
│   │   ├── class-api-init.php              # Route registration
│   │   └── class-common-settings.php       # REST settings controllers
│   ├── src/                            # React SPA source
│   │   ├── dashboard-app/                  # Dashboard pages
│   │   ├── store/                          # React Redux store
│   │   └── utils/                          # Utilities
│   ├── views/                          # PHP template wrappers
│   ├── webpack.config.js               # Admin webpack config
│   ├── tailwind.config.js              # Tailwind for admin UI
│   └── package.json                    # Admin dependencies
├── assets/                         # Static assets
│   ├── fonts/                          # Font Awesome, icon fonts
│   ├── images/                         # Block images
│   ├── icons/                          # SVG icon sprites
│   └── masks/                          # Image mask SVGs
├── lib/                            # Third-party libraries (via Composer)
│   ├── bsf-analytics/                  # Analytics opt-in UI
│   ├── zip-ai/                         # AI assistant integration
│   ├── gutenberg-templates/            # Template library
│   ├── astra-notices/                  # Admin notices
│   ├── nps-survey/                     # NPS survey
│   ├── utm-analytics/                  # UTM tracking
│   └── zipwp-images/                   # Image library
├── build/                          # Compiled output (git-ignored)
├── vendor/                         # Composer autoload (git-ignored)
├── languages/                      # Translation .pot files
├── composer.json                   # PHP dependencies
├── package.json                    # JS dependencies + build scripts
├── webpack.config.js               # Main webpack config
├── phpcs.xml.dist                  # PHPCS ruleset
├── phpunit.xml.dist                # PHPUnit config
└── Gruntfile.js                    # Release, i18n, version bump tasks
```

---

## File Structure — Spectra Blocks Pro

```
spectra-blocks-pro/
├── spectra-blocks-pro.php          # Main entry — constants, dependency checks
├── includes/                       # PHP (SpectraBlocksPro\ namespace)
│   ├── autoload.php                    # PSR-4 autoloader
│   ├── BlockManager.php                # Pro block registration
│   ├── ExtensionManager.php            # Pro extension initialization
│   ├── AssetLoader.php                 # Pro CSS/JS enqueuing
│   ├── AnalyticsManager.php            # Pro analytics tracking
│   ├── Helpers/
│   │   ├── register.php                    # User registration handler
│   │   ├── login.php                       # Login handler
│   │   ├── LoopBuilder.php                 # Loop/query builder logic
│   │   ├── BlockStyleManager.php           # Block style management
│   │   └── ColorConverter.php              # Color format conversion
│   ├── RestApi/
│   │   ├── RestApi.php                     # REST route registration
│   │   └── Controllers/                    # REST controllers
│   ├── Extensions/                     # Pro extension PHP
│   ├── Analytics/                      # Pro analytics
│   ├── Queries/                        # WP_Query builders
│   └── Utils/                          # Utility classes
├── src/                            # JS/SCSS source
│   ├── blocks/                     # 35 pro block implementations
│   ├── extensions/                 # 8 pro extensions
│   ├── helpers/                    # Pro JS helpers
│   └── components/                 # Pro editor components
├── build/                          # Compiled output (git-ignored)
├── composer.json                   # PHP dependencies
├── package.json                    # JS dependencies
├── Gruntfile.js                    # Release tasks
└── phpcs.xml.dist                  # PHPCS ruleset
```

### Pro ↔ Free Integration

Spectra Blocks Pro is **not standalone** — it requires Spectra Blocks (free) to be active.

**Initialization flow:**
1. Free loads at default priority via `spectra-blocks.php` → `class-spectra-blocks-loader.php`
2. Pro loads at `plugins_loaded` priority 20 via `spectra_blocks_pro_init()`
3. Pro checks `SPECTRA_BLOCKS_VER` constant exists and meets minimum version
4. Pro uses the `Spectra\Traits\Singleton` trait from free's `includes/Traits/`
5. Pro registers its own blocks under `spectra-pro/` prefix and `spectra-blocks-pro` category

**Shared resources:**
- Pro uses free's Singleton trait: `use Spectra\Traits\Singleton;`
- Pro extends free's block categories
- Pro hooks into free's asset loading pipeline
- Admin settings for pro features use free's admin infrastructure

---

## Architecture & Key Concepts

### Manager Pattern

Both free and pro use a manager pattern with the `Singleton` trait:

```php
namespace Spectra;
use Spectra\Traits\Singleton;

class BlockManager {
    use Singleton;

    public function init() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_filter( 'block_categories_all', array( $this, 'add_block_category' ) );
    }
}

// Usage:
( BlockManager::instance() )->init();
```

Key managers:
- `BlockManager` — Registers blocks from `build/blocks/**/block.json`
- `ExtensionManager` — Initializes editor extensions
- `AssetLoader` — Enqueues block CSS/JS, generates inline styles
- `FontManager` — Handles Google Fonts, local font hosting, FSE global fonts
- `AnalyticsManager` — Tracks block/extension usage (BSF Analytics)

### Infrastructure Classes (classes/)

These use a static `::init()` pattern (no Singleton):

```php
class Spectra_Blocks_Loader {
    public static function init() {
        // register hooks, load dependencies
    }
}
```

### Block Registration

All blocks are registered via `register_block_type_from_metadata()` from compiled `block.json` files:

```php
$block_files = glob( SPECTRA_BLOCKS_DIR . 'build/blocks/**/block.json' );
foreach ( $block_files as $block_file ) {
    register_block_type_from_metadata( $block_file );
}
```

This means each block's `block.json` is the single source of truth for:
- Block name, title, description, keywords, category
- Attributes with types and defaults
- Supports (border, color, spacing, typography, etc.)
- Script/style handles
- `viewScriptModule` for Interactivity API frontend scripts
- `render` for server-side PHP rendering (controller.php)

### Settings System

Settings use a wrapper class with `spectra_blocks_` prefix:

```php
// Get: returns option or default
Spectra_Blocks_Settings::get( 'file_generation' );
// Internally: get_option( 'spectra_blocks_file_generation' )

// Update
Spectra_Blocks_Settings::update( 'file_generation', 'enabled' );

// Delete
Spectra_Blocks_Settings::delete( 'file_generation' );
```

### Key wp_options Keys

| Option | Default | Purpose |
|--------|---------|---------|
| `spectra_blocks_enable_templates_button` | `yes` | Show templates in editor |
| `spectra_blocks_enable_on_page_css_button` | `yes` | On-page CSS generation |
| `spectra_blocks_enable_block_condition` | `disabled` | Block visibility conditions |
| `spectra_blocks_enable_masonry_gallery` | `enabled` | Masonry gallery feature |
| `spectra_blocks_enable_animations_extension` | `enabled` | AOS animations |
| `spectra_blocks_enable_gbs_extension` | `enabled` | Global Block Styles |
| `spectra_blocks_enable_block_responsive` | `enabled` | Responsive controls |
| `spectra_blocks_load_gfonts_locally` | `disabled` | Host Google Fonts locally |
| `spectra_blocks_container_global_padding` | `default` | Container default padding |
| `spectra_blocks_container_global_elements_gap` | `20` | Container elements gap |
| `spectra_blocks_analytics_optin` | `no` | Analytics opt-in |
| `spectra_blocks_select_font_globally` | `[]` | Global font selections |
| `spectra_blocks_visibility_mode` | `disabled` | Visibility mode |

### JS Globals

- `window.spectra_blocks_info` — Plugin config (url, ajax_url, rtl, version, settings)
- `window.spectraBlocksSvgIcons` — Full SVG icon library
- `window.spectraBlocksIconCategoryList` — Icon category mappings

In block JS, use `import { pluginUrl, isRTL } from '@spectra-config'` for typed access.

---

## Block Anatomy (V3)

Each block lives in `src/blocks/{block-name}/` and contains:

```
src/blocks/container/
├── block.json          # Block metadata (API v3) — name, attributes, supports
├── index.js            # Registration — registerBlockType(metadata.name, { icon, edit, save })
├── edit.js             # Editor component — <Settings> + <Render>
├── save.js             # Save function — static HTML output or null for dynamic
├── render.js           # Editor render component — block markup
├── settings.js         # InspectorControls — General/Style/Advanced tabs
├── controller.php      # Server-side render callback (dynamic blocks)
├── view.php            # Frontend PHP template (Interactivity API context)
├── style.scss          # Static styles (editor + frontend)
├── editor.scss         # Editor-only styles (optional)
├── toolbar.js          # BlockControls toolbar buttons (optional)
├── variations.js       # Block variations (optional, e.g., container layouts)
├── shapes.js           # JS shape definitions (optional, container-specific)
└── shapes.php          # PHP shape renderer (optional)
```

### block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "spectra/container",
    "version": "3.0.0",
    "title": "Container",
    "category": "spectra-blocks",
    "textdomain": "spectra-blocks",
    "supports": { ... },
    "attributes": { ... },
    "viewScriptModule": "file:./view.js",
    "render": "file:./controller.php"
}
```

### index.js

```js
import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';

registerBlockType( metadata.name, {
    icon: blockIcons.container(),
    edit,
    save,
} );
```

### edit.js

```js
import Settings from './settings';
import Render from './render';

export default function Edit( props ) {
    return (
        <>
            <Settings { ...props } />
            <Render { ...props } />
        </>
    );
}
```

### settings.js

Uses `InspectorControls` with tab-based layout:

```js
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

export default function Settings( { attributes, setAttributes } ) {
    return (
        <InspectorControls>
            {/* General Tab */}
            <PanelBody title="Layout">
                {/* Controls that call setAttributes() */}
            </PanelBody>
        </InspectorControls>
    );
}
```

### render.js (Editor Render)

```js
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Render( { attributes } ) {
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps( blockProps );
    return <div { ...innerBlocksProps } />;
}
```

### save.js

For **static blocks**, returns HTML markup. For **dynamic blocks**, returns `null`:

```js
// Static save
export default function Save( { attributes } ) {
    const blockProps = useBlockProps.save();
    return <div { ...blockProps }>{ /* markup */ }</div>;
}

// Dynamic save (rendered by controller.php)
export default function Save() {
    return null;
}
```

### controller.php (Server-Side Render)

For dynamic blocks, recreates the block markup in PHP:

```php
<?php
$block_id  = $attributes['block_id'] ?? '';
$classes   = 'spectra-container spectra-block-' . esc_attr( $block_id );
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes( array( 'class' => $classes ) ) ); ?>>
    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
```

### view.php (Interactivity API Frontend)

For blocks needing frontend interactivity (accordion, counter, tabs, etc.):

```php
<?php
wp_interactivity_state( 'spectra/accordion', array(
    'isOpen' => $attributes['defaultOpen'] ?? false,
) );
?>
<div
    data-wp-interactive="spectra/accordion"
    data-wp-context='<?php echo wp_json_encode( $context ); ?>'
    data-wp-bind--class="state.className"
    data-wp-on--click="actions.toggle"
>
    <?php echo $content; ?>
</div>
```

### Key Difference from Legacy (V2/UAG)

| Aspect | V2 (Legacy UAG) | V3 (Spectra Blocks) |
|--------|-----------------|---------------------|
| Registration | `registerBlockType('uagb/...')` with full config | `registerBlockType(metadata.name, { icon, edit, save })` — metadata from block.json |
| Frontend | styling.js → PHP `frontend.css.php` | CSS custom properties via `--spectra-{attr}` |
| Interactivity | jQuery/vanilla JS in `assets/js/` | WordPress Interactivity API (`data-wp-interactive`) |
| Block ID | `block_id` attribute, `.uagb-block-{id}` | `block_id` attribute, `.spectra-block-{id}` |
| PHP render | `blocks-config/{name}/class-uagb-{name}.php` | `src/blocks/{name}/controller.php` |
| Attributes | `attributes.js` + `attributes.php` duplicated | `block.json` single source of truth |
| PHP namespace | `UAGB_*` prefix (no namespace) | `Spectra\` namespace, PSR-4 |

---

## Creating a Spectra Block

### 1. Create Block Directory

Create a new folder in `src/blocks/` named with your block's slug (kebab-case):

```
src/blocks/my-block/
```

### 2. Create block.json

Define your block metadata:

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "spectra/my-block",
    "version": "3.0.0",
    "title": "My Block",
    "category": "spectra-blocks",
    "description": "Description of your block.",
    "textdomain": "spectra-blocks",
    "keywords": ["my", "block"],
    "supports": {
        "html": false,
        "anchor": true
    },
    "attributes": {
        "block_id": {
            "type": "string",
            "default": ""
        }
    }
}
```

### 3. Create Required JS Files

Create `index.js`, `edit.js`, `save.js`, `render.js`, `settings.js`, and `style.scss` following the patterns in the Block Anatomy section above.

### 4. Add Block Icon

Add your block's SVG icon to `src/helpers/block-icons.js`:

```js
myBlock: () => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        {/* SVG path */}
    </svg>
),
```

### 5. For Dynamic Blocks

If your block requires server-side rendering, create `controller.php` and reference it in block.json:

```json
{
    "render": "file:./controller.php"
}
```

For frontend interactivity, create `view.php` with Interactivity API directives and reference the view script module:

```json
{
    "viewScriptModule": "file:./view.js"
}
```

### 6. Build and Test

```bash
npm run start    # Dev mode with hot reload
npm run build    # Production build
```

Your block will automatically appear in the editor's block inserter under "Spectra Blocks".

---

## Extensions

Extensions add cross-cutting functionality to blocks (animations, responsive controls, etc.). They live in `src/extensions/{name}/`.

### Extension Structure

```
src/extensions/animations/
├── index.js            # Extension registration
├── editor.js           # Editor panel/controls
├── style.scss          # Styles
└── view.js             # Frontend Interactivity API (optional)
```

### Extension Registration (PHP)

Extensions are loaded by `ExtensionManager`:

```php
namespace Spectra;

class ExtensionManager {
    use Singleton;

    public function init() {
        // Loads extension PHP from includes/Extensions/
        // Registers extension scripts/styles
    }
}
```

### How Extensions Hook Into Blocks

Extensions use WordPress block filters to add attributes and controls to all compatible blocks:

1. **Attributes** — Added via `block.json` `supports` or `block_type_metadata` filter
2. **Editor UI** — Injected via `editor.BlockEdit` filter (Advanced tab)
3. **Frontend** — Applied via `render_block` filter or Interactivity API directives

### Backward Compatibility Note

Some extension attributes keep the `UAG` prefix for content compatibility with legacy saved posts:
- `UAGDisplayConditions`, `UAGUserRole`, `UAGBrowser`, `UAGSystem`, `UAGDay`
- `UAGHideDesktop`, `UAGHideMob`, `UAGHideTab`
- `UAGAnimationType`, `UAGAnimationTime`, `UAGAnimationDelay`, etc.

**Do NOT rename these** — they exist in saved post content across user sites.

---

## Dynamic CSS & Interactivity API

### CSS Custom Properties

V3 blocks use CSS custom properties instead of generating unique CSS per block:

```css
/* Block sets variables */
.spectra-block-abc123 {
    --spectra-padding-top: 20px;
    --spectra-font-size: 16px;
    --spectra-color: #333;
}

/* Styles consume variables */
.spectra-container {
    padding-top: var(--spectra-padding-top);
    font-size: var(--spectra-font-size);
    color: var(--spectra-color);
}
```

Variable naming: `--spectra-{attribute-name}`
Class naming: `spectra-{attribute-name}`

### Interactivity API Usage

Blocks with frontend behavior use the WordPress Interactivity API:

```js
// view.js
import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'spectra/accordion', {
    state: {
        get isOpen() {
            return getContext().isOpen;
        },
    },
    actions: {
        toggle() {
            const ctx = getContext();
            ctx.isOpen = ! ctx.isOpen;
        },
    },
} );
```

Blocks using Interactivity API: accordion, counter, countdown, tabs, modal, popup-builder, slider.

---

## Admin Dashboard

### Architecture

The admin dashboard is a React SPA in `admin/`:

```
admin/
├── src/
│   ├── dashboard-app/          # Main React app
│   │   └── pages/              # Route pages (blocks, settings, welcome)
│   ├── store/                  # React Redux (globalDataStore, globalDataReducer)
│   └── utils/                  # Initial state from localized PHP data
├── ajax/                       # AJAX handlers for settings save
├── api/                        # REST API endpoints
└── inc/                        # PHP: enqueue scripts, localize data
```

### Adding a New Admin Setting

1. **PHP** — Add to `get_common_settings()` in `admin/inc/class-admin-helper.php`:
   ```php
   'spectra_blocks_my_option' => self::get_admin_settings_option( 'spectra_blocks_my_option', 'default' ),
   ```

2. **Redux Store** — Add initial state in `admin/src/store/globalDataStore.js`

3. **Redux Reducer** — Add case in `admin/src/store/globalDataReducer.js`

4. **AJAX Handler** — Add save method in `admin/ajax/class-common-settings.php`

5. **React UI** — Create component in `admin/src/dashboard-app/pages/settings/`

### REST API Endpoints

Namespace: `spectra-blocks/v1`

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/spectra-blocks/v1/common-settings` | GET/POST | Read/update admin settings |

### AJAX Handlers

Prefix: `spectra_blocks_`

| Action | Purpose |
|--------|---------|
| `spectra_blocks_update_popup_status` | Toggle popup active/inactive |
| `spectra_blocks_recaptcha_*` | reCAPTCHA key management |
| `spectra_blocks_social` | Social login settings |
| `spectra_blocks_fse_font_globally` | Global FSE font management |

All AJAX handlers:
1. Verify nonce via `check_ajax_referer()`
2. Check `current_user_can( 'manage_options' )`
3. Sanitize input
4. Return via `wp_send_json_success()` / `wp_send_json_error()`

---

## Working with Spectra Blocks Pro

### Overview

Spectra Blocks Pro extends the free plugin with premium blocks and extensions. It uses the `SpectraBlocksPro\` namespace and its own `build/blocks/` output directory.

| Property | Value |
|----------|-------|
| **Plugin slug** | `spectra-blocks-pro` |
| **Text domain** | `spectra-blocks-pro` |
| **Block prefix** | `spectra-pro/` |
| **PHP namespace** | `SpectraBlocksPro\` |
| **Constant prefix** | `SPECTRA_BLOCKS_PRO_` |
| **Requires** | PHP 7.4+, WordPress 6.6+, Spectra Blocks (free) active |

### Dependency on Free

Pro checks for the free plugin at `plugins_loaded` priority 20:

```php
function spectra_blocks_pro_init() {
    if ( ! defined( 'SPECTRA_BLOCKS_VER' ) ) {
        // Show "install/activate Spectra Blocks" notice
        return;
    }
    if ( version_compare( SPECTRA_BLOCKS_VER, SPECTRA_BLOCKS_PRO_REQUIRED_SPECTRA_BLOCKS_VER, '<' ) ) {
        // Show "update Spectra Blocks" notice
        return;
    }
    // Initialize pro managers...
}
```

### Creating a Pro Block

Pro block development follows the same V3 pattern as free blocks. Key differences:

- Block directory: `spectra-blocks-pro/src/blocks/{name}/`
- Block name prefix: `spectra-pro/{name}` (e.g., `spectra-pro/loop-builder`)
- Text domain: `spectra-blocks-pro`
- Category: `spectra-blocks-pro`
- PHP namespace: `SpectraBlocksPro\`

### Pro-Specific Features

**Loop Builder** — WP_Query-based block for displaying post lists with filtering, pagination, and search. Uses `SpectraBlocksPro\Helpers\LoopBuilder` for query construction and `SpectraBlocksPro\Queries\` for query builders.

**User Registration** — Complete registration form with customizable fields. Uses `SpectraBlocksPro\Helpers\register.php` with role validation and forbidden role checking.

**Login** — Authentication form with redirect handling. Uses `SpectraBlocksPro\Helpers\login.php`.

**Dynamic Content** — Binds block attributes to dynamic WordPress data (post fields, custom fields, etc.). Implemented as a pro extension.

**Global Block Styles** — Reusable style presets applied across blocks. Uses `SpectraBlocksPro\Helpers\BlockStyleManager`.

### Pro REST API

Namespace: `spectra/pro/v2/`

Registered in `SpectraBlocksPro\RestApi\RestApi::init()` with controllers in `includes/RestApi/Controllers/`.

---

## Coding Standards

### Git

- **Branches:** `feat/block-name`, `fix/issue-description`, `chore/task-name`
- **Base branches:** `dev` for bug fixes, `next-release` for new features
- **Commits:** Frequent, descriptive messages in imperative mood
- **Build before PR:** Always run `npm run build` and test before creating a PR

### PHP

- Follow **WordPress PHP Coding Standards (WPCS)**
- Tabs for indentation
- Yoda conditions: `if ( 'value' === $variable )`
- Spaces inside parentheses: `if ( $condition )`, `function_name( $param )`
- Single quotes for strings (unless interpolation needed)
- Snake_case for variables and functions
- Open `<?php` tag, no closing `?>` tag
- All PHP files start with `defined( 'ABSPATH' ) || exit;`
- `@since x.x.x` placeholder for new code
- PHPCS: `composer lint` / `composer lint:fix`
- PHPStan for static analysis

### JavaScript

- ES6+ syntax, camelCase naming
- Functional components with hooks (no class components)
- Double quotes for JSX attributes, single quotes for other strings
- Trailing commas in arrays, objects, function parameters
- Import from `@wordpress/*` packages (never directly from React)
- All user-facing strings wrapped with `__()` or `_n()` from `@wordpress/i18n`
- ESLint: `npm run lint:js`

### CSS / SCSS

- BEM naming convention: `.spectra-block__element--modifier`
- CSS custom properties: `--spectra-{name}`
- Avoid `!important` — use selector specificity instead
- Stylelint: `npm run lint:css`
- Tailwind CSS used only in admin dashboard (not block frontend)

### File Naming

- Block directories: kebab-case (`my-block`)
- PHP files: `class-{name}.php` for classes, kebab-case for others
- JS files: camelCase for React components, kebab-case for utilities
- SCSS files: kebab-case matching their JS counterpart

### Naming Reference

| Type | Free Convention | Pro Convention |
|------|----------------|----------------|
| Block name | `spectra/{name}` | `spectra-pro/{name}` |
| Option key | `spectra_blocks_{name}` | — |
| PHP namespace | `Spectra\` | `SpectraBlocksPro\` |
| PHP class prefix | `Spectra_Blocks_` (classes/) | — |
| AJAX action | `spectra_blocks_{name}` | — |
| REST namespace | `spectra-blocks/v1` | `spectra/pro/v2/` |
| Script handle | `spectra-blocks-{name}` | `spectra-blocks-pro-{name}` |
| Constants | `SPECTRA_BLOCKS_{NAME}` | `SPECTRA_BLOCKS_PRO_{NAME}` |
| Text domain | `spectra-blocks` | `spectra-blocks-pro` |

---

## Build System

### Overview

Both plugins use `@wordpress/scripts` (webpack) for JS/CSS builds and Grunt for release tasks.

### Daily Development

```bash
# Spectra Blocks (free)
cd spectra-blocks/
npm install
npm run start              # Dev watch mode with hot reload

# Spectra Blocks Pro
cd spectra-blocks-pro/
npm install
npm run start              # Dev watch mode

# Admin Dashboard (separate build)
cd spectra-blocks/admin/
npm install
npm run start
```

### Production Build

```bash
npm run build              # Build blocks to build/ directory
npm run build:fresh        # Clean build/ first, then build
```

### Webpack Configuration

- Entry points auto-discovered from `src/blocks/**/index.js`
- `block.json` and PHP files copied via `--webpack-copy-php`
- Aliases: `@spectra-helpers`, `@spectra-config`, `@spectra-components`
- Output: `build/blocks/{block-name}/` with index.js, style-index.css, view.js, block.json, controller.php

### Grunt Tasks

```bash
grunt release              # Clean → copy → compress → cleanup (creates .zip)
grunt release-no-clean     # Copy without cleanup (for debugging)
grunt readme               # Convert readme.txt → README.md
grunt i18n                 # Add textdomain + generate .pot file
grunt textdomain           # Add textdomain to PHP files
grunt bump-version --ver=X.Y.Z  # Bump version in all files
```

### NPM Script Shortcuts

```bash
npm run release            # → grunt release
npm run bump-version       # → grunt bump-version
npm run readme             # → grunt readme
npm run makepot            # → grunt i18n
npm run zip                # → bash bin/create-zip.sh
```

### Composer

```bash
composer install           # Install PHP dependencies (requires BSF SSH access)
composer lint              # Run PHPCS
composer lint:fix          # Auto-fix PHPCS issues
```

**Important:** BSF shared libraries are private repos requiring SSH access:
- `git@github.com:brainstormforce/bsf-analytics.git`
- `git@github.com:brainstormforce/zip-ai.git`
- `git@github.com:brainstormforce/gutenberg-templates.git`
- etc.

They install to `lib/` (not `vendor/`) via Composer's `installer-paths`.

---

## Testing

### Code Quality

```bash
# JavaScript
npm run lint:js            # ESLint
npm run lint:css           # Stylelint
npm run format             # Prettier formatting

# PHP
composer lint              # PHPCS with WPCS rules
composer lint:fix          # Auto-fix PHPCS issues
```

### PHPUnit

```bash
./vendor/bin/phpunit                           # Full test suite
./vendor/bin/phpunit --filter TestClassName    # Single test class
```

Config: `phpunit.xml.dist`

### E2E Tests (Playwright)

```bash
npx playwright test                    # Full suite
npx playwright test --ui               # Interactive UI mode
npx playwright test tests/e2e/block    # Specific test
npx playwright codegen http://localhost:8888  # Record new test
```

Config: `playwright.config.js`

### Pre-PR Checklist

Before creating a pull request:

1. `npm run lint:js` — fix all JS lint errors
2. `npm run lint:css` — fix all CSS lint errors
3. `composer lint` — fix all PHP lint errors
4. `npm run build` — ensure clean build
5. Test block in editor (all settings, all devices)
6. Test block on frontend (markup, styles, interactivity)
7. Test with Spectra Pro active AND inactive (free blocks)
8. Check browser console for JS errors

---

## Spectra SDLC

### Agile Process

Spectra follows **Scrum** with 2-week sprints. Project management is done in Infinity.

### Task Types

| Type | Description | Branch Prefix |
|------|-------------|--------------|
| **New** | New block or major feature | `feat/` |
| **Fix** | Bug fix | `fix/` |
| **Feat** | New feature for existing block | `feat/` |
| **Improvement** | Enhancement to existing code | `chore/` or `feat/` |

### Stages

1. **To Do** — Created, assigned, not started
2. **WIP** — Active development
3. **In Review** — PR created, code review in progress
4. **In QA** — Quality assurance testing
5. **Ready to Merge** — Passed review and QA
6. **Done** — Merged to target branch

### Pull Requests

When creating a PR:

1. Build your files: `npm run build`
2. Pull latest from target branch and resolve conflicts
3. Run all linters and fix errors
4. Create PR with:
   - **Description** — What changed and why
   - **Test cases** — Link to test documentation
   - **Labels** — bug, enhancement, needs review, etc.
   - **Reviewers** — As assigned by your manager
5. Update the Infinity task accordingly

### Sprint Reviews

Every 2 weeks. Prepare by:
- Building your branch (`npm run build`)
- Having a demo-ready local site
- Preparing a summary of completed and upcoming work

### Communication

- **Slack:** `#spectra-discussion` — All Spectra communication
- **Daily Scrum:** Share yesterday's progress, today's goals
- **Scrum goals:** Send to designated team member after each scrum

---

## FAQs

**What is a block's slug?**
The kebab-case identifier used in the block name. For `spectra/social-profile`, the slug is `social-profile`.

**How do I access admin settings in my block?**
Settings are localized to `window.spectra_blocks_info` via `class-spectra-blocks-admin-helper.php`. Use `import { pluginUrl } from '@spectra-config'` for typed access.

**When should I use a dynamic block vs static?**
Use **static** when the frontend markup is fully determined at save time. Use **dynamic** (controller.php + `save() => null`) when the block needs server-side data (e.g., latest posts, user info) or Interactivity API behavior.

**How do child blocks work?**
Parent-child relationships are defined in `block.json` via `parent` attribute. For example, `spectra/accordion-child-item` has `"parent": ["spectra/accordion"]`. Child block categories use `spectra-blocks-inner`.

**How do I add a block preview image to the admin?**
Add a `.webp` image to `admin/assets/block-previews/{block-name}.webp`. It appears automatically on the Blocks admin page.

**Where does SVG sanitization happen?**
`includes/Helpers/class-html-sanitizer.php` uses the `enshrined/svg-sanitize` library. SVG uploads require the `upload_files` capability. Script tags are gated by `unfiltered_html` capability.

**How do I test with Pro active?**
Ensure both plugins are in `wp-content/plugins/`, both activated. Pro blocks only appear when Pro is active. Free blocks should work identically with or without Pro.

**What's the difference between `classes/` and `includes/`?**
`classes/` contains infrastructure code using the `Spectra_Blocks_` prefix (no namespace). `includes/` contains V3 code using the `Spectra\` namespace with PSR-4 autoloading. New code should go in `includes/`.
