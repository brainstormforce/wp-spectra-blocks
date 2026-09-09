# Style Guide Colors — v2 Architecture (as built)

> The colour system that shipped on `feat/style-guide-ui-rewrite` (free PR #542,
> pro PR #197), plus the `foreground` stored role (free PR #671), the Astra
> full-slot sync (free PR #670) and the active-colors abilities (free PR #697,
> shipped in 1.0.6). Storage keeps **only the ten user-selectable
> colours** (plus an optional custom layer) — and, since the auto-generation
> removal, the pipeline emits **only those colours** too: no shade ramps, no
> interpolated neutrals, no opacity tokens, no schemes. What the Style Guide UI
> shows (10 roles + 6 derived variables + custom colours) is exactly what the
> site gets.
>
> **Last verified against code:** 2026-08-26 (`dev` @ 1.0.6, `bbeea1e5`)
> The pre-rewrite system is preserved for reference in
> [`style-guide-color-audit.md`](./style-guide-color-audit.md).
> Theme sync details: [`astra-color-sync-audit.md`](./astra-color-sync-audit.md),
> [`spectra-one-color-sync-audit.md`](./spectra-one-color-sync-audit.md).

## 1. TL;DR

- **Stored:** `{ version: 2, colors (10 slug→hex), custom_colors }` in the
  `spectra_blocks_pro_style_guide` option. Nothing else.
- **Emitted at compute:** exactly 14 colour tokens — the 3 brand seeds
  (`primary`/`secondary`/`accent` = the stored brand colours), the 4 status seeds
  (`success`/`error`/`info`/`warning`, fixed constants), the 6 stored neutral
  stops (`neutral-0/1/2/4/5/7`), and the standalone `foreground` — plus the
  `white`/`transparent` constants. **Nothing is auto-generated**: no ramps, no
  `neutral-3`/`-6` interpolation, no opacity tokens, no schemes.
- **Tokens are named by semantic slug.** The legacy `chromaticN-7` token names
  are gone: chromatic index → slug is `ColorModel::CHROMATIC_SLUG`
  (1-3 = primary/secondary/accent, 4-7 = success/error/info/warning), and the
  emitted CSS variables are `--spectra-primary`, `--spectra-success`, and so on.
- **The WP picker palette is the semantic layer only** (the 10 roles + status +
  surviving `sg-*` aliases + user custom colours) —
  `TokenRegistry::get_wp_palette()` returns an empty array, so no raw-token
  swatches are published.
- **Slugs are first-class:** each stored colour keys directly to
  `--wp--preset--color--<slug>`.
- **No migration:** a non-v2 option (missing `colors`) falls back to the default
  config — v2 is a fresh feature by decision.
- **Defaults inherit the active theme.** On a site that never saved a guide,
  `Engine::inherited_default_colors()` overwrites each mapped role with the
  theme's own colour (via the same role → slug → hex chain the reverse sync
  uses), so the unsaved state matches the theme instead of Spectra's literals.
- **Two-way sync** writes `colors[slug]` directly (no pin layers); Astra and
  Spectra One mappers are isolated in `Sync/Astra/` and `Sync/SpectraOne/`.
  **All 9 Astra slots and all 10 Spectra One curated roles are managed**;
  Spectra One's `tertiary`/`quaternary` remain unmanaged (their old sources were
  generated tint shades).
- **A page can carry its own guide.** The same v2 shape is registered as post meta
  (`register_page_meta()`, key = the option key) for `post`/`page`, and a page
  that has one gets its own `:root` preset block.

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
        'foreground' => '#…',       // standalone      → foreground
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
  `chromatics` (rebuilt from `colors` + status constants), and
  `semantic_overrides` — which is **`derived_var_defaults()` merged *under*
  `custom_colors`**, not `custom_colors` alone. That merge order is the whole
  reason Surface-2 / Overlay resolve on the front end without being pinned
  (§4.1), while a pinned `custom_colors` entry still wins because it goes last.
  All three are recomputed on every read and **never persisted**.
