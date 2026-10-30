# Spectra Blocks — Product Philosophy

> Internal guide to _why_ Spectra Blocks is built the way it is. Use it to steer
> new feature work and as a rubric for reviewing proposals (human or AI). If a
> change contradicts a principle here, that's a signal to stop and reconsider —
> or to deliberately update this document.
>
> This is a philosophy doc, not an API reference. For _how_ the code is laid out,
> see [`ARCHITECTURE.md`](../ARCHITECTURE.md). For responsive/global-styles
> specifics, see the other files in [`docs/`](.).

## TL;DR

Spectra Blocks is a ground-up rebuild of Spectra on **WordPress core block APIs**.
It is **composable, core-first, and forward-compatible by design**. We extend core
blocks instead of duplicating them, keep a small set of flexible blocks instead of
many single-purpose ones, stay close to the native editor, and track WordPress as
the platform evolves. Everything else below follows from that.

---

## 1. Build on WordPress core, don't work around it

Spectra Legacy (UAGB) was one of the first block plugins, written before many of
today's WordPress APIs existed, so it had to build its own groundwork — a large
surface of custom controls that became hard to maintain and broke with WordPress
releases. Spectra Blocks takes the opposite stance: **lean on core APIs rather than
reinvent them.**

- Prefer a core capability (block supports, `theme.json`, the Style Engine, block
  bindings, the Interactivity API, template parts, global settings) over a bespoke
  implementation.
- When core gains a capability we already shimmed, **migrate to core and delete the
  shim.** Less code we own is a feature, not a regret.
- New custom infrastructure needs a real justification: core genuinely can't do it,
  or does it in a way that breaks our composability. Default to "no."

**Reviewer check:** _Does core (or `theme.json`, Style Engine, block supports,
bindings) already do this? If yes, use it. If we're re-implementing core, why?_

## 2. Composable, not single-purpose

Spectra Blocks ships a **small set of flexible primitives** — Container, Content,
Accordion, Tabs, Slider, Icon(s), Counter, Countdown, Modal — plus whatever Core and
third-party blocks nest inside them. We deliberately do **not** ship dedicated
Testimonial, Review, Info Box, Team, Pricing, etc. blocks.

- Those layouts are **composed** from Container + Core blocks (Image, Heading,
  Paragraph) and our primitives, with ready-made **patterns** in the Design Library
  as the shortcut.
- A new "block" idea should almost always be a **pattern** or a **variation**, not a
  new block type. New blocks are reserved for genuinely new _primitives_ or
  _mechanics_ that can't be composed from what exists.
- Fewer blocks = lighter plugin, more design freedom, less to maintain and keep
  compatible.

**Reviewer check:** _Can this be a pattern/variation/Container composition instead
of a new block? If a new block is proposed, what primitive or mechanic does it add
that composition can't?_

## 3. Extend Core blocks via Extensions

Where Core already owns a block (most importantly **`core/image`**), we don't clone
it. We add Spectra's design power through **Extensions** — spacing, borders, shadows,
responsive controls, dynamic content, display conditions, masks, etc. — layered onto
the Core block.

- Users get Core's reliability **plus** Spectra's controls, with no duplicate
  "Spectra Image" to maintain.
- Extensions must degrade gracefully: the Core block still works if the extension's
  value is absent, and uninstalling Spectra must not corrupt saved Core content.

**Reviewer check:** _Is there a Core block for this already? Then extend it, don't
wrap or re-register it._

## 4. Stay close to the native editor

Legacy's dense General/Style/Advanced custom panels felt convenient but drifted from
core and broke often. Spectra Blocks keeps controls **native and predictable.**

- Use core's control components and respect core's **ordering and grouping** (e.g.
  background color sits with the color controls; background image has its own
  section) even when a custom arrangement might feel tidier — consistency across
  every block beats per-block cleverness.
- Avoid large bespoke inspector surfaces. If core offers a control, use it.
- Accept a learning curve for ex-V2 users in exchange for an editor that survives
  WordPress updates.

**Reviewer check:** _Does this introduce a custom control where a native one exists?
Does it reorder native controls away from core's convention?_

## 5. Server-rendered, resolved at request time

Spectra blocks render on the server (`view.php` / controllers). This has direct
consequences every feature must honor:

- **No "Edit as HTML."** A server-rendered block's `save()` never matches the editor's
  HTML box, so `supports.html` must be `false` on every block (see the audit in
  issue tracking). Edit-as-HTML on these blocks only produces invalid-content errors
  or an empty box.
- **Resolve dynamic things at request time, never bake them.** Per-post, per-page, or
  per-device values must be computed when the page renders — not frozen into saved
  attributes at edit time. Baked values are the root of a whole class of bugs
  (frozen dates, one header site-wide, looped cards all showing the "current" page,
  stale per-device values).
