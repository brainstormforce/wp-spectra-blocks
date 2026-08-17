# Style Guide & Colors — System Audit (pre-rewrite, HISTORICAL)

> ⚠️ **SUPERSEDED.** This is the frozen snapshot of the **v1** system as it stood
> before the v2 rewrite. The v2 rewrite has since shipped: storage is now the
> 9-colour `colors` map + `custom_colors` (chromatics/neutral-tint/semantic_map/
> semantic_overrides/token_overrides are gone from storage), the DNA presets and
> the free colours UI were removed, and the theme mappers moved to `Sync/Astra/` /
> `Sync/SpectraOne/`. **For the current architecture read
> [`style-guide-color-rewrite.md`](./style-guide-color-rewrite.md).** This file
> stays as the record of what v1 was and why each dependency existed.
> Companion docs: [`astra-color-sync-audit.md`](./astra-color-sync-audit.md),
> [`spectra-one-color-sync-audit.md`](./spectra-one-color-sync-audit.md),
> [`GBS-ARCHITECTURE.md`](./GBS-ARCHITECTURE.md).

## 1. TL;DR

- The **engine is in the free plugin** (`spectra-blocks/includes/StyleGuide/`); Pro
  layers the editor UI + Style-DNA presets on top via a filter + action hook.
- Colours are **generated**, not stored per-slug: 3 brand + 4 status **chromatics**
  and a single **neutral base** expand (in OKLCH) into ~**103 colour tokens** + 100+
  UI-styling tokens, **57 section schemes**, and an **86-entry WP preset palette**.
- Two **override layers** sit on top: `semantic_overrides` (slug→hex, WP-preset layer
  only) and `token_overrides` (token→hex, applied inside `compute()` so pins reach the
  theme push).
- Two **unrelated preset systems**: the free `presetCatalog` (12 style dimensions / 44
  options, **0 colour presets**) and the Pro `STYLE_DNA_PRESETS` (**6** full presets).
- Colours **sync two-way** into themes through a 7-class Sync subsystem: **Astra**
  (dual-option palette) and **Spectra One / FSE** (`wp_global_styles` post) are the two
  wired adapters; brand roles round-trip, neutrals are push-only (+ Spectra One element
  pull).

## 2. Two-plugin split

| Concern | Lives in | Entry |
|---|---|---|
| Colour engine, shade math, tokens, schemes, sync, REST | **free** `spectra-blocks` | `includes/StyleGuide/class-engine.php` |
| Editor modal UI, Style-DNA presets, tab layout | **pro** `spectra-blocks-pro` | `src/extensions/gbs-editor-v2/` |
| Legacy free editor panels (colors/typography/presets) | **free** | `src/extensions/gbs-editor/` |

Coordination: Pro hooks the free engine via the `spectra_style_guide_config_before_save`
filter (injects Pro fields) and the `spectra_style_guide_engine_loaded` action.
`OPTION_KEY = spectra_blocks_pro_style_guide` (kept with the pro prefix for back-compat).

## 3. The colour model (engine)

`class-engine.php` is a singleton wiring `ShadeGenerator → ContrastEngine →
SchemeComputer → TokenRegistry`, plus `GlobalStylesBridge`.

### 3.1 Chromatics — 7 seed colours
Default config (`get_default_config()` L773-845; status SSOT `$status_chromatic_defaults` L618-635):

| # | Role | Default hex | Notes |
|---|---|---|---|
| 1 | Primary | `#6431f6` | `isMain: true` (drives schemes) |
| 2 | Secondary | `#7345f7` | brand |
| 3 | Accent | `#f59e0b` | brand |
| 4 | Success | `#10b981` | status |
| 5 | Error | `#ef4444` | status |
| 6 | Info | `#8b5cf6` | status |
| 7 | Warning | `#d97706` | status |

