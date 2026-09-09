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
> **Last verified against code:** 2026-08-26 (`dev` @ 1.0.6)

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
14. [Known Gaps — found in the 2026-08-26 audit](#14-known-gaps--found-in-the-2026-08-26-audit)

---

## 1. Architecture at a Glance

GBS is split across two plugins that share a single option as their source of truth.

```
spectra-blocks (free)              spectra-blocks-pro
──────────────────────────         ──────────────────────────────────────
PHP Engine & REST API              PHP Extension (overrides free enqueue)
  class-engine.php                   includes/Extensions/GlobalStyles.php
  class-rest-controller.php
  class-gen-css-renderer.php       JS Block UI (per-block surface)
  class-state-resolver.php           src/extensions/global-styles/
  class-jit-compiler.php              controls.js   (inspector panel)
  class-class-registry.php            options.js    (dropdown data)
  class-jit-cache.php                 helpers.js    (block attributes)
  class-sanitizer.php                 global-modal.js (toolbar entry point)
  class-gen-css-orphan-stripper.php   components/BlockCodeFlyout.jsx (BCE)
  class-system-sizes-endpoint.php
                                   JS Global Styles UI (the ONLY modal)
JS editor plumbing (no modal)        src/extensions/gbs-editor-v2/
  src/extensions/gbs-editor/           index.js (window.__spectraGBSEditorV2)
    index.js          (canvas re-sync   GBSEditorV2Modal.jsx
                       on SPA nav)      components/ (nav, views, styleguide/*)
    utils/liveVars.js (live <style>)    context/ · data/ · utils/
    hooks/            (useCustomClasses, useCustomVars, useKeyframes,
                       useGBSConfig, useGBSComputed, useEditedPostId)
```

> **The free-plugin GBS modal was retired.** `src/extensions/gbs-editor/` no
> longer mounts a modal and no longer exposes `window.__spectraGBSEditor`; it
> keeps the canvas CSS in sync as the Site Editor swaps the edited entity, and
> exports the hooks/utils the Pro V2 editor imports. The single Global Styles UI
> is Pro's `gbs-editor-v2` — opened with `window.__spectraGBSEditorV2.open( viewId )`.

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
    'variables'     => [             // User-authored CSS variables (the `/custom-vars` bucket)
        '--brand' => '#b36b2c',
    ],
    'remBase'       => '62.5%',      // Document-root font-size ONLY (the source's rem base)
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
- `variables` is the user-authored CSS-variable bucket written by `/custom-vars`.
  It renders onto the root scope alongside `rootStyles`/`presetLock`
  (`GenCssRenderer::render()` step 3b) — before that it existed only as editor
  live-paint and never reached the front end.
- `remBase` is a **scalar**, not a bucket: the source's *document-root*
  font-size (e.g. the 62.5% trick), emitted as `:root { font-size: … }` on the
  front end only. A `body` font-size is inheritance intent and belongs in
  `rootStyles` — hoisting it rescales every rem token on the page.

### 2.2 Per-Page Post Meta

Page-scoped payloads use the **same schema-v1 shape** stored as post meta under
the key `spectra_blocks_pro_gs_user_css` — the **same key string as the option**,
in a different table (constant `GenCssOrphanStripper::META_KEY`, also reachable as
`Engine::OPTION_KEY_USER_CSS`). The meta carries a
`sanitize_post_meta_{key}` filter, so every `update_post_meta()` re-runs the
orphan stripper.

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
- `replace: true` (POST `/save`) is the only way to hard-replace. Its semantics
  differ per scope:
  - `scope=page` → full overwrite (merge onto an empty base), clearing stale
    `gs-*` classes on re-import.
  - `scope=global` → resets the **import-owned non-class buckets**
    (`presetLock`/`rootStyles`/`wrapperStyles`/`scopeVars`/`mediaQuery`) while
    keeping `v`/`classes`/`keyframes`/`imports`, so a fresh `replace_site` build
    cannot inherit a prior build's body-level palette.
  - `reset_classes: true` (global only) additionally drops the `classes` bucket.
    Off by default so a partial write can never wipe the editor's own classes.
- POST `/save` with `scope=page` and an **empty payload** deletes the page meta
  (that is how a re-import clears stale per-page CSS).

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
        │         /* preflight + ClassRegistry utilities + user classes + keyframes */
        │       </style>
        │     NOTE: when Pro is active this is skipped unless the unified render
        │           flag is on (Pro owns the stylesheet) — see §6.1
        │
        ├── Engine::enqueue_jit_for_current_post()
        │     → <style id="spectra-gs-jit-styles">
        │         /* JIT-compiled tokens used on this post */
        │       </style>
        │     Depends on spectra-gs-utility-classes ONLY when that handle exists.
        │
        └── Engine::enqueue_gen_sitewide_css()
              → <style id="spectra-gen-sitewide-css">
                  /* site-wide non-class buckets: imports, rootStyles, scopeVars… */
                </style>

  └── enqueue_block_assets (priority 100)
        └── Engine::enqueue_gen_custom_css_for_current_post()
              → <style id="spectra-gen-custom-css-{post_id}">
                  /* per-page post meta: imports, rootStyles, classes, wrapperStyles… */
                </style>
              On the front end registration/enqueue is deferred to `wp_footer`.
```

**Everything is emitted UNLAYERED.** The utility sheet and the JIT sheet used to
sit in `@layer utilities`; they no longer do. Block-default rules (e.g.
`.wp-block-spectra-container { padding: 10px }`) are unlayered, and an `@layer`
rule loses to an unlayered rule at *any* specificity — so layering the utilities
made them lose the one contest they exist to win. They now win by specificity
(`:root .{class}`) plus source order. Do not reintroduce `@layer` here.

**Preflight.** `build_preflight_css()` prepends a Tailwind-v4-parity reset scoped
to `:where(.spectra-is-root-container)` (zero added specificity), so the theme's
non-Spectra UI is untouched. One deliberate exception: the heading/paragraph
`margin: 0` reset uses the bare `.spectra-is-root-container` selector, because a
block theme's `h3 { margin-top: … }` from theme.json ties a `:where()`-scoped
reset on specificity and wins on source order.

**Source-order matters.** `pin_styles_after_theme_globals()` runs on
`wp_enqueue_scripts` at `PHP_INT_MAX` and walks the queue, appending
`global-styles` to the deps of every enqueued `spectra-gs-*` / `spectra-gen-*`
handle. The dependency resolver then prints ours **after** theme.json output —
deterministic source order instead of hook trivia. It is a no-op on classic
themes (no `global-styles` handle registered).

**JIT cache.** `JitCache::rebuild(post_id)` runs on `save_post` (priority 20) to
pre-compile per-post JIT CSS. Version-bumped whenever a class or Style Guide config
is saved, so stale caches are never served.

---

## 4. CSS Pipeline (Editor Live Paint)

Live paint lets CSS changes appear instantly in the canvas without a page reload.
The pipeline lives in `src/extensions/gbs-editor/utils/liveVars.js` — the module
survived the modal's retirement and is imported by Pro's `gbs-editor-v2` and by
the BCE flyout.

```
User action in the Style Guide / class editor / BCE
  └── REST write (or local state update)
        └── liveVars call
              ├── injectStyleSheet(id, css) / removeStyleSheet(id)
              │     Inserts or replaces <style id="{id}"> in:
              │       1. host document (document.head)
              │       2. editor iframe (iframe[name="editor-canvas"].contentDocument.head)
              │     Both targets must be updated — styles in host don't reach iframe.
              │
              ├── refreshStyleGuidePalette()
              │     Re-fetches the computed Style-Guide tokens and repaints the
              │     preset palette + Astra aliases (buildPresetPaletteCSS /
              │     buildAstraAliasCSS, the latter driven by the server-resolved
              │     `astra_shade_map` on the computed payload — never a JS copy).
              │
              ├── refreshCustomVarsCSS()
              │     Re-injects the `/custom-vars` :root{} variables.
              │
              ├── regenerateEditorCSS()
              │     Refreshes the user-classes stylesheet in the canvas.
              │
              ├── regeneratePageCSS(postId) / removeOtherPageSheets(keepId)
              │     Calls REST /render (pure, no DB write) with the page payload,
              │     injects as <style id="spectra-gen-custom-css-{postId}">, and
              │     drops the previously-edited page's sheet so it can't bleed.
              │
              ├── regenerateSitewideCSS()
              │     Re-applies the PHP-localized site-wide chrome CSS (no fetch)
              │     after a Site-Editor entity swap.
              │
              └── syncEditorSwatches(computed, config)
                    Realigns the editor's colour swatches with the computed palette.
```

**The Site Editor is an SPA.** Navigating between pages/template parts swaps the
edited entity without a reload, so the PHP-seeded per-page and site-wide styles
never re-run. `src/extensions/gbs-editor/index.js` watches the edited post id
(`useEditedPostId`) and re-injects both. `watchExternalPageCssSaves()` covers
out-of-band writers (e.g. the ZIP AI chat editor) that save through the same REST
route without touching our overlay.

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
Auth: `edit_theme_options` (`check_permission()`) except `/jit-compile` **and**
`/render`, which use `check_jit_compile_permission()` (`edit_posts`) — both are
pure compilers with no DB side effects, and block authors need them for
live-inserted blocks. `/save` with `scope=page` additionally runs
`StyleGuide\Engine::check_page_scope( $post_id )`, because `edit_theme_options`
alone would otherwise let any holder write CSS onto someone else's post.

| Route | Method | Purpose |
|-------|--------|---------|
| `/custom-classes` | GET | Fetch site-wide classes (merges page-scoped when `post_id` is passed) |
| `/custom-classes` | POST | Create, update, or delete a single class |
| `/keyframes` | GET | Fetch all user keyframes |
| `/keyframes` | POST | Create, update, or delete a keyframe |
| `/custom-vars` | GET | Fetch the user CSS-variable map (`variables` bucket) |
| `/custom-vars` | POST | Full-replace the user CSS-variable map |
| `/metadata` | GET | Export utility grammar contract (content-addressed ETag) |
| `/bulk` | POST | Atomic write: classes + keyframes (reports skipped entries) |
| `/sitewide` | POST | Merge a full schema-v1 import payload into the site-wide option |
| `/jit-compile` | POST | Compile utility token strings to CSS (no persistence) |
| `/render` | POST | Render a payload to CSS (pure, no persistence); `scope=page\|global` |
| `/user-css` | GET | Return the entire schema-v1 option |
| `/save` | GET / POST | Unified read/write; `scope=page\|global`, `post_id`, `replace`, `reset_classes` |
| `/system-sizes` | GET / POST | System spacing/border/fontsize presets (Pro values) |

**The `/metadata` ETag is content-addressed.** It hashes the exported contract
plus the palette channels, not just the JIT version option — a version-only ETag
served stale 304s across code changes that altered the grammar without anyone
bumping a number. The comparison also strips a `W/` prefix, because proxies
legitimately weaken a strong ETag on the wire (a strict string compare silently
disabled every 304).

### When to use which write route

| You want to… | Use |
|---|---|
| Add/edit/delete one custom class via the GBS editor UI | `/custom-classes` POST |
| Import a full page payload (ERA / SaaS) | `/save` POST with `scope=page`, `post_id` |
| Import site-wide styles (SaaS build) | `/sitewide` POST or `/save` POST with `scope=global` |
| Atomically write many classes at once (SaaS batch) | `/bulk` POST |
| Write the user CSS-variable map | `/custom-vars` POST |
| Preview CSS without saving | `/render` POST |
| Compile JIT tokens for live-inserted blocks | `/jit-compile` POST |

**Never call `update_option()` directly.** Go through the REST layer so that
sanitization, merge, and JIT cache invalidation all fire correctly.

### Value sanitization on the write routes

Every write route funnels its payload through `Sanitizer::sanitize_json()`.
Class styles arrive unwrapped one level (`{bucket:{prop:value}}`), so the
controller passes `base_depth 1` — otherwise the CSS-aware value rules (which
apply at depth >= 2) never reach the declaration values at all.

**`var(--…)` is allowed in user CSS.** In strict mode the sanitizer rejects only
a `var()` whose first token is *not* a `--` custom property — `var(url(…))`, an
empty `var()`. A well-formed `var(--name)` or `var(--name, fallback)` falls
through to the character whitelist and is preserved verbatim. This changed in
free PR #735; before it, strict mode rejected *any* `var()`, which silently
dropped the most common thing a user writes in a Global Styles class — a
reference to a Style Guide token. The engine's own colour-utility emission path
still passes `$strict = false`, because it emits tokens the whitelist has no
reason to re-check.

---

## 6. Free ↔ Pro Coordination

### 6.1 Stylesheet Ownership

Free's `Engine::init()` checks `class_exists('\\SpectraBlocksPro\\Extensions\\GlobalStyles')`.
When Pro is active, free **skips** `enqueue_stylesheet()` and yields the utility-class
stylesheet to Pro's extension. Pro then enqueues a stylesheet that combines the
utility catalog + user classes + system variables.

**The unified-render flag.** `Engine::is_unified_render()` (filter
`spectra_blocks_gbs_unified_render`, **default false**) flips that ownership: when
on, free's `GenCssRenderer` is the single class renderer even with Pro active and
Pro trims its own stylesheet to block defaults only. Both plugins read this one
method so the flag has a single source of truth. It is a staged rollout — keep it
off until the Pro trim and the legacy read-compat land.

**Rule:** If you add a new enqueue to free's `Engine`, guard it with the same
`class_exists` check if Pro must be able to override it — and read the flag
through `is_unified_render()`, never re-derive it.

**Own your handles.** Free's JIT sheet uses its own `spectra-gs-jit-styles`
handle. It previously shared `spectra-gs-dynamic-styles` with Pro under a
`! wp_style_is( registered )` guard, so whenever Pro registered first, free
appended its JIT CSS into Pro's sheet — which Pro overwrites wholesale on any
Global Styles edit, deleting the JIT from the canvas until reload. Two owners,
one handle, last writer wins.

**Never depend on a handle that may not exist.** `spectra-gs-jit-styles` and
`spectra-gen-sitewide-css` list `spectra-gs-utility-classes` as a dep only after
checking it is registered/enqueued. Pinning an unregistered handle makes
`WP_Dependencies::all_deps()` take its missing-dependency branch and silently drop
the style entirely — CSS computed, cached, and rendered nowhere.

### 6.2 Shared Option Key

Both plugins read/write `spectra_blocks_pro_gs_user_css`. This is intentional.
Free's REST layer is the write surface; Pro reads the same data for its CSS
generation pass.

**Do not introduce a second option** for user CSS in either plugin. Any data
that is "Pro-only but needs to be part of the global CSS" still belongs in the
same schema-v1 option under a new top-level bucket.

### 6.3 Config Save Hook

Free fires `spectra_style_guide_config_before_save` so Pro can merge its own fields
into the Style Guide config before free's endpoint writes. The Style Guide is now
**colours-only** — Typography and UI Styling were removed, so Pro currently registers
no fields on this hook (the old `merge_pro_config_fields()` is gone). The hook point
remains for future Pro-only Style Guide fields. When adding one:

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
| GBS utility classes (ClassRegistry) | `:root .{class}` (editor: `:root .is-root-container .{class}`) | (0,2,0) | Block base styles |
| Block defaults (Pro) | `.wp-block-spectra-{name}` | (0,1,0) | Nothing from GBS |

**The `.{name}.{name}` repetition is deliberate.** Repeating the class name twice
lifts specificity to (0,3,0) without requiring a wrapper or `!important`. Do not
simplify it to `.{name}` — that breaks the cascade contract and user-class CSS will
stop overriding utility classes. The repeat count is one constant,
`GenCssRenderer::SELECTOR_CLASS_REPEAT`, read by both class renderers
(`GenCssRenderer` for page/import payloads, `Engine::render_user_classes()` for the
site-wide option) so the two paths cannot drift.

**Nothing is wrapped in `@layer`.** The utility sheet and the JIT sheet were
previously emitted inside `@layer utilities`; that was reverted. Unlayered rules
beat layered rules at any specificity, and block defaults are unlayered — so the
layer handed every contest to the thing utilities are supposed to override.
Utilities now win on specificity (`:root` prefix) plus source order. Do not
reintroduce the layer without re-checking the block-default cascade.

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
  liveVars.regeneratePageCSS(123) → POST /render { payload, post_id: 123 }
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

BCE is per-block **and** per-class: the CSS panel shows one tab per GBS class
applied to the block, plus a "Block" tab for the block-scoped custom CSS. The JS
panel is block-scoped only.

### Save flow

There is no longer a Save button with its own dirty/saved lifecycle. **The flyout
rides the editor's own save.**

```
User types CSS/JS
  → debounced write into local state (per tab)
  → block-scoped text → setAttributes({ spectraCustomCSS, spectraCustomJS })

Editor save (the normal post save)
  → flush any pending CSS/JS into block attributes first
  → for every class tab whose local text differs from the stored bucket:
        useCustomClasses.saveClass( name, { default: textToBucket( text ) } )
  → regenerateEditorCSS() + regeneratePageCSS( postId )
```

Two rules that fall out of this shape:
- The flush must happen **before** the attribute snapshot is taken, or the last
  keystrokes are lost. The save handler reads its inputs from a ref
  (`saveContextRef`), not from the closure, so it can't act on stale state.
- `/custom-classes` rejects an empty-styles payload, so a GBS entry is created on
  the **first** CSS save for that class, not when the tab is opened.

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

### Preview normalisation

Partial input previews correctly because the flyout runs
`normalizeCSSForPreview()` before injecting: it appends the missing semicolon to
a declaration line the user is still typing, while leaving braces, comments and
already-terminated lines alone. Without it, every keystroke mid-declaration
invalidates the rule and the preview flickers off.

### No independent save state

The flyout no longer owns a Save button, `isDirty`, `isSaving`, or `justSaved` —
persistence rides the editor's own save (see above). Do not reintroduce a
parallel save path; a second writer against `/custom-classes` would race the
editor-save flush and the later write would win with stale text.

---

## 11. Extending GBS — the Right Way

### Adding a new class property (state variant)

State resolution has ONE owner: `GlobalStyles\StateResolver`. Both class
renderers (`Engine::render_user_classes()` for the option, `GenCssRenderer` for
page/import payloads) resolve through it, so the breakpoint table and the pseudo
table exist in exactly one place.

1. Add the key to `StateResolver::PSEUDO` (selector suffix) or
   `StateResolver::BREAKPOINTS` (mobile-first `min-width` media condition).
   Compound states (`md_hover`) and already-formatted states (`:x`, `[open]`,
   `.is-active`, shape-validated) need no new entry.
2. Nothing to change in `GenCssRenderer::render_classes()` — it calls
   `StateResolver::resolve()`. New media conditions order themselves via
   `StateResolver::media_order()`.
3. Add the variant to the `useCustomClasses` hook's state tab list.
4. Update the `/metadata` contract if SaaS needs to know about it.

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

### Adding new Global Styles UI

- There is exactly one modal: Pro's `gbs-editor-v2`. Do not create a second modal
  root, and do not revive the free plugin's retired one.
- Open it with `window.__spectraGBSEditorV2.open( viewId )` (optionally a second
  argument for the view's sub-tab). Do not create parallel open/close state.
- A new view is a new entry in `config/navConfig.js` (id, label, icon, and a
  `SAVE_MODEL` of `footer` / `self` / `readonly`) plus a case in
  `components/GBSV2ViewRouter.jsx`. The nav is grouped: Design System · Custom CSS
  · Reference.
- Panels that both plugins need live in free `src/extensions/gbs-editor/`
  (hooks/utils) and are imported by Pro — that is why the folder outlived the
  modal.

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

### ❌ Naming a new GBS handle outside the `spectra-gs-*` / `spectra-gen-*` families

`pin_styles_after_theme_globals()` walks the enqueue queue and pins **only**
handles with those two prefixes to `global-styles`. There is no `spectra-gs-pin`
fake handle to depend on — the pin is prefix-driven. Name a new sheet outside the
families and it prints wherever hook order happens to put it, with undefined
cascade priority against theme.json output.

### ❌ Declaring a dep on a handle that may not be registered

```php
// WRONG — if the utility sheet never registered (Pro owns it, or it was empty),
// WP takes the missing-dependency branch and drops this style silently.
wp_register_style( $handle, false, array( 'spectra-gs-utility-classes' ), null );
```
Guard with `wp_style_is( …, 'registered' )` / `'enqueued' )` first, exactly as
`enqueue_jit_for_current_post()` and `enqueue_gen_sitewide_css()` do.

### ❌ Sharing a style handle between free and Pro

Both plugins registering the same handle means the last writer wins and one
plugin's inline CSS lands inside the other's sheet — which the other then
overwrites. Give each owner its own handle.

### ❌ Hardcoding the Astra slot map in JS

`liveVars.js` must read `astra_shade_map` off the computed/preview REST payload.
The slot order is flag-aware (Astra swaps background indices on pre-4.8.9
installs), so any JS-side copy is wrong on every legacy site.

---

## 13. File Map

```
spectra-blocks/
  includes/GlobalStyles/
    class-engine.php              Enqueue orchestrator; yields to Pro when active
    class-rest-controller.php     All REST routes; merge logic; option write surface
    class-gen-css-renderer.php    Pure payload → CSS compiler (frontend + editor scope)
    class-state-resolver.php      SSOT for class STATE → media query + selector suffix
    class-jit-compiler.php        Tailwind-parity token → CSS (arbitrary values, variants)
    class-class-registry.php      Static utility catalog + SG-driven color classes
    class-jit-cache.php           Per-post JIT compilation cache (post meta per post)
    class-sanitizer.php           CSS property/value sanitizer (strict: keeps var(--x), rejects malformed var())
    class-gen-css-orphan-stripper.php  Scrubs dead gc-spectra-* selectors on write
    class-system-sizes-endpoint.php    System spacing/border/fontsize REST route (Pro values)

  src/extensions/gbs-editor/      (editor plumbing + shared hooks — NO modal)
    index.js                      Re-injects page + sitewide CSS on Site-Editor SPA nav
    utils/liveVars.js             Live <style> injection into host + iframe
    utils/bucketHelpers.js        Stored bucket ⇄ raw CSS text conversion
    utils/cssCompletions.js       CSS autocomplete data
    hooks/useCustomClasses.js     REST CRUD for named classes
    hooks/useCustomVars.js        REST CRUD for the `variables` bucket
    hooks/useKeyframes.js         REST CRUD for @keyframes
    hooks/useGBSConfig.js         Style Guide config read/write
    hooks/useGBSComputed.js       Computed tokens (incl. astra_shade_map)
    hooks/useEditedPostId.js      The Site Editor's currently-edited post id
    components/CSSAutocomplete.jsx

  docs/
    GBS-ARCHITECTURE.md           ← this file
    GLOBAL-STYLES-CLASS-NAME-RULES.md  Class name validation rules + history
    style-guide-color-rewrite.md       Style Guide colour system (v2, current)
    astra-color-sync-audit.md          Astra theme colour sync
    spectra-one-color-sync-audit.md    Spectra One (FSE) colour sync
    style-guide-color-audit.md         v1 colour system (historical)

