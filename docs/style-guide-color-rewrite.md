# Style Guide Colors — v2 Architecture (as built)

> The colour system that shipped on `feat/style-guide-ui-rewrite` (free PR #542,
> pro PR #197). Storage keeps **only the nine user-selectable colours** (plus an
> optional custom layer) — and, since the auto-generation removal, the pipeline
> emits **only those colours** too: no shade ramps, no interpolated neutrals, no
> opacity tokens, no schemes. What the Style Guide UI shows (9 roles + 7 derived
> variables + custom colours) is exactly what the site gets.
> The pre-rewrite system is preserved for reference in
> [`style-guide-color-audit.md`](./style-guide-color-audit.md).
> Theme sync details: [`astra-color-sync-audit.md`](./astra-color-sync-audit.md),
> [`spectra-one-color-sync-audit.md`](./spectra-one-color-sync-audit.md).

## 1. TL;DR

- **Stored:** `{ version: 2, colors (9 slug→hex), custom_colors, presets, typography }`
  in the `spectra_blocks_pro_style_guide` option. Nothing else.
- **Emitted at compute:** exactly 13 colour tokens — the 3 brand seeds
  (`chromatic1-7..3-7` = stored primary/secondary/accent), the 4 status seeds
  (`chromatic4-7..7-7`, fixed constants), and the 6 stored neutral stops
  (`neutral-0/1/2/4/5/7`) — plus `white`/`transparent` constants and the UI
  styling tokens. **Nothing is auto-generated**: no ramps, no `neutral-3`/`-6`
  interpolation, no opacity tokens, no schemes.
- **The WP picker palette is the semantic layer only** (9 roles + status +
  `foreground` + surviving `sg-*` aliases + user custom colours) —
  `TokenRegistry::get_wp_palette()` publishes no raw-token swatches.
- **Slugs are first-class:** each stored colour keys directly to
  `--wp--preset--color--<slug>`.
- **No migration:** a non-v2 option (missing `colors`) falls back to the default
  config — v2 is a fresh feature by decision.
- **Two-way sync** writes `colors[slug]` directly (no pin layers); Astra and
  Spectra One mappers are isolated in `Sync/Astra/` and `Sync/SpectraOne/`.
  Astra slots 7/8 and Spectra One `tertiary`/`quaternary` are **unmanaged**
  (their old sources were generated shades).

## 2. Storage (the option, exactly)

```php
[
    'version'       => 2,
    'colors'        => [            // the ONLY colour source of truth
        'primary'    => '#6431f6',  // brand seed (untagged in the UI)
        'secondary'  => '#7345f7',  // brand
        'accent'     => '#f59e0b',  // brand
        'heading'    => '#…',       // neutral anchor  → neutral-7
        'body'       => '#…',       //                 → neutral-5
        'neutral'    => '#…',       //                 → neutral-4
        'outline'    => '#…',       //                 → neutral-2
        'surface'    => '#…',       //                 → neutral-1
        'background' => '#…',       //                 → neutral-0
    ],
    'custom_colors' => [            // optional; empty normally. slug => { hex, name }
        // • user "Add color variable" entries (custom-1, …)
        // • overrides of the derived variables (success, overlay, …)
        // A slug matching a generated preset slug OVERRIDES it at emit;
        // a new slug ADDS a colour. Absorbs the old semantic_overrides
        // + token_overrides layers.
    ],
    // NOTE: `presets` (UI Styling) and `typography` were REMOVED — the Style Guide
    // is colours-only. `save_config()` persists only version/colors/custom_colors,
    // and `get_stored_config()` strips any legacy `presets`/`typography` on read.
]
```

Engine contract (`class-engine.php`):
- `get_stored_config()` — the raw v2 option; falls back to `get_default_config()`
  when the option is absent or not v2-shaped (no `colors` key). No migration.
- `get_config()` — stored config **plus derived transitional keys** for readers
  not yet migrated to `ColorModel`: `semantic_map` (the code constant),
  `chromatics` (rebuilt from `colors` + status constants), `semantic_overrides`
  (from `custom_colors`). These are recomputed on every read and **never persisted**.
