<?php
/**
 * Every text-bearing block must render a label of "0".
 *
 * `"0"` is falsy in PHP, so `! $text` / `empty( $text )` treat a legitimate zero
 * label as absent — the block then renders a placeholder, or nothing at all. It
 * is the classic PHP falsy-string trap and it hit eight controllers
 * independently, which is why this is a data provider rather than eight tests:
 * the next text-bearing block belongs on the list, not in a new file.
 *
 * @package Spectra\Tests\Blocks
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * ZeroLabelRenderTest test case.
 *
 * @since x.x.x
 */
class ZeroLabelRenderTest extends WP_UnitTestCase {

	/**
	 * Blocks whose visible label is user-authored text.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, array{0:string, 1:array<string,mixed>}>
	 */
	public function zero_label_blocks(): array {
		return array(
			'button'                   => array( 'spectra/button', array( 'text' => '0' ) ),
			'content'                  => array( 'spectra/content', array( 'text' => '0' ) ),
			'accordion header content' => array( 'spectra/accordion-child-header-content', array( 'text' => '0' ) ),
			'tab button'               => array( 'spectra/tabs-child-tab-button', array( 'text' => '0' ) ),
			'countdown label'          => array( 'spectra/countdown-child-label', array( 'text' => '0' ) ),
			'countdown separator'      => array( 'spectra/countdown-child-separator', array( 'text' => '0' ) ),
			// DELIBERATELY ABSENT: spectra/modal-child-button and
			// spectra/modal-child-content. Both had the same "0" bug and both are
			// fixed in their controllers, but neither renders in isolation here —
			// they produce an empty string even with
			// `spectra/modal/modalTrigger` supplied, so the failure is the
			// Interactivity-driven parent runtime, not the label. Asserting on
			// them would pin the wrong thing; listing them with a skip would read
			// as coverage that does not exist. They need a modal-parent
			// integration harness, which is worth building separately.
		);
	}

	/**
	 * @dataProvider zero_label_blocks
	 *
	 * @since x.x.x
	 *
	 * @param string               $name  Registered block name.
	 * @param array<string, mixed> $attrs Block attributes.
	 * @return void
	 */
	public function test_a_zero_label_survives_rendering( string $name, array $attrs ): void {
		// A silent skip here is how this bug class stayed invisible: an absent
		// build and an unregistered block are indistinguishable, and both used
		// to come out green. The workflow guarantees a build now, so absence
		// is a FAILURE.
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( $name ),
			$name . ' is not registered — run `npm run build`.'
		);

		$html = (string) render_block(
			array(
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);

		$this->assertNotSame( '', trim( $html ), $name . ' rendered nothing for a "0" label.' );

		// Assert the VISIBLE TEXT, not the raw markup. `assertStringContainsString(
		// '0', $html )` is vacuous here: several of these blocks emit
		// `data-wp-context='…{"currentTab":0,…}'` and others carry a `0` in a class
		// name or length, so that assertion passed with the bug fully present —
		// measured on tabs-child-tab-button. Stripping tags drops attributes too,
		// leaving only what a reader actually sees.
		//
		// Deliberately EXACT: if a future change adds visible text next to the
		// label (a screen-reader span, a separator glyph, an &nbsp;), this
		// SHOULD fail and be investigated — loosen it only with a reason.
		$this->assertSame(
			'0',
			trim( wp_strip_all_tags( $html ) ),
			$name . ' did not render "0" as its visible label (placeholder substituted, or label dropped).'
		);
	}
}