Each chromatic expands into **11 shades**: 7 light (`chromatic{N}-1..7`, OKLCH lightness
ramp) + 4 dark (`chromatic{N}-8..11`, black-mix). `chromatic{N}-7` == the seed hex
(lossless), which the reverse-sync relies on.

### 3.2 Neutral ramp — computed from one base
`neutral` config = `{ tintIndex: 1, tintStrength: 0.05 }`. `ShadeGenerator::tint_neutral_oklch()`
builds a near-black base (`L=0.15`) carrying the tint-index chromatic's hue at
`tintStrength`, then `generate_neutral_shades()` produces **8 shades** `neutral-0..7`
(0 = near-white, 7 = darkest). There is **no per-shade neutral storage** — the whole
ramp is derived from `tintIndex`/`tintStrength`.

### 3.3 ShadeGenerator (OKLCH, all static) — `class-shade-generator.php`
- `NEUTRAL_RATIOS` (8), `CHROMATIC_RATIOS` (7), `BLACK_RATIOS` (4), `OPACITY_LEVELS`
  (8: 5/10/15/20/30/40/50/60).
- `SATURATION_PRESETS` (vivid 0.7 / balanced 1.0 / muted 1.5), `SHADE_PROFILES`
  (punchy/balanced/soft, 7 stops), `NEUTRAL_SHADE_PROFILES` (8 stops incl. pure white).
- OKLCH conversion with gamut-mapped `oklch_to_hex()` (chroma binary search).

### 3.4 TokenRegistry — `class-token-registry.php`
SSOT for CSS-var names/values, schemes, palette. `PREFIX = 'spectra'`.
- Token keys: `chromatic{N}-{shade}`, `neutral-{0-7}`. CSS vars: `--spectra-<name>`.
  WP palette slugs: `spectra-<kebab-name>` → `var(--wp--preset--color--spectra-…)`.
- `register_ui_tokens()` and the preset-derived UI token set (buttons/cards/shadows/
  roundness/inputs/badges/motion/…) were **REMOVED** — the Style Guide is colours-only.
  Those `--spectra-*` component tokens are now static defaults baked into
  `assets/css/component-tokens.css`.
- `get_wp_palette()` exposes **shade tokens + `white` only** (`is_shade_token()` regex).

### 3.5 `compute()` pipeline (L353-467)
1. Neutral shades (8) → `neutral-0..7`.
2. Per chromatic: 7 light + 4 dark shades; colour name stored.
3. Constants `white`, `transparent`.
4. Opacity tokens (8 dark + 8 light from `neutral-7`).
5. **`token_overrides` pins overlaid on derived shades** (before schemes) — so pins flow
   to the theme push.
6. Schemes — main accent = `isMain` chromatic; `SchemeComputer::compute_all_schemes()`.
7. UI tokens via `register_ui_tokens($presets)`.
8. Cache unless preview.

### 3.6 Semantic map (role → token) — 25 roles
`get_config()` L656-691 (canonical), mirrored in `get_default_config()`:

- **Core (11):** primary→`chromatic1-7`, secondary→`chromatic2-7`, tertiary→`chromatic1-2`,
  quaternary→`chromatic2-2`, heading→`neutral-7`, body→`neutral-5`, background→`neutral-0`,
  foreground→`chromatic1-7`, surface→`neutral-1`, outline→`neutral-2`, neutral→`neutral-4`.
- **Era/status (5):** accent→`chromatic3-7`, success→`chromatic4-7`, error→`chromatic5-7`,
  info→`chromatic6-7`, warning→`chromatic7-7`.
- **`sg-*` (9):** sg-accent→`chromatic1-7`, sg-secondary→`chromatic1-5`, sg-heading→`neutral-7`,
  sg-body→`neutral-5`, sg-surface→`neutral-1`, sg-background→`neutral-0`, sg-border→`neutral-2`,
  sg-neutral→`neutral-6`, sg-muted→`neutral-4`.

