# Global Block Styles — Architecture & Developer Reference

> **Purpose.** The canonical reference for every developer working on GBS. Covers
> storage, CSS generation, REST API, live paint, and ERA integration. Read this before
> touching any `GlobalStyles` file — free or pro — so new work follows the established
> path instead of carving a parallel one.
>
> **Scope.** The entire GBS stack: free plugin backend + editor, Pro UI overlay.
> For class-name validation rules specifically, see
> [`GLOBAL-STYLES-CLASS-NAME-RULES.md`](./GLOBAL-STYLES-CLASS-NAME-RULES.md).
> For the SaaS-side generation + routing contract, see
> `zipwp-credits-saas/docs/STYLE-SYSTEM.md`.
>
> **Last verified against code:** 2026-06-23

---

## Table of Contents

1. [Architecture at a Glance](#1-architecture-at-a-glance)
2. [Storage Model](#2-storage-model)
3. [CSS Pipeline (Frontend)](#3-css-pipeline-frontend)
4. [CSS Pipeline (Editor Live Paint)](#4-css-pipeline-editor-live-paint)
5. [REST API Surface](#5-rest-api-surface)
6. [Free ↔ Pro Coordination](#6-free--pro-coordination)
7. [Specificity Design](#7-specificity-design)
8. [ERA & Page-Scoped CSS](#8-era--page-scoped-css)
9. [Block Attributes (Pro)](#9-block-attributes-pro)
10. [Block Code Editor (BCE)](#10-block-code-editor-bce)
11. [Extending GBS — the Right Way](#11-extending-gbs--the-right-way)
12. [Anti-Patterns — What NOT to Do](#12-anti-patterns--what-not-to-do)
13. [File Map](#13-file-map)

---

## 1. Architecture at a Glance

GBS is split across two plugins that share a single option as their source of truth.

```
spectra-blocks (free)              spectra-blocks-pro
──────────────────────────         ──────────────────────────────────────
PHP Engine & REST API              PHP Extension (overrides free enqueue)
  class-engine.php                   includes/Extensions/GlobalStyles.php
  class-rest-controller.php
  class-gen-css-renderer.php       JS Editor UI
  class-jit-compiler.php             src/extensions/global-styles/
  class-class-registry.php            controls.js   (inspector panel)
  class-jit-cache.php                 options.js    (dropdown data)
  class-sanitizer.php                 sidebar.js    (Pro sidebar sections)
  class-gen-css-orphan-stripper.php   global-modal.js (modal mount)
                                      components/BlockCodeFlyout.jsx
JS Editor (GBS Editor Modal)
  src/extensions/gbs-editor/
    index.js          (plugin entry, window.__spectraGBSEditor API)
    utils/liveVars.js (live <style> injection)
    hooks/            (useCustomClasses, useGlobalCSS, useKeyframes)
    components/       (GBSEditorModal, ClassFlyout)
```

**Single SSOT option:** `spectra_blocks_pro_gs_user_css`
Both free and Pro read/write this key. Never introduce a second option for
user-authored CSS — that splits the source of truth and breaks live paint,
import merge, and JIT cache.

**CSS is never stored as literal CSS.** All user data is stored as structured
payloads (schema-v1 — see §2) and compiled on demand. The renderers are pure
functions of (payload, context); they never write to disk.

---

## 2. Storage Model

### 2.1 The Schema-v1 Payload

Everything lives in one PHP array keyed in `spectra_blocks_pro_gs_user_css`:

```php
[
    'v'             => '1',          // Version marker — always '1' for schema-v1
    'classes'       => [             // User-authored named classes
        'my-class' => [
            'default' => [ 'color' => '#111', 'font-size' => '1rem' ],
            'hover'   => [ 'color' => '#000' ],
            'sm'      => [ 'gap'   => '1rem' ],   // responsive variant
            'md_hover'=> [ 'opacity' => '0.8' ],  // compound: md + hover
        ],
    ],
    'keyframes'     => [             // User @keyframes definitions
        'fadeUp' => [ 'css' => '0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}' ],
    ],
    'rootStyles'    => [             // Body-level declarations (typography, CSS vars)
        'font-family'  => 'DM Sans, sans-serif',
        '--brand-color'=> '#b36b2c',
    ],
    'wrapperStyles' => [             // Arbitrary selector → declarations (for elements you don't own)
        '.wp-block-button__link' => [ 'border-radius' => '4px' ],
    ],
    'scopeVars'     => [             // Overrides WordPress layout variables
        '--wp--style--global--content-size' => '1164px',
    ],
    'presetLock'    => [             // Pins WP color preset CSS variables
        '--wp--preset--color--primary' => '#b36b2c',
    ],
    'imports'       => [             // Web font @import URLs (must be first in stylesheet)
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700',
    ],
    'mediaQuery'    => [             // Any bucket, wrapped in a media query
        '(max-width: 960px)' => [
            'classes' => [ 'hero' => [ 'default' => [ 'font-size' => '2rem' ] ] ],
        ],
    ],
]
```

**Rules:**
- Add new top-level buckets only if they have a distinct render semantic that
  `wrapperStyles` cannot express. Every new bucket requires a renderer update in
  `GenCssRenderer`, an enqueue handle in `Engine`, and a live-paint call in `liveVars.js`.
- Never store literal CSS strings in `classes` values — properties and values only.
  Raw CSS belongs in `wrapperStyles` (keyed by selector) or `keyframes`.
- The `v` field is reserved for schema migration. Do not remove it.

### 2.2 Per-Page Post Meta

Page-scoped payloads use the **same schema-v1 shape** stored as post meta under
the key `spectra_gs_classes` (constant `GenCssOrphanStripper::META_KEY`).

Per-page and site-wide payloads are **independent**. `Engine::enqueue_gen_custom_css_for_current_post()`
enqueues the per-page CSS on top of the site-wide CSS. When a class with the same
name exists in both, the per-page entry wins via cascade (later in source order).

### 2.3 Never-Clobber Merge Rule

All import write paths (`/bulk`, `/sitewide`, `/save`) apply a **merge, not replace**:

- `classes` and `keyframes`: entry-level merge — new entry wins on collision,
  existing entries not in the import are kept.
- `rootStyles`, `wrapperStyles`, `scopeVars`, `presetLock`, `mediaQuery`:
  entry-level merge — `null` value deletes the entry.
- `imports`: union + dedup.
- `replace: true` flag (POST `/save`) is the only way to hard-replace a bucket.

**Do not bypass this.** Direct `update_option()` on the GBS key from anywhere other
than `RestController::save_user_css()` breaks the merge contract and will clobber
in-progress SaaS builds on multi-page sites.

---

## 3. CSS Pipeline (Frontend)

```
Page request
  └── wp_enqueue_scripts (priority PHP_INT_MAX)
        Engine::pin_styles_after_theme_globals()
  └── enqueue_block_assets (priority 99)
        ├── Engine::enqueue_stylesheet()
        │     → <style id="spectra-gs-utility-classes">
        │         @layer utilities { /* ClassRegistry: Tailwind-parity classes */ }
        │       </style>
        │     NOTE: when Pro is active this is skipped (Pro owns the stylesheet)
        │
        ├── Engine::enqueue_jit_for_current_post()
        │     → <style id="spectra-gs-dynamic-styles">
        │         @layer utilities { /* JIT-compiled tokens used on this post */ }
        │       </style>
        │
        ├── Engine::enqueue_gen_custom_css_for_current_post()
        │     → <style id="spectra-gen-custom-css-{post_id}">
        │         /* per-page post meta: imports, rootStyles, classes, wrapperStyles… */
        │       </style>
        │
        └── Engine::enqueue_gen_sitewide_css()
              → <style id="spectra-gen-sitewide-css">
                  /* site-wide non-class buckets: imports, rootStyles, scopeVars… */
                </style>
```

**Source-order matters.** `pin_styles_after_theme_globals()` runs last to ensure
`spectra-gs-*` handles are enqueued after any theme `global-styles` handle, giving
GBS rules cascade priority over theme defaults at equal specificity.

**JIT cache.** `JitCache::rebuild(post_id)` runs on `save_post` (priority 20) to
pre-compile per-post JIT CSS. Version-bumped whenever a class or Style Guide config
is saved, so stale caches are never served.

---

## 4. CSS Pipeline (Editor Live Paint)

Live paint lets CSS changes appear instantly in the canvas without a page reload.
The pipeline lives in `src/extensions/gbs-editor/utils/liveVars.js`.

```
User action in GBS modal
  └── REST write (or local state update)
        └── liveVars call
              ├── injectStyleSheet(id, css)
              │     Inserts or replaces <style id="{id}"> in:
              │       1. host document (document.head)
              │       2. editor iframe (iframe[name="editor-canvas"].contentDocument.head)
              │     Both targets must be updated — styles in host don't reach iframe.
              │
              ├── refreshComputedCSS()
              │     Re-fetches computed token vars from Pro AJAX, injects as
              │     <style id="spectra-gbs-live-color-vars">
              │
              ├── refreshCustomVarsCSS()
              │     Re-injects :root{} custom vars as
              │     <style id="spectra-gbs-live-custom-vars">
              │
              ├── regenerateEditorCSS()
              │     Calls Pro AJAX to get fresh user-classes stylesheet, injects as
              │     <style id="spectra-gs-dynamic-user-classes">
              │
              └── regeneratePageCSS(postId)
                    Calls REST /render (pure, no DB write) with current payload,
                    injects as <style id="spectra-gen-custom-css-{postId}">
```

**Invariant:** `injectStyleSheet` always writes to **both** host and iframe.
If you add a new live-paint call and only write to one, styles will appear to work
in some editor modes and silently fail in others (iframe canvas vs. classic canvas).

**`/render` is pure.** It takes a payload and returns CSS — no DB side effects.
Use it for preview/live paint. Use `/save` or `/custom-classes` for persistence.
Never call a persistence endpoint for live-paint-only updates; it will fire the
JIT cache rebuild unnecessarily.

---

## 5. REST API Surface

All routes are under `spectra-blocks/v1/global-styles/`.
Auth: `edit_theme_options` except `/jit-compile` which requires `edit_posts`.

| Route | Method | Purpose |
|-------|--------|---------|
| `/custom-classes` | GET | Fetch site-wide + page-scoped classes |
| `/custom-classes` | POST | Create, update, or delete a single class |
| `/keyframes` | GET | Fetch all user keyframes |
| `/keyframes` | POST | Create, update, or delete a keyframe |
| `/metadata` | GET | Export utility grammar contract (ETag-cached) |
| `/bulk` | POST | Atomic write: classes + keyframes (reports skipped entries) |
| `/sitewide` | POST | Merge a full schema-v1 import payload into the site-wide option |
| `/jit-compile` | POST | Compile utility token strings to CSS (no persistence) |
| `/render` | POST | Render a payload to CSS (pure, no persistence) |
| `/user-css` | GET | Return the entire schema-v1 option |
| `/save` | GET / POST | Unified read/write; supports `scope=page\|global` + `post_id` |
| `/system-sizes` | GET / POST | System spacing/border/fontsize presets (Pro values) |

### When to use which write route

| You want to… | Use |
|---|---|
| Add/edit/delete one custom class via the GBS editor UI | `/custom-classes` POST |
| Import a full page payload (ERA / SaaS) | `/save` POST with `scope=page`, `post_id` |
| Import site-wide styles (SaaS build) | `/sitewide` POST or `/save` POST with `scope=global` |
| Atomically write many classes at once (SaaS batch) | `/bulk` POST |
| Preview CSS without saving | `/render` POST |
| Compile JIT tokens for live-inserted blocks | `/jit-compile` POST |

**Never call `update_option()` directly.** Go through the REST layer so that
sanitization, merge, and JIT cache invalidation all fire correctly.

---

## 6. Free ↔ Pro Coordination

### 6.1 Stylesheet Ownership

Free's `Engine::init()` checks `class_exists('\\SpectraBlocksPro\\Extensions\\GlobalStyles')`.
When Pro is active, free **skips** `enqueue_stylesheet()` and yields the utility-class
stylesheet to Pro's extension. Pro then enqueues a stylesheet that combines the
utility catalog + user classes + system variables.

**Rule:** If you add a new enqueue to free's `Engine`, guard it with the same
`class_exists` check if Pro must be able to override it.

### 6.2 Shared Option Key

Both plugins read/write `spectra_blocks_pro_gs_user_css`. This is intentional.
Free's REST layer is the write surface; Pro reads the same data for its CSS
generation pass.

**Do not introduce a second option** for user CSS in either plugin. Any data
that is "Pro-only but needs to be part of the global CSS" still belongs in the
same schema-v1 option under a new top-level bucket.

### 6.3 Config Save Hook

Pro hooks `spectra_style_guide_config_before_save` to merge its own palette fields
(chromatics 4–7, neutral tint, typography pair, presets) before free's config
endpoint writes. When adding Pro-only Style Guide fields:

1. Handle them in `GlobalStyles.php` on `spectra_style_guide_config_before_save`.
2. Do NOT add them to free's config schema or REST handler.

### 6.4 JIT Cache Invalidation

`Engine` bumps `spectra_blocks_gs_jit_version` on every option write and fires the
`spectra_style_guide_config_saved` action. Pro listens on this action to invalidate
its own computed CSS.

If you add a new storage key that affects rendered CSS, hook into this action to
invalidate, rather than adding a separate cache buster.

---

## 7. Specificity Design

The cascade is intentional and should not be changed without a cross-team review.

| Layer | Selector shape | Specificity | Wins over |
|---|---|---|---|
| Block attribute CSS (from ResponsiveControls) | `.wp-block[data-spectra-id].wp-block.wp-block` | (0,4,0) | Everything below |
| GBS custom classes (user-authored) | `[class].{name}.{name}` (frontend) / `.editor-styles-wrapper .{name}.{name}` (editor) | (0,3,0) | Block defaults |
| GBS utility classes (ClassRegistry) | `:root .{class}` | (0,2,0) | Block base styles |
| Block defaults (Pro) | `.wp-block-spectra-{name}` | (0,1,0) | Nothing from GBS |

**The `.{name}.{name}` repetition is deliberate.** Repeating the class name twice
lifts specificity to (0,3,0) without requiring a wrapper or `!important`. Do not
simplify it to `.{name}` — that breaks the cascade contract and user-class CSS will
stop overriding utility classes.

**Utility classes go in `@layer utilities`.** The `@layer` declaration sits in
`spectra-gs-utility-classes`. This means any unlayered CSS (theme or user) beats
utility classes regardless of specificity, which is the correct Tailwind-parity
behavior. Do not move utility classes outside the layer.

---

## 8. ERA & Page-Scoped CSS

ERA (Edit Ryte with AI — the SaaS builder) writes page-specific CSS to post meta
rather than to the site-wide option, so edits to one page don't affect others.

### Write path

```
ERA sends:
  POST /spectra-blocks/v1/global-styles/save
       { scope: 'page', post_id: 123, payload: { … schema-v1 … } }

RestController::save()
  if post_id > 0:
    read_page_payload(post_id)  ← get_post_meta(post_id, META_KEY)
    merge incoming payload
    update_post_meta(post_id, META_KEY, merged_payload)
  else:
    site-wide merge path
```

### Read / enqueue path

```
Frontend page load (post_id = 123):
  Engine::enqueue_gen_custom_css_for_current_post(123)
    → GenCssRenderer::render(page_payload, post_id=123, is_editor=false)
    → <style id="spectra-gen-custom-css-123"> … </style>
```

### Live paint in editor

```
ERA sends live update:
  useGlobalCSS → POST /render { payload, post_id: 123 }
    → GenCssRenderer::render(payload, 123, is_editor=true)
  liveVars.injectStyleSheet('spectra-gen-custom-css-123', css)
```

**Rules:**
- Always pass `post_id` when the operation is page-specific.
- Use `/save` with `scope=page` for persistence; use `/render` for live paint only.
- Page-scoped CSS is **additive** to site-wide CSS, not a replacement.
  Never use page-scoped writes to override site-wide variables — use the
  `scopeVars` bucket in the site-wide payload instead.

---

## 9. Block Attributes (Pro)

Pro extends every allowed block with four attributes added by `extendBlockAttributes()`:

| Attribute | Type | Purpose |
|---|---|---|
| `spectraGSClasses` | `string[]` | Class names from the GBS registry applied to this block |
| `spectraCustomCSS` | `string` | Raw CSS scoped to this block instance |
| `spectraCustomJS` | `string` | Inline JS executed in this block's context |
| `spectraBCEId` | `string` | Stable reference for the Block Code Editor flyout |

`spectraGSClasses` is mirrored into WP's standard `className` attribute so that
the classes appear on the block wrapper in both editor and frontend. **Do not apply
`spectraGSClasses` a second time via a `render_block` filter** — they are already
on `className` and will double-render.

`spectraBCEId` is set once (UUID) when the BCE flyout is first opened and never
changes, even if `spectraCustomCSS` is cleared. It is used to scope the live-preview
`<style>` tag in the editor. Do not generate it from block `clientId` — `clientId`
changes across sessions.

---

## 10. Block Code Editor (BCE)

BCE is the DevTools-style flyout (bottom of the editor) for per-block custom CSS/JS.

### Save flow

```
User types CSS/JS
  → handleCSSChange / handleJSChange (immediate setIsDirty)
  → debounced (400 ms) setAttributes({ spectraCustomCSS, spectraCustomJS })

User clicks Save
  → dispatch('core/editor').savePost()   ← correct store
  → setIsDirty(false), setJustSaved(true) for 2 s
```

**Do not call `dispatch('core').savePost()`** — that store does not expose `savePost`.
The correct call is `dispatch('core/editor').savePost()`.

### Live preview in editor

`addGSControls` HOC has a `useEffect` that watches `spectraCustomCSS` and
`spectraBCEId`. On every change it injects a `<style id="spectra-bce-{id}-preview">`
into both host and iframe:

```javascript
const rule = css ? `.spectra-bce-${bceId}{${css}}` : '';
injectInto(document);
injectInto(iframe?.contentDocument);
```

The `useEffect` must be placed **before any early returns** (Rules of Hooks).

### Dirty-state rules

- `isDirty` is set on the **first keystroke**, not after the debounce.
- `isSaving` is set for the duration of `savePost()`.
- `justSaved` is set for 2 s after a successful save, then cleared.
- The Save button is blue when `isDirty && !isSaving`, green when `justSaved`.
- If `savePost()` throws, keep `isDirty = true` so the user can retry.

---

## 11. Extending GBS — the Right Way

### Adding a new class property (state variant)

1. Add the variant key to `RestController::ALLOWED_STATES`.
2. Add the CSS selector logic to `GenCssRenderer::render_class_declarations()`.
3. Add the variant to the `useCustomClasses` hook's state tab list.
4. Update `/metadata` contract if SaaS needs to know about it.

### Adding a new schema-v1 bucket

1. Add a renderer in `GenCssRenderer::render()` with a named constant for the
   render order (imports must stay first).
2. Add an enqueue call in `Engine` with a new style handle (`spectra-gen-{name}`).
3. Add the live-paint call in `liveVars.js` for the new bucket.
4. Add merge logic in `RestController::merge_user_css()` (entry-level merge,
   null-deletes).
5. Update this document and `STYLE-SYSTEM.md`.

### Adding a new REST route

- Follow the existing `register_rest_route` pattern in `class-rest-controller.php`.
- Always require `edit_theme_options` unless the route is purely read-only for
  block authors (then `edit_posts`).
- Return `WP_Error` for all error conditions — never `wp_die()` or throw.
- Use `rest_ensure_response()` for success responses.

### Adding new Pro-only UI panels

- Mount inside `global-modal.js` or `sidebar.js` — do not create a new modal root.
- Use the existing `window.__spectraGBSEditor.open(tabId)` API to programmatically
  open the modal. Do not create parallel open/close state.
- New tabs go into `GBSEditorModal.jsx` in the free plugin; new sidebar sections go
  into `sidebar.js` in Pro.

---

## 12. Anti-Patterns — What NOT to Do

These are things that have been tried, broken things, or will break things.

### ❌ Direct `update_option()` on the GBS key

```php
// WRONG — bypasses merge, sanitization, and JIT invalidation
update_option( 'spectra_blocks_pro_gs_user_css', $my_payload );
```
Always go through `RestController::save_user_css()` or the REST endpoint.

### ❌ Storing compiled CSS in the option

```php
// WRONG — bloats the option, stales on every style change
update_option( 'spectra_blocks_pro_gs_user_css', [ 'compiled_css' => $css_string ] );
```
Store structured data. CSS is always compiled on read.

### ❌ Injecting live CSS into only one canvas target

```javascript
// WRONG — works only in classic canvas mode
document.head.appendChild(style);

// RIGHT — inject into both
injectStyleSheet(id, css); // liveVars helper handles both
```

### ❌ Calling a persistence route for live paint

```javascript
// WRONG — triggers JIT rebuild, DB write, action hooks on every keystroke
await apiFetch({ path: '/save', method: 'POST', data: { payload } });
```
Call `/render` for preview; only call `/save` when the user explicitly commits.

### ❌ Using `clientId` as the BCE scope identifier

```javascript
// WRONG — clientId is re-generated on copy/paste and across page reloads
const scopeId = `spectra-bce-${clientId}`;
```
Use `spectraBCEId` attribute (stable UUID set once).

### ❌ Repeating `spectraGSClasses` in a `render_block` filter

Classes are already on `className`. Adding them again via `render_block` doubles
the class list, which can trip CSS pseudo-class selectors (`:not(.my-class)`) and
causes unexpected specificity.

### ❌ Adding user CSS to a second option for "Pro-only" storage

Any Pro-only CSS that affects the rendered page must go through the shared
`spectra_blocks_pro_gs_user_css` SSOT. A second option won't be enqueued, won't be
live-painted, won't be merged by the import pipeline, and won't be JIT-compiled.

### ❌ Enqueuing a new GBS stylesheet without a `pin_styles_after_theme_globals()` dependency

New `spectra-gen-*` handles must be declared as dependents of the pin function's
fake handle (`spectra-gs-pin`) so they appear after theme global styles in source
order. Without this the cascade priority is undefined.

### ❌ `dispatch('core').savePost()`

The `core` store does not expose `savePost`. The correct call is:
```javascript
dispatch('core/editor').savePost();
```

---

## 13. File Map

```
spectra-blocks/
  includes/GlobalStyles/
    class-engine.php              Enqueue orchestrator; yields to Pro when active
    class-rest-controller.php     All REST routes; merge logic; option write surface
    class-gen-css-renderer.php    Pure payload → CSS compiler (frontend + editor scope)
    class-jit-compiler.php        Tailwind-parity token → CSS (arbitrary values, variants)
    class-class-registry.php      Static utility catalog + SG-driven color classes
    class-jit-cache.php           Per-post JIT compilation cache (option per post)
    class-sanitizer.php           CSS property/value sanitizer (strict: no var() in user input)
    class-gen-css-orphan-stripper.php  Scrubs dead gc-spectra-* selectors on write
    class-system-sizes-endpoint.php   System spacing/border/fontsize REST route (Pro values)

  src/extensions/gbs-editor/
    index.js                      Plugin entry; mounts toolbar; exposes window.__spectraGBSEditor
    utils/liveVars.js             Live <style> injection into host + iframe
    hooks/useCustomClasses.js     REST CRUD for named classes
    hooks/useGlobalCSS.js         Full payload read + /render live paint
    hooks/useKeyframes.js         REST CRUD for @keyframes
    components/GBSEditorModal.jsx Main 6-tab modal (Colors, Typography, Presets, Variables, Classes, Custom CSS)
    components/ClassFlyout.jsx    Single-class state → declarations editor

  docs/
    GBS-ARCHITECTURE.md           ← this file
    GLOBAL-STYLES-CLASS-NAME-RULES.md  Class name validation rules + history

spectra-blocks-pro/
  includes/Extensions/GlobalStyles.php  Pro extension: block defaults, system vars, stylesheet override
  src/extensions/global-styles/
    index.js                      Extension entry; registers block filters
    helpers.js                    extendBlockAttributes, addGSClasses, applyDefaultsToNestedBlocks
    controls.js                   Inspector panel (class dropdown, BCE button)
    options.js                    Class dropdown option formatting for react-select
    sidebar.js                    Pro sidebar sections (system vars, presets, defaults)
    global-modal.js               Modal mount + window.__spectraGBSEditor API
    components/BlockCodeFlyout.jsx  BCE flyout (CSS/JS editor with dirty/save state)
  src/styles/extensions/
    block-code-flyout.scss        BCE flyout styles
  docs/
    GBS-PRO-EXTENSION.md          Pro-specific extension reference
```
