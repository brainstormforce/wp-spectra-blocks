# Spectra One Color Sync — Storage & Rendering Audit

**Scope:** How the **Spectra One** (FSE) theme stores, reads, renders, and syncs its
colours with the Style Guide — the block-theme counterpart to the Astra audit
(`astra-color-sync-audit.md`). Spectra One is a pure block theme, so the mechanism
is `theme.json` layers + the `wp_global_styles` post, **not** an Astra-style option
palette or Customizer.

**Audited theme:** `wp-content/themes/spectra-one` (v1.2.2, text domain `spectra-one`, prefix `swt`, namespace `Swt\`).
**Related code:** `includes/StyleGuide/Sync/SpectraOne/class-spectra-one-compat.php`, `includes/StyleGuide/class-global-styles-bridge.php`, `includes/StyleGuide/Sync/class-fse-global-styles-adapter.php`.
**Date:** 2026-07-24.

> ℹ️ **Style-Guide side (v2 storage).** The Style Guide uses the v2 colour storage
> (see [`style-guide-color-rewrite.md`](./style-guide-color-rewrite.md)). The key
> facts for the Spectra One sync:
> - `SpectraOneCompat` lives in **`Sync/SpectraOne/`** (namespace
>   `…\Sync\SpectraOne`).
> - Both PULL paths (palette + element edits) route through
>   `SyncOrchestrator::apply_reverse_colors()`: each pulled token maps to its
>   owning core slug (`ColorModel::slug_for_token()`) and writes
>   **`config['colors'][slug]`** — the pulled hex simply becomes the stored role
>   colour.
> - The palette PULL covers **every mapped role** (all 9 in the curated profile —
>   brand + neutrals).
> - The slug → token mapping in §2 lives as the `ColorModel::SEMANTIC_MAP` code
>   constant (never stored in the config).

---

## 1. TL;DR — how it works

Spectra One is **pure FSE**: every colour is a `theme.json` palette slug that
WordPress resolves to a `--wp--preset--color--<slug>` CSS variable. The theme adds
**no** dynamic colour CSS and **caches nothing** colour-related — it relies entirely
on WordPress core's theme.json resolver plus the `wp_global_styles` post.

The Style Guide drives Spectra One's colours through **two push paths**, both
theme-agnostic:

| Path | Layer | Code | What it does |
| --- | --- | --- | --- |
| Runtime injection | **theme layer** (`wp_theme_json_data_theme`) | `GlobalStylesBridge::inject_palette` (@20) + `SpectraOneCompat::override_theme_colors` (@30) | Rewrites each palette slug's colour to the computed Style-Guide value on every request |
| Durable write | **user layer** (`wp_global_styles` post) | `FseGlobalStylesAdapter::write()` | Persists mapped slugs into the user global-styles post via a raw `$wpdb->update` |

Because Spectra One binds every element to a slug (`button bg = var(--wp--preset--color--primary)`),
the sync only changes *what the slug resolves to* — it never rewrites templates or
patterns. There is no "stale Customizer" problem like Astra; the analogue is the
**user layer (`wp_global_styles`)** shadowing the theme layer, which the bridge
reconciles at runtime (see §6).

**Two-way:** every **palette swatch in the curated mapping** (all 9 roles — brand +
neutrals) round-trips, **and** all the Site-Editor **element colour settings**
(Text/Background/Link/Heading/Button/Captions) round-trip — each pulled value is
written to the stored colour that owns its token, `config['colors'][slug]` (§2.3).

---

## 2. Color Sync Mapping (reference)

> **Living reference** — colours today; extend per style category (typography, etc.)
> as the sync grows. Mapping only, no technical steps.

### 2.1 The mapping — which Spectra One colour = which Style Guide colour

Read every row left-to-right: **this Spectra One colour becomes this Style Guide colour.**

#### A. Brand colours (two-way ⇄)

| Spectra One slug | Label | Theme default | Sync | → Style Guide colour | SG token | SG default |
| --- | --- | --- | :--: | --- | --- | --- |
| `primary` | Primary | `#6431F6` | ⇄ | **Primary** | `chromatic1-7` | `#6431f6` |
| `secondary` | Secondary | `#7345F7` | ⇄ | **Secondary** | `chromatic2-7` | `#7345f7` |
| `accent` \* | Accent | *(injected)* | ⇄ | **Accent** | `chromatic3-7` | `#f59e0b` |

\* `accent` is **not** a native `theme.json` slug — the Style Guide adds it to the
palette. It is still two-way because it maps to a brand seed (`chromatic3`). This is
the key difference from Astra, which has no accent slot at all.

#### B. Neutrals (two-way ⇄) & tints/mirrors (push-only →)

| Spectra One slug | Label | Theme default | Sync | → Style Guide colour | SG token | SG default |
| --- | --- | --- | :--: | --- | --- | --- |
| `heading` | Heading | `#1F2937` | ⇄ | **Heading text** | `neutral-7` | computed |
| `body` | Body | `#4B5563` | ⇄ | **Body text** | `neutral-5` | computed |
| `background` | Background | `#FFFFFF` | ⇄ | **Background** (page) | `neutral-0` | `#ffffff` |
| `surface` | Surface | `#F8FAFC` | ⇄ | **Surface** | `neutral-1` | computed |
| `outline` | Outline | `#E6E9EF` | ⇄ | **Border / Outline** | `neutral-2` | computed |
| `neutral` | Neutral | `#6E7787` | ⇄ | **Muted** | `neutral-4` | computed |
| `tertiary` | Tertiary | `#F6EBFE` | — | *unmanaged* (was a primary tint — tint shades are no longer generated) | — | — |
| `quaternary` | Quaternary | `#FFFBEB` | — | *unmanaged* (was a secondary tint) | — | — |
| `foreground` | Foreground | `#6431F7` | → | **Primary** (mirror) | `chromatic1-7` | `#6431f6` |

Spectra One's `primary`/`secondary` theme defaults (`#6431F6` / `#7345F7`) match the
Style Guide seeds exactly — it's the sibling theme. `heading`/`body` are the theme's
own values; the Style Guide's `neutral-7`/`neutral-5` are stored colours and may differ.

**Sync direction legend**
- `⇄` **two-way** — a Style Guide save updates the theme, *and* editing that colour
  in the Site Editor updates the Style Guide. All **9 curated palette swatches**
  (3 brand + 6 neutrals) are two-way, **and** all the **element colour settings**
  (Text/Background/Link/Heading/Button/Captions) are two-way — see §2.3.
- `—` **unmanaged** — `tertiary`/`quaternary` kept the theme's own values: they were
  driven by generated tint shades, which no longer exist.
- `→` **push-only** — Style Guide → theme only. `foreground` mirrors Primary, so a
  direct edit of that swatch is not pulled back.

### 2.2 Colours the Style Guide injects (no native slot)

Beyond the 11 native palette slugs, `inject_palette` / `override_theme_colors` add
the full semantic set from the config's `semantic_map`, so these resolve as
`--wp--preset--color--<slug>` too even though `theme.json` doesn't define them:

| Injected slug | → Style Guide colour | SG token | Two-way? |
| --- | --- | --- | --- |
| `accent` | Accent | `chromatic3-7` | ⇄ (brand) |
| `success` | Success | `chromatic4-7` | → |
| `error` | Error | `chromatic5-7` | → |
| `info` | Info | `chromatic6-7` | → |
| `warning` | Warning | `chromatic7-7` | → |
| `sg-accent` / `sg-heading` / `sg-body` / `sg-surface` / `sg-background` / `sg-border` / `sg-muted` | migration-compat aliases | (mirror of the above) | → |

(`sg-secondary` and `sg-neutral` are no longer injected — their tokens were the
generated `chromatic1-5` tint and interpolated `neutral-6`, which were removed.)

The `sg-*` family exists for cross-theme pattern compatibility; on the front end it
is also emitted directly on `:root` via `get_sg_preset_css()` so a user-layer edit
can't strip it.

### 2.3 Element colour settings (Site Editor → Styles → Colors) — two-way

Spectra One binds elements to slugs in `theme.json` `styles`, so changing a slug's
value cascades automatically. Each element setting is **two-way**: a custom
colour set on an element in the Site Editor is pulled into the stored Style Guide
colour its default binding uses, then the element override is cleared so it
inherits the palette `var()` again (Style Guide stays the single source of truth).

| Element setting | Bound to | → SG token | Writes stored colour |
| --- | --- | --- | --- |
| **Text** | `var(--…--body)` | `neutral-5` | `colors['body']` |
| **Background** | `var(--…--background)` | `neutral-0` | `colors['background']` |
| **Link** | `var(--…--primary)` | `chromatic1-7` | `colors['primary']` |
| **Link — hover** | `var(--…--secondary)` | `chromatic2-7` | `colors['secondary']` |
| **Heading** | `var(--…--heading)` | `neutral-7` | `colors['heading']` |
| **Button — background** | `var(--…--primary)` | `chromatic1-7` | `colors['primary']` |
| **Button — text** | `var(--…--background)` | `neutral-0` | `colors['background']` |
| **Button — hover bg** | `var(--…--secondary)` | `chromatic2-7` | `colors['secondary']` |
| **Captions** | inherits Text | `neutral-5` | `colors['body']` |

Mechanics (`SpectraOneCompat::pull_element_colors`, `save_post_wp_global_styles`@20):
- **Only a literal custom hex is pulled.** An element left as `var(--…--slug)` is
  just a rebind to an existing swatch (already covered by the palette sync) and is
  ignored.
- Each pulled token maps to the core role slug that owns it
  (`ColorModel::slug_for_token()`) and the hex is written to
  **`config['colors'][slug]`**, via the shared
  `SyncOrchestrator::apply_reverse_colors()`.
- After pulling, the element's `styles.*` override is **stripped** (raw `$wpdb`
  write, no `save_post`) so it reverts to inheriting the palette `var()`.