- `save_config()` — strips to exactly the five canonical keys above.
- `build_config_from_request()` — sanitizes `colors` (core slugs only,
  `sanitize_hex_color`) and `custom_colors` (full-replace map; `sanitize_key` +
  hex + optional name); Pro merges `typography` + `presets` via the
  `spectra_style_guide_config_before_save` filter (colour keys are never merged).

## 3. ColorModel — the code SSOT (`class-color-model.php`)

Replaces the stored `semantic_map` and the index-keyed chromatics:

- `CORE_ROLES` — the 9 roles: kind (`brand`/`neutral`), token, chromatic index or
  neutral ramp stop.
- `STATUS_COLORS` — fixed Success `#10b981` / Error `#ef4444` / Info `#8b5cf6` /
  Warning `#d97706` → chromatics 4-7 (generated, not stored, not user-editable
  at the palette level; overridable per-slug via `custom_colors`).
- `DEFAULT_BRAND` — fresh-install brand seeds.
- `SEMANTIC_MAP` — the 21-entry slug→token constant (core roles + `foreground` +
  status + the `sg-*` Astra-compat family). `tertiary`/`quaternary`/
  `sg-secondary`/`sg-neutral` were removed with the auto-generation — their
  targets were generated shades.
- `default_colors()` — **the single PHP source for every colour default**: brand
  constants + six fixed neutral literals (`#ffffff`/`#f0f1f1`/`#d4d5d8`/
  `#767884`/`#464757`/`#09081b`, frozen from the retired OKLCH derivation).
  (`Engine::default_colors()` just delegates; status defaults are
  `STATUS_COLORS`.)
- Helpers: `core_slugs()`, `is_core_slug()`, `brand_chromatic_map()`,
  `neutral_anchor_map()`, `semantic_map()`, `slug_for_token()` (token → owning
  core slug; used by the reverse sync).

## 4. Compute pipeline (`Engine::compute()`) — no generation

1. `colors_from_config()` fills any missing slug from defaults →
   `chromatics_from_colors()` builds the internal index map (brand 1-3 + status 4-7).
2. **Neutral stops:** the 6 stored neutral roles are set directly at stops
   0/1/2/4/5/7. Stops 3 and 6 are **not generated** (no interpolation).
3. **Chromatic seeds:** one token per colour — `chromatic{N}-7` = the seed hex.
   No ramps, no dark shades.
4. Constants (`white`, `transparent`).
5. UI tokens from `presets`.

There is no ShadeGenerator, SchemeComputer, or ContrastEngine any more — those
classes were deleted with the auto-generation. `custom_colors` is applied at the
**palette/bridge layer** (via the derived `semantic_overrides`), overlaid last so
a matching slug wins. `TokenRegistry::get_wp_palette()` returns an empty list —
the picker palette is the semantic layer + custom colours only.

## 5. Editor (pro `gbs-editor-v2`)

- Tab bar: **Colors · Typography · UI styling** (segmented, one tab mounted, no
  cross-section scroll). Save in the tab bar; shuffling lives inside Colors.
