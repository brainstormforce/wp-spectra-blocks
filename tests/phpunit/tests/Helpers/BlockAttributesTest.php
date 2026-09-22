<?php
/**
 * Tests for the BlockAttributes helper.
 *
 * Focuses on GIT-106 §2.1: the `should_emit_helper_class` gate that
 * suppresses re-emission of `spectra-text-color` / `spectra-background-color`
 * when the block's `className` already carries a GBS JIT utility token on
 * the same visual axis.
 *
 * @package Spectra\Tests\Helpers
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Helpers;

use SpectraBlocks\Helpers\BlockAttributes;
use WP_UnitTestCase;

/**
 * BlockAttributesTest test case.
 *
 * @since x.x.x
 */
class BlockAttributesTest extends WP_UnitTestCase {

	/**
	 * `htmlAttributes` is source-authored, and core's
	 * `get_block_wrapper_attributes()` prints the attribute NAME raw while
	 * escaping only the value — so a second attribute smuggled inside a name
	 * (`data-a="" onmouseover="…" data-b`) walked straight past the `on*` check.
	 * The name must match a strict shape, and a URL-valued attribute must have
	 * its scheme filtered, because `esc_attr()` never blocks `javascript:` and
	 * `wp_kses_data()` on a bare attribute list is a no-op.
	 *
	 * @return void
	 */
	public function test_html_attributes_never_print_a_script_url_or_a_second_attribute(): void {
		// `get_block_wrapper_attributes()` reads the block being rendered.
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(),
		);

		$html = BlockAttributes::get_wrapper_attributes(
			array(
				'htmlAttributes' => array(
					'data-tab'                                => 'pricing',
					'role'                                    => 'tab',
					'aria-label'                              => 'Pricing',
					'Data-Upper'                              => 'kept-lowercased',
					'href'                                    => 'https://example.test/pricing',
					'data-a="" onmouseover="alert(1)" data-b' => '1',
					' onmouseover'                            => 'alert(1)',
					'onclick'                                 => 'alert(1)',
					'srcdoc'                                  => '<script>alert(1)</script>',
				),
			)
		);

		// The attributes a design may legitimately author still land.
		$this->assertStringContainsString( 'data-tab="pricing"', $html );
		$this->assertStringContainsString( 'role="tab"', $html );
		$this->assertStringContainsString( 'aria-label="Pricing"', $html );
		$this->assertStringContainsString( 'data-upper="kept-lowercased"', $html );
		$this->assertStringContainsString( 'href="https://example.test/pricing"', $html );

