<?php
/**
 * Tests for the Global Styles Sanitizer.
 *
 * Focuses on security-critical paths: blocking XSS vectors inside CSS values,
 * preserving safe CSS functions/units, and validating keyframe/animation inputs.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Sanitizer;
use WP_UnitTestCase;

/**
 * SanitizerTest test case.
 *
 * @since 1.0.10
 */
class SanitizerTest extends WP_UnitTestCase {

	// ─────────────────────────────────────────────────────────────
	// sanitize_css_property
	// ─────────────────────────────────────────────────────────────

	/**
	 * Standard property is preserved and lowercased.
	 *
	 * @return void
	 */
	public function test_property_standard_is_lowercased(): void {
		$this->assertSame( 'background-color', Sanitizer::sanitize_css_property( 'Background-Color' ) );
	}

	/**
	 * CSS custom property is allowed.
	 *
	 * @return void
	 */
	public function test_property_custom_variable_is_allowed(): void {
		$this->assertSame( '--my-var', Sanitizer::sanitize_css_property( '--my-var' ) );
	}

	/**
	 * Property with invalid characters is rejected.
	 *
	 * @return void
	 */
	public function test_property_invalid_is_rejected(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_property( 'color; background: red' ) );
		$this->assertSame( '', Sanitizer::sanitize_css_property( '123invalid' ) );
		$this->assertSame( '', Sanitizer::sanitize_css_property( '' ) );
	}

	/**
	 * Non-string input returns an empty string.
	 *
	 * @return void
	 */
	public function test_property_non_string_returns_empty(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_property( 123 ) );
		$this->assertSame( '', Sanitizer::sanitize_css_property( null ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_css_value — security
	// ─────────────────────────────────────────────────────────────

	/**
	 * javascript: URLs are blocked.
	 *
	 * @return void
	 */
	public function test_value_blocks_javascript_uri(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'javascript:alert(1)' ) );
	}

	/**
	 * expression() is blocked (old IE XSS vector).
	 *
	 * @return void
	 */
	public function test_value_blocks_expression(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'expression(alert(1))' ) );
	}

	/**
	 * <script> tag patterns are blocked.
	 *
	 * @return void
	 */
	public function test_value_blocks_script_tag(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( '<script>alert(1)</script>' ) );
	}

	/**
	 * On-event handlers are blocked.
	 *
	 * @return void
	 */
	public function test_value_blocks_onclick(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'red; onclick=alert(1)' ) );
	}

	/**
	 * data:text/html is blocked.
	 *
	 * @return void
	 */
	public function test_value_blocks_data_text_html(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'data:text/html,<script>' ) );
	}

	/**
	 * vbscript: URLs are blocked.
	 *
	 * @return void
	 */
	public function test_value_blocks_vbscript(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'vbscript:msgbox(1)' ) );
	}

	/**
	 * A CSS escape cannot spell a blocked token one character at a time: the
	 * patterns are matched against the value as a browser reads it.
	 *
	 * @return void
	 */
	public function test_value_blocks_escape_obfuscated_tokens(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'j\61 vascript:alert(1)' ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'expression\28 alert(1))' ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'url(data:image/svg+xml,<svg \6fnload=alert(1)>)' ) );
	}

	/**
	 * decode_css_escapes reads a value the way a browser does.
	 *
	 * @return void
	 */
	public function test_decode_css_escapes_reads_like_a_browser(): void {
		$this->assertSame( 'javascript:', Sanitizer::decode_css_escapes( 'j\61 vascript\3a' ) );
		$this->assertSame( '"', Sanitizer::decode_css_escapes( '\"' ) );
		$this->assertSame( 'plain', Sanitizer::decode_css_escapes( 'plain' ) );
		// A backslash before a newline is a line continuation — removed.
		$this->assertSame( 'javascript:', Sanitizer::decode_css_escapes( "java\\\nscript:" ) );
		$this->assertSame( 'javascript:', Sanitizer::decode_css_escapes( "java\\\r\nscript:" ) );
		// CRLF after a hex escape is ONE whitespace, consumed by the escape.
		$this->assertSame( 'javascript:', Sanitizer::decode_css_escapes( "j\\61\r\nvascript:" ) );
	}

	/**
	 * A line continuation or a CRLF after a hex escape cannot split a blocked
	 * token past the as-read check.
	 *
	 * @return void
	 */
	public function test_value_blocks_newline_split_tokens(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( "url(\"java\\\nscript:alert(1)\")" ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( "url(\"j\\61\r\nvascript:alert(1)\")" ) );
	}

	/**
	 * A character the whitelist strips cannot join its neighbours into a
	 * blocked token after the checks ran: the checks see the stripped value.
	 *
	 * @return void
	 */
	public function test_value_blocks_tokens_joined_by_whitelist_strip(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( "url(\"j\\\x0161 vascript:x\")" ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( "java\x01script:alert(1)" ) );
	}

	/**
	 * Strict mode reads an escaped function name the way a browser does:
	 * `v\61r(` is `var(`.
	 *
	 * @return void
	 */
	public function test_value_strict_rejects_escaped_malformed_var(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'v\61r(url(x))', true ) );
		$this->assertSame( 'v\61r(--x)', Sanitizer::sanitize_css_value( 'v\61r(--x)', true ) );
	}

	/**
	 * A trailing unpaired backslash is dropped — printed, it would escape the
	 * `;` ending the declaration. A paired `\\` is an escaped backslash and stays.
	 *
	 * @return void
	 */
	public function test_value_drops_trailing_unpaired_backslash(): void {
		$this->assertSame( 'red', Sanitizer::sanitize_css_value( 'red\\' ) );
		$this->assertSame( '"a\\\\"', Sanitizer::sanitize_css_value( '"a\\\\"' ) );
		$this->assertSame( 'a\\\\', Sanitizer::sanitize_css_value( 'a\\\\' ) );
		$this->assertSame( 'a\\\\', Sanitizer::sanitize_css_value( 'a\\\\\\' ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_css_value — preservation
	// ─────────────────────────────────────────────────────────────

	/**
	 * CSS functions like calc() and var() are preserved in permissive mode.
	 *
	 * @return void
	 */
	public function test_value_preserves_css_functions(): void {
		$this->assertSame( 'calc(100% - 20px)', Sanitizer::sanitize_css_value( 'calc(100% - 20px)' ) );
		$this->assertSame( 'var(--primary)', Sanitizer::sanitize_css_value( 'var(--primary)' ) );
	}

	/**
	 * A CSS escape is part of the value: `content: "\201C"` is a curly quote and
	 * `\2192` an arrow. Dropping the backslash ships the hex digits as text.
	 *
	 * @return void
	 */
	public function test_value_preserves_css_escapes(): void {
		$this->assertSame( '"\201C"', Sanitizer::sanitize_css_value( '"\201C"' ) );
		$this->assertSame( '"\2192 "', Sanitizer::sanitize_css_value( '"\2192 "' ) );
		$this->assertSame( '"\201C"', Sanitizer::sanitize_css_value( '"\201C"', true ) );
	}

	/**
	 * Strict mode rejects malformed `var(...)` — anything whose first token is not a `--` custom property.
	 *
	 * @return void
	 */
	public function test_value_strict_mode_rejects_malformed_var(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'var()', true ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'var(url(#x))', true ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'var(10px)', true ) );
	}

	/**
	 * Strict mode preserves well-formed custom-property references — `var(--name)` / `var(--name, fallback)`.
	 *
	 * @return void
	 */
	public function test_value_strict_mode_preserves_wellformed_var(): void {
		$this->assertSame( 'var(--primary)', Sanitizer::sanitize_css_value( 'var(--primary)', true ) );
		$this->assertSame( 'var(--x, 10px)', Sanitizer::sanitize_css_value( 'var(--x, 10px)', true ) );
		$this->assertSame( 'calc(100% - var(--pad))', Sanitizer::sanitize_css_value( 'calc(100% - var(--pad))', true ) );
		$this->assertSame( 'VAR(--x)', Sanitizer::sanitize_css_value( 'VAR(--x)', true ) );
	}

	/**
	 * Strict mode still preserves safe functions like calc() without var().
	 *
	 * @return void
	 */
	public function test_value_strict_mode_preserves_safe_functions(): void {
		$this->assertSame( 'calc(100% - 20px)', Sanitizer::sanitize_css_value( 'calc(100% - 20px)', true ) );
		$this->assertSame( 'rgb(255, 0, 0)', Sanitizer::sanitize_css_value( 'rgb(255, 0, 0)', true ) );
	}

	/**
	 * rgba() values with commas are preserved.
	 *
	 * @return void
	 */
	public function test_value_preserves_rgba(): void {
		$this->assertSame( 'rgba(255, 0, 0, 0.5)', Sanitizer::sanitize_css_value( 'rgba(255, 0, 0, 0.5)' ) );
	}

	/**
	 * Linear gradients are preserved.
	 *
	 * @return void
	 */
	public function test_value_preserves_linear_gradient(): void {
		$this->assertSame(
			'linear-gradient(90deg, #fff, #000)',
			Sanitizer::sanitize_css_value( 'linear-gradient(90deg, #fff, #000)' )
		);
	}

	/**
	 * Non-string input returns empty string.
	 *
	 * @return void
	 */
	public function test_value_non_string_returns_empty(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 123 ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( null ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( array() ) );
	}

	/**
	 * Values longer than the 2000-char limit are truncated.
	 *
	 * @return void
	 */
	public function test_value_length_is_capped(): void {
		$long = str_repeat( 'a', 2500 );
		$this->assertSame( 2000, strlen( Sanitizer::sanitize_css_value( $long ) ) );
	}

	/**
	 * Non-ASCII text and a URL query survive; blocked tokens stay blocked.
	 *
	 * @return void
	 */
	public function test_value_keeps_non_ascii_and_query(): void {
		$this->assertSame( "'↔'", Sanitizer::sanitize_css_value( "'↔'" ) );
		$this->assertSame( 'url("https://x.test/a.jpg?w=1")', Sanitizer::sanitize_css_value( 'url("https://x.test/a.jpg?w=1")' ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'url("↔javascript:alert(1)")' ) );
	}

	/**
	 * The length cap counts characters, not bytes.
	 *
	 * @return void
	 */
	public function test_value_length_cap_counts_characters(): void {
		$this->assertSame( str_repeat( '↔', 2000 ), Sanitizer::sanitize_css_value( str_repeat( '↔', 2001 ) ) );
	}

	/**
	 * SVG data URLs are preserved verbatim — the `<svg>` markup inside
	 * `url('data:image/svg+xml;utf8,...')` is legitimate CSS, not HTML.
	 *
	 * Regression for the bug where `wp_strip_all_tags()` ripped the
	 * `<svg>...</svg>` block out of CSS values, leaving an unterminated
	 * `url('data:image/svg+xml;utf8,` that poisoned the browser CSS
	 * parser and silently dropped every subsequent rule in the inline
	 * stylesheet (`spectra-gs-utility-classes-inline-css`).
	 *
	 * @return void
	 */
	public function test_value_preserves_svg_data_url(): void {
		$svg_value = "url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><path d=\"M5 13l4 4L19 7\"/></svg>')";
		$out       = Sanitizer::sanitize_css_value( $svg_value );

		$this->assertStringContainsString( '<svg', $out, 'SVG opening tag must survive sanitization' );
		$this->assertStringContainsString( '</svg>', $out, 'SVG closing tag must survive — without it the url() is unterminated and breaks the parser' );
	}

	/**
	 * Even with `<svg>` markup preserved, the dangerous-pattern guard
	 * must still block `<script>` injection inside CSS values. This
	 * pins that removing wp_strip_all_tags didn't widen the XSS surface.
	 *
	 * @return void
	 */
	public function test_value_still_blocks_script_tag_even_after_svg_fix(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( "url('data:image/svg+xml;utf8,<script>alert(1)</script>')" ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( '<script src="//evil"></script>' ) );
	}

	/**
	 * SVG with inline event-handler attributes must still be rejected —
	 * `on{event}=` is in the dangerous-pattern guard. This blocks the
	 * obvious post-fix attack: a "looks like SVG" payload that smuggles
	 * an XSS via `<svg onload=alert(1)>`.
	 *
	 * @return void
	 */
	public function test_value_rejects_svg_with_inline_event_handler(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( "url('data:image/svg+xml;utf8,<svg onload=alert(1)></svg>')" ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_json
	// ─────────────────────────────────────────────────────────────

	/**
	 * A malformed JSON string returns an empty array.
	 *
	 * @return void
	 */
	public function test_json_malformed_returns_empty(): void {
		$this->assertSame( array(), Sanitizer::sanitize_json( 'not json' ) );
	}

	/**
	 * Pre-decoded arrays are also sanitized.
	 *
	 * @return void
	 */
	public function test_json_accepts_array_input(): void {
		$input  = array(
			'default' => array(
				array(
					'color' => 'red',
				),
			),
		);
		$result = Sanitizer::sanitize_json( $input );
		$this->assertArrayHasKey( 'default', $result );
	}

	/**
	 * Non-string/non-array input returns an empty array.
	 *
	 * @return void
	 */
	public function test_json_invalid_type_returns_empty(): void {
		$this->assertSame( array(), Sanitizer::sanitize_json( 123 ) );
		$this->assertSame( array(), Sanitizer::sanitize_json( null ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_animation_duration
	// ─────────────────────────────────────────────────────────────

	/**
	 * Valid s/ms durations are preserved.
	 *
	 * @return void
	 */
	public function test_duration_valid_values(): void {
		$this->assertSame( '0.5s', Sanitizer::sanitize_animation_duration( '0.5s' ) );
		$this->assertSame( '300ms', Sanitizer::sanitize_animation_duration( '300ms' ) );
	}

	/**
	 * Values outside the allowed range snap to the default.
	 *
	 * @return void
	 */
	public function test_duration_out_of_range_snaps_to_default(): void {
		$this->assertSame( '0.3s', Sanitizer::sanitize_animation_duration( '999s' ) );
		$this->assertSame( '0.3s', Sanitizer::sanitize_animation_duration( 'garbage' ) );
		$this->assertSame( '0.3s', Sanitizer::sanitize_animation_duration( '' ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_animation_easing
	// ─────────────────────────────────────────────────────────────

	/**
	 * Keyword easings are preserved and lowercased.
	 *
	 * @return void
	 */
	public function test_easing_keywords(): void {
		$this->assertSame( 'ease-in-out', Sanitizer::sanitize_animation_easing( 'Ease-In-Out' ) );
		$this->assertSame( 'linear', Sanitizer::sanitize_animation_easing( 'linear' ) );
	}

	/**
	 * Valid cubic-bezier() is preserved.
	 *
	 * @return void
	 */
	public function test_easing_cubic_bezier(): void {
		$this->assertStringStartsWith(
			'cubic-bezier(',
			Sanitizer::sanitize_animation_easing( 'cubic-bezier(0.25, 0.1, 0.25, 1)' )
		);
	}

	/**
	 * Invalid easing snaps to default.
	 *
	 * @return void
	 */
	public function test_easing_invalid_snaps_to_default(): void {
		$this->assertSame( 'ease-out', Sanitizer::sanitize_animation_easing( 'evil(javascript:1)' ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_animation_iterations
	// ─────────────────────────────────────────────────────────────

	/**
	 * Integer counts are preserved as integers.
	 *
	 * @return void
	 */
	public function test_iterations_integer(): void {
		$this->assertSame( '3', Sanitizer::sanitize_animation_iterations( '3' ) );
	}

	/**
	 * Infinite keyword is preserved.
	 *
	 * @return void
	 */
	public function test_iterations_infinite(): void {
		$this->assertSame( 'infinite', Sanitizer::sanitize_animation_iterations( 'infinite' ) );
	}

	/**
	 * Decimal counts are preserved.
	 *
	 * @return void
	 */
	public function test_iterations_decimal(): void {
		$this->assertSame( '1.5', Sanitizer::sanitize_animation_iterations( '1.5' ) );
	}

	/**
	 * Invalid values snap to 1.
	 *
	 * @return void
	 */
	public function test_iterations_invalid(): void {
		$this->assertSame( '1', Sanitizer::sanitize_animation_iterations( 'alert(1)' ) );
	}

	// ─────────────────────────────────────────────────────────────
	// sanitize_keyframe_data
	// ─────────────────────────────────────────────────────────────

	/**
	 * New CSS-format keyframe is sanitized and wrapped with defaults.
	 *
	 * @return void
	 */
	public function test_keyframe_data_new_format(): void {
		$data = array(
			'css'  => 'from { opacity: 0; } to { opacity: 1; }',
			'meta' => array(
				'defaultDuration'   => '0.5s',
				'defaultEasing'     => 'ease-in',
				'defaultIterations' => '1',
			),
		);

		$result = Sanitizer::sanitize_keyframe_data( $data );

		$this->assertArrayHasKey( 'css', $result );
		$this->assertArrayHasKey( 'meta', $result );
		$this->assertSame( '0.5s', $result['meta']['defaultDuration'] );
		$this->assertSame( 'ease-in', $result['meta']['defaultEasing'] );
		$this->assertStringContainsString( 'opacity', $result['css'] );
	}

	/**
	 * Keyframe css that tries to inject scripts is stripped.
	 *
	 * @return void
	 */
	public function test_keyframe_data_strips_scripts(): void {
		$data = array(
			'css' => 'from { opacity: 0; background: javascript:alert(1); } to { opacity: 1; }',
		);

		$result = Sanitizer::sanitize_keyframe_data( $data );

		$this->assertStringNotContainsString( 'javascript', $result['css'] );
	}

	/**
	 * Unknown input shape returns default-shaped array.
	 *
	 * @return void
	 */
	public function test_keyframe_data_invalid_returns_defaults(): void {
		$result = Sanitizer::sanitize_keyframe_data( 'not json' );
		$this->assertSame( '', $result['css'] );
		$this->assertSame( '0.3s', $result['meta']['defaultDuration'] );
	}

	/**
	 * A `</style` in a value is escaped, not printed: the HTML parser would end
	 * the `<style>` element there and print the rest as live HTML.
	 *
	 * @return void
	 */
	public function test_value_style_end_tag_is_escaped(): void {
		$this->assertSame( 'red\3c /style><base href=//evil.test>', Sanitizer::sanitize_css_value( 'red</style><base href=//evil.test>' ) );
		$this->assertSame( 'red\3c /STYLE ><b>', Sanitizer::sanitize_css_value( 'red</STYLE ><b>' ) );
		$this->assertSame( 'a\3c /style/x', Sanitizer::sanitize_css_value( 'a</style/x' ) );
	}

	/**
	 * An SVG data URL keeps its own `<style>` element: the CSS parser reads the
	 * escape back, so the URL is unchanged as a browser reads it.
	 *
	 * @return void
	 */
	public function test_svg_data_url_style_element_survives(): void {
		$svg = "url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\"><style>.a{fill:red}</style><rect class=\"a\"/></svg>')";
		$out = Sanitizer::sanitize_css_value( $svg );

		$this->assertStringNotContainsString( '</style', $out );
		$this->assertSame( $svg, Sanitizer::decode_css_escapes( $out ) );
	}

	/**
	 * The escape is idempotent, and it never unblocks a blocked token.
	 *
	 * @return void
	 */
	public function test_style_end_tag_escape_is_idempotent_and_keeps_blocks(): void {
		$once = Sanitizer::sanitize_css_value( 'red</style>' );
		$this->assertSame( $once, Sanitizer::sanitize_css_value( $once ) );
		$this->assertSame( $once, Sanitizer::escape_style_end_tag( $once ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'x</script>' ) );
	}
}