- **Colors tab** (`ColorsSection.jsx` + `PaletteRow.jsx`):
  - **Colour-scheme selector + Shuffle** (the prototype's `randPalette`):
    the scheme offsets live in `COLOR_SCHEMES` (`paletteRoles.js`) —
    Monochromatic `[0,0]`, Analogous `[30,−30]`, Complementary `[15,180]`,
    Triadic `[120,240]`, Split complementary `[150,210]`. The selector drives
    two things:
    - **Selecting a scheme (`applyScheme`) applies immediately**: Secondary and
      Accent are re-pinned to the scheme's hue offsets from the CURRENT Primary,
      keeping each colour's own saturation + lightness (only the hue relationship
      moves), Accent re-contrast-fixed against the background (4.5 for
      Monochromatic, else 3). Primary, background, text and neutrals are left
      untouched; locked rows are skipped. This gives the dropdown visible,
      deterministic feedback rather than only affecting the next Shuffle.
    - **Shuffle-all** picks one random base hue and places the Secondary/Accent
      hues at the same scheme offsets, with saturation/lightness randomised in
      tuned ranges, Heading/Body contrast-fixed (7 / 4.5) against the NEW
      background, Primary fixed to ≥3, and Monochromatic accents held to 4.5.
      Per-row shuffle on Secondary/Accent lands at the scheme offset from the
      current Primary (tone-only random); locked/customised rows are kept.

    Reset/AUTO derivation does NOT use the scheme (fixed `autoFn` formulas, per
    the prototype). Undo/redo: 60-entry draft history in `StyleGuideContext`,
    500 ms coalescing.
  - **Palette (9 rows):** swatch → WP `ColorPicker`, hex, **AUTO/CUSTOM** tag
    (Primary is the untagged seed), WCAG badge, lock, per-row shuffle, Reset.
    Non-primary roles auto-derive from Primary via `autoFn` (mirrors the design
    prototype); an edit marks the role CUSTOM (session state); Reset recomputes
    the auto value. Every edit writes `colors[slug]`.
  - **More color variables:** 7 derived rows — Success, Warning, Error, Info
    (computed tokens) + Foreground, Surface-2, Overlay (client-derived) — each
    editable (→ `custom_colors[slug]`, CUSTOM + Reset), plus user variables
    (remove ×) and **Add color variable**.
- Context: `REPLACE_KEYS = ['custom_colors']` (full-replace so removals delete);
  `colors` deep-merges one-slug edits.

## 6. Two-way theme sync (v2 mechanics)

- **Push (SG → theme):** unchanged flow — `SyncOrchestrator::push_to_theme()`
  resolves roles from computed tokens, diffs, writes via the adapters.
- **Pull (theme → SG):** `apply_reverse_colors( token => hex )` maps each token
  to its owning core slug via `ColorModel::slug_for_token()` and writes
  `config['colors'][slug]` directly; tokens with no owning role are ignored.
  - Astra: **the 7 managed slots round-trip** (slots 0-6 → the 7 core roles the
    adapter maps). Slots 7/8 (Subtle background / Other supporting) are
    **unmanaged** — their old sources were the interpolated `neutral-3`/`-6`;
    which stored colour should own them is an open decision (audit doc §9).
  - Spectra One: the FSE palette pull covers **every mapped role** (all 9 in the
    curated profile), and the element pull routes through the same applier.
    `tertiary`/`quaternary` are unmanaged (were generated tints).
- **Folder isolation** (namespace = folder; custom autoloader):
  - `Sync/Astra/AstraPaletteAdapter` — all Astra store logic; registers its own
    `update_option_astra-settings` hook via `register_reverse_hooks()` so core
    never names the option.
  - `Sync/SpectraOne/SpectraOneCompat` — theme.json override + element pull.
  - Core `Sync/`: `ColorSyncAdapter` (interface), `ColorRoles`,
    `ThemeColorMapping`, `MappingResolver`, `SyncOrchestrator`.

## 7. Cleanup executed (evidence-verified, per stage)

~24 files deleted / ~1,000+ dead lines across four commits per repo:

1. **Pro dead UI:** `ColorGrid`, `DNAPresetCards`, `SemanticColorsSection` +
   344 lines of orphan SCSS.
2. **Style-DNA system retired:** top-bar Shuffle wiring, `dnaConfig.js`,
   `style-dna-presets.js`, and the `/style-guide/dna-presets` REST surface
   (`rest_get_dna_presets`, `get_dna_presets`) — zero consumers.
3. **Free retired UI (19 files):** Colors/Presets/FontSize/Spacing/Typography
   panels, the colours section family, HexColorInput/ShadeStrip,
   Headings/Body/Mono typography sections, `useGlobalCSS`/`useSystemSizes`,
   plus dead `liveVars` exports (`buildWPPresetCSS`, `refreshComputedCSS`,
   `SEMANTIC_MAP`).
4. **Dead v1 engine paths:** `Engine::get_derived_token()`, the empty
   `token_overrides` write, deprecated `ShadeGenerator::tint_neutral()`,
   `ColorRoles::chromatic_index()`/`BRAND_CHROMATIC`,
   `AstraPaletteAdapter::brand_chromatic_index()`, `data/style-guide-defaults.json`;
   Pro save-filter colour writes (chromatics 4-7, neutral tint, lockedChromatics,
   hiddenCoreIds, `presets.activeStyleDNA`).