		// Nothing that can execute may reach the wrapper.
		$this->assertStringNotContainsString( 'onmouseover', $html );
		$this->assertStringNotContainsString( 'onclick', $html );
		$this->assertStringNotContainsString( 'srcdoc', $html );
		$this->assertStringNotContainsString( 'data-a', $html );
	}

	/**
	 * A `javascript:` URL in an authored `href` is stripped rather than escaped.
	 * On `spectra/button` the wrapper `href` is the ONLY one on the `<a>` when
	 * `linkURL` is empty, so `esc_attr()` alone would have shipped a live
	 * script URL an editor could persist.
	 *
	 * @dataProvider provide_script_urls
	 *
	 * @param string $url The authored URL.
	 * @return void
	 */
	public function test_html_attributes_strip_script_schemes_from_url_attributes( string $url ): void {
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(),
		);

		$html = BlockAttributes::get_wrapper_attributes( array( 'htmlAttributes' => array( 'href' => $url ) ) );

		$this->assertStringNotContainsString( 'javascript', strtolower( $html ) );
		$this->assertStringNotContainsString( 'vbscript', strtolower( $html ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function provide_script_urls(): array {
		return array(
			'plain'          => array( 'javascript:alert(1)' ),
			'spaced'         => array( 'javascript :alert(1)' ),
			'mixed case'     => array( 'JaVaScRiPt:alert(1)' ),
			'vbscript'       => array( 'vbscript:msgbox(1)' ),
			'leading spaces' => array( '   javascript:alert(1)' ),
		);
	}

	/**
	 * When no className is present the legacy helper class is still emitted.
	 *
	 * @return void
	 */
	public function test_emits_helper_when_no_classname(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array( 'textColor' => '#ff0000' ),
			array( 'textColor' )
		);

		$this->assertContains( 'spectra-text-color', $classes );
	}

	/**
	 * A palette `text-*` token on className suppresses `spectra-text-color`.
	 *
	 * @return void
	 */
	public function test_palette_utility_suppresses_text_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#ff0000',
				'className' => 'text-chromatic1-6',
			),
			array( 'textColor' )
		);

		$this->assertNotContains( 'spectra-text-color', $classes );
	}

	/**
	 * A bracket-escape `text-[#hex]` token on className suppresses the helper.
	 *
	 * @return void
	 */
	public function test_bracket_utility_suppresses_text_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#f59e0b',
				'className' => 'text-[#f59e0b]',
			),
			array( 'textColor' )
		);

		$this->assertNotContains( 'spectra-text-color', $classes );
	}

	/**
	 * `bg-*` tokens do NOT suppress `spectra-text-color` — different axis.
	 *
	 * @return void
	 */
	public function test_bg_utility_does_not_suppress_text_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#ff0000',
				'className' => 'bg-primary',
			),
			array( 'textColor' )
		);

		$this->assertContains( 'spectra-text-color', $classes );
	}

	/**
	 * `backgroundColor` helper is suppressed when className has `bg-*`.
	 *
	 * @return void
	 */
	public function test_bg_utility_suppresses_background_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'backgroundColor' => '#000000',
				'className'       => 'bg-dark',
			),
			array( 'backgroundColor' )
		);

		$this->assertNotContains( 'spectra-background-color', $classes );
	}

	/**
	 * Multi-token className containing both axis-covering and unrelated tokens
	 * still suppresses the helper correctly.
	 *
	 * @return void
	 */
	public function test_multi_token_classname_suppresses_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#ff0000',
				'className' => 'flex items-center text-chromatic1-6 p-4',
			),
			array( 'textColor' )
		);

		$this->assertNotContains( 'spectra-text-color', $classes );
	}

	/**
	 * Non-`spectra-` custom class names on configs are never suppressed by the
	 * gate — only the legacy `spectra-*` helper family is gated.
	 *
	 * @return void
	 */
	public function test_custom_non_spectra_class_is_always_emitted(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#ff0000',
				'className' => 'text-primary',
			),
			array(
				array(
					'key'        => 'textColor',
					'class_name' => 'my-project-text',
				),
			)
		);

		$this->assertContains( 'my-project-text', $classes );
	}

	/**
	 * Tokens that look similar to `text-*` but aren't exact matches (e.g.
	 * `textual-content`) do NOT suppress the helper.
	 *
	 * @return void
	 */
	public function test_lookalike_token_does_not_suppress_helper(): void {
		list( , $classes ) = BlockAttributes::generate_styles_and_classes(
			array(
				'textColor' => '#ff0000',
				'className' => 'textual-content textWrapper',
			),
			array( 'textColor' )
		);

		$this->assertContains( 'spectra-text-color', $classes );
	}

	/**
	 * Regression: size/alignment tokens on the `text-` axis are NOT colors —
	 * helper must still emit so `--spectra-text-color` resolves.
	 *
	 * @return void
	 */
	public function test_non_color_text_tokens_do_not_suppress_text_helper(): void {
		foreach ( array( 'text-[clamp(48px,_8vw,_84px)]', 'text-[16px]', 'text-xs', 'text-2xl', 'text-center' ) as $cls ) {
			list( , $classes ) = BlockAttributes::generate_styles_and_classes(
				array(
					'textColor' => '#ffffff',
					'className' => $cls,
				),
				array( 'textColor' )
			);
			$this->assertContains( 'spectra-text-color', $classes, $cls );
		}
	}

	/**
	 * When a config entry declares `default` and the resolved value matches
	 * it, no inline style is emitted. Prevents the plugin from shadowing an
	 * authored utility class (e.g. `overflow-hidden`) with an inline default.
	 *
	 * @return void
	 */
	public function test_default_value_skips_inline_emission(): void {
		list( $styles ) = BlockAttributes::generate_styles_and_classes(
			array(),
			array(
				array(
					'key'        => 'overflow',
					'css_var'    => 'overflow',
					'class_name' => null,
					'value'      => 'visible',
					'default'    => 'visible',
				),
			)
		);
		$this->assertArrayNotHasKey( 'overflow', $styles );
	}

	/**
	 * A non-default value still emits — only equality-to-default is the gate.
	 *
	 * @return void
	 */
	public function test_non_default_value_still_emits(): void {
		list( $styles ) = BlockAttributes::generate_styles_and_classes(
			array(),
			array(
				array(
					'key'        => 'overflow',
					'css_var'    => 'overflow',
					'class_name' => null,
					'value'      => 'hidden',
					'default'    => 'visible',
				),
			)
		);
		$this->assertSame( 'hidden', $styles['overflow'] ?? null );
	}

	/**
	 * Config entries WITHOUT a `default` field retain the prior always-emit
	 * behavior — the new rule is opt-in so existing configs don't regress.
	 *
	 * @return void
	 */
	public function test_missing_default_field_retains_legacy_behavior(): void {
		list( $styles ) = BlockAttributes::generate_styles_and_classes(
			array(),
			array(
				array(
					'key'   => 'textColor',
					'value' => '#ffffff',
				),
			)
		);
		$this->assertSame( '#ffffff', $styles['--spectra-text-color'] ?? null );
	}
}
