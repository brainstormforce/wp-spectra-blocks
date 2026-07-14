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
 * @since x.x.x
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
	 * Strict mode rejects `var(...)` references entirely.
	 *
	 * @return void
	 */
	public function test_value_strict_mode_rejects_var(): void {
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'var(--primary)', true ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'calc(100% - var(--pad))', true ) );
		$this->assertSame( '', Sanitizer::sanitize_css_value( 'VAR(--x)', true ) );
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
}
