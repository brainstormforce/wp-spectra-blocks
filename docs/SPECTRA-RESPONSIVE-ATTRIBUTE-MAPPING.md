# Custom Attributes & Responsive Styles (WordPress 7.1)

> **Purpose.** How to add a custom style attribute to a block so that it (a) is
> *visible* in WordPress 7.1's responsive style view on Tablet and Mobile, and (b)
> *behaves* like a core control — per-device only when "Responsive styles" is on,
> global otherwise.
>
> **Scope.** Block-instance attributes. Two things are covered, and they are
> genuinely separate problems with separate fixes: **visibility** (§3–§5) and
> **alignment** (§6). A control can be perfectly aligned and still invisible, or
> visible and silently writing to the wrong layer.
>
> **Worked example.** A `spectra/demo-button` block with one custom attribute —
> **Button Width** — carried end to end: registration, panel, editor, CSS.
>
> **Last verified against code:** 2026-08-26 (WordPress 7.1, Gutenberg trunk
> `36b742ee7f`)

---

## Table of Contents

1. [The 60-second version](#1-the-60-second-version)
2. [The storage model](#2-the-storage-model)
3. [Where a control can appear: the group system](#3-where-a-control-can-appear-the-group-system)
4. [Registering the attribute and the panel](#4-registering-the-attribute-and-the-panel)
5. [Handling the attribute in the editor](#5-handling-the-attribute-in-the-editor)
6. [Aligning changes with core — the crucial part](#6-aligning-changes-with-core--the-crucial-part)
7. [Generating CSS on the front end](#7-generating-css-on-the-front-end)
8. [Reset behaviour](#8-reset-behaviour)
9. [Checklist](#9-checklist)
10. [Anti-patterns](#10-anti-patterns)
11. [File map](#11-file-map)

---

## 1. The 60-second version

```
┌─ VISIBILITY ─────────────────────────────────────────────────────────┐
│ Fill one of the SEVEN groups core renders in the responsive view:    │
│   typography · color · background · layout · dimensions · border ·   │
│   elements                                                           │
│ NOT `styles` — it is not rendered there, and your panel disappears   │
│ on Tablet/Mobile.                                          → §3      │
└──────────────────────────────────────────────────────────────────────┘
┌─ ALIGNMENT ──────────────────────────────────────────────────────────┐
│ The layer you write to = f( device, responsive-styles-mode )         │
│ NEVER the device alone.                                              │
│   mode off → base, whatever the device                               │
│   mode on  → '@tablet' / '@mobile' / base                  → §6      │
└──────────────────────────────────────────────────────────────────────┘
```

In this codebase most of §6 is already done for you: add your attribute name to
`BLOCK_RESPONSIVE_KEYS` and `withResponsiveControls` handles the routing. Read §6
anyway — when something misbehaves, it is nearly always because these two rules
were confused with each other.

---

## 2. The storage model

WordPress 7.1 stores per-viewport values as **layers inside a single `style`
attribute**, not as extra attributes:

```js
style: {
    typography: { fontSize: '40px' },                      // base
    '@tablet':  { typography: { fontSize: '30px' } },       // tablet band only
    '@mobile':  { typography: { fontSize: '20px' } },       // mobile band only
}
```

Three facts that everything else follows from:

**1. Desktop is not a layer — Desktop *is* the base.** The base carries no media
query, so it applies at every width. `@tablet` and `@mobile` are overrides on top
of it.

**2. The bands are mutually exclusive ranges**, not stacked `max-width` queries:

```
@mobile   @media (width <= 480px)
@tablet   @media (480px < width <= 782px)
```

**3. Therefore mobile falls back to the BASE, never to tablet.** Resolution is
`array_replace( base, state )`. This differs from Spectra's pre-1.0.6 generator,
which resolved `sm → md → lg`; legacy content keeps the old cascade because
migration bakes it in (`Legacy/class-legacy-store.php`), but all new content
follows core.

> Breakpoints come from `settings.viewport` in theme.json. Never hardcode them —
> read them through `WP_Theme_JSON::get_viewport_media_queries()`. See §7.

`style` is declared by core as a free-form `{ type: 'object' }` attribute with no
key allow-list, so a namespaced key of your own survives serialization untouched.
Core only *generates CSS* for paths it owns, which is why a custom key needs §7.

---

## 3. Where a control can appear: the group system

### 3.1 What changes on Tablet/Mobile

When "Responsive styles" is on and the device is Tablet or Mobile, the inspector
**does not render its usual tabs at all**. It swaps to a different component,
`StyleStateInspectorSlots`, which renders a **fixed, hardcoded list of seven
slots**. No filter, no loop, no extension point.

### 3.2 The groups core provides

**17 group names, 16 distinct slots** (`settings` is an alias for `default`),
registered in `inspector-controls/groups.js`:

| Group | Renders in Styles tab | Renders in responsive view |
|---|:---:|:---:|
| `typography` | ✅ | ✅ |
| `color` | ✅ | ✅ |
| `background` | ✅ | ✅ |
| `dimensions` | ✅ | ✅ |
| `border` | ✅ | ✅ |
| `elements` | ✅ | ✅ |
| `layout` | ✅ | ✅ ¹ |
| `filter` | ✅ | ❌ |
| `position` | ✅ | ❌ |
| **`styles`** | ✅ | **❌** |
| `default` / `settings` | — | ❌ |
| `bindings` | — | ❌ |
| `advanced` | — | ❌ |
| `content` | — | ❌ |
| `list` | — | ❌ |
| `effects` | never rendered anywhere | ❌ |
| **Total** | **10** | **7** |

¹ `layout` renders for viewport states but not pseudo states (`:hover` etc.).

**You cannot register a new group.** The registry is a frozen object literal, is
not exported from `@wordpress/block-editor`, and an unknown group name logs
`Unknown InspectorControls group "…" provided.` and renders nothing.

> A `viewport` group intended for exactly this purpose is in progress upstream
> (Gutenberg draft PR #82003, tracked by #80388). It does not exist in 7.1.

### 3.3 The trap: `styles` is the natural choice and the wrong one

`styles` is the general-purpose group for third-party panels, and it is the one
group core renders **unlabelled** — which is what lets a fill bring its own
`ToolsPanel`, its own heading and its own reset menu.

It is **not** rendered in the responsive view. So a panel filling `styles`:

- looks perfect on Desktop, with its own heading;
- **vanishes** on Tablet and Mobile;
- while its stored per-device values remain in post content, unreachable.

That is a data-access bug, not a cosmetic one, and it is the single most common
mistake in this area.

**Also note: all seven responsive slots are labelled.** A labelled slot wraps its
fills in core's own `ToolsPanel`, so your fill must contribute bare
`ToolsPanelItem`s — not a `ToolsPanel` of its own, or you nest two panels with two
headings and two reset menus.

### 3.4 Choosing a group by style family

Pick by what the property *is*, not by which panel is convenient. The panel
heading the user sees is core's.

| Your control | Group | Why |
|---|---|---|
| font size, line height, letter spacing, transform | `typography` | |
| text / link / gradient colours | `color` | Core no longer fills this group — effectively yours |
| background type, size, repeat, position, overlay | `background` | |
| width, height, min/max, gap, icon size, spacing | `dimensions` | |
| border width/colour/radius, box shadow, text shadow | `border` | Core labels the panel "Border & Shadow" |
| flex direction, orientation, justification | `layout` | |
| per-element (link, heading, button) styling | `elements` | |

**If nothing fits**, you have two honest options and one wrong one:

- **Right:** host it in the nearest surviving group and accept core's heading.
- **Right:** leave it out of the responsive view *if the value is not per-device*
  — see below.
- **Wrong:** fill `styles` and hope. It disappears.

**The test to apply per control:**

> Is this attribute per-device (is it in `BLOCK_RESPONSIVE_KEYS`)?
> - **No** → let it stay out of the responsive view. It holds one value for all
>   devices; showing it while the user believes they are editing Mobile means
>   they silently change every device. Core hides its own non-per-viewport
>   panels — `position`, `filter`, `advanced` — for exactly this reason.
> - **Yes** → it must be reachable. Map it to a group from the table.

---

## 4. Registering the attribute and the panel

Worked example: `spectra/demo-button` with a per-device **Button Width**.

### 4.1 `block.json`

```json
{
    "apiVersion": 3,
    "name": "spectra/demo-button",
    "title": "Demo Button",
    "category": "spectra-blocks",
    "textdomain": "spectra-blocks",

    "attributes": {
        "text":  { "type": "string", "default": "" },
        "width": { "type": "string" }
    },

    "supports": {
        "typography": {
            "fontSize": true,
            "__experimentalDefaultControls": { "fontSize": true }
        },
        "color": { "text": true, "background": true },
        "spacing": { "padding": true }
    },

    "editorScript": "file:./index.js",
    "render": "file:./render.php"
}
```

`width` is a flat attribute. It is **not** covered by any support, so nothing in
core will render a control or emit CSS for it — both are ours.

> Note the block declares no `dimensions` support. That is deliberate: it keeps
> the `dimensions` panel free of core controls, so the Width control is
> unambiguous in this example.

### 4.2 Declare it responsive

This is the one step that makes the whole routing machinery apply to your
attribute. In `src/extensions/responsive-controls/utils/constants.js`:

```js
export const BLOCK_RESPONSIVE_KEYS = Object.freeze( {
    // …
    'spectra/demo-button': [ 'width' ],
} );
```

Keep the PHP side in sync — `Extensions\ResponsiveControls` reads the same list
when it generates CSS.

Once the key is declared, `withResponsiveControls` gives you two things for free:

| | What it does |
|---|---|
| **Read projection** | `props.attributes.width` already holds the value for the previewed device (state, falling back to base) |
| **Write routing** | `props.setAttributes({ width })` is intercepted and stored in the correct layer |

So the block's own code stays ordinary. That is the point of the extension — see
§6.5 for what it is actually doing under the hood.

### 4.3 The control

```jsx
/**
 * Demo Button — Width control.
 *
 * Fills `dimensions`, one of the seven groups core renders in the responsive
 * view, so the control survives on Tablet and Mobile. Filling `styles` would
 * give it its own panel heading on Desktop and make it disappear there.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
    __experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalUnitControl as UnitControl,
    __experimentalUseCustomUnits as useCustomUnits,
} from '@wordpress/components';

const WidthSettings = ( { clientId, attributes: { width }, setAttributes } ) => {
    const [ availableUnits ] = useSettings( 'spacing.units' );
    const units = useCustomUnits( {
        availableUnits: availableUnits || [ 'px', '%', 'em', 'rem' ],
    } );

    return (
        <InspectorControls group="dimensions">
            {/*
              * A bare ToolsPanelItem, no ToolsPanel of our own: `dimensions` is
              * a LABELLED slot, so core has already wrapped these fills in its
              * own panel.
              */}
            <ToolsPanelItem
                hasValue={ () => !! width }
                label={ __( 'Width', 'spectra-blocks' ) }
                onDeselect={ () => setAttributes( { width: undefined } ) }
                resetAllFilter={ () => ( { width: undefined } ) }
                isShownByDefault
                panelId={ clientId }
            >
                <UnitControl
                    __next40pxDefaultSize
                    label={ __( 'Width', 'spectra-blocks' ) }
                    labelPosition="top"
                    value={ width }
                    min={ 0 }
                    units={ units }
                    onChange={ ( value ) => setAttributes( { width: value } ) }
                />
            </ToolsPanelItem>
        </InspectorControls>
    );
};
```

Note `panelId={ clientId }` — core's `BlockSupportToolsPanel` sets its own
`panelId` from the selected block, and a mismatch makes the item render as a
placeholder that never appears.

---

## 5. Handling the attribute in the editor

### 5.1 Two different read rules

This distinction causes more confusion than anything else here.

| Purpose | Rule | Why |
|---|---|---|
| **What the control shows** | the value for the state being edited, **no fallback** | An empty field on Tablet means "no tablet override". Showing the inherited base value would be a lie: the user sees a number, changes nothing, and believes an override exists. |
| **What the canvas shows** | state value **falling back to base** | That is what a visitor at that width actually gets. |

`withResponsiveControls` already applies the second rule when it builds
`deviceAttributes`:

```js
const candidates = [ stateBucket[ key ], baseBucket[ key ] ];   // state, then base
const shown = candidates.find( ( value ) => hasValue( value ) );
```

Note the two-element candidate list — state then base, with **no tablet
in-between for mobile**. That is core's model, deliberately.

### 5.2 The canvas preview

Only needed for custom attributes, because core emits nothing for them:

```jsx
const blockProps = useBlockProps(
    // Memoised on purpose: useBlockProps() feeds this into useMergeRefs(), so a
    // fresh object literal every render churns the block root's ref — enough to
    // disturb typing in a RichText child.
    useMemo(
        () => ( width ? { style: { '--spectra-demo-button-width': width } } : {} ),
        [ width ]
    )
);
```

Prefer a CSS variable over a direct property: it matches the plugin's dynamic-CSS
convention (`--spectra-{attribute-name}`) and lets the stylesheet decide how the
value is used.

### 5.3 Editor / front-end parity

The canvas is already sized to the previewed device, so an inline value for the
*resolved* device is correct there. The front end needs real media queries (§7).
Both must resolve identically — `state ?? base` — or the editor and the site will
disagree, which is the hardest class of bug to diagnose in this area.

---

## 6. Aligning changes with core — the crucial part

### 6.1 The rule

> **The layer you write to is a function of the device AND whether core is
> editing a viewport state. Never the device alone.**

| Responsive styles | Device | Control reads/writes | Effect |
|---|---|---|---|
| off | Desktop | **base** | all devices |
| off | **Tablet** | **base** | all devices — tablet value untouched, invisible |
| off | **Mobile** | **base** | all devices |
| on | Desktop | **base** | all devices |
| on | Tablet | **`@tablet`** | tablet only |
| on | Mobile | **`@mobile`** | mobile only |

Rows 2 and 3 are the ones that get implemented wrong.

### 6.2 Why "mode off on Tablet" must write to the base

With the mode off, the device switcher is a **preview**, not an editing mode. Core
writes every edit to the base then. If your control writes to `@tablet` instead:

1. The canvas shows nothing changing — core previews state CSS only when the mode
   is on. The user concludes the control is broken.
2. But a value **has** been stored, in a layer the user never asked for and
   cannot see.
3. Weeks later the site looks wrong at tablet width, and nothing in the editor
   explains why.

Silently storing invisible data is worse than an inert control. **This was a real
bug in Spectra**: routing by device alone meant a change to core's own Typography
panel, made while previewing Tablet with the mode off, was intercepted and
re-routed to `@tablet` — Spectra breaking a core panel.

### 6.3 Detecting the mode — you cannot read it

`isResponsiveEditing()` and `getSelectedBlockStyleState()` live in
`block-editor/src/store/private-selectors.js`, are registered through
`registerPrivateSelectors`, and are reachable only via `unlock()` — which
**throws** for anything not on core's package allow-list. `useBlockStyleState` and
`BlockStyleStateProvider` are not exported at all. There is no filter and no
editor setting.

So it is **inferred from slots core renders conditionally**. A fill in such a slot
renders only when core has decided to render the slot, so a component mounted
there can report its own presence:

```jsx
/** Renders no UI. Its presence IS the signal. */
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
| toolbar | `BlockControls` group `style-state` | `isResponsiveEditing() && hasViewportBlockStyleState()` — an exact mirror of the private selector |
| inspector | `InspectorControls` group `styles` | the normal style view — absent in the responsive view |

```js
const isResponsiveEditingActive =
    toolbarShowsStyleState ||                          // positive, stands alone
    ( isNarrowDevice && ! inspectorShowsNormalView );  // negative, needs the device guard
```

**Both are required.** The block toolbar unmounts while the user types
(`isBlockInterfaceHidden`), which alone would flip the router back to base
mid-edit. The inspector probe cannot distinguish "responsive view" from "inspector
closed" or "Settings tab open" — harmless, since no control is on screen to
mis-route in those cases, but not safe to rely on by itself.

**Do not use the DOM check for this.** `coreResponsiveEditingActive()` reads
`.editor-preview-dropdown.is-responsive-editing`. It still has a job — it is
synchronous and works outside React — but it is **not reactive**, so a router
reading it keeps routing to the old layer until something else forces a re-render.

### 6.4 Guard for WordPress below 7.1 — do not skip this

The two probe slots have **different histories**, and the asymmetry is a trap:

| Slot | Introduced | Present on WP 6.6 |
|---|---|---|
| `InspectorControls` `styles` | Gutenberg #47105, Jan 2023 | **yes** |
| `BlockControls` `style-state` | Gutenberg #80037, Jul 2026 | **no — 7.1 only** |

Ungated on 6.6, the signal reads *"toolbar absent, normal view present"* — which
this logic interprets as "not editing a viewport state", **forever**. Every edit
would go to the base layer on every pre-7.1 site, breaking responsive controls
outright, plus a console warning per block.

So gate on the capability, and gate it in **both** places:

```js
const breakpoint = useMemo( () => {
    /*
     * Below 7.1 the device is the only signal there is. Those versions have no
     * viewport states and no Responsive styles mode, so the device buttons ARE
     * the mechanism. `isResponsiveEditingActive` is false there — necessarily —
     * and treating that false as "editing the base" would send every per-device
     * edit to the base layer.
     */
    if ( ! coreViewportStatesAreIndependent() ) {
        return getBreakpointType( deviceType );
    }

    return isResponsiveEditingActive ? getBreakpointType( deviceType ) : 'base';
}, [ deviceType, isResponsiveEditingActive ] );
```

Use `coreViewportStatesAreIndependent()` — **capability-based, not
version-based**. PHP measures core once with `function_exists()` / `is_callable()`
and exports the answer as `spectra_blocks_info.viewport_support`, so a 7.0 site
running the Gutenberg plugin is treated as having the states it actually has, and
a partially-updated install is not fooled by its version string.

The reset path needs the same guard, from a non-React context — see §8.

### 6.5 What Spectra already does for you

For a block inside this plugin, §6.1–§6.4 is implemented once in
`withResponsiveControls`. Declaring `'spectra/demo-button': [ 'width' ]` in
`BLOCK_RESPONSIVE_KEYS` is enough; the extension:

1. calls `useResponsiveEditing( deviceType )` to get the mode signal;
2. renders `<ResponsiveEditingProbes />` — **required**, or the signal is
   permanently false;
3. computes `breakpoint` with the guarded rule above;
4. projects the resolved value onto `props.attributes` for display;
5. routes `setAttributes` writes into that layer.

**Consequence to be aware of, not a bug:** Spectra's own device buttons in a panel
header no longer decide storage on their own. With the mode off, picking Tablet
there previews tablet and edits the base — exactly as it does for a core control.
Per-device values require the mode to be on. That *is* the alignment; a control
that stores per-device while core stores globally, in the same panel, is the bug.

You only need to implement §6 yourself when writing a control **outside** this
extension — a different plugin, or a block excluded from
`isAllowedBlock()`.

---

## 7. Generating CSS on the front end

Core emits CSS only for style paths it owns, so a custom attribute produces
nothing by itself.

**Always take the media queries from core:**

```php
/**
 * Media queries keyed by viewport state, from core where available.
 *
 * `WP_Theme_JSON::get_viewport_media_queries()` is public and documented
 * (@since 7.1.0). It reads `settings.viewport` from theme.json, so a theme that
 * moves the breakpoints moves ours with it — and the bands match core's own
 * output exactly, which is what keeps a custom control and a core control on the
 * same block in agreement.
 */
if ( is_callable( array( '\WP_Theme_JSON', 'get_viewport_media_queries' ) ) ) {
    $queries = \WP_Theme_JSON::get_viewport_media_queries( $viewport_settings );
}
```

Emit the base rule **without** a media query, then one rule per state:

```php
$css = sprintf( '%s{--spectra-demo-button-width:%s;}', $selector, $base );

foreach ( $queries as $state_key => $media_query ) {
    $value = $states[ $state_key ] ?? '';

    if ( '' === $value ) {
        continue;
    }

    $css .= sprintf( '%s{%s{--spectra-demo-button-width:%s;}}', $media_query, $selector, $value );
}
```

### 7.1 Three things that will bite you

**Never reimplement the bands with `max-width`.** Core uses mutually exclusive
ranges. Stacked `max-width` queries silently reintroduce a tablet-into-mobile
cascade and disagree with every core control on the same block.

**Never run generated CSS through `wp_strip_all_tags()` or `esc_attr()`.** Core's
range syntax contains a `<`:

```
in:  .btn{width:200px;}@media (480px < width <= 782px){.btn{width:150px;}}
out: .btn{width:200px;}@media (480px < width          ← everything after < is gone
```

Escaping is the wrong tool anyway — it would mangle the same `<`, and no escaping
makes `expression(…)` safe inside a declaration. **Allow-list the values
instead:**

```php
/**
 * Whether a value is a CSS length we are willing to print.
 *
 * The value arrives from post content, so it is untrusted even though only an
 * editor could have written it.
 */
public static function is_safe_length( $value ) {
    return 1 === preg_match( '/^-?(?:\d+\.?\d*|\.\d+)(?:px|%|em|rem|vw|vh)$/', $value );
}
```

**Scope by value, not by instance.** Deriving the class from a hash of the values
means two identical buttons on a page collapse onto one rule instead of emitting
a near-duplicate each.

---

## 8. Reset behaviour

Two independent things must be scoped to the active state.

### 8.1 `resetAllFilter` on your fill

Core scopes its **own** reset filters through `scopeResetAllFilterToState` in
`inspector-controls/fill.js` — but that reads `useBlockStyleState()`, whose
context comes from the **fill's** position in the React tree. A plugin fill sits
outside core's `BlockStyleStateProvider`, so it reads the **default** state, and
**"Reset all" on Tablet wipes the Desktop value.**

Scope it yourself:

```jsx
<InspectorControls
    group="dimensions"
    resetAllFilter={ ( attributes ) => ( {
        style: clearAtState( attributes?.style, stateKey ),
    } ) }
>
```

Clearing `@tablet` must **reveal** the Desktop value, not destroy it.

### 8.2 The reset handler must agree with the write router

If an edit went to the base but the reset clears `@tablet`, the reset removes
nothing and appears broken. Both must read the same signal. Spectra's reset runs
from a document click listener, outside React, so it uses the non-React getter:

```js
const breakpoint =
    ! coreViewportStatesAreIndependent() || getResponsiveEditingActive()
        ? BREAKPOINT_TYPE_MAP[ deviceType ] || 'base'
        : 'base';
```

`getResponsiveEditingActive()` reads a module-level value the probes keep
updated, falling back to the DOM check when no probe has reported yet (no block
selected). Returning `false` there would claim the base layer during a reset
performed immediately after selecting a block in the responsive view.

---

## 9. Checklist

**Visibility**

- [ ] The control fills one of the seven groups from §3.2 — **not `styles`**.
- [ ] It contributes bare `ToolsPanelItem`s, no `ToolsPanel` of its own.
- [ ] `panelId` is the block's `clientId`.
- [ ] Controls that are *not* per-device are deliberately left out of the
      responsive view.

**Alignment**

- [ ] The attribute name is in `BLOCK_RESPONSIVE_KEYS` (JS **and** PHP).
- [ ] The write layer depends on device **and** mode.
- [ ] Guarded by `coreViewportStatesAreIndependent()` for pre-7.1.
- [ ] `<ResponsiveEditingProbes />` is rendered, if implementing detection
      yourself.
- [ ] The control shows the **exact** state value; the canvas shows the
      **resolved** one.
- [ ] `resetAllFilter` is scoped to the active state.
- [ ] The reset handler reads the same signal as the write router.

**Front end**

- [ ] Media queries come from `WP_Theme_JSON::get_viewport_media_queries()`.
- [ ] Base rule carries no media query.
- [ ] Values pass a length allow-list; no `wp_strip_all_tags()` on the CSS.
- [ ] Editor and front end resolve identically (`state ?? base`).

**Verification**

- [ ] Mode off + Tablet → edits the base; **no `@tablet` key** appears in the
      block's `style`.
- [ ] Mode on + Tablet → writes `@tablet`; Desktop keeps its own value.
- [ ] Mode on + Tablet + Reset → clears tablet only.
- [ ] A pre-existing post opens unchanged.
- [ ] On WP 6.x: still stores per-device, console clean.

---

## 10. Anti-patterns

| Don't | Why | Instead |
|---|---|---|
| `<InspectorControls group="styles">` for a per-device control | Not rendered in the responsive view; the panel vanishes on Tablet/Mobile with its values stranded | One of the seven groups (§3.2) |
| Invent `widthTablet` / `widthMobile` attributes | Cannot align with core's state model, and doubles on every new breakpoint | One value per layer inside `style` |
| Decide the layer from the device alone | Writes invisible overrides when the mode is off | `f( device, mode )` (§6.1) |
| `unlock()` to read `isResponsiveEditing()` | Throws for non-core packages | The probes (§6.3) |
| Read `.is-responsive-editing` from the DOM in a router | Not reactive; routes to a stale layer | The probes; keep the DOM check for non-React callers |
| Ship the detection without a capability guard | Breaks every pre-7.1 site (§6.4) | `coreViewportStatesAreIndependent()` |
| Show the inherited value in the control on Tablet | User cannot tell whether an override exists | Exact state value, empty when unset |
| Reimplement bands with `max-width` | Reintroduces the tablet→mobile cascade; disagrees with core | Core's `get_viewport_media_queries()` |
| `wp_strip_all_tags()` on generated CSS | Truncates at the `<` in core's range syntax | Allow-list the values |
| A `ToolsPanel` inside a labelled slot | Two headings, two reset menus | Bare `ToolsPanelItem`s |
| An unmemoised object into `useBlockProps()` | Churns the block root's ref; can disturb typing | `useMemo` |

---

## 11. File map

**Spectra — the mechanism**

| Path | Role |
|---|---|
| `src/extensions/responsive-controls/utils/use-responsive-editing.js` | The mode signal: probes, combination, capability guard |
| `src/extensions/responsive-controls/utils/helpers.js` | `withResponsiveControls` — read projection and write routing |
| `src/extensions/responsive-controls/utils/constants.js` | `BLOCK_RESPONSIVE_KEYS`, `BREAKPOINT_TYPE_MAP`, `coreViewportStatesAreIndependent()` |
| `src/extensions/responsive-controls/index.js` | Reset handling |
| `src/extensions/responsive-controls/control-injection.js` | Device buttons and the "Turn On" hint |
| `includes/Extensions/class-responsive-controls.php` | Band resolution via core |
| `includes/Extensions/ResponsiveControls/class-responsive-attribute-css.php` | CSS generation |
| `includes/Extensions/ResponsiveControls/class-viewport-support.php` | The capability measurement |
| `src/blocks/icon/settings.js` | A clean example of a flat per-device key in `dimensions` |

**WordPress core — the contract**

| Path | What to look at |
|---|---|
| `block-editor/src/components/block-inspector/index.js` | `StyleStateInspectorSlots` — the seven slots |
| `block-editor/src/components/inspector-controls/groups.js` | The group registry |
| `block-editor/src/components/inspector-controls-tabs/styles-tab.js` | The Styles tab, including the unlabelled `styles` slot |
| `block-editor/src/components/block-controls/groups.js` | The `style-state` group |
| `block-editor/src/components/block-toolbar/index.js` | The condition the toolbar probe mirrors |
| `block-editor/src/store/private-selectors.js` | The selectors we cannot use |
| `block-editor/src/hooks/style.js` | `BlockStyleStateProvider` — why our reset needs manual scoping |
| `wp-includes/class-wp-theme-json.php` | `get_viewport_media_queries()` |

**Related documents**

| Doc | Covers |
|---|---|
| [`GENERAL-RESPONSIVE-STYLES.md`](./GENERAL-RESPONSIVE-STYLES.md) | How the routing mechanism itself works — the mode signal, the shared-state model, the pre-7.1 guards. §14 scopes **Phase 2**, where CSS generation moves to core. |
| `../../blockkit/docs/RESPONSIVE-STYLES-EXPERIMENT.md` | The R&D bench the mechanism was proved on, with a live diagnostics readout |

**Upstream**

| Ref | Subject |
|---|---|
| [#78280](https://github.com/WordPress/gutenberg/pull/78280) | Created the seven-slot allow-list — deliberately, for the reason in §6.2 |
| [#80388](https://github.com/WordPress/gutenberg/issues/80388) | 7.2 tasks, including a third-party API |
| [#82003](https://github.com/WordPress/gutenberg/pull/82003) | Draft: adds a `viewport` group, rendered unlabelled |
