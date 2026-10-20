<?php
/**
 * Escaping for prebuilt attribute strings in block views.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Escape a string of HTML attributes for printing inside a start tag.
 *
 * Block views build their attributes as one string (`get_block_wrapper_attributes()`,
 * `wp_interactivity_data_wp_context()`, …) and print it inside a tag. Those
 * strings used to go through `wp_kses_data()`, but KSES filters HTML, not
 * attributes: a bare attribute list has no `<`, so it is read as text. Up to
 * WordPress 7.1 that text came back unchanged. From 7.2, KSES runs on the HTML
 * API and normalizes text, so `&quot;` and `&#039;` come back as real quotes.
 * Printed inside a tag, those quotes end the attribute value early, and JSON
 * data (`data-responsive-videos`, `data-wp-context`), quoted font names and
 * `url("…")` backgrounds broke the markup.
 *
 * This reads the string as the attributes of a tag with the HTML API and
 * prints each attribute again, in its original order, with its value escaped
 * the way `WP_HTML_Tag_Processor::set_attribute()` escapes it: URL attributes
 * through `esc_url()`, which drops unsafe schemes such as `javascript:`, and
 * every other value with its special characters encoded. Values round-trip
 * exactly. Anything that is not an attribute is dropped.
 *
 * @since 1.0.12
 * @param string $attributes Attributes as they would appear inside a start tag.
 * @return string The attributes, safe to print inside a start tag.
 */
function spectra_blocks_esc_attributes( $attributes ) {
	if ( ! is_string( $attributes ) || '' === trim( $attributes ) ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( '<div ' . $attributes . '>' );

	if ( ! $processor->next_tag() ) {
		return '';
	}

	$names = $processor->get_attribute_names_with_prefix( '' );

	if ( empty( $names ) ) {
		return '';
	}

	$escaped = array();

	foreach ( $names as $name ) {
		$value = $processor->get_attribute( $name );

		// A boolean attribute such as `hidden` has no value to escape.
		if ( true === $value ) {
			$escaped[] = $name;
			continue;
		}

		$value = (string) $value;

		$escaped_value = in_array( $name, wp_kses_uri_attributes(), true )
			? esc_url( $value )
			: strtr(
				$value,
				array(
					'<' => '&lt;',
					'>' => '&gt;',
					'&' => '&amp;',
					'"' => '&quot;',
					"'" => '&apos;',
				)
			);

		// `esc_url()` returns an empty string for a URL it rejects: drop the attribute, as core does.
		if ( '' === $escaped_value && '' !== $value ) {
			continue;
		}

		$escaped[] = $name . '="' . $escaped_value . '"';
	}

	return implode( ' ', $escaped );
}