> **Shared-role side effect.** Because the theme reuses slugs across elements, some
> element edits move a stored colour shared with others: e.g. **Button text** is
> bound to `background`, so editing it also shifts the page **Background**;
> **Button background** and **Link** both write `colors['primary']`. This is
> faithful to Spectra One's own `var()` bindings, not a sync bug. On a shared role
> the last element processed wins.

Block styles `swt-button-secondary` (body/surface) and `swt-button-inverse` (the one
hardcoded pair `#1F2937`/`#fff`) are unaffected.

### 2.4 Notes on the values

- **Style variations.** Spectra One ships 8 `styles/*.json` variations (Mango, Pink,
  Rain Forest, Ultra Marine, Aquamarine, Dark, Easter Green, Sweet Corn). Each
  overrides the **same slug set** with different hexes. The mapping above is
  slug-based, so it holds for every variation — only the raw defaults differ.
- **SG neutral defaults are computed** (OKLCH-derived from the brand seed); only
  `neutral-0` = `#ffffff` is a fixed literal. Neutral rows describe the value rather
  than pin a hex.
- **`foreground` mirrors `primary`** (both → `chromatic1-7`); it is a separate slug,
  so it's push-only even though it equals the two-way Primary.
- **`transparent` / `currentColor` / `inherit`** are utility palette entries and are
  never synced.

