<?php
/**
 * HTML Attributes Extension.
 *
 * Applies user-authored htmlAttributes to block wrappers on the frontend
 * using WP_HTML_Tag_Processor. Runs after every block's own render callback
 * so it works uniformly across all Spectra blocks without per-block changes.
 *
 * Security model:
 *   - on* event handler attributes are silently dropped.
 *   - class / id / style are skipped (managed by block supports).
 *   - Attribute names must match [a-z][a-z0-9\-:]* (case-insensitive).
 *   - URL-type attributes (href, cite, src, …) are run through esc_url().
 *   - All other values are run through sanitize_text_field().
 *   - When target="_blank" is present, noopener noreferrer is appended to rel.
 *
 * @package SpectraBlocks\Extensions
 * @since   1.0.15
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * HtmlAttributes extension class.
 *
 * @since 1.0.15
 */
class HtmlAttributes {

	use Singleton;

	/**
	 * The block attribute holding a block's custom HTML attributes.
	 *
	 * @since 1.0.15
	 *
	 * @var string
	 */
	private const ATTRS_ATTRIBUTE = 'htmlAttributes';

	/**
	 * Attributes that must never be applied, regardless of user input.
	 *
	 * Class, id, and style have dedicated paths in block supports; injecting
	 * them here would conflict with those mechanisms. srcdoc can carry a full
	 * HTML document (script included) into an iframe, so it is refused outright.
	 *
	 * @since 1.0.15
	 *
	 * @var array<string>
	 */
	private const BLOCKED_ATTRS = array( 'class', 'id', 'style', 'srcdoc' );

	/**
	 * Attributes whose values must be treated as URLs.
	 *
	 * @since 1.0.15
	 *
	 * @var array<string>
	 */
	private const URL_ATTRS = array( 'href', 'cite', 'src', 'action', 'formaction', 'poster' );

	/**
	 * HTML boolean attributes. Valid with no value (e.g. a bare `download` on an
	 * `<a>`, `reversed` on an `<ol>`, `open` on a `<dialog>`), so an empty value
	 * must NOT be treated as an unfinished repeater row and dropped. See #568.
	 *
	 * @since 1.0.15
	 *
	 * @var array<string>
	 */
	private const BOOLEAN_ATTRS = array(
		'download',
		'reversed',
		'open',
		'required',
		'hidden',
		'inert',
		'autofocus',
		'readonly',
		'disabled',
		'multiple',
		'selected',
		'checked',
		'novalidate',
		'formnovalidate',
		'ismap',
		'default',
		'nomodule',
		'allowfullscreen',
	);

	/**
	 * Block name prefixes that this extension processes.
	 *
	 * @since 1.0.15
	 *
	 * @var array<string>
	 */
	private const ALLOWED_PREFIXES = array( 'spectra/', 'spectra-pro/' );

	/**
	 * HTML tag names allowed as replacements via the htmlTag attribute.
	 *
	 * Restricted to semantic flow/sectioning elements and tags that have
	 * contextual attribute fields — void elements and inline-only tags excluded.
	 *
	 * @since 1.0.15
	 *
	 * @var array<string>
	 */
	private const ALLOWED_TAGS = array(
		'div',
		'section',
		'article',
		'aside',
		'main',
		'header',
		'footer',
		'nav',
		'figure',
		'figcaption',
		'address',
		'time',
		'blockquote',
		'details',
		'summary',
		'dialog',
		'hgroup',
		'a',
		'span',
	);