- **The current post is the looped/global post.** `current_post` dynamic content and
  loop items resolve against the post actually being rendered, with any stored id used
  only as an editor-preview fallback.
- **Always provide a fallback path.** If a dynamic value can't resolve, render the
  configured fallback (image, text) — don't emit nothing.

**Reviewer check:** _Does this bake a per-request value into saved markup? Is there a
fallback when resolution fails? Is `supports.html: false` set on any new block?_

## 6. Forward-compatible with WordPress

We assume WordPress will keep moving (FSE, viewport style states, block bindings,
the HTML API, KSES-on-HTML-API, the Abilities API) and we **track it deliberately.**

- Test against `trunk`, not just the current stable release. A feature that only
  works on today's WordPress is not done.
- When core changes behavior under us (e.g. 7.1 viewport states, 7.2's KSES
  normalizing attribute strings, 6.9 block bindings for `core/post-date`), the fix is
  to **align with the new core behavior**, not to pin to the old one.
- Never depend on private/experimental core internals without a guarded fallback and
  a removal note for when they stabilize.

**Reviewer check:** _Does this pass on WP `trunk`? Does it rely on current-version
behavior that core is changing? Does it degrade safely on the minimum supported
version?_

## 7. Performance and clean, semantic output

- Enqueue assets **only for the blocks a page actually uses.** A page built with one
  block set must not pull another's assets (this is also what keeps Legacy +
  Spectra Blocks coexistence cheap).
- Emit **lighter, cleaner, semantic HTML** than Legacy. Cleaner markup is both a
  performance and an SEO win, and rebuilt pages should improve, not regress, output
  quality.
- Styling flows through **CSS custom properties** and the Style Engine, not inline
  style soup.

**Reviewer check:** _Does this load assets globally when it could scope to the block?
Does it add non-semantic wrapper markup?_

## 8. Global Styles as the single source of truth

Site-wide design — colors, typography, spacing — lives in **Global Styles**, applied
consistently rather than edited block-by-block. This is also the surface our AI
writes to: ZipAI stores generated styling as Global Styles so everything stays in one
editable place.

**Reviewer check:** _Should this be a global token/setting instead of a per-block
value? Does it keep Global Styles authoritative?_

## 9. AI-native

Spectra Blocks is the home for Spectra's AI-driven building (ZipAI / ZipWP):
template generation, HTML-to-blocks conversion, and AI-authored designs that land as
Global Styles on the Spectra One theme. The core blocks, settings, and design tools
are **always free and need no account**; only the AI/online features connect to
ZipWP. Keep that boundary clear in UI and labeling.

- Composability and predictable, native markup are what make reliable AI generation
  possible — another reason to hold principles 1–4.

**Reviewer check:** _Does this keep free/offline features free and clearly separate
from ZipWP-connected ones? Does it preserve the clean, composable structure AI relies
on?_

## 10. Non-disruptive, user-paced, coexistent

Spectra Blocks is a **separate plugin** from Spectra Legacy — not an auto-upgrade.
Users move page by page at their own pace; both can run side by side; a block always
needs its own plugin active to render.

- Never silently switch or migrate an existing site. Changes to defaults or output
  must not alter already-saved content.
- Minimum platform: **WordPress 6.6+, PHP 8.1+.** Respect it.
- Coexist cleanly with the wider Brainstorm Force family (Astra, Starter Templates,
  SureForms, SureCart) and with Core/third-party blocks nested in Containers.

**Reviewer check:** _Could this change what existing saved pages render? Does it break
coexistence with Legacy or with Core/third-party blocks?_

---

## Using this document for validation

For any proposed feature, PR, or AI-generated change, walk the principles as a gate:

1. **Core-first (1,3)** — are we reusing/extending core, or re-implementing it?
2. **Composable (2)** — pattern/variation/Container over a new block?
3. **Native editor (4)** — native controls, core ordering?
4. **Server-render discipline (5)** — nothing baked, fallback present, `html:false`?
5. **Forward-compat (6)** — green on `trunk`, aligned with core's direction?
6. **Performance & semantics (7)** — scoped assets, clean markup?
7. **Global Styles (8)** — global token vs per-block value?
8. **AI boundary (9)** — free/offline stays free; structure stays AI-friendly?
9. **Non-disruption (10)** — existing content unchanged, coexistence intact, 6.6/8.1?

A proposal that trips a principle isn't automatically wrong — but it must either be
reworked to fit, or this document must be consciously amended to record the new
direction. Keep this file current; it's only useful while it reflects what we
actually believe.