> ⚠️ Documented quirk: the `sg-*` namespace is **not** a 1:1 alias of the plain namespace
> (e.g. `secondary→chromatic2-7` but `sg-secondary→chromatic1-5`; `sg-neutral→neutral-6`).
> The rewrite must decide whether to unify these.

### 3.7 Override layers
| Layer | Key shape | Applied | Wins over |
|---|---|---|---|
| `token_overrides` | token → hex (`neutral-5`) | inside `compute()` (§3.5 step 5) | derived shade; **reaches theme push** |
| `semantic_overrides` | slug → hex (`primary`) | WP-preset layer (`inject_palette`) | shade derivation at render only |

`get_derived_token()` returns the *un-pinned* neutral value — used by reverse-sync to
detect divergence (pin) vs convergence (clear).

### 3.8 Counts (default config, 7 chromatics)
- Colour tokens: neutrals 8 + chromatics 77 + constants 2 + opacity 16 ≈ **103**.
- UI-styling tokens: **100+** (shadows/radii/buttons/cards/spacing/type/…).
- WP palette entries: **86** (shade tokens + white).
- Schemes: **57** (8 neutral + 7×7 chromatic).

## 4. Presets — REMOVED

The Style Guide is **colours-only**, so both preset systems were removed:
- Free `presetCatalog.js` (the UI-style dimensions: buttons/cards/shadows/roundness/
  inputs/badges/motion/shades/saturation) — gone.
- Pro `STYLE_DNA_PRESETS` (`style-dna-presets.js`) plus the `GET /style-guide/dna-presets`
  route and `get_dna_presets()` — gone.

There are no preset or typography inputs left; the stored config carries only
`colors` + `custom_colors`.

## 5. REST surface — `spectra-blocks/v1` (perm: `edit_theme_options`)
| Route | Method | Returns |
|---|---|---|
| `/style-guide/config` | GET | stored config |
| `/style-guide/config` | POST | `{ success, config, tokens, schemes, palette }` (option-only save) |
| `/style-guide/compute` | GET | `{ tokens, schemes, palette, css }` |
| `/style-guide/preview` | POST | draft compute, no persist; `+ css_full` |

> There is **no** `/save` route — saving is `POST /config`.

## 6. Theme adapters & sync (`Sync/`, 7 classes)

Registered from `GlobalStylesBridge::init()` → `new SyncOrchestrator($engine)->register()`.

### 6.1 SyncOrchestrator — `class-sync-orchestrator.php`
The only piece that knows both roles and stores. `$syncing` static re-entrancy guard.
- Hooks: `spectra_style_guide_config_saved`→**push_to_theme**; `save_post_wp_global_styles`→
  **pull_from_theme**; `update_option_astra-settings`→**pull_from_astra**; `switch_theme`→push;
  `admin_init`→`maybe_resync_on_upgrade` (version-stamped re-push).
- `adapters()` = `[ FseGlobalStylesAdapter, AstraPaletteAdapter ]` (each self-gates).
- `push_to_theme()` — resolve role→slug→hex, diff vs `read()` before write.
- `pull_from_theme()` (FSE) / `pull_from_astra()` (changed slots only) → `apply_reverse_colors()`.
- `apply_reverse_colors($token_hexes)` — **shared reverse applier**: brand seed
  (`chromatic{N}-7`) reseeds `chromatics[N].hex`; neutral tokens **pin on divergence /
  clear on convergence** (vs `get_derived_token()`); one guarded `save_config()`.

### 6.2 Supporting role classes
- **ColorSyncAdapter** (`class-color-sync-adapter.php`) — interface: `is_supported/read/write/resolve_patch/label`.
- **ColorRoles** (`class-color-roles.php`) — theme-independent vocabulary. Brand (two-way):
  primary/secondary/accent; neutral (push-only): page-background/surface/body-text/
  heading-text/link/border/muted. `BRAND_CHROMATIC`, `SG_TOKEN`.
