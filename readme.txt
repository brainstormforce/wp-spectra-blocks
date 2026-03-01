=== Spectra Blocks ===
Contributors: brainstormforce
Tags: gutenberg, blocks, page builder, container, accordion
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.0.1
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

* Block source files are located in the `src/` directory (excluded from the distribution zip for size).
* Admin dashboard source files are located in `admin/assets/src/` (also excluded from distribution).
* The compiled, production-ready assets are in `build/` and `admin/assets/build/` respectively.
* Source code is available on GitHub: [https://github.com/brainstormforce/spectra-blocks](https://github.com/brainstormforce/spectra-blocks)

To build from source:

1. `npm install && npm run build` — compiles block JavaScript and CSS.
2. `cd admin && npm install && npm run build` — compiles the admin dashboard React app.

== External Services ==

This plugin connects to the following third-party services under certain conditions:

= Google Maps API =
The Google Map block uses the Google Maps JavaScript API to render interactive maps. An API key must be configured by the user before any requests are made. No data is sent without a key being configured.
* Service URL: `https://maps.googleapis.com/`
* [Terms of Service](https://developers.google.com/maps/terms)
* [Privacy Policy](https://policies.google.com/privacy)

= Google Fonts =
The admin dashboard settings page loads the Inter font from Google Fonts for the settings interface typography.
* Service URL: `https://fonts.googleapis.com/`
* [Privacy Policy](https://policies.google.com/privacy)

= ZipWP API =
When the AI features are enabled and authorized, the plugin communicates with the ZipWP API for AI-powered content generation and template library features. User site URL and content prompts are sent to generate suggestions.
* Service URL: `https://api.zipwp.com/`
* [Terms of Service](https://zipwp.com/terms/)
* [Privacy Policy](https://zipwp.com/privacy-policy/)

= Starter Templates Credit Server =
When AI features are enabled, the plugin checks the credit balance for AI content generation features.
* Service URL: `https://credits.startertemplates.com/`
* [Privacy Policy](https://startertemplates.com/privacy-policy/)

= Brainstorm Force Store =
When the "Get Spectra Pro" upsell is displayed, the plugin may fetch pricing and license information from the Brainstorm Force store.
* Service URL: `https://store.brainstormforce.com/`
* [Privacy Policy](https://www.brainstormforce.com/privacy-policy/)

= ipify =
When AI features are enabled, the bundled Zip AI library may detect the user's IP address for authorization purposes.
* Service URL: `https://api.ipify.org/`
* [Privacy Policy](https://www.ipify.org/)

= WordPress.org =
When installing recommended plugins or themes from the admin dashboard, the plugin uses the standard WordPress.org API to download packages.
* Service URL: `https://downloads.wordpress.org/`
* [Privacy Policy](https://wordpress.org/about/privacy/)

= WebsiteDemos.net =
When the Starter Templates library is enabled, template data may be synced from the WebsiteDemos.net service.
* Service URL: `https://websitedemos.net/`
* [Privacy Policy](https://startertemplates.com/privacy-policy/)

== Changelog ==

= 0.0.1 =
* Initial release.
