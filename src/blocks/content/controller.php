<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Content
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the text content from attributes with empty string fallback.
$text = $attributes['text'] ?? '';

// Early return if no text content is provided.
if ( empty( $text ) ) {
	return '';
}

$valid_tag_names = array(
	// Core text containers.
	'p',
	'span',
	'div',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	// Semantic text elements.
	'blockquote',
	'address',
	'cite',
	'time',
	'label',
	'figcaption',
	'caption',
	'legend',
	'dt',
	'dd',
	// Inline semantic elements.
	'strong',
	'em',
	'small',
	'mark',
	'del',
	'ins',
	'sub',
	'sup',
	'abbr',
	'code',
	'pre',
	'kbd',
	'samp',
	'var',
	'output',
	'q',
	's',
	'dfn',
	'bdi',
	'bdo',
	'summary',
	'li',
	// Table cells — text-only cells with no nested elements should
	// render through content (RichText editor) instead of being
	// silently demoted to <p> and breaking the table structure.
	'td',
	'th',
	// List/table containers — render the DECLARED tag (2026-07-02).
	// Demoting these to <p> destroyed native HTML semantics: the browser
	// hoisted <li> children out of the invalid <p> parent (unmarked,
	// unindented stacked text — measured live), and table display/colspan
	// semantics died the same way. `wp_kses_post( $text )` already admits
	// li/tr/td/th markup, and the view template is tag-generic, so the
	// whitelist was the only blocker. Importers can now emit real,
	// editable content blocks for lists/tables instead of raw-HTML leaves.
	'ul',
	'ol',
	'table',
	// Text anchors — render the DECLARED tag (2026-07-03). The importer's
	// pure-text `<a>` leaves (no button class, zero block anatomy) ride
	// content with tagName 'a'; the link payload (href/target/rel) reaches
	// the wrapper via the htmlAttributes pipe (class-block-attributes.php),
	// which already forwards every non-event attribute. Demoting to <p>
	// killed the link outright. core/html leaves are banned (the editor
	// renders them as sandboxed preview iframes — white boxes breaking the
	// canvas), so content is the only valid carrier.
	'a',
);
$tag_name        = ( ! empty( $attributes['tagName'] ) && in_array( $attributes['tagName'], $valid_tag_names, true ) ) ? $attributes['tagName'] : 'p';

$anchor        = $attributes['anchor'] ?? '';
$drop_cap      = $attributes['dropCap'] ?? false;
$align         = $attributes['style']['typography']['textAlign'] ?? '';
$is_root_block = $attributes['isRootBlock'] ?? true;


// Determine if we need a span wrapper.
$needs_span_wrapper = 'span' === $tag_name && $is_root_block;

// Check if the drop cap is disabled.
$has_drop_cap_disabled = in_array( $align, array( 'center', 'right' ), true ) || 'span' === $tag_name;
$drop_cap_class        = ( ! $has_drop_cap_disabled && $drop_cap ) ? 'has-drop-cap' : '';

// Check for various color settings.
$has_link_color       = ! empty( $attributes['style']['elements']['link']['color']['text'] ?? '' );
$has_background_color = ! empty( $attributes['backgroundColor'] ?? '' );
$has_text_color       = ! empty( $attributes['textColor'] ?? '' );

// Get link hover color. Note: core bug prevents :focus styles from applying when using Tab key.
$link_hover_color = $attributes['style']['elements']['link'][':hover']['color']['text'] ?? '';

// Generate inline CSS style for the link hover color.
if ( $link_hover_color ) {
	// Generate inline CSS style for the hover color.
	$styles           = wp_style_engine_get_styles(
		array(
			'color' => array( 'text' => $link_hover_color ),
		),
		array( 'context' => 'block-supports' )
	);
	$link_hover_color = $styles['declarations']['color'] ?? $link_hover_color;
}

// Style and class configurations.
//
// Normal-state `textColor` / `backgroundColor` / `backgroundGradient` ARE
// listed here: WP core `supports.color` only paints values that are real
// palette slugs (via its `has-<slug>-color` / `has-<slug>-background-color`
// rules). Saved/imported content routinely stores a CUSTOM value (hex, rgb,
// etc.) in these attributes — e.g. `"textColor":"#ffffff"` — which core turns
// into a bogus class like `has-ffffff-color` that no rule backs, so the colour
// silently drops. The Spectra CSS-var helper (`spectra-text-color` +
// `--spectra-text-color`) paints those custom values; for real palette slugs
// the resolved value is not a valid CSS colour and is simply ignored, letting
// core's `has-<slug>-color` win — so both cases render correctly. The
// `should_emit_helper_class()` gate (GIT-106) still suppresses the helper when
// a GBS utility token already owns the axis, so there is no duplication.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),

	// Link hover color as a custom variable for fixing core bug with focus.
	array(
		'key'        => 'linkHoverColor',
		'css_var'    => '--spectra-link-hover-color',
		'class_name' => 'spectra-link-hover-color',
		'value'      => $link_hover_color,
	),

);

// Additional classes.
$additional_classes = array(
	$drop_cap_class,
	$has_background_color ? 'has-background' : '',
	$has_text_color ? 'has-text-color' : '',
	$has_link_color ? 'has-link-color' : '',
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// return the view.
return 'file:./view.php';
