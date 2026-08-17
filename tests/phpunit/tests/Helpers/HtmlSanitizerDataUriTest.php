<?php
/**
 * `data:image/*` sources must survive `HtmlSanitizer::render()` intact — and only there.
 *
 * `wp_kses_bad_protocol()` does not blank a disallowed scheme, it REMOVES it and
 * keeps the rest, so `src="data:image/svg+xml,…"` came back as a relative URL the
 * browser 404s on. The shield swaps those values out before kses and back after.
 *
 * The count in {@see self::URI_COUNT} is deliberately above ten: a `str_replace()`
 * restore processes its pairs sequentially, so placeholder `…/1` is a strict prefix
 * of `…/10` and used to eat it — corrupting images 11+ with the exact broken-image
 * symptom this code exists to fix.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Helpers;

use SpectraBlocks\Helpers\HtmlSanitizer;
use WP_UnitTestCase;

/**
 * HtmlSanitizerDataUriTest test case.
 *
 * @since x.x.x
 */
class HtmlSanitizerDataUriTest extends WP_UnitTestCase {

	/**
	 * More than ten, to cover the placeholder prefix-collision regression.
	 *
	 * @since x.x.x
	 * @var int
	 */
	const URI_COUNT = 12;

	/**
	 * Every one of a dozen data URIs comes back byte-identical.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_all_data_uris_survive_beyond_ten() {
		$expected = array();
		$markup   = '';

		for ( $i = 0; $i < self::URI_COUNT; $i++ ) {
			// Distinct payloads: a shared value would hide a mis-restore.
			$uri        = 'data:image/png;base64,' . base64_encode( "spectra-image-$i" ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- building a data URI fixture.
			$expected[] = $uri;
			$markup    .= '<img src="' . $uri . '" alt="img ' . $i . '" />';
		}

		$rendered = HtmlSanitizer::render( '<div class="wp-block-spectra-container">' . $markup . '</div>', null, false );

		foreach ( $expected as $i => $uri ) {
			$this->assertStringContainsString( 'src="' . $uri . '"', $rendered, "Data URI #$i was not restored verbatim." );
		}

		$this->assertSame( self::URI_COUNT, substr_count( $rendered, 'src="data:image/' ), 'Every image should keep its data: scheme.' );
		$this->assertStringNotContainsString( 'spectra-data-uri.invalid', $rendered, 'A placeholder leaked into the output.' );
	}

	/**
	 * `poster` is shielded too, and non-image mediatypes still lose the scheme.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_poster_is_shielded_but_data_text_html_is_not() {
		$rendered = HtmlSanitizer::render(
			'<video poster="data:image/gif;base64,R0lGOD" src="data:text/html;base64,PHNjcmlwdD4="></video>',
			null,
			false
		);

		$this->assertStringContainsString( 'poster="data:image/gif;base64,R0lGOD"', $rendered );
		$this->assertStringNotContainsString( 'data:text/html', $rendered );
	}

	/**
	 * Tags that execute script in `data:image/svg+xml` must keep losing the scheme.
	 *
	 * SVG rendered through `<img>`/`poster` is inert, which is what makes the shield
	 * safe. `<iframe>`/`<embed>`/`<object>` are a document context and run the SVG's
	 * `<script>` — and `iframe`/`embed` are allowlisted in the sanitizer itself.
	 *
	 * @since x.x.x
	 * @dataProvider provide_script_executing_tags
	 *
	 * @param string $markup Markup whose `data:` URI must not survive.
	 * @return void
	 */
	public function test_script_executing_tags_are_not_shielded( $markup ) {
		$rendered = HtmlSanitizer::render( $markup, null, false );

		$this->assertStringNotContainsString( 'data:image/', $rendered );
		$this->assertStringNotContainsString( 'spectra-data-uri.invalid', $rendered );
	}

	/**
	 * Markup for {@see self::test_script_executing_tags_are_not_shielded()}.
	 *
	 * @since x.x.x
	 * @return array<string,array<int,string>>
	 */
	public function provide_script_executing_tags() {
		$svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxzY3JpcHQ+YWxlcnQoMSk8L3NjcmlwdD48L3N2Zz4=';

		return array(
			'iframe' => array( '<iframe src="' . $svg . '"></iframe>' ),
			'embed'  => array( '<embed src="' . $svg . '" />' ),
			'object' => array( '<object data="' . $svg . '"></object>' ),
		);
	}

	/**
	 * Content cannot forge a placeholder to smuggle a `data:` URI into an `href`.
	 *
	 * The restore is an unscoped pass over the whole document, so a *predictable*
	 * placeholder written into a valid `https` link would be swapped back to the
	 * shielded `data:` URI — bypassing the `href` exclusion without ever matching
	 * `href` in the shield. The per-call salt is the only thing preventing it.
	 *
	 * The forgeries below are therefore the exact strings the placeholder would
	 * take if the salt were dropped — `…/{n}` and `…/{n}/`. Guessing at a salted
	 * key instead would make this test unfailable: it would stay green even after
	 * someone removed the salt, which is precisely the regression it guards.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_forged_placeholder_in_href_is_not_restored() {
		$rendered = HtmlSanitizer::render(
			'<img src="data:image/png;base64,SEEDVALUE" />'
			. '<a href="https://spectra-data-uri.invalid/0">unsalted</a>'
			. '<a href="https://spectra-data-uri.invalid/0/">unsalted trailing slash</a>',
			null,
			false
		);

		$this->assertStringContainsString( 'src="data:image/png;base64,SEEDVALUE"', $rendered, 'The genuine image should still be restored.' );
		$this->assertStringNotContainsString( 'href="data:', $rendered, 'A forged placeholder pulled a data: URI into an href.' );
	}

	/**
	 * The shield must not depend on the case of the scheme.
	 *
	 * Both patterns are `/i` and a scheme and mediatype are case-insensitive, so a
	 * case-sensitive fast-path guard made the outcome depend on whether some
	 * unrelated image in the same string happened to be lowercase.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_uppercase_scheme_is_shielded_on_its_own() {
		$rendered = HtmlSanitizer::render( '<img src="DATA:image/png;base64,UPPER" />', null, false );

		$this->assertStringContainsString( 'src="DATA:image/png;base64,UPPER"', $rendered );
	}

	/**
	 * A value carrying raw `<`/`>` is not shielded, so kses still neutralises it.
	 *
	 * Restoring verbatim is what makes the shield lossless, but it also means any
	 * byte inside a shielded value bypasses kses's entity encoding. Excluding the
	 * angle brackets from the match keeps `HtmlSanitizer::render()` from emitting a
	 * literal `<script>` sequence it never emitted before — which matters for the
	 * `preg`-based minifiers and cache rewriters this output is fed to.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_angle_brackets_in_a_data_uri_are_not_re_emitted() {
		$rendered = HtmlSanitizer::render(
			'<img src="data:image/svg+xml,<script>alert(1)</script>" />',
			null,
			false
		);

		$this->assertStringNotContainsString( '<script>', $rendered );
		$this->assertStringNotContainsString( 'spectra-data-uri.invalid', $rendered );
	}

	/**
	 * The shield must not widen kses anywhere else.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_classic_vectors_are_still_stripped() {
		$rendered = HtmlSanitizer::render(
			'<a href="javascript:alert(1)">x</a><img src="data:image/png;base64,AAA" onerror="alert(1)" />',
			null,
			false
		);

		$this->assertStringNotContainsString( 'javascript:', $rendered );
		$this->assertStringNotContainsString( 'onerror', $rendered );
		$this->assertStringContainsString( 'src="data:image/png;base64,AAA"', $rendered );
	}
}
