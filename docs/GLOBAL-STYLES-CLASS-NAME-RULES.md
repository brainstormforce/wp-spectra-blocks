# Global Styles Registry — Which Class Names Are Accepted

> **Purpose.** The single reference for what class names the Global Styles (GBS) registry
> accepts, why some are rejected, and where the validation lives. Read this before touching
> class-name validation, the import endpoints, or the SaaS-side class generator — so the
> next person doesn't have to re-derive these rules from the code.
>
> **Scope.** The user/import class registry stored in the
> `spectra_blocks_pro_gs_user_css` **option** (site-wide classes + keyframes), written by the
> REST endpoints in `includes/GlobalStyles/class-rest-controller.php` and rendered site-wide
> by `includes/GlobalStyles/class-engine.php`. (The per-page **post meta** of the same key
> string is a different surface — see "Related surfaces" at the end.)

**Last verified against code:** 2026-06-12

---

## 1. The rule (TL;DR)

A class name is accepted by the import endpoints (`/global-styles/bulk`,
`/global-styles/sitewide`) when it passes **BOTH** checks in
`RestController::is_allowed_class_name()`:

1. **Syntax** — it is a valid CSS class identifier:

   ```php
   const CLASS_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]*$/';
   ```

   - first character: a letter (`a–z`, `A–Z`) or underscore `_`
   - remaining characters: letters, digits, `-`, `_`
   - no spaces, dots, colons, or any other character; must not start with a digit or hyphen

2. **Not reserved** — it does not start with any prefix in:

   ```php
   const RESERVED_CLASS_PREFIXES = array(
       'gc-spectra-', 'ast-', 'astra-', 'swt-', 'wp-', 'uagb-', 'spectra-', 'is-', 'has-',
   );
   ```

Rejected names are **skipped silently per-entry** (the rest of the payload still saves).
`/bulk` reports skips in its response (`skipped_classes` + reason); `/sitewide` currently
does **not** (known gap — see §6).

---

## 2. Examples

| Class name | Result | Why |
|---|---|---|
| `gs-a1c93a-ap-footer-colophon` | ✅ | normal SaaS-generated name (letter-leading hash) |
| `gs-69f07e-ap-footer` | ✅ | **digit-leading hex hash is fine** (see §5 — this used to be rejected) |
| `gs-3b99f5-ap-btn` | ✅ | digit-leading hex hash |
| `ap-btn-signal`, `era-nav-link`, `btn-primary` | ✅ | bare source-author classes — no prefix requirement |
| `animate-fade-in` | ✅ | animation binding (pairs with a registered keyframe) |
| `my_card`, `Hero2` | ✅ | underscore / uppercase / mid-name digits are legal CSS |
| `ast-button`, `astra-header` | ❌ | reserved — Astra theme classes |
| `wp-block-group`, `wp-element-button` | ❌ | reserved — WP core block/element classes |
| `is-style-rounded`, `has-primary-color` | ❌ | reserved — WP state/preset vocabulary |
| `uagb-button`, `spectra-container` | ❌ | reserved — UAG-legacy / this plugin's own classes |
| `gc-spectra-123` | ❌ | reserved — legacy generated family (orphan-scrubbed) |
| `swt-hero` | ❌ | reserved — legacy Spectra website-templates family |
| `1hero` | ❌ | invalid syntax — CSS identifier can't start with a digit |
| `-foo` | ❌ | invalid syntax — hyphen-leading excluded by design |
| `.card` | ❌ | invalid syntax — would render as `..card` |
| `a b` | ❌ | invalid syntax — space = selector injection (descendant selector) |

---

## 3. WHY the reserved prefixes are blocked

**The mechanism that makes this matter:** every class in this registry is rendered
**site-wide** by `Engine`/`GenCssRenderer` with a specificity-boosted selector — frontend
`[class].{name}`, editor `.editor-styles-wrapper .{name}`. That selector matches **every
element on the site already carrying the class**. So registering a name that other code
already uses doesn't "add a class" — it **hijacks existing elements**:

| Prefix | What allowing it would do |
|---|---|
| `ast-` / `astra-` | An imported `.ast-button { background: orange }` restyles **every Astra theme button on every page** — elements the import never touched. |
| `wp-` | `wp-block-group` sits on virtually every Group block; `wp-element-button` on every button. One registered class = a global restyle of core blocks. |
| `is-` / `has-` | WordPress's own state/preset vocabulary (`is-style-rounded` = the user picked the "Rounded" block style; `has-primary-color` = they picked a palette color). Registering these silently changes what the editor's pickers *mean*, globally. |
| `uagb-` / `spectra-` | Same hijack against Spectra's own shipped block styles. |
| `swt-` | Legacy Spectra website-template classes still present in old content. |
| `gc-spectra-` | Different reason: the **dead legacy family** that `GenCssOrphanStripper` actively scrubs at write time. Registering them fights the cleanup machinery, and no rendered element carries the class anymore — dead weight. |

There is also a UX reason: the registry is **user-facing** — the Style Guide UI lists these
entries as the user's editable custom classes. Theme/core names showing up there would be
confusing and dangerous to edit.

**Nothing is lost by blocking these.** When imported source CSS legitimately needs to style
such elements (e.g. a rule targeting `.wp-block-button`), it travels in the **`wrapperStyles`
bucket** of the schema-v1 payload — keyed by *selector*, no name registry, designed exactly
for "style elements I don't own". The `classes` bucket is strictly "names the import *owns*".