- **MappingResolver** (`class-mapping-resolver.php`) — curated profiles per stylesheet
  (`spectra-one`, `twentytwentyfive`, `astra`), child→parent fallback, tier-2 auto-derive
  from theme `styles`; filterable via `spectra_style_guide_theme_color_mapping`.
- **ThemeColorMapping** (`class-theme-color-mapping.php`) — immutable role↔slug value object.

### 6.3 AstraPaletteAdapter — `class-astra-palette-adapter.php`
Astra's hybrid palette: `astra-settings['global-color-palette']` (CSS source) + standalone
`astra-color-palettes` (Customizer swatches). `SLUG_PREFIX = ast-global-color-`.
- `SEMANTIC_TOKENS` — **9 slots, 1:1**: brand→`chromatic1-7`, alt-brand→`chromatic2-7`,
  headings→`neutral-7`, text→`neutral-5`, primary-bg→`neutral-0`, secondary-bg→`neutral-1`,
  alternate-bg→`neutral-2`, subtle-bg→`neutral-3`, other-supporting→`neutral-6`.
- `write()` patches active slots + **mirrors both stores** + `astra_clear_all_assets_cache`.
  `mirror_to_color_palettes()` self-heals divergence of the Customizer option.
- `semantic_index()` is **flag-aware** (Astra 4.8.9 background-slot reorder).
- `reverse_map()` (slot→token), `brand_chromatic_index()` (static). `$writing` guard.
- Full detail: [`astra-color-sync-audit.md`](./astra-color-sync-audit.md).

### 6.4 FseGlobalStylesAdapter — `class-fse-global-styles-adapter.php`
Pure-FSE store in the `wp_global_styles` user post; store mechanics only.
- Gated on `wp_is_block_theme()`; whole-palette write via **raw `$wpdb->update`** (bypasses
  `content_save_pre`, does **not** fire `save_post` → no sync loop); preserves foreign entries.

### 6.5 SpectraOneCompat — `class-spectra-one-compat.php`
Self-gates on active theme. `ELEMENT_MAP` = 9 Site-Editor element paths → SG token.
- **Push** `override_theme_colors()` (`wp_theme_json_data_theme` prio 30) — replaces Spectra
  One palette entries with computed hex from `get_color_map()` (reads `semantic_map`).
- **Pull** `pull_element_colors()` (`save_post_wp_global_styles` prio 20) — literal-hex element
  overrides → tokens → `apply_reverse_colors()`, then clears overrides so elements re-inherit
  palette vars. Guarded by `is_syncing()`.
- Full detail: [`spectra-one-color-sync-audit.md`](./spectra-one-color-sync-audit.md).

### 6.6 GlobalStylesBridge — `class-global-styles-bridge.php`
Writes computed tokens into native WP Global Styles (`wp_theme_json_data_*` filters).
- `ASTRA_SHADE_MAP` (index→token, editor-only surfaces).
- `inject_palette()` — strip prior `spectra-*`, merge theme palette, resolve `semantic_map`
  slugs → hex, **`semantic_overrides` win**; generic theme override via `ThemeStyleCompat`.
- `maybe_override_managed_user_palette()`, `normalize_user_palette_names()`,
  `get_astra_compat_css()`, `get_sg_preset_css()`, `get_editor_data()`.

## 7. Editor UI

