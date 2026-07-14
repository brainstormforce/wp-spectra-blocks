<?php
/**
 * Block JS Renderer
 *
 * Renders every per-block `spectraCustomJS` attribute on the current page as a
 * single inline script in wp_footer — instead of N separate inline <script>
 * tags scattered through block HTML.
 *
 * The `spectraCustomJS` block attribute (in post_content) is the single source
 * of truth: there is no compiled post-meta cache and no sitewide option. The JS
 * is collected by parsing the post's blocks at render time, so it can never go
 * stale relative to the content.
 *
 * Lives in the free plugin so imported/authored block JS renders even without
 * Spectra Pro. NOTE: the `_current_block_` placeholder resolves to a block's
 * `spectra-bce-{id}` scope class, but that class is stamped onto the element by
 * Spectra Pro's GlobalStyles (`inject_block_custom_code`) — so on a free-only
 * site the placeholder resolves in the JS but no element carries the class.
 * Imported JS targets its own authored classes, so it is unaffected.
 *
 * @package Spectra\Extensions
 * @since   1.0.0
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;

/**
 * BlockJsCompiler class.
 *
 * @since 1.0.0
 */
class BlockJsCompiler {

	use Singleton;

	/**
	 * Placeholder token a user can write in their block JS to reference the
	 * block's own scope class. Resolved at render time to `spectra-bce-{id}`,
	 * so e.g. `document.querySelector('._current_block_')` targets this block.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BLOCK_PLACEHOLDER = '_current_block_';

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_footer', array( $this, 'output_block_js' ), 20 );
	}

	/**
	 * Collect every block's `spectraCustomJS` on the current singular post and
	 * print them as one inline script in the footer. The block attribute is the
	 * source of truth — parsed fresh each request (no meta cache).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_block_js(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post( get_queried_object_id() );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$snippets = $this->collect_block_js( parse_blocks( $post->post_content ) );
		if ( empty( $snippets ) ) {
			return;
		}

		$compiled = implode(
			"\n",
			array_map(
				static function ( string $js ): string {
					return '(function(){' . $js . '})();';
				},
				$snippets
			)
		);

		wp_print_inline_script_tag(
			self::escape_script_close( $compiled ),
			array( 'id' => 'spectra-block-js-' . $post->ID )
		);
	}

	/**
	 * Neutralise any `</script>` inside inline JS (string literal, comment,
	 * etc.) so it can't terminate the tag early. Case-insensitive — the HTML
	 * parser does not require the closing tag to be lowercase — and, unlike
	 * preg_replace, str_ireplace has no replacement-string backslash ambiguity.
	 *
	 * @since 1.0.0
	 *
	 * @param string $js Inline JS.
	 * @return string JS with `</script` rewritten to `<\/script`.
	 */
	private static function escape_script_close( string $js ): string {
		return str_ireplace( '</script', '<\/script', $js );
	}

	/**
	 * Recursively walk blocks and collect non-empty spectraCustomJS values.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks (parse_blocks() output).
	 * @return string[] JS snippets (HTML-entity-decoded, ready to wrap in IIFE).
	 */
	private function collect_block_js( array $blocks ): array {
		$snippets = array();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			$raw_js = $attrs['spectraCustomJS'] ?? '';
			$js     = is_string( $raw_js ) ? trim( $raw_js ) : '';

			if ( '' !== $js ) {
				// WordPress may entity-encode `>` → `&gt;` during serialisation.
				$js = html_entity_decode( $js, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				// Resolve the `_current_block_` placeholder to this block's scope
				// class so user JS can target its own block, e.g.
				// document.querySelector('._current_block_'). The matching class
				// is stamped on the block wrapper by Spectra Pro's
				// GlobalStyles::inject_block_custom_code().
				$raw_id = $attrs['spectraBCEId'] ?? '';
				$bce_id = is_string( $raw_id ) ? sanitize_html_class( $raw_id ) : '';
				if ( '' !== $bce_id ) {
					$js = str_replace( self::BLOCK_PLACEHOLDER, 'spectra-bce-' . $bce_id, $js );
				}

				$snippets[] = $js;
			}

			$inner_blocks = $block['innerBlocks'] ?? null;
			if ( ! empty( $inner_blocks ) && is_array( $inner_blocks ) ) {
				$snippets = array_merge( $snippets, $this->collect_block_js( $inner_blocks ) );
			}
		}//end foreach

		return $snippets;
	}
}
