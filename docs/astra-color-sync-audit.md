# Astra Color Sync — Storage & Caching Audit

**Scope:** How the Astra theme stores, reads, caches, and renders the two options
that hold the global colour palette — `astra-settings['global-color-palette']`
and `astra-color-palettes` — with a focus on why a Style Guide → Astra sync can
update the database yet the **Customizer** (`customize.php?theme=astra`) shows
stale swatches.

**Audited theme:** `wp-content/themes/astra` (free theme).
**Related code:** `includes/StyleGuide/Sync/Astra/class-astra-palette-adapter.php`.
**Date:** 2026-07-23.

> ℹ️ **Style-Guide side (v2 storage).** The Style Guide uses the v2 colour storage
> (see [`style-guide-color-rewrite.md`](./style-guide-color-rewrite.md)). The key
> facts for the Astra sync:
> - The adapter lives in **`Sync/Astra/`** (namespace `…\Sync\Astra`) and
>   registers its own `update_option_astra-settings` hook via
>   `register_reverse_hooks()` — core never names the option.
> - On reverse sync, every pulled slot maps token → owning core slug
>   (`ColorModel::slug_for_token()`) and writes **`config['colors'][slug]`**.
> - **The 7 managed slots are two-way**, each mapping to a stored core role.
>   Slots 7 ("Subtle background") and 8 ("Other supporting") are **unmanaged**:
>   their old tokens were the interpolated ramp stops (`neutral-3`/`neutral-6`),
>   which are no longer generated. The sync neither pushes nor pulls them —
>   which stored colour (if any) should own them is an open product decision.

---

## 1. TL;DR — root cause

There is **no persistent cache** hiding the update. The Customizer colour pickers
read `astra-color-palettes` **live from the DB** on every load
(`astra_get_palette_colors()` → `get_option()`, no static/transient cache). So
once that option is correct, a fresh Customizer load shows the correct colours.

The pickers stayed stale because **Astra keeps the palette in two separate
options and the sync only kept one of them current**:

| Option | Read by | Purpose |
| --- | --- | --- |
| `astra-settings['global-color-palette']` | `astra_get_option()` → dynamic CSS | The colours the **rendered site/CSS** uses |
| `astra-color-palettes` | `astra_get_palette_colors()` → **Customizer control** | The colours the **Customizer swatches** show |

Astra's own save writes **both**. The Style Guide adapter originally wrote only
the first. Even after a mirror was added, it was gated behind an
"`astra-settings` changed?" check — so when `astra-settings` was already correct
(the common case, since it is written first) the mirror never ran and
`astra-color-palettes` was never reconciled. **Fix:** reconcile the two options
**independently** in `AstraPaletteAdapter::write()` (see §7).

---

## 2. Color Sync Mapping (reference)