- `save_config()` — strips to exactly the canonical keys above, then fires
  `spectra_style_guide_config_saved` (the sync + cache invalidation hook).
- `build_config_from_request()` — sanitizes `colors` (core slugs only,
  `sanitize_hex_color`) and `custom_colors` (full-replace map; `sanitize_key` +
  hex + optional name); Pro merges `typography` + `presets` via the
  `spectra_style_guide_config_before_save` filter (colour keys are never merged).

### 2.1 Page-scoped guides (post meta)

A single page can carry its own guide. `register_page_meta()` registers the
**same v2 shape** as post meta on `post` and `page`, deliberately under the same
key string as the option:

- `show_in_rest => false` — never writable over the posts REST API. The only
  writer is `style-guide/config`, which gates on `edit_theme_options` **plus**
  `edit_post` (`Engine::check_page_scope()`) and sanitizes every hex.
- `protect_page_meta()` marks the key protected, so the classic Custom Fields box
  neither lists nor writes it (the key carries no `_` prefix of its own).
- `page_preset_css()` emits the page's palette as a `:root{ --wp--preset--color--*
  }` block on `wp_head`@100. It resolves from the **config**, never from the token
  registry — the registry is cached site-wide, so a page palette there would leak
  onto every other page under a persistent object cache.
- `page_managed_color_slugs()` exists so the page-scoped GBS `presetLock` defers
  to these slugs; the lock renders on `body` and would otherwise beat the `:root`
  block.

## 3. ColorModel — the code SSOT (`class-color-model.php`)

Replaces the stored `semantic_map` and the index-keyed chromatics:

- `CORE_ROLES` — the **10** roles, each declaring how it feeds the token set:
  - `brand` (3) — seeds chromatic ramp N; the role resolves to that slug's token
    (`primary` / `secondary` / `accent`).
  - `neutral` (6) — an anchor at ramp `stop` (`heading` 7, `body` 5, `neutral` 4,
    `outline` 2, `surface` 1, `background` 0).
  - `standalone` (1) — `foreground`: neither a ramp seed nor a neutral stop, so it
    carries its own token. It is the colour that sits **on** a filled surface
    (button labels, icons). Stored rather than derived so it round-trips with
    themes that expose a matching swatch — Spectra One ships `foreground`, and
    while this was a derived variable every Style Guide save silently repainted
    that swatch.
- `STATUS_COLORS` — fixed Success `#10b981` / Error `#ef4444` / Info `#8b5cf6` /
  Warning `#d97706` → chromatics 4-7 (generated, not stored, not user-editable
  at the palette level; overridable per-slug via `custom_colors`).
- `CHROMATIC_SLUG` — chromatic index (1-7) → semantic token slug. The SSOT shared
  by the token emitter and `ClassRegistry`, so the emitted CSS variable and the
  `var(--spectra-…)` reference in a utility class body cannot drift.
- `DEFAULT_BRAND` — fresh-install brand seeds.
- `SEMANTIC_MAP` — the slug→token constant (10 core roles + status + the `sg-*`
  Astra-compat family). `foreground` maps to its **own** token now; it previously
  pointed at `neutral-7` while the derived layer overrode it with a
  contrast-derived value, so the slug resolved to neither.
  `tertiary`/`quaternary`/`sg-secondary`/`sg-neutral` were removed with the
  auto-generation — their targets were generated shades.