spectra-blocks-pro/
  includes/Extensions/GlobalStyles.php  Pro extension: block defaults, system vars, stylesheet override
  src/extensions/global-styles/         (per-block surface)
    index.js                      Extension entry; registers block filters
    helpers.js                    extendBlockAttributes, addGSClasses, applyDefaultsToNestedBlocks
    controls.js                   Inspector panel (class dropdown, BCE + Style Guide buttons)
    options.js                    Class dropdown option formatting for react-select
    blockDefaultsStore.js         Block-default class state
    global-modal.js               Toolbar entry point → __spectraGBSEditorV2.open()
    components/BlockCodeFlyout.jsx  BCE flyout (CodeMirror CSS/JS + per-class tabs)
    components/CSSCodeMirror.js     CM6 setup + CSS completion source
  src/extensions/gbs-editor-v2/         (the Global Styles modal)
    index.js                      Mounts the modal; exposes window.__spectraGBSEditorV2
    GBSEditorV2Modal.jsx          Modal shell
    config/navConfig.js           7 nav items / 3 groups + SAVE_MODEL
    components/GBSV2ViewRouter.jsx  id → view
    components/styleguide/*       StyleGuideView, ColorsSection, PaletteRow, sub-nav
    context/StyleGuideContext.jsx   Draft state, undo/redo, unsaved guard
    data/paletteRoles.js          PALETTE_ROLES (10) · DERIVED_VARS (6) · COLOR_SCHEMES
    utils/colorMath.js            Contrast / AUTO derivations
  docs/
    GBS-PRO-EXTENSION.md          Pro-specific extension reference
```

---

## 14. Known Gaps — found in the 2026-08-26 audit

Recorded here rather than fixed silently; each is a code change, not a doc change.
All six were re-checked on 2026-08-26 against free `dev` @ 1.0.6 (`bbeea1e5`) and
pro `dev` (`3529358`); all six are still open.

1. **`/sitewide` still skips invalid class names silently.** `/bulk` returns
   `skipped_classes` + `skipped_keyframes`; `/sitewide` returns nothing. This is
   the exact blind spot that hid CHG-006 for as long as it did — see
   [`GLOBAL-STYLES-CLASS-NAME-RULES.md`](./GLOBAL-STYLES-CLASS-NAME-RULES.md) §6.
2. **`update_custom_class()` does not validate names.** The single-class UI route
   accepts any non-empty name, including reserved families (`wp-`, `ast-`, …) that
   the import routes reject. The two write surfaces disagree about what a legal
   class name is.
3. **The unified-render flag is unfinished.** `Engine::is_unified_render()` is
   wired and filterable but must stay off until the Pro stylesheet trim and the
   legacy read-compat land. Anyone flipping it early gets block defaults rendered
   twice or not at all.
4. **`Sanitizer` docblocks still cite `var(--spectra-chromatic1-7)`.** That token
   name was retired when the Style Guide moved to semantic slugs
   (`--spectra-primary`); the comment now names a variable the engine never emits.
5. **The BCE flyout has no independent save affordance.** Class CSS persists only
   when the post is saved, so a user who edits CSS and navigates away without
   saving the post loses it with no warning — the Style Guide modal has an
   unsaved-changes guard, this surface does not.
6. **Per-page and site-wide payloads share one key string** in two tables. It
   works, and both readers are explicit about which store they touch, but a
   `get_option`/`get_post_meta` mix-up is a silent wrong-scope write rather than
   an error. A distinct meta key would make the mistake impossible.