> **Living reference.** This section records *which* Astra style maps to *which*
> Style Guide style — the mapping only, no technical steps. It's meant to grow:
> colours today, then typography and other style categories as the sync expands
> (see [Future mappings](#future-mappings)). When you add a new mapping in code,
> add its table here.

### 2.1 The mapping — which Astra colour = which Style Guide colour

Read every row left-to-right: **this Astra colour becomes this Style Guide colour.**

#### A. The 9 Astra global colours (the source — this is what the sync writes)

| Astra colour (label) | Astra variable | Astra default | Sync | → Style Guide colour | SG token | SG default |
| --- | --- | --- | :--: | --- | --- | --- |
| Brand | `--ast-global-color-0` | `#046bd2` | ⇄ | **Primary** | `chromatic1-7` | `#6431f6` |
| Alternate Brand | `--ast-global-color-1` | `#045cb4` | ⇄ | **Secondary** | `chromatic2-7` | `#7345f7` |
| Headings | `--ast-global-color-2` | `#1e293b` | ⇄ | **Heading text** | `neutral-7` | computed |
| Text | `--ast-global-color-3` | `#334155` | ⇄ | **Body text** | `neutral-5` | computed |
| Primary Background | `--ast-global-color-4` | `#FFFFFF` | ⇄ | **Background** (page) | `neutral-0` | `#ffffff` |
| Secondary Background | `--ast-global-color-5` | `#F0F5FA` | ⇄ | **Surface** | `neutral-1` | computed |
| Alternate Background | `--ast-global-color-6` | `#111111` | ⇄ | **Border / Outline** | `neutral-2` | computed |
| Subtle Background | `--ast-global-color-7` | `#D1D5DB` | — | *unmanaged* | — | — |
| Other Supporting | `--ast-global-color-8` | `#111111` | — | *unmanaged* | — | — |

The 7 mapped slots are **two-way** (⇄). The mapping is 1:1 — every managed Astra
slot has its own Style Guide token — so each round-trips independently. Slots 7
and 8 are **unmanaged** (their old tokens were the interpolated `neutral-3`/
`neutral-6`, which are no longer generated); Astra keeps its own values for them.
See [§2.4](#24-notes-on-the-values).

#### B. Other named Astra colours (they inherit a global colour above, so they land on the same Style Guide colour)

These are separate Customizer settings, but their default is a reference to one of
the 9 (`var(--ast-global-color-N)`), so they follow it automatically — no separate
sync needed.

| Astra colour | Astra setting (key) | Astra default (references) | → inherits global | → Style Guide colour | SG token |
| --- | --- | --- | --- | --- | --- |
| Accent | `theme-color` | `var(--ast-global-color-0)` | Brand | **Primary** | `chromatic1-7` |
| Links — normal | `link-color` | `var(--ast-global-color-0)` | Brand | **Primary** | `chromatic1-7` |
| Links — hover | `link-h-color` | `var(--ast-global-color-1)` | Alternate Brand | **Secondary** | `chromatic2-7` |
| Heading (H1–H6) | `heading-base-color` | `var(--ast-global-color-2)` | Headings | **Heading text** | `neutral-7` |
| Body Text | `text-color` | `var(--ast-global-color-3)` | Text | **Body text** | `neutral-5` |
| Borders | `border-color` | `var(--ast-global-color-7)` * | Subtle Background | *(unmanaged slot)* | — |

\* Fresh installs reference slot 7 (Subtle Background — unmanaged). **Legacy** installs
reference slot 6 (Alternate Background → **Border / Outline**, `neutral-2`).

**Sync direction legend**
- `⇄` **two-way** — a Style Guide save updates Astra, *and* editing that colour in
  Astra updates the Style Guide. **The 7 managed global colours are two-way.**
- `—` **unmanaged** — slots 7/8: the sync neither pushes nor pulls them.
- `→` **push-only** — Style Guide → Astra only. (No global colour is push-only;
  the legend is kept for the derived/inherited settings in §2.2 that have no
  reverse path.)

### 2.2 Astra's other colours (inherited via the 9)

Astra exposes **many** more colour settings than the 9 global colours — body
text/links, headings, buttons, header/footer builder (site title, menus, search,
account, cart), sidebar/content backgrounds, WooCommerce/EDD, transparent header,
breadcrumbs. **We do not map these individually, and we don't need to:** on a
fresh install Astra defaults almost all of them to one of two things, both of
which follow the 9 automatically:

1. **A palette variable** — default is literally `var(--ast-global-color-N)`, so
   the setting *is* the global colour by reference. Editing the palette (or our
   sync) updates it with no extra work.
2. **Empty (`''`)** — resolved at render time to a base colour (e.g. accent /
   `text-color` / `border-color`), which are themselves palette-linked. So these
   inherit transitively too.

The most common ones (Accent, Links, Heading H1–H6, Body Text, Borders) are
listed explicitly in **§2.1 B** above. The rest of the palette-linked settings —
which likewise resolve to one of the 9, so to a Style Guide colour — are:

| Astra setting | Label | Inherits global → Style Guide colour |
| --- | --- | --- |
| `header-color-site-title` | Site title | `-2` Headings → **Heading text** |
| `header-menu1-color-responsive` / `-h-` / `-a-` | Primary menu normal / hover / active | `-3` → Body text · `-1` → Secondary · `-1` → Secondary |
| `header-mobile-menu-color-responsive` … | Mobile menu normal / hover / active | `-3` → Body text · `-1` → Secondary · `-1` → Secondary |
| `content-bg-obj-responsive`, `site-layout-outside-bg-obj-responsive` | Content / site background | `-5` (reorg `-4`) → **Surface** / **Background** |
| `hb-header-main-sep-color`, `hbb-footer-top-border-color`, … | Header/footer separators & borders | subtle-bg `-7` → *(unmanaged slot — keeps Astra's value)* |
| `footer-copyright-color` | Footer copyright | `-3` Text → **Body text** |

**The only colours that do NOT track the palette** are a few settings with
**literal-hex** defaults — they keep their hardcoded value regardless of the
palette (and regardless of our sync):

| Astra setting | Literal default |
| --- | --- |
| `header-menu1-submenu-item-b-color`, `header-mobile-menu-submenu-item-b-color` | `#eaeaea` |
| `footer-adv-border-color`, `footer-sml-divider-color` | `#7a7a7a` |
| `hb-footer-bottom-border-color` | `#e6e6e6` |
| `off-canvas-close-color` | `#3a3a3a` |

> **Caveat — user overrides:** any of the inheriting settings can be overridden
> with a literal hex in the Customizer. Once overridden, that setting stops
> tracking the palette (and the sync) until it's cleared back to default. This is
> Astra's behaviour, not something the sync controls.

> **Note:** individual `h1-color`…`h6-color` and dedicated form/input colour
> pickers do **not** exist in the free Astra theme (they're Astra Pro); the free
> theme has one combined `heading-base-color` and forms inherit the base colours.

### 2.3 Not mapped to Astra

These Style Guide colours have **no** Astra global-palette slot, so they are
intentionally left out of the sync (Astra has no equivalent to map them to):

| Style Guide colour | SG token | SG default |
| --- | --- | --- |
| Accent | `chromatic3-7` | `#f59e0b` |
| Success | `chromatic4-7` | `#10b981` |
| Error | `chromatic5-7` | `#ef4444` |
| Info | `chromatic6-7` | `#8b5cf6` |
| Warning | `chromatic7-7` | `#d97706` |

### 2.4 Notes on the values

- **Astra ordering:** the Astra labels/defaults above are the fresh-install
  "reorganized" layout (`astra_4_8_9_compatibility()` = `true`). On legacy installs
  the flag is `false` and slots **4↔5** and **6↔7** swap (both label and default
  hex). The adapter resolves the slot index at runtime
  (`AstraPaletteAdapter::semantic_index()`), so the *mapping* (e.g. Page Background
  → `neutral-0`) always holds — only which raw `--ast-global-color-N` index it
  lands on differs.
- **SG defaults are fixed literals.** The nine default colours are hard-coded in
  `ColorModel::default_colors()` (brand seeds + six neutral literals frozen from
  the retired OKLCH derivation). Nothing is generated.
- **SG "-7" is the brand seed token.** `chromaticN-7` is the raw stored colour
  (Primary = `chromatic1-7`, Secondary = `chromatic2-7`); no other shades exist.
- **How the round-trip works.** `SyncOrchestrator::apply_reverse_colors()` maps
  every pulled token to the core role slug that owns it
  (`ColorModel::slug_for_token()`) and writes **`config['colors'][slug]`**
  directly: the two brand slots (0/1 → `primary`/`secondary`) and five neutral
  slots (2/3/4/5/6 → `heading`, `body`, `background`, `surface`, `outline`).
  Slots 7/8 are **unmanaged** — their old tokens were the interpolated
  `neutral-3`/`neutral-6`, which are no longer generated, so the sync skips
  them entirely (no push, no pull, no pins).

### 2.5 Future mappings

Reserved for the next style categories as the sync grows. Append a table per
category using the same "Astra → Style Guide" shape.

- **Typography** — *TBD* (headings/body font family, size, weight, line-height).
- **Spacing / other** — *TBD*.

---

## 3. Where the two options live and how they are read

### `astra-settings['global-color-palette']`
- Read through `astra_get_option('global-color-palette')`
  (`inc/core/common-functions.php:558`).
- `astra_get_option()` does **not** hit the DB directly. It reads a **per-request
  static array** `Astra_Theme_Options::$db_options`
  (`inc/core/class-astra-theme-options.php:50,943`), built once by `refresh()`
  on the `after_setup_theme` hook.
- `refresh()` merges `get_option('astra-settings')` over the theme defaults. It is
  called again explicitly in:
  - `Astra_Customizer::preview_init()` (`class-astra-customizer.php:1927`, on `customize_preview_init`)
  - `Astra_Customizer::customize_save()` (`:1983`, on `customize_save_after`)
  - the background updater.
- **There is no `update_option_astra-settings` invalidation hook.** After a
  programmatic `astra_update_option()`, `astra_get_option()` keeps returning the
  value cached at `after_setup_theme` **for the rest of that request**. A fresh
  request re-runs `refresh()` and picks up the new value. (This is why
  same-request read-backs during a sync must go to the DB, not the getter.)

### `astra-color-palettes`
- Read through `astra_get_palette_colors()` (`inc/extras.php:868`).
- Calls `get_option('astra-color-palettes')` **directly on every call — no static,
  no transient, no object-cache key of its own.** Falls back to
  `Astra_Global_Palette::get_default_color_palette()` only if the option is empty.
- `astra_get_palette_presets()` returns a hardcoded array; `astra_get_palette_names()`
  also reads the option live. None cache.

---

## 4. How the Customizer renders the initial swatch values

This is the decisive path. Astra does **not** use WordPress core's
`_wpCustomizeSettings.settings[id].value` for these controls. It registers its
settings **client-side**:

1. `prepare_javascript_control_configs()` (`class-astra-customizer.php:869`) pushes
   each control's `default`, `type` (`datastore_type`), `transport`, and
   `sanitize_callback` into `self::$dynamic_options['settings']`.
2. Localized as `AstraBuilderCustomizerData.dynamic_setting_options`
   (`class-astra-customizer.php:1168`).
3. The React control bundle (`inc/customizer/extend-custom-controls/build/index.js`)
   does, for each entry: `wp.customize.add( new wp.customize.Setting( name, o.default, o ) )`.

**Therefore the initial value a control renders is `o.default`** — the value
computed **in PHP at `customize_register` time**, not a `WP_Customize_Setting`
value and not a separate localized palette global. For the two settings
(`inc/customizer/configurations/colors-background/class-astra-body-colors-configs.php:36`):

| Setting name | Control | `default` | `datastore_type` | `transport` |
| --- | --- | --- | --- | --- |
| `astra-settings[global-color-palette]` | `ast-hidden` | `astra_get_option('global-color-palette')` | `option` | `postMessage` |
| `astra-color-palettes` | `ast-color-palette` (visible pickers) | `astra_get_palette_colors()` | `option` | `postMessage` |

Because the visible pickers' `default` = `astra_get_palette_colors()` = live
`get_option('astra-color-palettes')`, the swatches are only ever as fresh as that
option. Nothing between the DB and the control caches it.

---

## 5. Cache layers that touch these options

| Layer | Applies to | Location | Invalidation |
| --- | --- | --- | --- |
| `Astra_Theme_Options::$db_options` (per-request static, merged w/ defaults) | `global-color-palette` via `astra_get_option()` | `class-astra-theme-options.php:50,943,950` | Per-request `after_setup_theme` `refresh()`; explicit `refresh()` in preview_init / customize_save / updater. **No `update_option` hook.** |
| `Astra_Theme_Options::$astra_options` (per-request static, raw DB) | same | `:66,931` | null-check + `is_customize_preview()` bypass |
| WP core option cache (`alloptions`) | both options | WP core `get_option()` | WP core on `update_option` |
| Config `default` snapshot → JS | both, as the control's initial value | `class-astra-customizer.php:913,1168` + `build/index.js` | recomputed each `customize_register` |
| Dynamic-CSS / asset **file** cache (`Astra_Cache_Base`) | rendered palette CSS | `astra-update-functions.php:19` | **Not present in the free theme** — the class is Pro-only, so `astra_clear_all_assets_cache()` is a no-op and CSS is regenerated & inlined every request |

**Environment confirmed on this install:** no persistent object cache
(`wp_using_ext_object_cache() === false`), **opcache not installed**. So options
are always fresh from the DB across requests, and PHP edits take effect
immediately. **No transients or `wp_cache_*`** are used by Astra for these options.

---

## 6. The Customizer changeset layer (secondary factor)

The Customizer overlays staged **changeset** values on top of option values. On
this install there are stale `customize_changeset` **auto-draft** posts that hold
old palette values (e.g. post 186 stages `astra-color-palettes[palette_1][0] = #e217e8`
while the DB option holds the synced value).

WordPress core behaviour (`WP_Customize_Manager::establish_loaded_changeset()`,
`wp-includes/class-wp-customize-manager.php:624`):
- Changeset **branching defaults to `false`** (linear).
- When no `?changeset_uuid=` is in the URL, the resume query **excludes
  `auto-draft`** status — it only resumes `draft`/`pending`/`future`. All the
  stale changesets here are `auto-draft`, so a plain `customize.php` load does
  **not** resume them; it mints a fresh empty changeset.

So stale auto-drafts do not normally shadow a fresh load. They can still surface
via the Customizer's client-side **autosave-restore** ("You have unpublished
changes — restore?"). Recommended hygiene: discard unpublished changes in the
Customizer, or garbage-collect old auto-draft changesets. This is **not** the
primary cause of the reported staleness.

---

## 7. Why the sync appeared to "not update", and the fix

**Timeline observed on this install**
1. Sync wrote `astra-settings['global-color-palette']` but not `astra-color-palettes`
   → pickers stale. (Two options diverged: `#1eb027` vs `#17e826`.)
2. A mirror into `astra-color-palettes` was added — but placed **after** the
   `astra-settings` "already in sync" early-return. When only the picker option
   was stale, `write()` bailed before mirroring, so it never healed.
3. Changing the Style Guide colour made `astra-settings` change too, which let the
   mirror run — both options converged (`#fa5c16`). This is why it "sometimes"
   worked (only on a genuine colour change).

**Fix** (`AstraPaletteAdapter::write()`): compute the target slots once, then
reconcile the two option stores **independently**:
- Update `astra-settings['global-color-palette']` only if its slots differ.
- Reconcile `astra-color-palettes[currentPalette]` on its own via
  `mirror_to_color_palettes()`, which returns whether it changed anything.
- Return `true` if **either** store changed; `false` only when both already match.

Effect: a stale picker option is healed on the **next sync of any kind**, even
when `astra-settings` is already correct — no colour change required.

---

## 8. Verification performed

- `astra_get_palette_colors()` reads `get_option('astra-color-palettes')` live — no cache. ✔
- No persistent object cache; opcache absent. ✔
- Customizer control initial value = PHP `default` snapshot (client-side `wp.customize.Setting`). ✔
- Regression test — arranged the exact gap (`astra-settings` correct,
  `astra-color-palettes` stale); one `write()` healed the picker option and
  returned `true`; a second `write()` was a clean no-op (`false`). Originals
  restored afterwards. ✔
- PHPCS clean; PHPStan (level 9) clean. ✔

---

## 9. Recommendations / follow-ups

1. **Ship the independent-reconcile `write()`** (done in this branch) so the
   picker option self-heals.
2. **Optional:** on sync, clear stale `customize_changeset` auto-drafts (or strip
   the palette keys from them) so the Customizer's autosave-restore can't offer
   old values. Low priority — not the primary cause.
3. **Note on same-request read-backs:** never verify an Astra write via
   `astra_get_option()` in the same request (per-request static, no `update_option`
   invalidation) — read the DB option directly.
4. The visible pickers depend only on `astra-color-palettes`; the hidden
   `global-color-palette` control drives the rendered CSS. Keep both in sync,
   index-for-index, exactly as Astra's own updater does
   (`inc/abilities/customizer/globals/colors/class-astra-update-global-palette.php:276`).
5. **Slots 7/8 allotment (open decision):** the sync no longer manages Astra's
   "Subtle background" (slot 7) and "Other supporting" (slot 8) — their old
   sources (the interpolated `neutral-3`/`neutral-6`) were removed with the
   colour auto-generation. Decide which stored colour (if any) should own each
   slot, then add the two rows back to
   `AstraPaletteAdapter::SEMANTIC_TOKENS`/`semantic_index()` and the two
   `ASTRA_SHADE_MAP` copies (bridge + liveVars.js).