- `default_colors()` — **the single PHP source for every colour default**: brand
  constants + six fixed neutral literals (`#ffffff`/`#f0f1f1`/`#d4d5d8`/
  `#767884`/`#464757`/`#09081b`, frozen from the retired OKLCH derivation) +
  `foreground` `#ffffff` (white clears 4.5:1 on the default Primary, so the
  literal equals what the editor's AUTO rule derives — the role became stored
  without changing any existing site's rendering).
  (`Engine::default_colors()` just delegates; status defaults are
  `STATUS_COLORS`.)
- Helpers: `core_slugs()`, `is_core_slug()`, `brand_chromatic_map()`,
  `standalone_map()`, `neutral_anchor_map()`, `semantic_map()`,
  `chromatic_token()`, `slug_for_token()` (token → owning core slug; used by the
  reverse sync).

## 4. Compute pipeline (`Engine::compute()`) — no generation

1. `colors_from_config()` fills any missing slug from defaults →
   `chromatics_from_colors()` builds the internal index map (brand 1-3 + status 4-7).
2. **Neutral stops:** the 6 stored neutral roles are set directly at stops
   0/1/2/4/5/7. Stops 3 and 6 are **not generated** (no interpolation).
3. **Chromatic seeds:** one token per colour, keyed by its **semantic slug**
   (`ColorModel::chromatic_token()`) — `primary`, `secondary`, `accent`,
   `success`, `error`, `info`, `warning`. No ramps, no dark shades.
4. **Standalone roles:** each `standalone_map()` entry (today just `foreground`)
   sets its own token straight from the stored hex.
5. Constants (`white`, `transparent`).

`compute( $config, $persist )` with `$persist = false` is the preview path — it
must not write the site-wide token cache, or a preview leaks onto every request.

There is no ShadeGenerator, SchemeComputer, or ContrastEngine any more — those
classes were deleted with the auto-generation. `custom_colors` is applied at the
**palette/bridge layer** (via the derived `semantic_overrides`), overlaid last so
a matching slug wins. `TokenRegistry::get_wp_palette()` returns an empty list —
the picker palette is the semantic layer + custom colours only.

### 4.1 Programmatic entry points

Three `Engine` methods wrap the pipeline above so a non-REST caller gets the
identical result:

| Method | Does |
|---|---|
| `active_colors()` | The core roles currently in effect (`colors_from_config( get_stored_config() )`). Never empty — an unsaved site returns the theme-inherited defaults, i.e. exactly what a save would round-trip. |
| `preview_css_for( $colors )` | Merges a partial map over the current palette, computes with `persist = false`, builds the preview CSS, then **restores the live token registry** so a later reader in the same request still sees the site's own tokens. Writes nothing. |
| `apply_colors( $colors, $custom_colors = null )` | The write path: merge → `save_config()` → `compute()`. Same pipeline as a site-scoped `POST /style-guide/config`, so the theme sync fires. `$colors` merges; `$custom_colors` **full-replaces** the custom layer (`[]` clears it, `null` leaves it untouched). |

`preview_overrides()` is the shared override set behind all three *and* behind
`/style-guide/preview`'s `css_full`: **`derived_var_defaults()` ∪ the
`custom_colors` pins** — the same set `GlobalStylesBridge::managed_preset_map()`
resolves on the front end. Before it was extracted, the preview passed the pins
alone, so Surface-2 / Overlay were missing from the *preview* while the live site
had them; the two agree now.

**Abilities (1.0.6).** Two abilities expose this as the ERA "change my site
colors" flow, both gated on `edit_theme_options` to match
`Engine::rest_permission_check()` and both registered in `AbilitiesManager` under
`spectra-blocks-configuration`. They replace the removed DNA-presets catalogue.

- **`spectra-blocks/get-active-colors`** — no input. Returns `colors`,
  `custom_colors` (the **stored** layer, `slug => { hex, name }`), `saved`, and
  `preview_css`. It deliberately does **not** return `semantic_overrides`: those
  are derived, and pinning them back would stop Surface-2 / Overlay tracking the
  palette. Its input schema declares a top-level `default` of `{}` so a no-input
  call over the Abilities run-route validates instead of failing on `null`.
- **`spectra-blocks/set-active-colors`** — a partial `colors` map (required) plus
  an optional `custom_colors` full-replace. A bare hex is normalised
  (`ff0000` → `#ff0000`); known roles with an unusable value come back in
  `ignored`, non-core slugs in `unknown`, and a payload with nothing valid `400`s
  rather than writing. Annotated non-destructive and idempotent-in-result — the
  same palette applied twice leaves the same stored state, though each call does
  re-fire `spectra_style_guide_config_saved` and re-push to the theme.

## 5. Editor (pro `gbs-editor-v2`)

- **Shell:** a left nav, not a tab bar — `config/navConfig.js` declares 7 items in
  3 groups, each with a save model (`footer` = the modal footer saves it,
  `self` = the view carries its own controls, `readonly` = nothing to save):
  - *Design System* — **Style Guide** (`footer`)
  - *Custom CSS* — CSS variables · Classes & CSS · Keyframes (all `self`)
  - *Reference* — Block defaults (`self`) · Token browser · Cheat sheet (`readonly`)
  Typography and UI-styling tabs are **gone**; the Style Guide is colours-only.
- **Style Guide view** (`components/styleguide/` — `StyleGuideView.jsx`,
  `ColorsSection.jsx`, `PaletteRow.jsx`, `StyleGuideSubNav.jsx`):
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
  - **Palette (10 rows — `PALETTE_ROLES`):** swatch → WP `ColorPicker`, hex, **AUTO/CUSTOM** tag
    (Primary is the untagged seed), WCAG badge, lock, per-row shuffle, Reset.
    Non-primary roles auto-derive from Primary via `autoFn` (mirrors the design
    prototype); an edit marks the role CUSTOM (session state); Reset recomputes
    the auto value. Every edit writes `colors[slug]`.
    Each row declares the colour it is judged **against** for its WCAG badge —
    `background` for most, `heading` for Background/Surface, and **`primary` for
    Foreground** (that is the pair a reader actually sees on a filled button).
  - **More color variables:** 6 derived rows (`DERIVED_VARS`) — Success, Warning,
    Error, Info (computed tokens) + Surface-2, Overlay (client-derived) — each
    editable (→ `custom_colors[slug]`, CUSTOM + Reset), plus user variables
    (remove ×) and **Add color variable**. **Foreground left this list** when it
    became a stored palette role.
- Context: `REPLACE_KEYS = ['custom_colors']` (full-replace so removals delete);
  `colors` deep-merges one-slug edits.

## 6. Two-way theme sync (v2 mechanics)

- **Push (SG → theme):** unchanged flow — `SyncOrchestrator::push_to_theme()`
  resolves roles from computed tokens, diffs, writes via the adapters.
- **Pull (theme → SG):** `apply_reverse_colors( token => hex )` maps each token
  to its owning core slug via `ColorModel::slug_for_token()` and writes
  `config['colors'][slug]` directly; tokens with no owning role are ignored.
  - Astra: **all 9 slots are managed and round-trip.** Slots 7 and 8 were briefly
    unmanaged after the auto-generation removal (their old sources were the
    interpolated `neutral-3`/`-6`); they now resolve to stored colours —
    `subtle-bg → neutral-4` (the Neutral role) and `other-supp → accent`. The
    slot→token map is one SSOT, `AstraPaletteAdapter::shade_map()`, and it is
    **flag-aware**: Astra swaps the background indices on installs predating its
    4.8.9 palette reorganization, so a hardcoded copy is wrong on legacy sites.
    Everything that speaks both languages reads it — the push, the reverse map,
    the unsaved-state inheritance, and the render-time aliases via
    `GlobalStylesBridge::astra_shade_map()` (localized to JS as
    `astra_shade_map`, so `liveVars.js` keeps no copy of its own).
  - Spectra One: the FSE palette pull covers **every mapped role** — all **10** in
    the curated profile now that `foreground` is mapped — and the element pull
    routes through the same applier. `tertiary`/`quaternary` stay unmanaged (they
    were generated tints).
- **Folder isolation** (namespace = folder; custom autoloader):
  - `Sync/Astra/AstraPaletteAdapter` — all Astra store logic; registers its own
    `update_option_astra-settings` hook via `register_reverse_hooks()` so core
    never names the option.
  - `Sync/SpectraOne/SpectraOneCompat` — theme.json override + element pull.
  - Core `Sync/`: `ColorSyncAdapter` (interface), `ColorRoles`,
    `ThemeColorMapping`, `MappingResolver`, `SyncOrchestrator`,
    `FseGlobalStylesAdapter` (the generic `wp_global_styles` writer), and
    `PaletteCleanup` — a **one-time** migration that strips the Spectra colours
    older builds pushed into `wp_global_styles` (whole shade ramps, `sg-*`
    aliases, status colours). It flags itself done and never repeats; it is
    deliberately self-contained so it can be deleted wholesale once every site
    has upgraded past the build that introduced it (delete the file and the single
    `PaletteCleanup::register()` call in `GlobalStylesBridge`).
  - Beyond the two dedicated themes, `MappingResolver::CURATED` also ships a
    **Twenty Twenty-Five** profile, and `ThemeStyleCompat` handles every other
    theme generically (`DEDICATED_THEMES = ['astra', 'spectra-one']` opt out of
    the generic path).

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
  `sg-neutral`:** unmanaged (mappings deleted). *Astra slots 7/8 have since been
  re-mapped to `neutral-4` / `accent` — see §6.*
- **Picker palette:** `get_wp_palette()` returns empty; semantic layer only.
- **ClassRegistry:** `CHROMATIC_SHADE_MAP` = `{7: '600'}`; neutral maps drop
  stops 3/6 and the 400/900 gap-fills (`bg-primary-600` is the one chromatic
  utility shade per family; `bg-base-300/400/800/900` are gone).
- **Legacy alias layers** (`get_legacy_mapping()`, `GlobalStylesCompat`) repoint
  shade aliases to the seeds / nearest surviving neutral.

**Accepted breakage** (treat-as-new-feature): content using shade preset
swatches, shade utility classes, removed slugs, `data-spectra-scheme` sections.

## 8. Known follow-ups

**Closed since the last revision**

- ~~*Astra slots 7/8 allotment*~~ — **done.** `subtle-bg → neutral-4`,
  `other-supp → accent`; all 9 slots are managed, and the two `ASTRA_SHADE_MAP`
  copies collapsed into the single `AstraPaletteAdapter::shade_map()` SSOT.
- ~~*Engine emission of `foreground`*~~ — **done.** `foreground` is a stored core
  role with its own token and its own `SEMANTIC_MAP` entry, so the engine emits
  the value the editor shows. The contrast formula survives as the row's AUTO
  derivation only.
- ~~*Surface-2 / Overlay are editor-only AUTO values*~~ — **this was never true;
  the claim is withdrawn (2026-08-26).** `get_config()` merges
  `derived_var_defaults()` into `semantic_overrides`, and
  `GlobalStylesBridge::managed_preset_map()` feeds that straight into the emitted
  `--wp--preset--color--*` block — so an unpinned Surface-2 / Overlay does render
  on the front end, with the same formula the editor shows. This has held since
  `e91e3ce9` ("make the auto-mode *More color variables* render + reverse-sync"),
  which predates the previous audit; the doc simply had it wrong. The real
  residual is narrower and is item 1 below.

**Still open**

1. **Surface-2 / Overlay are recomputed, never stored.** They are the only
   palette values with no entry in `colors` and no token in the registry: every
   request re-derives them from `primary`/`background`/`heading` in PHP
   (`Engine::derived_var_defaults()`) and the editor re-derives them again in JS
   (`colorMath.js`). They do reach the site — see the withdrawn item above — but
   the two formulas are kept in step **by hand**, so a change to one drifts
   silently from the other. `foreground` closed this class of gap by becoming a
   stored role; these two have not.
2. **The code still says "nine core roles".** `ColorModel::core_slugs()` has
   returned **10** since `foreground` became a stored role, but 18 occurrences of
   "nine" survive across `class-engine.php` (16), `class-get-active-colors.php`
   and `class-set-active-colors.php` — and one has reached a **translated,
   user-facing string**: the `colors` arg description on `POST
   /style-guide/config` (`class-engine.php:1304`). Only the prose is wrong; the
   two ability descriptions enumerate all ten slugs correctly and every payload
   iterates `core_slugs()`, so no behaviour is affected.
3. **Palette AUTO/CUSTOM persistence** — the tags are session state; values
   persist but the auto/custom distinction resets on reload.
4. **Deeper Astra extraction** — `Engine::TOKEN_TO_SG_SLUG` + the render rewrite,
   the `GlobalStylesBridge` Astra-compat CSS, `MappingResolver::CURATED` rows, and
   `ThemeStyleCompat::DEDICATED_THEMES` still live in core files rather than in
   `Sync/Astra/`.
5. **Transitional derived keys** — retire `semantic_map`/`chromatics`/
   `semantic_overrides` from `get_config()` once the bridge + compat layers read
   `ColorModel`/`custom_colors` directly.
6. **`PaletteCleanup` is scheduled for deletion**, not permanent. Retire it once
   every site has upgraded past the build that introduced it.

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

> **Note (2026-08-26).** The results above date from the auto-generation-removal
> branch. They have **not** been re-run since `foreground` became a stored role,
> since Astra slots 7/8 were re-mapped, or since the 1.0.6 active-colors
> abilities added a second write path into `save_config()` — all three change
> what a round-trip is expected to write. Treat the numbers as historical until
> re-measured. (The abilities themselves do ship tests: `GetActiveColorsTest`
> and `SetActiveColorsTest`, plus the `AbilitiesManagerTest` allowlist entries.)

---

## 10. Doc audit — 2026-08-26

What this revision corrected, so the next reader knows which claims were stale
and why:

| Was documented | Actual code | Where |
|---|---|---|
| 9 stored colours | **10** — `foreground` is a stored `standalone` role | `ColorModel::CORE_ROLES` |
| Tokens named `chromaticN-7` | semantic slugs (`primary`, `success`, …) | `ColorModel::CHROMATIC_SLUG`, `Engine::compute()` |
| `foreground` is a derived variable | stored role with its own token + `SEMANTIC_MAP` entry | `ColorModel::SEMANTIC_MAP` |
| 7 derived variable rows | **6** — `foreground` left the list | `paletteRoles.js` `DERIVED_VARS` |
| Editor = Colors · Typography · UI styling tab bar | 7-item left nav in 3 groups; colours-only | `config/navConfig.js` |
| Astra slots 7/8 unmanaged | managed — `neutral-4` / `accent`; all 9 slots two-way | `AstraPaletteAdapter::SEMANTIC_TOKENS` |
| Two `ASTRA_SHADE_MAP` copies | one SSOT, flag-aware, localized to JS | `AstraPaletteAdapter::shade_map()` |
| Spectra One curated profile = 9 roles | **10** — `foreground` mapped | `MappingResolver::CURATED` |
| Defaults are fixed literals | literals **then overwritten by the active theme** when unsaved | `Engine::inherited_default_colors()` |
| (absent) | page-scoped Style Guide post meta + `page_preset_css()` | `Engine::register_page_meta()` |
| (absent) | `PaletteCleanup` one-time migration; Twenty Twenty-Five curated profile | `Sync/class-palette-cleanup.php`, `MappingResolver::CURATED` |

Corrected again in the 2026-08-26 pass, re-verified against `dev` @ 1.0.6:

| Was documented | Actual code | Where |
|---|---|---|
| `semantic_overrides` is derived "from `custom_colors`" | `derived_var_defaults()` merged **under** `custom_colors` — the derived tones are in it too | `Engine::get_config()` |
| Surface-2 / Overlay reach the site only once pinned | they reach it unpinned, via `semantic_overrides` → the emitted preset block | `GlobalStylesBridge::managed_preset_map()` |
| (absent) | `active_colors()` / `preview_css_for()` / `apply_colors()` and the two active-colors abilities | `Engine`, `includes/Abilities/class-{get,set}-active-colors.php` |
| (absent) | `preview_overrides()` — `/style-guide/preview`'s `css_full` now carries the derived tones, not the pins alone | `Engine::preview_overrides()` |
