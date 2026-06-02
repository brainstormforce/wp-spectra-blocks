=== Spectra Blocks ===
Contributors: brainstormforce
Tags: gutenberg, blocks, block-editor, container, accordion
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.0.8
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fresh, clean Gutenberg block plugin built on Spectra V3 with modern standards.

== Description ==

Spectra Blocks provides feature-rich Gutenberg blocks built with modern standards using the WordPress Interactivity API. It includes a complete set of layout and content blocks such as Container, Accordion, Tabs, Countdown, Counter, Slider, Modal, Popup Builder, List, Buttons, Google Map, Icons, Separator, and more.

All blocks support responsive conditions, animations, sticky positioning, and z-index controls out of the box.

**Key Features:**

* 15+ professionally designed blocks
* Built on the WordPress Interactivity API (V3 architecture)
* Container block with Flex, Grid, Constrained, and Flow layouts
* Accordion with fully customizable icon, animation, and color controls
* Tabs block with horizontal and vertical orientations
* Responsive visibility controls (hide on desktop, tablet, mobile)
* Animation support for all blocks
* Sticky container support
* SVG upload support with server-side sanitization


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/spectra-blocks` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Use the Block Editor to insert Spectra Blocks from the **Spectra Blocks** category in the block inserter.

== Frequently Asked Questions ==

= What PHP version is required? =

PHP 8.1 or higher is required.

= What WordPress version is required? =

WordPress 6.6 or higher is required.

= Does this plugin require the original Spectra plugin? =

No. Spectra Blocks is a fully standalone plugin and does not require the original Spectra (ultimate-addons-for-gutenberg) plugin.

== Screenshots ==

1. Block inserter showing the Spectra Blocks category with all available blocks.
2. Container block with Flex/Grid/Constrained layout options.
3. Accordion block with customizable colors and animation settings.
4. Tabs block with horizontal and vertical orientations.

== Source Code ==

This plugin's JavaScript and CSS are built from source files using standard WordPress build tools.

= Plugin Source =


* Block source files are located in the `src/` directory (excluded from the distribution zip for size).
* Admin dashboard source files are located in `admin/assets/src/` (also excluded from distribution).
* The compiled, production-ready assets are in `build/` and `admin/assets/build/` respectively.
* Source code is available on GitHub: [https://github.com/brainstormforce/wp-spectra-blocks](https://github.com/brainstormforce/wp-spectra-blocks)

To build from source:

1. `npm install && npm run build` — compiles block JavaScript and CSS.
2. `cd admin && npm install && npm run build` — compiles the admin dashboard React app.

= Third-Party Libraries =

The following pre-compiled third-party libraries are bundled in `assets/`:

* **Swiper** (slider functionality) — Source: [https://github.com/nolimits4web/swiper](https://github.com/nolimits4web/swiper) — License: MIT
* **AOS** (Animate on Scroll) — Source: [https://github.com/michalsnik/aos](https://github.com/michalsnik/aos) — License: MIT

= Bundled Library Source =

Source code for each bundled library and third-party utility used by this plugin:

* `lib/gutenberg-templates/dist/` and `lib/gutenberg-templates/inc/block/dist/` — Source: [https://github.com/brainstormforce/bsf-gutenberg-templates](https://github.com/brainstormforce/bsf-gutenberg-templates) (build: `npm install && npm run build`)
* `lib/zip-ai/sidebar/build/` and `lib/zip-ai/admin/dashboard-app/build/` — Source: [https://github.com/brainstormforce/bsf-zip-ai](https://github.com/brainstormforce/bsf-zip-ai) (build: `npm install && npm run build`)
* `lib/nps-survey/dist/` — Source: [https://github.com/brainstormforce/bsf-nps-survey](https://github.com/brainstormforce/bsf-nps-survey) (build: `npm install && npm run build`)
* `lib/zipwp-images/dist/` — Source: [https://github.com/brainstormforce/bsf-zipwp-images](https://github.com/brainstormforce/bsf-zipwp-images) (build: `npm install && npm run build`)
* `lib/astra-notices/` — Source: [https://github.com/brainstormforce/astra-notices](https://github.com/brainstormforce/astra-notices) (plain PHP, ships unbuilt)
* `vendor/enshrined/svg-sanitize/` — Source: [https://github.com/darylldoyle/svg-sanitizer](https://github.com/darylldoyle/svg-sanitizer) (plain PHP, ships unbuilt)
* `admin/assets/build/dashboard-app.js` — bundles the `@bsf/force-ui` admin UI components — Source: [https://github.com/brainstormforce/bsf-admin-ui](https://github.com/brainstormforce/bsf-admin-ui) (build: `npm install && npm run build` in the `admin/` directory)

== External Services ==

This plugin connects to the following third-party services under certain conditions:

= Google Maps (maps.google.com) =
The Google Map block embeds a map on the frontend using the public Google Maps embed URL (`https://maps.google.com/maps?q=...&output=embed`). No API key is required and no data is sent from the server. The request is made by the visitor's browser each time a page containing the block is rendered; the address entered in the block is included in the URL so Google can return the matching map tile.
* Service URL: `https://maps.google.com/maps`
* [Terms of Service](https://developers.google.com/maps/terms)
* [Privacy Policy](https://policies.google.com/privacy)

= Google Fonts =
When the "Load Google Fonts Locally" option is enabled by an administrator, the plugin downloads selected font files from Google Fonts to the site's uploads directory so they are served from the local server instead of Google's CDN. This download happens once per font, triggered by an administrator saving font settings. No user data is sent; only the font file URL is requested.
* Service URL: `https://fonts.googleapis.com/` and `https://fonts.gstatic.com/`
* Data sent: Font file URL only (no user data)
* When: Only when an administrator enables local font loading and saves the font settings
* [Terms of Service](https://developers.google.com/fonts/terms)
* [Privacy Policy](https://policies.google.com/privacy)

= Brainstorm Force Store =
When the "Get Spectra Pro" upsell is displayed in the admin dashboard, pricing information is fetched from the Brainstorm Force store. This request is made by the administrator's browser, not the plugin server.
* Service URL: `https://store.brainstormforce.com/`
* [Terms of Service](https://www.brainstormforce.com/terms-and-conditions/)
* [Privacy Policy](https://www.brainstormforce.com/privacy-policy/)

= WordPress.org =
When installing recommended plugins or themes from the admin dashboard, the plugin uses the standard WordPress.org API to download packages.
* Service URL: `https://downloads.wordpress.org/`
* [Privacy Policy](https://wordpress.org/about/privacy/)

= YouTube =
The admin dashboard settings page embeds tutorial videos from YouTube using the privacy-enhanced mode (youtube-nocookie.com). Videos are loaded only when a user clicks play on the admin dashboard. No user data is sent until playback is initiated.
* Service URL: `https://www.youtube-nocookie.com/`
* [Terms of Service](https://www.youtube.com/t/terms)
* [Privacy Policy](https://policies.google.com/privacy)

== Changelog ==

= 0.0.8 =
* Fix: Remove bundled BSF shared libraries (zip-ai, astra-notices, nps-survey, zipwp-images, gutenberg-templates) to resolve WordPress.org plugin review compliance issues.
* Fix: Remove admin dashboard RSS feed fetch (What's New panel) that contacted wpspectra.com without explicit opt-in.
* Update: Simplify External Services documentation to cover only services used by core plugin functionality.

= 0.0.7 =
* Fix: WordPress.org compliance fixes — sanitize CSS properties/values in inline styles.
* Update: Updated all shared libraries to latest versions.

= 0.0.6 =
* Fix: Removed server-side IP geolocation lookups (WordPress.org compliance).
* Fix: Hardened the SVG upload sanitizer to fail closed when the sanitizer library is unavailable.
* Update: Documented all external services accessed by the plugin in readme.txt.

= 0.0.5 =
* Fix: Externalized Swiper library from webpack build.
* Fix: Improved Swiper asset loading via block.json declarations for proper editor iframe and frontend support.
* Fix: Updated GitHub repository URLs for source repositories.

= 0.0.4 =
* Fix: Updated Swiper library to v12.1.3.
* Fix: Added wp_kses() escaping at render output point in HtmlSanitizer.
* Fix: Wired lib-abspath-guard into grunt zip build pipeline.

= 0.0.3 =
* Fix: Added ABSPATH guards to all generated .asset.php files via webpack plugin.
* Fix: Added wp_unslash() before sanitization on all superglobal accesses.
* Fix: Added function_exists() guards around core file includes.
* Fix: Added capability check to SVG upload filter.
* Fix: Updated all shared libraries to latest versions.


= 0.0.2 =
* Fix: Address WordPress.org plugin review compliance issues.
* Fix: Sanitize all input data and escape all output.
* Fix: Use wp_print_inline_script_tag() for inline scripts.
* Fix: Prefix all global variables and class names.
* Fix: Bundle learn section images locally.
* Fix: Remove direct plugin activation and theme switching.
* Fix: Document all external services in readme.txt.
* Update: Tested up to WordPress 6.9.

= 0.0.1 =
* Initial release.