	/**
	 * Registers the render_block filter.
	 *
	 * @since 1.0.15
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'apply_html_attributes' ), 10, 2 );

		// Keep a Button's native link (href/target/rel) authoritative over the
		// custom-attributes repeater. Runs on render_block_data so the stripped
		// names never reach apply_html_attributes (or any later render filter).
		// See reserve_native_link_attributes() and #568 (IF-02).
		add_filter( 'render_block_data', array( $this, 'reserve_native_link_attributes' ) );

		// Custom HTML attributes are an `unfiltered_html` capability: a role without
		// it must never INTRODUCE them, so an untrusted user can never persist an
		// attribute in the first place. kses is no defence — it splits a block
		// delimiter, filters the inner JSON and re-wraps the comment, so
		// `<!-- wp:paragraph {"htmlAttributes":[…]} -->` survives intact for a
		// Contributor or Author. Gating at save (not render) is what keeps the
		// render-time filter safe: `render_block` has no author context and a block
		// renders far beyond its own post (archive/search excerpts call it too).
		//
		// Attachments take their own filter, and a media description is block content
		// the front end renders like any other, so both are hooked.
		add_filter( 'wp_insert_post_data', array( $this, 'strip_untrusted_html_attributes' ), 10, 2 );
		add_filter( 'wp_insert_attachment_data', array( $this, 'strip_untrusted_html_attributes' ), 10, 2 );
	}

	/**
	 * Keep a Button's native link settings authoritative over the custom repeater.
	 *
	 * The spectra/button block renders href/target/rel from its own link controls.
	 * If a user also authors those names in the custom htmlAttributes repeater, the render-time
	 * injection would silently overwrite the native link. Drop those names from the
	 * block's htmlAttributes here — before any render filter runs — so the block's
	 * own link always wins. Every other authored attribute (aria-label, download,
	 * data-*, …) is untouched and still applies. See #568 (IF-02).
	 *
	 * @since 1.0.15
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return array<string, mixed> Block with native-owned link names removed from htmlAttributes.
	 */
	public function reserve_native_link_attributes( $block ) {
		if ( ! is_array( $block ) || ( $block['blockName'] ?? '' ) !== 'spectra/button' ) {
			return $block;
		}

		$attrs = $block['attrs'] ?? null;
		if ( ! is_array( $attrs ) || empty( $attrs['linkURL'] ) ) {
			return $block;
		}

		$html_attrs = $attrs['htmlAttributes'] ?? null;
		if ( ! is_array( $html_attrs ) || array() === $html_attrs ) {
			return $block;
		}

		$owned    = array( 'href', 'target', 'rel' );
		$filtered = array_values(
			array_filter(
				$html_attrs,
				static function ( $entry ) use ( $owned ) {
					$name = is_array( $entry ) ? strtolower( trim( (string) ( $entry['attribute'] ?? '' ) ) ) : '';
					return ! in_array( $name, $owned, true );
				}
			)
		);

		$attrs['htmlAttributes'] = $filtered;
		$block['attrs']          = $attrs;

		return $block;
	}

	/**
	 * Stop a user without `unfiltered_html` from INTRODUCING custom HTML attributes.
	 *
	 * Introducing, not carrying: only an `htmlAttributes` value that is NOT already
	 * stored on this post is dropped. Most writes that reach here re-read the whole
	 * row first (Quick Edit, Bulk Edit, trash/untrash, autosave, revision restore),
	 * so dropping attributes the user never touched would be unrecoverable data loss
	 * and would hit hardest where the cap is scarcest — every non-super-admin on
	 * multisite, and everyone under `DISALLOW_UNFILTERED_HTML`.
	 *
	 * @since 1.0.15
	 *
	 * @param array<string, mixed> $data    Sanitized post data, slashed.
	 * @param array<string, mixed> $postarr Raw post array (for the id on updates).
	 * @return array<string, mixed> Post data, slashed.
	 */
	public function strip_untrusted_html_attributes( $data, $postarr = array() ) {
		if ( ! is_array( $data ) || ! isset( $data['post_content'] ) || ! is_string( $data['post_content'] ) ) {
			return $data;
		}

		// WP-CLI and cron are the server acting on its own, never an untrusted
		// browser session. Checked explicitly rather than via "no current user",
		// which would also exempt a front-end plugin inserting visitor content.
		if ( current_user_can( 'unfiltered_html' ) || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return $data;
		}

		$content = wp_unslash( $data['post_content'] );
		if ( ! has_blocks( $content ) ) {
			return $data;
		}

		$blocks  = parse_blocks( $content );
		$dropped = self::drop_new_html_attributes( $blocks, self::stored_html_attributes( $postarr ) );
		if ( 0 === $dropped ) {
			// Nothing was introduced, so nothing is rewritten: a parse/serialize round
			// trip never touches content this filter had no reason to change.
			return $data;
		}

		$data['post_content'] = wp_slash( serialize_blocks( $blocks ) );

		return $data;
	}

	/**
	 * The htmlAttributes values already stored on the post being written, if any.
	 *
	 * Each entry is the JSON-encoded attribute array, used for exact membership
	 * checks against the incoming blocks.
	 *
	 * @since 1.0.15
	 *
	 * @param array<string, mixed> $postarr Raw post array.
	 * @return string[] Stored htmlAttributes, JSON-encoded.
	 */
	private static function stored_html_attributes( $postarr ): array {
		$raw     = is_array( $postarr ) && isset( $postarr['ID'] ) ? $postarr['ID'] : null;
		$post_id = is_numeric( $raw ) ? (int) $raw : 0;
		if ( $post_id <= 0 ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || ! is_string( $post->post_content ) ) {
			return array();
		}

		$found = array();
		self::collect_html_attributes( parse_blocks( $post->post_content ), $found );

		return $found;
	}