Also fixed along the way: `typography` added to the canonical keys (the strict
keep-list had been stripping the Typography tab's saves).

**Deliberately kept:** `presetCatalog.js` (UI-styling tab), `SegmentRow`/`FontCard`/
`FontPicker`/`fonts.js`, the transitional derived keys in `get_config()` (bridge/
adapters still read them), `MappingResolver::has_curated()` (test infra).

## 7b. Auto-generation removal (second cleanup wave)

Removed on user decision ("expose only what the UI shows; re-add later if
required"):

- **Deleted classes:** `ShadeGenerator` (683 lines), `SchemeComputer` (202),
  `ContrastEngine` (168); `assets/css/scheme-override.css` (294).
- **Engine:** `compute()` emits only the 13 seed/stop tokens; neutral
  interpolation, chromatic ramps, opacity tokens and schemes removed;
  `default_colors()` hardcoded.
- **Pins:** `ColorModel::INTERPOLATED_STOPS`/`is_token_pin_slug()` and the whole
  `custom_colors` token-pin mechanism removed (orchestrator, engine overlay,
  editor filters).
- **Astra slots 7/8 + Spectra One `tertiary`/`quaternary` + `sg-secondary`/
  `sg-neutral`:** unmanaged (mappings deleted).
- **Picker palette:** `get_wp_palette()` returns empty; semantic layer only.
- **ClassRegistry:** `CHROMATIC_SHADE_MAP` = `{7: '600'}`; neutral maps drop
  stops 3/6 and the 400/900 gap-fills (`bg-primary-600` is the one chromatic
  utility shade per family; `bg-base-300/400/800/900` are gone).
- **Legacy alias layers** (`get_legacy_mapping()`, `GlobalStylesCompat`) repoint
  shade aliases to the seeds / nearest surviving neutral.

**Accepted breakage** (treat-as-new-feature): content using shade preset
swatches, shade utility classes, removed slugs, `data-spectra-scheme` sections.

## 8. Known follow-ups

1. **Astra slots 7/8 allotment** — decide which stored colour (if any) should own
   Subtle background / Other supporting, then re-add the adapter rows + the two
   `ASTRA_SHADE_MAP` copies (see astra audit §9.5).
2. **Engine emission of derived-variable AUTO values** — Foreground/Surface-2/
   Overlay display their auto values in the editor, but the site only gets them
   once overridden into `custom_colors`; and the engine's `foreground` semantic
   maps to `chromatic1-7`, not the editor's contrast rule. Align + emit.
3. **Palette AUTO/CUSTOM persistence** — the tags are session state; values
   persist but the auto/custom distinction resets on reload.
4. **Deeper Astra extraction** — `Engine::ASTRA_TO_SG_SLUG` + render rewrite,
   `GlobalStylesBridge` Astra-compat CSS, `MappingResolver::CURATED` rows, and
   `ThemeStyleCompat::DEDICATED_THEMES` still live in core files.
5. **Transitional derived keys** — retire `semantic_map`/`chromatics`/
   `semantic_overrides` from `get_config()` once the bridge + compat layers read
   `ColorModel`/`custom_colors` directly.

## 9. Verification status

- Builds: free + pro webpack clean. Lint: ESLint + PHPCS clean.
- Static analysis: PHPStan level 9 **[OK]**.
- PHPUnit: full free suite green (1013 tests) after rewriting the
  ClassRegistryTest shade fixtures to the one-seed model and fixing a stale
  `MappingResolverTest` Astra assertion (pre-existing failure).
- Live at :10018 (post-removal): dead vars (`--spectra-chromatic1-4`,
  `--spectra-neutral-3/6`, `--spectra-opacity-*`) confirmed absent; seeds +
  stored stops present; picker palette = semantic set only (no `spectra-*`
  swatches); Style Guide edit → preset + `--ast-global-color-0` update live in
  host + canvas; Astra slots 7/8 untouched by edits.
- Still pending: Astra Customizer ↔ SG and Spectra One Site-Editor ↔ SG
  round-trips in a live browser.
