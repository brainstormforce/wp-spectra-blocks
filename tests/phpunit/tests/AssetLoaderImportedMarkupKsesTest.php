<?php
/**
 * The imported-markup KSES allowance must be SCOPED to the content block's own call,
 * and must cover every element an imported page legitimately renders.
 *
 * WP's `post` KSES context strips `<canvas>`, which kills a JS-drawn canvas an
 * imported page relies on. The allowance therefore has to exist — but it used to
 * be registered on `wp_kses_allowed_html` for the whole request, so every other
 * `post`-context `wp_kses_post()` on that page (comment text, widget text, any
 * third party's) silently got a wider allow-list too.
 *
 * Both halves are asserted here. Only checking that canvas survives would pass
 * just as well with the old request-wide filter — the leak is the point.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests;

use SpectraBlocks\AssetLoader;
use WP_UnitTestCase;

/**
 * AssetLoaderImportedMarkupKsesTest test case.
 *
 * @since x.x.x
 */
class AssetLoaderImportedMarkupKsesTest extends WP_UnitTestCase {

	/**
	 * Markup the content block would hand to KSES.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const CANVAS_HTML = '<p>chart</p><canvas id="c" width="300" height="150"></canvas>';

	/**
	 * Put a zip-built page on the singular frontend query the filter gates on.
	 *
	 * @since x.x.x
	 *
	 * @param bool $imported Whether to set the importer marker.
	 * @return int Post ID.
	 */
	private function go_to_page( bool $imported ): int {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		if ( $imported ) {
			update_post_meta( $post_id, AssetLoader::IMPORTED_MARKER_META_KEY, true );
		}
		$this->go_to( get_permalink( $post_id ) );

		return $post_id;
	}

	/**
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_canvas_survives_only_inside_the_wrapped_call(): void {
		$this->go_to_page( true );

		$wrapped = AssetLoader::with_imported_markup_allowed( self::CANVAS_HTML );
		$this->assertStringContainsString( '<canvas', $wrapped, 'the content block must keep its canvas' );
		$this->assertStringContainsString( 'width="300"', $wrapped, 'canvas geometry attributes must survive' );

		// THE SCOPING GUARANTEE: an unrelated wp_kses_post() on the very same
		// request — a comment, a widget, another plugin — must be unaffected.
		// This is the assertion the old request-wide filter would fail.
		$this->assertStringNotContainsString(
			'<canvas',
			wp_kses_post( self::CANVAS_HTML ),
			'the allowance leaked outside the content block'
		);
	}

	/**
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_canvas_is_stripped_on_a_page_the_importer_did_not_write(): void {
		$this->go_to_page( false );

		$this->assertStringNotContainsString(
			'<canvas',
			AssetLoader::with_imported_markup_allowed( self::CANVAS_HTML ),
			'a non-imported page must keep the WP-standard allow-list'
		);
	}

	/**
	 * The filter must not stay registered after the wrapped call returns, even
	 * across repeated use — a leaked `add_filter` is exactly the failure mode
	 * the wrapper exists to prevent, and it would only show on the second call.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_the_filter_is_removed_again_after_each_call(): void {
		$this->go_to_page( true );

		AssetLoader::with_imported_markup_allowed( self::CANVAS_HTML );
		AssetLoader::with_imported_markup_allowed( self::CANVAS_HTML );

		$this->assertFalse(
			has_filter( 'wp_kses_allowed_html', array( AssetLoader::class, 'allow_imported_markup_on_zip_built_pages' ) ),
			'the imported-markup filter outlived the call that added it'
		);
		$this->assertStringNotContainsString( '<canvas', wp_kses_post( self::CANVAS_HTML ) );
	}

	/**
	 * Inline SVG icons on an imported page.
	 *
	 * WP's `post` context has no `svg`/`path`/`rect`, so a `spectra/content`
	 * block whose text carries an inline icon rendered the surrounding `<span>`
	 * and a bare orphaned `<title>` — a filled icon box with no glyph. Measured
	 * live 2026-09-08 on the Saffron & Stone home: `wp_kses_post()` on the stored
	 * text returned `<span class="icon-box"><title>…</title></span>`, byte-equal
	 * to what the page served.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const SVG_HTML = '<span class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-label="Cal" role="img" data-era-art="1" class="gs-0f26f6-svg"><title>Reservation calendar</title><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4"></path></svg></span>';

	/**
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_inline_svg_survives_on_an_imported_page(): void {
		$this->go_to_page( true );

		$out = AssetLoader::with_imported_markup_allowed( self::SVG_HTML );

		$this->assertStringContainsString( '<svg', $out, 'the icon element itself must survive' );
		$this->assertStringContainsString( '<path', $out, 'shape children must survive' );
		$this->assertStringContainsString( '<rect', $out, 'shape children must survive' );
		// Case-sensitive: KSES lowercases attribute names and an unscaled icon is
		// the tell that the camelCase restore did not run.
		$this->assertStringContainsString( 'viewBox="0 0 24 24"', $out, 'viewBox must keep its case' );
		$this->assertStringContainsString( 'data-era-art="1"', $out, 'data-* must survive' );
		$this->assertStringContainsString( 'stroke-linecap="round"', $out, 'presentation attributes must survive' );
	}

	/**
	 * The subset stops at inert paint. `sanitize_svg()` can afford `use`/
	 * `style`/`animate` because it validates and post-sweeps; this lane does
	 * neither, so they must not ride in with the shapes, and no shape may carry
	 * a reference out of the document.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_non_inert_svg_elements_are_still_stripped(): void {
		$this->go_to_page( true );

		$out = AssetLoader::with_imported_markup_allowed(
			'<span><svg viewBox="0 0 10 10">'
			. '<use href="#x"></use><rect x="0" href="/q" xlink:href="/r"></rect>'
			. '<style>*{fill:red}</style><animate attributeName="href" to="/z"></animate>'
			. '<script>alert(1)</script></svg></span>'
		);

		foreach ( array( '<use', '<style', '<animate', '<script' ) as $tag ) {
			$this->assertStringNotContainsString( $tag, $out, $tag . ' is not inert paint' );
		}
		$this->assertStringContainsString( '<rect', $out, 'shapes survive' );
		$this->assertStringContainsString( 'x="0"', $out, 'a shape keeps its geometry' );
		// The linking attributes are unset from every inherited tag, so a shape
		// cannot become a fetch. `<a href>` itself is NOT this lane's to strip —
		// it is core `post` HTML on every page, protocol-filtered by KSES.
		$this->assertStringNotContainsString( 'href="/q"', $out, 'href is unset from inherited SVG tags' );
		$this->assertStringNotContainsString( 'xlink:href', $out, 'xlink:href is unset from inherited SVG tags' );
	}

	/**
	 * The allowance is gated on the page being zip-built — an ordinary post must
	 * keep WordPress' own list.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_inline_svg_is_stripped_on_a_normal_page(): void {
		$this->go_to_page( false );

		$out = AssetLoader::with_imported_markup_allowed( self::SVG_HTML );

		$this->assertStringNotContainsString( '<svg', $out, 'a non-imported page keeps the core list' );
	}
}
