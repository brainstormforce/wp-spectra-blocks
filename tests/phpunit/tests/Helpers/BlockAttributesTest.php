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