**WHY the syntax rejects:** the stored name is interpolated into a stylesheet as `.{name}`.
`1hero` → `.1hero` (invalid CSS, browser drops the rule — stored but never works);
`.card` → `..card` (invalid); `a b` → `.a b` (the space **changes selector semantics** to a
descendant selector — i.e. selector injection through the name field).

---

## 4. Where the validation lives (code map)

| What | Where |
|---|---|
| Syntax pattern | `includes/GlobalStyles/class-rest-controller.php` → `CLASS_NAME_PATTERN` |
| Reserved prefixes | same file → `RESERVED_CLASS_PREFIXES` |
| The validator | same file → `is_allowed_class_name()` (private static) |
| Call site: legacy bulk endpoint | `update_bulk()` (`POST /spectra-blocks/v1/global-styles/bulk`) — reports skips |
| Call site: import sitewide endpoint | `update_sitewide()` (`POST /spectra-blocks/v1/global-styles/sitewide`) — **silent** skips |
| NOT validated here | `update_custom_class()` (Style Guide UI single-class POST) — accepts any non-empty name; **no prefix/denylist check**. The import endpoints are the strict ones. |
| Declaration sanitization (separate layer) | `class-sanitizer.php` → `Sanitizer::sanitize_json()` — property/value-level; runs *after* the name check |
| Permission gate | all CRUD routes require `edit_theme_options` |
| Render (why hijack matters) | `class-engine.php` → `render_user_classes()` / `enqueue_gen_sitewide_css()` → `class-gen-css-renderer.php` `GenCssRenderer::render()` |
| Regression test | `tests/phpunit/tests/GlobalStyles/RestControllerTest.php` → `test_update_sitewide_validates_names_by_syntax_and_denylist` |

Keyframe names are validated separately (`KEYFRAME_NAME_PATTERN` + `RESERVED_KEYFRAME_NAMES`,
same file) — this doc covers class names only.

---

## 5. History — why the rule looks like this (the CHG-006 bug)

Until 2026-06-12 the pattern was a **prefix allowlist**:

```php
const CLASS_NAME_PATTERN = '/^(?:gs|animate)-[a-z][a-z0-9-]*$/';   // OLD
```

Two silent failure modes:

1. The char after the prefix had to be a **letter**, but the SaaS name-resolver
   (`zipwp-credits-saas/workers/modules/scripts/fse-token-align/src/name-resolver.ts`)
   emits `gs-{6-hex-hash}-{base}` — and a hex hash starts with a digit **~10/16 (~62%)** of
   the time. So `gs-69f07e-ap-footer` was rejected while `gs-a1c93a-ap-footer-colophon`
   passed — whether a class survived was literally the luck of its content-hash.
2. Bare source-author classes routed site-wide by the import (`ap-btn-signal`) had no
   `gs-`/`animate-` prefix and were always rejected.

Both were `continue;` with no skip report on the `/sitewide` path, and the SaaS sender is
fire-and-forget — so ~62% of every import's site-wide classes vanished with zero signal.
It was masked visually because the `wp_global_styles` chrome sentinel block (no name
validation) still rendered the same rules.

Verified end-to-end during diagnosis: the SaaS `laravel.log` showed the POST carried 29
classes; the option kept exactly the 13 whose hash starts `a–f`.

The fix changed the philosophy from **allowlist-by-prefix** to
**accept-by-syntax + denylist-known-dangerous** (matching the UI path, which never enforced
a prefix). Full change record: `zipwp-credits-saas/docs/STYLE-SYSTEM.md` §5 **CHG-006**.

**Deploy note:** sites imported under the old pattern are missing the dropped classes from
the option until **re-imported** (the import merge never deletes, so re-import is safe).

---

## 6. Known gaps / extension points

- **`update_sitewide` skips silently** — no `skipped_classes` in its response (unlike
  `/bulk`). This is exactly why CHG-006 went unnoticed. If you add validation rules, add
  skip reporting first.
- **Generic-name collisions are accepted by design** — `btn-primary` or `card` from a source
  site now registers site-wide; if a theme/plugin uses the same class, imported rules can
  affect those elements. The denylist covers known-dangerous families; **extend
  `RESERVED_CLASS_PREFIXES`** (one array) if a new family proves problematic in practice.
- If a *legitimate* need arises to register a reserved-family name, carve the exception in
  `is_allowed_class_name()` — don't widen the prefix list semantics ad hoc.

---

## 7. Related surfaces (don't conflate)

| Surface | Store | Name validation |
|---|---|---|
| Site-wide class registry (THIS doc) | option `spectra_blocks_pro_gs_user_css` | `is_allowed_class_name()` (syntax + denylist) |
| Per-page imported payload | **post meta** `spectra_blocks_pro_gs_user_css` (same string, different table) | **none** — `zip-ai` `SetPageCustomCss` stores the decoded array verbatim; rendered per-post only |
| Authoring-service CSS | `wp_global_styles.styles.css` (sentinel blocks) | **none** — raw CSS, written by the SaaS |

The cross-plugin big picture (generation → routing → save → render) lives in
`zipwp-credits-saas/docs/STYLE-SYSTEM.md`.
