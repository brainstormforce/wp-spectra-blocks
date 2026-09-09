# Responsive Write Routing — Implementation Reference

> **Purpose.** How Spectra decides which layer a block edit is stored in — base,
> `@tablet` or `@mobile` — and why that decision needs two signals instead of one.
> Read this before touching anything in `src/extensions/responsive-controls/`.
>
> **Scope.** The routing mechanism itself: the mode signal, the layer decision,
> the read projection, the reset path, and the pre-7.1 guards. For *authoring* a
> new per-device control, see
> [`SPECTRA-RESPONSIVE-ATTRIBUTE-MAPPING.md`](./SPECTRA-RESPONSIVE-ATTRIBUTE-MAPPING.md).
>
> **Implemented in:** [PR #779](https://github.com/brainstormforce/spectra-blocks/pull/779)
> — branch `feat/align-responsive-write-routing` → `wp-7-1-compact`, commits
> `04d78863` (routing) and `b8784bb6` (shared signal), on top of `9b1f8e95`.
> That PR is **Phase 1**: where a value is *stored*. Phase 2 — who *generates the
> CSS* — is scoped in §14.
>
> **Last verified against code:** 2026-08-26 (WordPress 7.1, Gutenberg trunk
> `36b742ee7f`)

---

## Table of Contents

1. [The rule](#1-the-rule)
2. [What it replaced, and what broke](#2-what-it-replaced-and-what-broke)
3. [Architecture](#3-architecture)
4. [The mode signal](#4-the-mode-signal)
5. [Why the signal is shared, not per-block](#5-why-the-signal-is-shared-not-per-block)
6. [The layer decision](#6-the-layer-decision)
7. [The read projection](#7-the-read-projection)
8. [The reset path](#8-the-reset-path)
9. [Pre-7.1 guards](#9-pre-71-guards)
10. [Behaviour matrix](#10-behaviour-matrix)
11. [Consequences and known limits](#11-consequences-and-known-limits)
12. [Debugging guide](#12-debugging-guide)
13. [File map](#13-file-map)
14. [Roadmap — Phase 2: CSS generation](#14-roadmap--phase-2-css-generation)

---

## 1. The rule

> **The layer an edit belongs to is a function of the device AND whether core is
> editing a viewport style state — never the device alone. Below WordPress 7.1
> there is no such state, so the device is the only signal there is.**

Everything in this document is either that sentence, the reason it is true, or
the machinery needed to evaluate it.

---

## 2. What it replaced, and what broke

### 2.1 The old code

`utils/helpers.js`, inside `withResponsiveControls`:

```js
const deviceType = select( 'core/editor' ).getDeviceType();
const breakpoint = getBreakpointType( deviceType );   // Tablet → '@tablet'
```

The previewed device decided the layer, on its own.

### 2.2 Why that disagreed with core

WordPress 7.1 has two independent things: a **device preview** and a
**per-viewport editing mode**. With the mode OFF, the device switcher is only a
preview — core writes every edit to the **base** layer.

So on Tablet with the mode off, core wrote base and Spectra wrote `@tablet`: two
components disagreeing about where the same edit belongs.

### 2.3 The failure users saw

`spectra/button` shows it worst, because it declares **no typography attributes
of its own** — its Typography panel is entirely core's:

1. Mode off, previewing Tablet. The user changes font size, expecting a global
   change, which is what core does there.
2. Spectra's router intercepts and stores it at `style['@tablet']`.
3. The canvas shows nothing — core previews state CSS only when the mode is on.
4. The user concludes the control is broken.
5. A tablet override they never asked for is now in their content, invisible.

**Spectra was silently re-routing core's own panels**, and storing data the user
could not see. That is worse than a control appearing inert.

### 2.4 What was already correct

Worth knowing before editing: `withResponsiveControls` splits every edit into two
branches.

| Branch | Meaning | Status |
|---|---|---|
| `stateEdited` | core wrote the viewport state itself (mode on) | **was already correct** — Spectra mirrors it |
| `else` | edit landed at the root — a Spectra control, or core with the mode off | **the bug lived here** |

The change alters the **input** to that decision (`breakpoint`). Neither branch's
logic was touched.

---

## 3. Architecture

```
                    ┌──────────────────────────────────────┐
                    │ core/editor.getDeviceType()          │  PUBLIC
                    │   → 'Desktop' | 'Tablet' | 'Mobile'  │
                    └──────────────────┬───────────────────┘
                                       │
  ┌────────────────────────────────────┼────────────────────────────────┐
  │  useResponsiveEditing( deviceType, clientId )                       │
  │                                                                     │
  │   BlockControls  group="style-state"  ──probe──┐                     │
  │   InspectorControls group="styles"    ──probe──┤                     │
  │                                                ▼                     │
  │                            observed = toolbar || (narrow && !normal) │
  │                                                │                     │
  │              canObserve? ──yes──► trust self ──┴──► publishShared()  │
  │                    └────no──────► read shared answer                 │
  │                                                                     │
  │   isResponsiveEditingActive = isSupported && resolved                │
  └────────────────────────────────────┬────────────────────────────────┘
                                       │
        ┌──────────────────────────────┴──────────────────────────────┐
        ▼                                                             ▼
  breakpoint (React)                                    getResponsiveEditingActive()
  helpers.js — the write router                         index.js — the reset path
        │                                                             │
        ├──► write routing: setAttributes → correct layer             │
        └──► read projection: attributes → resolved for device        │
                                                                      │
                                            resets the layer the router used
```

Three consumers, one signal. That is deliberate: a second, independently-derived
signal is how the write and the reset end up disagreeing about which layer they
are touching.

---

## 4. The mode signal

**File:** `utils/use-responsive-editing.js`

### 4.1 The mode cannot be read

| API | Why unavailable |
|---|---|
| `isResponsiveEditing()` | private selector, `unlock()` only |
| `getSelectedBlockStyleState()` | private selector, `unlock()` only |
| `getStyleStateViewport()` | private selector, `unlock()` only |
| `useBlockStyleState` | not exported from the package at all |
| `BlockStyleStateProvider` | not exported at all |

`unlock()` **throws** for anything not on core's package allow-list
(`private-apis/src/implementation.ts`). There is no filter and no editor setting.
So the answer is inferred.

### 4.2 Inference from conditionally-rendered slots

A fill renders only when core has decided to render its slot. Mount a component
inside such a fill and its **presence is the signal**:

```js
const Probe = ( { onChange } ) => {
    useEffect( () => {
        onChange( true );
        return () => onChange( false );
    }, [ onChange ] );

    return null;
};
```

| Probe | Slot | Core renders it when |
|---|---|---|
| toolbar | `BlockControls` group `style-state` | `isResponsiveEditing() && hasViewportBlockStyleState()` — `block-toolbar/index.js` |
| inspector | `InspectorControls` group `styles` | the normal style view; **absent** in `StyleStateInspectorSlots` |

```js
const observed =
    toolbarShowsStyleState ||                          // positive, stands alone
    ( isNarrowDevice && ! inspectorShowsNormalView );  // negative, needs the guard
```

### 4.3 Why both terms

- **Toolbar alone is not enough.** The block toolbar unmounts while the user types
  (`isBlockInterfaceHidden`). Alone, that would flip the router back to the base
  layer **mid-edit**.
- **Inspector alone is not enough.** It is a *negative* test, and absence has more
  than one cause: the responsive view, a closed inspector, or the Settings tab.
  The `isNarrowDevice` guard removes the reading that would otherwise be wrong on
  Desktop, where there is no viewport state to edit at all.

### 4.4 Why not the DOM check

`coreResponsiveEditingActive()` (`utils/constants.js`) reads
`.editor-preview-dropdown.is-responsive-editing`. It still has a job — it is
synchronous, works outside React, and is the fallback before any probe has
reported — but it is **not reactive**. A router reading it would keep routing to
the old layer until something else forced a re-render.

---

## 5. Why the signal is shared, not per-block

**This is the subtlest part of the implementation** (commit `b8784bb6`). The
mechanism measures a property of the **editor**, but it can only observe it from
inside the **selected block**.

Core gates both probe fills on the block-edit context —
`useBlockControlsFill()` returns null unless `mayDisplayControlsKey` is set, and
`InspectorControlsFill` bails on the same key. So in an unselected block, neither
probe mounts.

Combined with the inspector term being a negative test:

```
selected,   Tablet, mode off → toolbar false, inspector true  → false ✓
unselected, Tablet, mode off → toolbar false, inspector false → TRUE  ✗
```

**Every unselected block concluded the mode was on whenever the device was
narrow.** Two things broke:

1. **The reset path** read whichever block wrote the module value last. Unselected
   blocks wrote `true`, the selected one `false`, and unselected blocks outnumber
   it — so a reset with the mode off could clear `@tablet` instead of the base.
   Exactly the write/reset disagreement this work exists to prevent.
2. **Unselected blocks routed to `@tablet`** while the block being edited routed
   to base, so their root-attribute sync resolved from the tablet state — and for
   `layout`, that is what core draws the canvas from.

### 5.1 The fix

Exactly one block publishes; every other block subscribes.

```js
const canObserve = useSelect( ( select ) => {
    if ( ! clientId ) {
        return false;
    }

    const store = select( 'core/block-editor' );

    return Boolean(
        store?.isBlockSelected?.( clientId ) ||
            store?.isFirstMultiSelectedBlock?.( clientId )
    );
}, [ clientId ] );

useEffect( () => {
    if ( canObserve ) {
        publishSharedState( observed );
    }
}, [ canObserve, observed ] );
```

Resolution order:

```js
if ( canObserve )            resolved = observed;                    // trust yourself
else if ( null !== shared )  resolved = shared;                      // trust the observer
else                         resolved = coreResponsiveEditingActive(); // nobody yet → DOM
```

`isFirstMultiSelectedBlock` is included because that is the block core itself
treats as controls-bearing during a multi-selection.

### 5.2 Why a subscriber set, not a plain variable

A module variable is enough for the non-React reset path, but **not** for the
consuming blocks: they must **re-render** when the value changes, or an unselected
block keeps routing to whatever layer was current when it last rendered.

```js
const subscribers = new Set();

const publishSharedState = ( value ) => {
    if ( value === sharedState ) {
        return;   // no-op on no change, so this cannot loop
    }

    sharedState = value;
    subscribers.forEach( ( notify ) => notify( value ) );
};
```

### 5.3 Why `null` is not `false`

`sharedState` starts `null`, meaning *no block has reported yet* — distinct from
`false`, meaning *reported, mode off*. Collapsing them would claim the base layer
on the first edit after a reload in the responsive view, which is precisely the
wrong-layer write the whole change exists to stop.

---

## 6. The layer decision

**File:** `utils/helpers.js`, inside `withResponsiveControls`

```js
const { isResponsiveEditingActive, ResponsiveEditingProbes } =
    useResponsiveEditing( deviceType, props.clientId );

const breakpoint = useMemo( () => {
    if ( ! coreViewportStatesAreIndependent() ) {
        return getBreakpointType( deviceType );          // pre-7.1: unchanged
    }

    return isResponsiveEditingActive
        ? getBreakpointType( deviceType )
        : 'base';
}, [ deviceType, isResponsiveEditingActive ] );
```

Desktop needs no special case: `BREAKPOINT_TYPE_MAP` already maps it to `'base'`.

### 6.1 The probes must be rendered

```jsx
return (
    <>
        <ResponsiveEditingProbes />
        <BlockEdit { ...props } attributes={ deviceAttributes } setAttributes={ wrappedSetAttributes } />
    </>
);
```

**Not optional.** The hook reports what these mount into. Remove them and
`isResponsiveEditingActive` is permanently false, so every edit routes to base and
per-device editing stops working entirely — with no error to point at.

---

## 7. The read projection

Routing only decides where a value is **stored**. The block's controls also have
to **display** the right one, which `withResponsiveControls` does by projecting
resolved values onto `props.attributes` before handing them to `BlockEdit`:

```js
const candidates = [ stateBucket[ key ], baseBucket[ key ] ];   // state, then base
const shown = candidates.find( ( value ) => hasValue( value ) );
```

Note the two-element list — **state then base, with nothing in between**. Mobile
falls back to the base, never to tablet, because core's bands are mutually
exclusive ranges resolved as `array_replace( base, state )`.

This is why a block's own code stays ordinary: it reads `attributes.width` and
calls `setAttributes({ width })`, and the extension handles both directions.

> Legacy content is the deliberate exception. Migration bakes the old
> `sm → md → lg` cascade into the stored buckets
> (`Legacy/class-legacy-store.php`), so old posts render as they always did while
> new content follows core.

---

## 8. The reset path

**File:** `index.js`, in the reset click handler

```js
const breakpoint =
    ! coreViewportStatesAreIndependent() || getResponsiveEditingActive()
        ? BREAKPOINT_TYPE_MAP[ deviceType ] || 'base'
        : 'base';
```

**The write router and the reset must agree.** If an edit went to the base but a
reset clears `@tablet`, the reset removes nothing and the value the user is
looking at survives — "Reset" appears broken.

This runs from a document click listener, outside any component, so it uses
`getResponsiveEditingActive()` rather than the hook. That getter returns the
**published** answer — the selected block's — which is what §5 exists to
guarantee.

---

## 9. Pre-7.1 guards

**The most dangerous part of the change.** The two probe slots have different
histories:

| Slot | Introduced | Present on WP 6.6 |
|---|---|---|
| `InspectorControls` `styles` | Gutenberg #47105, Jan 2023 | **yes** |
| `BlockControls` `style-state` | Gutenberg #80037, Jul 2026 | **no — 7.1 only** |

Ungated on 6.6 the signal reads *"toolbar absent, normal view present"* — which
this logic interprets as "not editing a viewport state", **permanently**. Every
edit would route to the base layer on every pre-7.1 site, breaking Spectra's
device buttons outright, plus `Unknown BlockControls group "style-state"` logged
once per block.

Five guard points:

| # | Location | Pre-7.1 behaviour |
|---|---|---|
| 1 | `use-responsive-editing.js` — `isSupported` in the flag | forced false |
| 2 | `use-responsive-editing.js` — `ResponsiveEditingProbes` | returns `null`, **no warnings** |
| 3 | `use-responsive-editing.js` — `getResponsiveEditingActive()` | returns false early |
| 4 | `helpers.js` — the router | `return getBreakpointType( deviceType )` |
| 5 | `index.js` — the reset path | `||` short-circuits to the device |

**4 and 5 are load-bearing.** They exist because *false must not be read as "the
mode is off"* — below 7.1 the mode does not exist, so the device is the only
signal there is. Without them, guards 1–3 would actively cause the bug they look
like they prevent.

The check is `coreViewportStatesAreIndependent()` —
**capability-based, not version-based**. PHP measures core once with
`function_exists()` / `is_callable()` and exports the answer as
`spectra_blocks_info.viewport_support`
(`includes/Extensions/ResponsiveControls/class-viewport-support.php`), so a 7.0
site running the Gutenberg plugin is treated as having the states it actually
has, and a partially-updated install is not fooled by its version string.

**Net effect below 7.1: byte-for-byte the previous code path.**

---

## 10. Behaviour matrix

### 10.1 WordPress 7.1

| Mode | Device | Layer | Effect |
|---|---|---|---|
| off | Desktop | base | all devices |
| off | Tablet | **base** | all devices — tablet value untouched |
| off | Mobile | **base** | all devices |
| on | Desktop | base | all devices |
| on | Tablet | `@tablet` | tablet only |
| on | Mobile | `@mobile` | mobile only |

Rows 2 and 3 are what changed.

### 10.2 Below 7.1

| Device | Layer |
|---|---|
| Desktop | base |
| Tablet | `@tablet` (legacy `md`) |
| Mobile | `@mobile` (legacy `sm`) |

Unchanged — the device decides, because nothing else exists to consult.

---

## 11. Consequences and known limits

### 11.1 Spectra's device buttons now depend on core's mode

Intended, but a **workflow change that needs a product decision and a release
note**, not just a code review.

| | Before | After |
|---|---|---|
| Spectra device buttons, mode off | stored per-device | preview only, edits base |
| Core controls, mode off | edits base | edits base |
| Both, mode on | per-device | per-device |

The columns now agree, which is the point. But the top-left cell was a capability
people were using: a user could set a tablet-only value without ever touching
core's toggle.

Consequence: the "Turn On" hint in `control-injection.js` becomes **load-bearing**
rather than advisory. It was compensating for the mismatch; it is now the path to
per-device editing.

### 11.2 Existing mis-routed values are left alone

Values written to `@tablet` by the old behaviour stay there and still render at
that band. No migration, no notice — deliberate, and a separate decision.

### 11.3 The detection is inference

It mirrors core's own conditions but remains inference. If core changes when it
renders either slot, the signal changes with it. Mitigations: both slots are
**public API**, and the fallback direction is harmless — a stale reading means
editing the base, never writing an invisible override.

### 11.4 One known inaccuracy

Inspector closed + narrow device + mode off, in a block that cannot observe,
resolves from the shared answer — correct. But before *any* block has reported,
the DOM fallback is used, and if that check fails (core markup changed) the
reading defaults to false, i.e. the base layer. The safe direction.

### 11.5 Not verified in a browser at time of writing

The implementation was built and lint-checked but **not exercised in the editor**,
and never on a pre-7.1 install. See the handoff document for the test plan.

---

## 12. Debugging guide

| Symptom | Likely cause | Check |
|---|---|---|
| Every edit goes to base, mode on | probes not rendered | Is `<ResponsiveEditingProbes />` still in the `withResponsiveControls` return? |
| Per-device editing dead on WP 6.x | a pre-7.1 guard removed | Guards 4 and 5 in §9 |
| `Unknown BlockControls group "style-state"` in console | probes rendering below 7.1 | Guard 2 |
| Reset clears the wrong layer | write and reset disagree | Both must read the same signal (§8) |
| Unselected blocks behave differently from the selected one | per-block reading reintroduced | §5 — `canObserve` and the shared publish |
| Routing flips while typing | toolbar term used alone | `observed` needs both terms (§4.3) |
| Canvas shows the wrong value but storage is right | read projection, not routing | §7 |
| Layout renders wrong on tablet only | root-attribute sync resolving from the wrong state | §5, consequence 2 |

**Fastest way to see the signal:** the reference implementation in
`../../blockkit/src/button/responsive-width/` prints device, both probe readings,
the resolved state and all three stored values into the inspector. That plugin is
the R&D bench this mechanism was proved on, and its `EXPERIMENT_GROUP` constant
reproduces the inspector-group failure on demand.

---

## 13. File map

**Spectra**

| Path | Role |
|---|---|
| `src/extensions/responsive-controls/utils/use-responsive-editing.js` | The mode signal — probes, shared state, capability guard |
| `src/extensions/responsive-controls/utils/helpers.js` | `withResponsiveControls` — layer decision, read projection, write routing |
| `src/extensions/responsive-controls/utils/constants.js` | `BREAKPOINT_TYPE_MAP`, `BLOCK_RESPONSIVE_KEYS`, `coreViewportStatesAreIndependent()`, `coreResponsiveEditingActive()` |
| `src/extensions/responsive-controls/index.js` | Reset handling |
| `src/extensions/responsive-controls/control-injection.js` | Device buttons, help text, the "Turn On" hint |
| `src/extensions/responsive-controls/legacy/` | Pre-7.1 implementation and the legacy cascade migration |
| `includes/Extensions/class-responsive-controls.php` | Band resolution via core |
| `includes/Extensions/ResponsiveControls/class-viewport-support.php` | The capability measurement |

**WordPress core — the contract this relies on**

| Path | What matters |
|---|---|
| `block-editor/src/components/block-controls/groups.js` | `style-state` is a public group |
| `block-editor/src/components/block-toolbar/index.js` | The condition the toolbar probe mirrors |
| `block-editor/src/components/inspector-controls/groups.js` | `styles` is a public group |
| `block-editor/src/components/block-inspector/index.js` | `StyleStateInspectorSlots` — omits `styles` |
| `block-editor/src/store/private-selectors.js` | The selectors we cannot use |
| `editor/src/store/selectors.js` | `getDeviceType()` — public |
| `wp-includes/class-wp-theme-json.php` | `get_viewport_media_queries()` |

**Related documents**

| Doc | Covers |
|---|---|
| [`SPECTRA-RESPONSIVE-ATTRIBUTE-MAPPING.md`](./SPECTRA-RESPONSIVE-ATTRIBUTE-MAPPING.md) | Authoring a new per-device control: groups, visibility, CSS |
| `wp-audit-7.1/spectra-routing-alignment-handoff-2026-08-26.md` | Handoff, test plan, open decisions |
| `wp-audit-7.1/spectra-responsive-inspector-findings-2026-08-26.md` | The wider 7.1 analysis |

---

## 14. Roadmap — Phase 2: CSS generation

**Status:** scoped, not started. Deliberately deferred — revisit after roughly
**1–2 months of user feedback on Phase 1**, and only when the signal says it is
safe.

### 14.1 The two phases

| | Question | State |
|---|---|---|
| **Phase 1** — PR #779 | *Where is the value stored?* | Done. Spectra and core now agree on the layer (§1–§10). |
| **Phase 2** | *Who turns that value into CSS?* | Not started. Spectra still generates its own per-viewport CSS. |

Phase 1 was the prerequisite: there is no point aligning CSS generation while the
two sides still disagree about which layer a value belongs to.

### 14.2 The goal

Retire Spectra's own per-viewport CSS generation
(`ResponsiveControls/class-responsive-attribute-css.php`) and let core emit the
rules, wiring our custom attributes and custom selectors into core's pipeline
instead of running a parallel one.

Why it is worth doing:

- One generator instead of two, so specificity and band edges cannot drift apart.
- Core's own optimisations, caching and future changes come for free.
- Less surface to maintain against each WordPress release.

### 14.3 A premise worth correcting before planning

The assumption behind this task was that core styles **only the wrapper** —
`.wrap { font-size: 12px; }` — and cannot express descendant rules such as
`.wrap .child { … }`. **That was true of older block supports, but it is not true
of 7.1's state styles.**

`wp-includes/block-supports/states.php` reads a block's declared selectors and
splits state styles across them:

```php
// wp_get_block_state_style_rules()
$block_selectors = isset( $block_type->selectors ) && is_array( $block_type->selectors )
    ? $block_type->selectors
    : array();

foreach ( wp_get_state_style_groups( $state_style, $block_selectors ) as $group ) {
    wp_add_block_state_style_rule( $css_rules, $state, $group['selector'], $group['style'], $rules_group );
}
```

What that gives us today:

| Capability | Where |
|---|---|
| Per-**feature** selectors (`typography`, `border`, …) | `wp_get_state_style_groups()` |
| Per-**subfeature** selectors (e.g. `border.radius`) | same, array branch |
| Fallback to `selectors.<feature>.root`, then `selectors.root` | same |
| **Element** selectors (link, heading, button…) prefixed under the block root | `wp_get_block_state_element_selectors()` |
| The media query passed through as a rules group | `$rules_group` argument |

So a block can already target a descendant for state styles by declaring it in
`block.json`:

```json
"selectors": {
    "root": ".wp-block-spectra-button",
    "typography": ".wp-block-spectra-button .spectra-button__label"
}
```

Core then emits the tablet font-size rule against that descendant selector, inside
the correct band, with no Spectra code involved.

**Plan Phase 2 around this, not around the wrapper-only assumption.** A
significant part of the wiring we thought we needed to build already exists.

### 14.4 The boundary that remains

Selectors are solved; **coverage** is not. Core generates state CSS only for style
paths it owns — its own block supports. An attribute with no corresponding
support gets no CSS from core no matter which selectors are declared.

That splits Spectra's per-device attributes in two:

| | Examples | Phase 2 action |
|---|---|---|
| **Maps onto a core support** | font size, line height, padding, margin, border width/colour/radius, box shadow, background image/size/repeat/position, width, height, min/max | Declare `selectors` in `block.json`, store under the core `style` path, **delete our generator for it** |
| **No core support exists** | Slider "Slides Per View" / "Space Between", overlay blend mode, shape divider geometry, icon-specific sizing | **Keep our generator** until core gains a support or the third-party API lands |

Gutenberg #80388's *"Settings that can be converted to style controls"* is core
doing exactly this conversion for its own blocks (Gallery columns, Cover focal
point, Spacer height, Search width…). The second column above shrinks as that
work lands — which is another reason to wait rather than convert everything now.

### 14.5 Work breakdown

1. **Audit.** For every entry in `BLOCK_RESPONSIVE_KEYS`, classify it into the two
   columns above. This is the deliverable that makes the rest estimable.
2. **Declare selectors.** Add `selectors` to the `block.json` of blocks whose
   styles target a descendant rather than the wrapper. Verify against core's
   output before removing anything of ours.
3. **Migrate storage** for column-one attributes onto the core `style` path core
   already understands (e.g. `style.typography.fontSize` rather than a flat key).
   Needs a content migration and a deprecation path — the largest risk in Phase 2.
4. **Retire per-attribute generation** in
   `class-responsive-attribute-css.php`, one attribute at a time, each behind
   verification that core's output matches ours at all three bands.
5. **Keep the band resolution as-is.** `class-responsive-controls.php` already
   delegates to `WP_Theme_JSON::get_viewport_media_queries()`, so nothing there
   changes.
6. **Re-check the editor canvas.** Our generator also feeds editor preview in
   places; core's state CSS is emitted differently there
   (`hooks/state-utils.js`, `buildCanvasStateSelector`).

### 14.6 Preconditions before starting

- [ ] Phase 1 has run in the field for **1–2 months** with no routing regressions
      reported.
- [ ] The §11.1 product decision (Spectra's device buttons requiring core's mode)
      is settled and shipped.
- [ ] The §14.5 audit is complete, so the scope is known rather than assumed.
- [ ] Legacy content behaviour is pinned by tests — Phase 2 touches storage, so
      the `lg`/`md`/`sm` migration path must be provably unaffected.
- [ ] Gutenberg #80388 has been re-read; some of column two may no longer need us.

### 14.7 What not to do

- **Do not delete the generator wholesale.** Column two has no core equivalent;
  removing it would drop those styles entirely.
- **Do not migrate storage without a deprecation.** Moving a flat key into a core
  `style` path changes post content; existing posts must keep rendering.
- **Do not reimplement the bands.** Core's mutually-exclusive ranges are already
  what we use; a `max-width` rewrite would reintroduce the tablet→mobile cascade.
- **Do not start before the audit.** Roughly half of the attribute list is likely
  column one, but "likely" is not a plan.
