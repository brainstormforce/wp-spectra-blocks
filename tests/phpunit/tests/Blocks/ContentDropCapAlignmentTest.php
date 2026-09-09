<?php
/**
 * The front-end drop cap honours the alignment gate the editor applies.
 *
 * The editor refuses a drop cap for centred or right-aligned text (and for a
 * span). The controller applied the same gate, but read the alignment from
 * `style.typography.textAlign`, which the responsive extension has already
 * moved into the store's base bucket by render time — so the gate never fired
 * on the front end and a centred paragraph rendered a drop cap the editor said
 * it could not have. The controller now reads the base bucket as well.
 *
 * Alignment set only on a narrower device does not gate the drop cap; the
 * editor gates on the root value too, and the class is per element.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_UnitTestCase;

/**
 * ContentDropCapAlignmentTest test case.
 *
 * @since x.x.x
 */
class ContentDropCapAlignmentTest extends WP_UnitTestCase {

	/**
	 * Render a drop-cap paragraph with the given `style` and report the class.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $style The `style` attribute.
	 * @return bool Whether `has-drop-cap` was emitted.
	 */
	private function has_drop_cap( array $style ): bool {
		$attrs = array(
			'spectraId' => 'dc' . wp_generate_password( 6, false ),
			'text'      => 'Drop cap',
			'dropCap'   => true,
		);
		if ( $style ) {
			$attrs['style'] = $style;
		}
		$html = do_blocks( '<!-- wp:spectra/content ' . wp_json_encode( $attrs ) . ' --><p></p><!-- /wp:spectra/content -->' );

		return false !== strpos( $html, 'has-drop-cap' );
	}

	/**
	 * Left / unset alignment keeps the drop cap.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_left_or_unset_alignment_keeps_the_drop_cap() {
		$this->assertTrue( $this->has_drop_cap( array() ) );
		$this->assertTrue( $this->has_drop_cap( array( 'typography' => array( 'textAlign' => 'left' ) ) ) );
	}

	/**
	 * Centred or right-aligned base text has no drop cap, as in the editor.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_centred_or_right_base_alignment_disables_the_drop_cap() {
		$this->assertFalse( $this->has_drop_cap( array( 'typography' => array( 'textAlign' => 'center' ) ) ), 'Centred text: the editor offers no drop cap, the front end must not paint one.' );
		$this->assertFalse( $this->has_drop_cap( array( 'typography' => array( 'textAlign' => 'right' ) ) ) );
	}

	/**
	 * Alignment on a narrower device alone does not gate the (per-element) drop cap.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_device_only_alignment_does_not_gate() {
		$this->assertTrue( $this->has_drop_cap( array( '@tablet' => array( 'typography' => array( 'textAlign' => 'center' ) ) ) ) );
	}
}