### 7.1 Free — `src/extensions/gbs-editor/`
- **colors/** section family: `PaletteSection`, `SemanticSection` (read-only), `PrimarySection`,
  `NeutralSection`, `SchemesSection`, `ExtendedSection`; `controls/` (`HexColorInput`, `ShadeStrip`).
- **panels/** (11): BlockDefaults, CheatSheet, **Colors**, CustomClasses, CustomVars, FontSize,
  Keyframes, **Presets**, Spacing, Typography, Variables.
- **hooks:** `useGBSConfig` (`/config` GET+POST; save→`pushPaletteToEditor`), `useGBSComputed`
  (`/compute` GET, `/preview` POST debounced 300ms).
- **utils/liveVars.js:** `injectStyleSheet`, `buildPresetPaletteCSS`, `buildAstraAliasCSS`,
  `syncEditorSwatches`, `ASTRA_SHADE_MAP`, `SEMANTIC_MAP`, + many `regenerate*` helpers.

### 7.2 Pro — `src/extensions/gbs-editor-v2/`
- **Shell:** `GBSEditorV2Modal`, `index.js` (module-level open/close store), `GBSV2Header`,
  `GBSV2Logo`, `GBSV2Nav`, `GBSV2ViewRouter`, `PanelFrame`, `navConfig` (7 items / 3 groups).
- **Context:** `StyleGuideContext` — shares one `useGBSConfig` + `useGBSComputed`, owns the draft,
  debounced `preview`, `save`, and `REPLACE_KEYS` (`semantic_overrides`, `token_overrides`).
- **New Colors tab (live):** `StyleGuideView` (tabs), `StyleGuideSubNav` (segmented control),
  `ColorsSection`, `PaletteRow`, `utils/colorMath.js`, `data/paletteRoles.js`.
- **Typography/UI tabs:** `TypographySection` (fonts + scale/weight via `typography-presets.js`),
  `UIStylingSection` (consumes the free `presetCatalog` cross-plugin), `SegmentRow`, `FontCard`.

### 7.3 Dead vs alive after the Colors rebuild (Pro)
| File | Status |
|---|---|
| `ColorGrid.jsx`, `DNAPresetCards.jsx`, `SemanticColorsSection.jsx` | **DEAD** — no imports |
| `NeutralTint` JSX | gone; orphan SCSS at `style.scss` "Neutral tint" section |
| `SegmentRow.jsx` | **alive** (Typography + UIStyling) |
| `dnaConfig.js`, `data/style-dna-presets.js` | **alive** only via `StyleGuideView` shuffle (L25,46-54) |

## 8. Dependency map — what binds to the engine colour model

Critical for a **full rewrite incl. engine** — changing chromatics/neutrals/tokens ripples into:

- **Token names** (`chromatic{N}-{1-11}`, `neutral-{0-7}`) are hard-referenced by:
  AstraPaletteAdapter `SEMANTIC_TOKENS`, SpectraOneCompat `ELEMENT_MAP`, ColorRoles `SG_TOKEN`,
  GlobalStylesBridge `ASTRA_SHADE_MAP`, engine `semantic_map`, free `liveVars.js`
  (`ASTRA_SHADE_MAP`/`SEMANTIC_MAP`), pro `paletteRoles.js` `token`.
- **`semantic_map` slugs** drive `inject_palette`, Astra/Spectra One push, WP preset palette,
  and both editor UIs.
- **Reverse-sync** assumes `chromatic{N}-7 == seed` and `get_derived_token()` for neutrals.
- **Presets:** `register_ui_tokens()` + free `presetCatalog` + pro `STYLE_DNA_PRESETS`
  ("shuffle whole style") all assume the current preset keys.
- **REST shape** `{ tokens, schemes, palette, css }` is consumed by both editors' hooks.

Any engine simplification must update all of the above in lock-step — enumerated as the
retire/rewire sequence in [`style-guide-color-rewrite.md`](./style-guide-color-rewrite.md).

## 9. Recommendations (feed the rewrite)
1. Decide the **canonical token set** first (which of the 103 tokens the new UI actually needs);
   everything else keys off it.
2. Unify or explicitly retire the `sg-*` namespace quirk (§3.6).
3. Treat the **two preset systems** as separate decisions — the free catalog and the Pro DNA
   presets can be retired/kept independently.
4. Keep the **adapter contract** (`ColorSyncAdapter` + `apply_reverse_colors`) stable even if
   the engine internals change — it's the safest seam.