### 2.5 Future mappings

Reserved for the next style categories as the sync grows. Append a table per
category using the same "Spectra One → Style Guide" shape.

- **Typography** — *TBD* (font family/size/weight/line-height).
- **Spacing / other** — *TBD*.

---

## 3. Where the colours live and how they are read

Three layers feed WordPress's theme.json resolver, lowest → highest precedence:

1. **Theme layer** — `theme.json` `settings.color.palette` (11 slugs + 3 utility),
   optionally replaced by the active `styles/*.json` variation. Rewritten at runtime
   by the Style Guide's `wp_theme_json_data_theme` filters (bridge @20, compat @30).
   Regenerated every request (subject to WP's theme.json cache), so it is the
   always-live source.
2. **User layer** — the `wp_global_styles` CPT (one post, `wp-global-styles-spectra-one`).
   Highest precedence. Written durably by `FseGlobalStylesAdapter::write()` and read
   by the Site Editor; filtered at runtime by `normalize_user_palette_names` (@20)
   and `maybe_override_managed_user_palette` (@21).
3. **WP core theme.json resolver cache** — flushed by the adapter after each write
   (`clean_post_cache()` + `wp_clean_theme_json_cache()`).

The theme reads its own palette via `\Swt\get_theme_json()` (`file_get_contents` on
`theme.json`) and the user post via `get_posts()` on `wp_global_styles`
(`inc/theme-options.php`), with **no** caching of its own.

---

## 4. How the editor and front end render

Pure FSE resolution — WordPress compiles the three layers into
`--wp--preset--color--*` custom properties. On top of that the bridge adds
belt-and-suspenders `:root` output so a Style Guide save is visible immediately:

- **Front end:** `GlobalStylesBridge::enqueue_frontend_css()` emits the `--spectra-*`
  token vars, `get_sg_preset_css()` `--wp--preset--color--sg-*` vars, and Astra-compat
  aliases directly on `:root` — bypassing the user layer entirely.
- **Editor:** `enqueue_editor_css()` + iframe injection via `block_editor_settings_all`
  push the same vars into the Site Editor canvas; the durable user-layer write keeps
  the editor's own reads in sync.

The theme itself only enqueues static compiled CSS (`assets/css/…`) and registers
block styles (`inc/block-styles/*` on `init`@10) whose `inline_style` uses the CSS
vars — no dynamic colour generation.

---

## 5. Caching layers

| Layer | Applies to | Invalidation |
| --- | --- | --- |
| WP core theme.json resolver cache | the merged theme+user palette | flushed by the FSE adapter (`wp_clean_theme_json_cache()`) after each write |
| `wp_global_styles` post cache | the user layer | `clean_post_cache()` after each write |
| Runtime theme-layer injection | every request | recomputed each request from the Style Guide tokens |

**The theme caches nothing** colour-related (no transients, no `wp_cache_*`, no
generated CSS files) — confirmed by searching `inc/` + `functions.php`. This is why,
unlike Astra, there is no cache/Customizer staleness class of bug here; a Style Guide
save is live once the theme.json cache is flushed (which the adapter does).

---

## 6. The two-way sync

### PUSH (Style Guide → Spectra One)
`SyncOrchestrator::push_to_theme()` fires on `spectra_style_guide_config_saved`. For
the FSE path it builds a generic role → slug patch (`MappingResolver` spectra-one
profile) and hands it to `FseGlobalStylesAdapter::write()`, which patches the mapped
slugs into the user `wp_global_styles` post via **raw `$wpdb->update`** (preserving
foreign/`custom` entries, keeping the `isGlobalStylesUserThemeJSON` envelope). In
parallel, `inject_palette` + `override_theme_colors` rewrite the theme layer at
runtime. A diff-check skips the write when nothing changed.

**MappingResolver — Spectra One curated profile** (role → slug):
`primary→primary, secondary→secondary, accent→accent, page-background→background,
surface→surface, body-text→body, heading-text→heading, border→outline,
muted→neutral`. `link` is intentionally omitted (the theme routes links through
`primary`).

### PULL (Spectra One → Style Guide)
Two handlers fire on `save_post_wp_global_styles` (a real Site-Editor save):
1. **Palette swatches** — `SyncOrchestrator::pull_from_theme()` (@10) reads the FSE
   palette and pulls **every role in the curated mapping** (all 9 — brand +
   neutrals, via `$mapping->mapped_roles()`).
2. **Element settings** — `SpectraOneCompat::pull_element_colors()` (@20) reads
   `styles.color`/`styles.elements.*`; each element left as a literal custom hex is
   pulled to its token and its override stripped (§2.3).

Both feed the shared `SyncOrchestrator::apply_reverse_colors()`, which writes each
pulled token's owning stored colour (`config['colors'][slug]`) and saves once, then
recomputes and re-pushes the harmonized palette.

### Loop guards
1. The FSE adapter's raw `$wpdb->update` does **not** fire `save_post_wp_global_styles`,
   so a PUSH never triggers a PULL.
2. `SyncOrchestrator::$syncing` blocks the pull → save → push round-trip from
   re-entering the pull.
3. `pull_from_theme()` acts only on the active theme's user post; `push_to_theme()`
   diff-checks before writing.
4. `pull_element_colors()` bails on `SyncOrchestrator::is_syncing()`, and its
   override-clearing write is also raw `$wpdb` (no `save_post`), so it can't loop.

> **Historical note:** an earlier `heal_global_styles_entity` REST filter (which
> stripped managed presets from the raw `wp_global_styles` REST entity) has been
> **removed** (shipped as its own chunk against `release/1.0.2`). The remaining
> user-layer reconciliation is `normalize_user_palette_names` (@20) +
> `maybe_override_managed_user_palette` (@21), which overwrite stale managed slugs at
> runtime rather than healing the stored entity.

---

## 7. Verification

- **Palette source of truth:** `theme.json` `settings.color.palette` (11 slugs +
  utilities); confirmed no `sg-*` slugs are native (those are injected). ✔
- **No theme-side caching** of colours (grep of `inc/` + `functions.php`). ✔
- **Slug bindings:** patterns/templates/styles reference colours by slug/CSS-var, not
  hex (only exceptions: `swt-button-inverse` hardcode + core social-icon `#ffffff`). ✔
- **Palette round-trip:** a Site-Editor edit of any mapped swatch writes the owning
  `colors[slug]` and re-harmonizes; a Style Guide save updates the theme + user
  layers and is visible after the theme.json cache flush.
- **Element round-trip (§2.3):** a literal-hex **Text** edit writes `colors['body']`;
  a literal-hex **Link** edit writes `colors['primary']`; both element overrides are
  then stripped from the post; an element left as `var(--…)` is ignored; no sync
  loop. (Last live-verified on `localhost:10018` against the pre-v2 implementation —
  re-verification under v2 storage is pending, see
  [`style-guide-color-rewrite.md`](./style-guide-color-rewrite.md) §9.)

---

## 8. Recommendations / differences from Astra

| Aspect | Astra | Spectra One |
| --- | --- | --- |
| Storage | `astra-settings` option + `astra-color-palettes` (Customizer) | `theme.json` + `wp_global_styles` post |
| UI surface | Customizer (stateful; changeset caveats) | Site Editor / Styles (FSE) |
| Accent | no slot (unmapped) | `accent` slug (injected) — **two-way** |
| Element settings two-way | n/a (option-based) | **yes** — Text/Background/Link/Heading/Button/Captions (§2.3) |
| Neutral **palette swatches** two-way | **yes** for the 7 managed slots (7/8 unmanaged) | **yes** (all 9 curated roles write `colors[slug]` directly) |
| Theme-side cache | per-request static + generated CSS (Pro) | none |

**Remaining gap / follow-up:** `tertiary`/`quaternary` are unmanaged (they were
driven by generated tint shades, removed with the colour auto-generation) and the
`foreground` mirror is push-only. Managing the tints again would require deciding
which stored colour should own each slug.
