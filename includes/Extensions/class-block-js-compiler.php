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
 * is harvested from the `render_block` pass, so it travels with the block
 * wherever it renders — template part, pattern or post content alike — and can
 * never go stale relative to the content.
 *
 * Because rendering carries a snippet beyond its own post (archive and search
 * excerpts call `render_block()` too), a user without `unfiltered_html` cannot
 * INTRODUCE the attribute through the post APIs; see strip_untrusted_block_js().
 * That gate sits on `wp_insert_post_data`/`wp_insert_attachment_data`, so a
 * writer that bypasses those — a raw `$wpdb->update()` on `post_content`, as
 * `Abilities\AbstractAbility` does — bypasses it too.
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
	 * The block attribute holding a block's own inline JS.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const JS_ATTRIBUTE = 'spectraCustomJS';

	/**
	 * `spectraCustomJS` of every block rendered this request, in render order.
	 *
	 * @since x.x.x
	 * @var string[]
	 */
	private $rendered_js = array();

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		// Arbitrary inline JS is an `unfiltered_html` capability. Stop anyone without
		// it from INTRODUCING the attribute, so an untrusted role can never persist a
		// snippet in the first place — kses is no defence here: it splits a block
		// delimiter, runs kses over the inner JSON and RE-WRAPS the comment
		// (`wp_kses_split2()`), so `<!-- wp:paragraph {"spectraCustomJS":"…"} -->`
		// survives intact for a Contributor or Author.
		//
		// Attachments take their OWN filter (`post.php`), and a media description is
		// block content the front end renders like any other, so both are hooked.
		add_filter( 'wp_insert_post_data', array( $this, 'strip_untrusted_block_js' ), 10, 2 );
		add_filter( 'wp_insert_attachment_data', array( $this, 'strip_untrusted_block_js' ), 10, 2 );

		// Harvest during RENDER, not from post_content: a block's JS must travel
		// with the block wherever it renders. On an FSE site the header and footer
		// are `wp_template_part` posts the editor opens on their own, so their
		// blocks never appear in the queried post's content — parsing that content
		// alone left every pattern's JS stored but never printed.
		add_filter( 'render_block', array( $this, 'harvest_block_js' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'output_block_js' ), 20 );
	}

	/**
	 * Stop a user without `unfiltered_html` from INTRODUCING block JS.
	 *
	 * Gating at save rather than at render is what makes the render-time harvest
	 * safe: `render_block` has no author context, and a snippet reaches far beyond
	 * its own post — `excerpt_remove_blocks()` calls `render_block()` while building
	 * archive and search excerpts, so a snippet on a published post would otherwise
	 * execute in an administrator's session on the blog index. Core already denies
	 * `unfiltered_html` to everyone but super admins on multisite, so this one check
	 * covers both.
	 *
	 * Introducing, not carrying. Only a snippet that is NOT already stored on this
	 * post is dropped, because most writes that reach here are not about the content
	 * at all: `wp_update_post()` re-reads the whole row first, so Quick Edit, Bulk
	 * Edit, trash/untrash, autosave and revision restore all push existing content
	 * back through this filter. Deleting a snippet the user never touched would be
	 * unrecoverable data loss — restoring the revision re-runs the strip — and it
	 * would hit hardest exactly where the cap is scarcest: every non-super-admin on
	 * multisite, and everyone at all under `DISALLOW_UNFILTERED_HTML`.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $data    Sanitized post data, slashed.
	 * @param array<string, mixed> $postarr Raw post array (for the id on updates).
	 * @return array<string, mixed> Post data, slashed.
	 */
	public function strip_untrusted_block_js( $data, $postarr = array() ) {
		if ( ! is_array( $data ) || ! isset( $data['post_content'] ) || ! is_string( $data['post_content'] ) ) {
			return $data;
		}

		// WP-CLI and cron are the server acting on its own, never an untrusted
		// browser session. Checked explicitly rather than via "no current user",
		// which would also exempt a front-end plugin inserting visitor content
		// from a `nopriv` handler.
		if ( current_user_can( 'unfiltered_html' ) || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return $data;
		}

		// Matched on the DECODED block, never as a substring of the raw text. JSON
		// permits `\uXXXX` inside an object KEY, so an escaped spelling of the
		// attribute name carries no literal to find while `parse_blocks()` (which
		// `json_decode`s attributes) yields the real key.
		//
		// Core happens to normalise that today — `pre_kses` runs
		// `wp_pre_kses_block_attributes()` → `filter_block_content()`, which
		// re-serializes every block through `wp_json_encode()` — but that only runs
		// for users who get kses at all, and this gate must not depend on an
		// incidental round trip elsewhere to see its own attribute.
		$content = wp_unslash( $data['post_content'] );
		if ( ! has_blocks( $content ) ) {
			return $data;
		}

		$blocks  = parse_blocks( $content );
		$dropped = self::drop_new_block_js( $blocks, self::stored_block_js( $postarr ) );
		if ( 0 === $dropped ) {
			// Nothing was introduced, so nothing is rewritten: a parse/serialize round
			// trip never touches content this filter had no reason to change.
			return $data;
		}

		$data['post_content'] = wp_slash( serialize_blocks( $blocks ) );

		return $data;
	}

	/**
	 * The JS snippets already stored on the post being written, if any.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $postarr Raw post array.
	 * @return string[] Snippets, as stored.
	 */
	private static function stored_block_js( $postarr ): array {
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
		self::collect_block_js( parse_blocks( $post->post_content ), $found );

		return $found;
	}

	/**
	 * Recursively gather every JS snippet in a parsed block tree.
	 *
	 * @since x.x.x
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks.
	 * @param string[]                 $found  Collected snippets, by reference.
	 * @return void
	 */
	private static function collect_block_js( array $blocks, array &$found ): void {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['attrs'][ self::JS_ATTRIBUTE ] ) && is_string( $block['attrs'][ self::JS_ATTRIBUTE ] ) ) {
				$found[] = $block['attrs'][ self::JS_ATTRIBUTE ];
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::collect_block_js( $block['innerBlocks'], $found );
			}
		}
	}

	/**
	 * Remove JS attributes that are not already stored, in place.
	 *
	 * @since x.x.x
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks, edited by reference.
	 * @param string[]                 $stored Snippets already on the post.
	 * @return int How many were removed.
	 */
	private static function drop_new_block_js( array &$blocks, array $stored ): int {
		$dropped = 0;

		foreach ( $blocks as &$block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
				$js = $block['attrs'][ self::JS_ATTRIBUTE ] ?? null;
				if ( null !== $js && ( ! is_string( $js ) || ! in_array( $js, $stored, true ) ) ) {
					unset( $block['attrs'][ self::JS_ATTRIBUTE ] );
					++$dropped;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$dropped += self::drop_new_block_js( $block['innerBlocks'], $stored );
			}
		}

		unset( $block );

		return $dropped;
	}

	/**
	 * Record a block's `spectraCustomJS` as it renders. Pass-through filter — the
	 * markup is never touched.
	 *
	 * Rendering is the one pass that sees every block actually on the page:
	 * post content, template parts, patterns, synced blocks alike. Duplicate
	 * snippets collapse, so a part rendered twice still runs once.
	 *
	 * @since x.x.x
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 * @return string The unmodified block HTML.
	 */
	public function harvest_block_js( $block_content, $block ) {
		$attrs  = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$raw_js = $attrs[ self::JS_ATTRIBUTE ] ?? '';
		$js     = is_string( $raw_js ) ? trim( $raw_js ) : '';
		if ( '' === $js ) {
			return $block_content;
		}

		// NOT entity-decoded. `serialize_block_attributes()` escapes `<`, `>` and `&`
		// as JSON unicode escapes, which `parse_blocks()` has already decoded by
		// here — so a decode pass would only undo kses's `&` => `&amp;` normalisation
		// and hand back live syntax.

		// Resolve the `_current_block_` placeholder to this block's scope class so
		// user JS can target its own block, e.g.
		// document.querySelector('._current_block_'). The matching class is stamped
		// on the block wrapper by Spectra Pro's
		// GlobalStyles::inject_block_custom_code().
		$raw_id = $attrs['spectraBCEId'] ?? '';
		$bce_id = is_string( $raw_id ) ? sanitize_html_class( $raw_id ) : '';
		if ( '' !== $bce_id ) {
			$js = str_replace( self::BLOCK_PLACEHOLDER, 'spectra-bce-' . $bce_id, $js );
		}

		if ( ! in_array( $js, $this->rendered_js, true ) ) {
			$this->rendered_js[] = $js;
		}
		return $block_content;
	}

	/**
	 * Print every snippet harvested this request as ONE inline script in the
	 * footer. The block attribute is the source of truth — read fresh from the
	 * render pass each request (no meta cache).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_block_js(): void {
		$snippets = $this->rendered_js;
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
			array( 'id' => 'spectra-block-js' )
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
}