	/**
	 * Recursively gather every htmlAttributes value in a parsed block tree.
	 *
	 * @since 1.0.15
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks.
	 * @param string[]                 $found  Collected values (JSON), by reference.
	 * @return void
	 */
	private static function collect_html_attributes( array $blocks, array &$found ): void {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$value = $block['attrs'][ self::ATTRS_ATTRIBUTE ] ?? null;
			if ( ! empty( $value ) && is_array( $value ) ) {
				$found[] = (string) wp_json_encode( $value );
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::collect_html_attributes( $block['innerBlocks'], $found );
			}
		}
	}

	/**
	 * Remove htmlAttributes values that are not already stored, in place.
	 *
	 * @since 1.0.15
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks, edited by reference.
	 * @param string[]                 $stored Values already on the post (JSON).
	 * @return int How many were removed.
	 */
	private static function drop_new_html_attributes( array &$blocks, array $stored ): int {
		$dropped = 0;

		foreach ( $blocks as &$block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['attrs'][ self::ATTRS_ATTRIBUTE ] ) ) {
				$value = $block['attrs'][ self::ATTRS_ATTRIBUTE ];
				if ( ! empty( $value ) && is_array( $value )
					&& ! in_array( (string) wp_json_encode( $value ), $stored, true )
				) {
					unset( $block['attrs'][ self::ATTRS_ATTRIBUTE ] );
					++$dropped;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$dropped += self::drop_new_html_attributes( $block['innerBlocks'], $stored );
			}
		}

		unset( $block );

		return $dropped;
	}

	/**
	 * Reads htmlAttributes (and htmlTag) from block attrs and applies them to the wrapper.
	 *
	 * Tag replacement (htmlTag) is applied first so attribute injection lands on
	 * the already-renamed element. Both operations are no-ops when the respective
	 * block attribute is absent or empty.
	 *
	 * @since 1.0.15
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data including blockName and attrs.
	 * @return string Modified block HTML.
	 */
	public function apply_html_attributes( $block_content, $block ) {
		if ( ! $this->is_allowed_block( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		/* @var array<string,mixed> $attrs */
		$attrs    = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		$raw      = $attrs['htmlAttributes'] ?? null;
		$html_tag = strtolower( trim( is_string( $attrs['htmlTag'] ?? null ) ? $attrs['htmlTag'] : '' ) );

		$has_attrs = ! empty( $raw ) && is_array( $raw );
		$has_tag   = '' !== $html_tag && in_array( $html_tag, self::ALLOWED_TAGS, true );

		if ( ! $has_attrs && ! $has_tag ) {
			return $block_content;
		}

		// Replace the outermost HTML element before injecting attributes.
		if ( $has_tag ) {
			$block_content = $this->replace_tag( $block_content, $html_tag );
		}

		if ( ! $has_attrs ) {
			return $block_content;
		}

		$attrs = $this->sanitize_attributes( $raw );

		if ( empty( $attrs ) ) {
			return $block_content;
		}

		return $this->inject_attributes( $block_content, $attrs );
	}

	/**
	 * Replaces the outermost HTML element name in block content.
	 *
	 * Uses WP_HTML_Tag_Processor to detect the existing first tag, then performs
	 * targeted string replacements on the opening and closing tags only.
	 * Self-closing and void elements are left untouched (no closing tag to swap).
	 *
	 * @since 1.0.15
	 *
	 * @param string $html    Block HTML.
	 * @param string $new_tag Validated replacement tag name (lowercase).
	 * @return string HTML with the outermost element renamed.
	 */
	private function replace_tag( string $html, string $new_tag ): string {
		if ( '' === $html ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag() ) {
			return $html;
		}

		$old_tag = strtolower( (string) $processor->get_tag() );

		if ( $old_tag === $new_tag ) {
			return $html;
		}

		// Replace the opening tag name (first occurrence, word-boundary safe).
		$html = preg_replace(
			'/(<\s*)' . preg_quote( $old_tag, '/' ) . '(?=\s|>|\/)/i',
			'$1' . $new_tag,
			$html,
			1
		) ?? $html;

		// Replace the closing tag at the end of the block content.
		$html = preg_replace(
			'/<\/' . preg_quote( $old_tag, '/' ) . '>(\s*)$/i',
			'</' . $new_tag . '>$1',
			$html
		) ?? $html;

		return $html;
	}

	/**
	 * Sanitizes the raw htmlAttributes array from block data.
	 *
	 * Accepts the JS-side storage format: array of { attribute, value } objects
	 * (each decoded from JSON as an associative PHP array).
	 *
	 * Also handles the legacy object format { "aria-label": "nav" } stored by
	 * older versions of this extension that used type:object in block.json.
	 *
	 * @since 1.0.15
	 *
	 * @param array $raw Raw htmlAttributes value from block attrs.
	 * @return array<string,string|bool> Map of sanitized attribute name => value.
	 */
	private function sanitize_attributes( array $raw ): array {
		$sanitized = array();
		$target    = '';

		// Convert legacy object format {"attr":"value"} to array format.
		if ( ! empty( $raw ) && array_keys( $raw ) !== range( 0, count( $raw ) - 1 ) ) {
			$converted = array();
			foreach ( $raw as $key => $val ) {
				$converted[] = array(
					'attribute' => $key,
					'value'     => $val,
				);
			}
			$raw = $converted;
		}

		// First pass: collect all valid attrs so we can resolve rel after.
		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$name       = strtolower( trim( (string) ( $entry['attribute'] ?? '' ) ) );
			$value      = (string) ( $entry['value'] ?? '' );
			$is_boolean = in_array( $name, self::BOOLEAN_ATTRS, true );

			if ( '' === $name ) {
				continue;
			}

			// Skip an incomplete custom row: a name typed with no value yet (a
			// repeater row the author has not finished). Compare the trimmed
			// string rather than empty(), so a literal "0" — a real value, e.g.
			// tabindex="0" — is kept. Boolean attributes (download, reversed,
			// open, …) are valid with no value, so they are NOT skipped here.
			if ( '' === trim( $value ) && ! $is_boolean ) {
				continue;
			}

			// Block event handlers and reserved attrs.
			if ( str_starts_with( $name, 'on' ) || in_array( $name, self::BLOCKED_ATTRS, true ) ) {
				continue;
			}

			// Validate name: must be a legal HTML attribute identifier.
			if ( ! preg_match( '/^[a-z][a-z0-9\-:]*$/i', $name ) ) {
				continue;
			}

			// A boolean attribute with no value renders as a bare attribute
			// (e.g. `<a download>`). set_attribute( $name, true ) emits that.
			if ( $is_boolean && '' === trim( $value ) ) {
				$sanitized[ $name ] = true;
				continue;
			}

			// Sanitize value by attribute type.
			if ( in_array( $name, self::URL_ATTRS, true ) ) {
				$value = esc_url( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			$sanitized[ $name ] = $value;

			if ( 'target' === $name ) {
				$target = $value;
			}
		}

		// Auto-append noopener noreferrer to rel when target is _blank.
		if ( '_blank' === $target ) {
			$rel   = (string) ( $sanitized['rel'] ?? '' );
			$parts = array_filter( array_map( 'trim', explode( ' ', $rel ) ) );

			if ( ! in_array( 'noopener', $parts, true ) ) {
				$parts[] = 'noopener';
			}
			if ( ! in_array( 'noreferrer', $parts, true ) ) {
				$parts[] = 'noreferrer';
			}

			$sanitized['rel'] = implode( ' ', $parts );
		}

		return $sanitized;
	}

	/**
	 * Injects sanitized attributes into the first tag of the block HTML.
	 *
	 * Uses WP_HTML_Tag_Processor so no string manipulation touches the markup.
	 *
	 * @since 1.0.15
	 *
	 * @param string                    $html  Block HTML.
	 * @param array<string,string|bool> $attrs Sanitized attribute map.
	 * @return string Modified HTML.
	 */
	private function inject_attributes( string $html, array $attrs ): string {
		if ( '' === $html ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );

		if ( ! $processor->next_tag() ) {
			return $html;
		}

		foreach ( $attrs as $name => $value ) {
			$processor->set_attribute( $name, $value );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Checks whether a block name should be processed by this extension.
	 *
	 * @since 1.0.15
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( string $block_name ): bool {
		if ( '' === $block_name ) {
			return false;
		}

		foreach ( self::ALLOWED_PREFIXES as $prefix ) {
			if ( str_starts_with( $block_name, $prefix ) ) {
				return true;
			}
		}

		return false;
	}
}
