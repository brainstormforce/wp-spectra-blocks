<?php
/**
 * Every attribute the editor stores per device must have something that paints it.
 *
 * Per-device support is spread across two languages. The editor decides which
 * attributes get a per-viewport value — `BLOCK_RESPONSIVE_KEYS` in
 * `src/extensions/responsive-controls/utils/constants.js` — and the renderer
 * decides which ones become banded CSS: `ATTR_DEFINITIONS` here in PHP. Neither
 * side can see the other, and nothing at runtime notices when they disagree.
 *
 * A key added to the JS list alone is the damaging direction: the control
 * appears per device, the value is stored per device, and the front end ignores
 * it. No error, no warning — the author sets a tablet value and it silently
 * does nothing.
 *
 * The opposite direction is capability nobody can reach: the renderer bands an
 * attribute the editor never writes per device, so the code is carried and
 * maintained while no author can ever trigger it.
 *
 * The two lists agree exactly today, in both directions, so both are asserted
 * strictly. Keeping the reverse assertion as tight as the forward one is what
 * makes the pair a description of the contract rather than a snapshot of its
 * current drift — a mismatch in either direction is a genuine decision for
 * someone to make, not something to append to an allowlist.
 *
 * The JS file is parsed rather than duplicated. A copy of the key list here
 * would be one more thing to keep in sync, and would pass happily while the
 * real list drifted.
 *
 * KNOWN LIMIT — this asserts that a definition EXISTS, not that it emits CSS.
 * An empty definition (`'slidesPerView' => array()`) counts as painted, which is
 * deliberate: `spectra/post`'s Swiper parameters are tracked per device so the
 * value is stored per viewport, then read by JS rather than turned into a rule.
 * A key whose definition is emptied by accident would therefore still pass here.
 * Catching that needs a rendering assertion, not a parity one.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use WP_UnitTestCase;

/**
 * ResponsiveKeyParityTest test case.
 *
 * @since x.x.x
 */
class ResponsiveKeyParityTest extends WP_UnitTestCase {

	/**
	 * The editor's per-block responsive keys, read from the JS source.
	 *
	 * @since x.x.x
	 * @return array<string, array<string>> Block name to attribute names.
	 */
	private function editor_keys(): array {
		$path = dirname( __DIR__, 4 ) . '/src/extensions/responsive-controls/utils/constants.js';

		$this->assertFileExists( $path, 'The editor key map must be where this test expects it.' );

		$source = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading plugin source in a test.

		$start = strpos( $source, 'BLOCK_RESPONSIVE_KEYS = Object.freeze(' );
		$this->assertNotFalse( $start, 'BLOCK_RESPONSIVE_KEYS must still be declared in the JS constants.' );

		$end = strpos( $source, "\n} );", $start );
		$this->assertNotFalse( $end, 'The key map literal must be terminated as expected.' );

		$body = substr( $source, $start, $end - $start );

		preg_match_all( "/'(spectra\/[^']+)'\s*:\s*\[(.*?)\]/s", $body, $matches, PREG_SET_ORDER );

		$keys = array();

		foreach ( $matches as $match ) {
			preg_match_all( "/'([^']+)'/", $match[2], $found );
			$keys[ $match[1] ] = $found[1];
		}

		$this->assertNotEmpty( $keys, 'The parse must find blocks — a silent empty result would pass everything.' );

		return $keys;
	}

	/**
	 * The parse itself has to be trustworthy before anything is asserted on it.
	 *
	 * A regex that quietly stops matching would turn every assertion below into
	 * a no-op, so the shape of the result is checked against known landmarks.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_the_editor_key_map_parses_to_a_plausible_shape() {
		$keys = $this->editor_keys();

		$this->assertGreaterThanOrEqual(
			20,
			count( $keys ),
			'The editor map has carried 20+ blocks since 1.0.6; far fewer means the parse broke.'
		);

		$this->assertArrayHasKey( 'spectra/container', $keys, 'The container is always in the map.' );
		$this->assertContains( 'minHeight', $keys['spectra/container'], 'The container has always stored minHeight per device.' );
	}

	/**
	 * No attribute may be stored per device without a painter behind it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_every_editor_key_has_a_renderer_painting_it() {
		$orphans = array();

		foreach ( $this->editor_keys() as $block_name => $attributes ) {
			$painted = ResponsiveAttributeCSS::get_responsive_attributes( $block_name );

			foreach ( $attributes as $attribute ) {
				if ( ! in_array( $attribute, $painted, true ) ) {
					$orphans[] = $block_name . ' :: ' . $attribute;
				}
			}
		}

		$this->assertSame(
			array(),
			$orphans,
			"These attributes are stored per device but nothing paints them, so the value silently does nothing on the front end:\n- "
				. implode( "\n- ", $orphans )
		);
	}

	/**
	 * No painter may band an attribute the editor cannot author per device.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_no_painter_bands_an_attribute_the_editor_cannot_author() {
		$unreachable = array();

		foreach ( $this->editor_keys() as $block_name => $attributes ) {
			foreach ( ResponsiveAttributeCSS::get_responsive_attributes( $block_name ) as $painted ) {
				if ( ! in_array( $painted, $attributes, true ) ) {
					$unreachable[] = $block_name . ' :: ' . $painted;
				}
			}
		}

		$this->assertSame(
			array(),
			$unreachable,
			"These attributes are banded by the renderer but the editor never writes them per device, so no author can reach them. Either give them a per-device control or drop the painter:\n- "
				. implode( "\n- ", $unreachable )
		);
	}

	/**
	 * The extension point Pro depends on must keep working.
	 *
	 * Pro publishes its own blocks into the same map through this filter. If the
	 * filter stops being applied, every Pro block loses its banded CSS while the
	 * free plugin's own blocks carry on working — a failure that is invisible
	 * from inside this repository.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_third_party_definitions_can_be_added_through_the_filter() {
		$callback = static function ( $definitions ) {
			$definitions['spectra-pro/parity-probe'] = array(
				'gap' => array(
					'default'  => '10px',
					'property' => 'gap',
				),
			);

			return $definitions;
		};

		add_filter( 'spectra_blocks_responsive_attr_definitions', $callback );

		$painted = ResponsiveAttributeCSS::get_responsive_attributes( 'spectra-pro/parity-probe' );

		remove_filter( 'spectra_blocks_responsive_attr_definitions', $callback );

		$this->assertSame(
			array( 'gap' ),
			$painted,
			'A block added through the filter must be visible to the renderer.'
		);
	}

	/**
	 * An unknown block resolves to nothing rather than erroring.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_an_unknown_block_has_no_responsive_attributes() {
		$this->assertSame( array(), ResponsiveAttributeCSS::get_responsive_attributes( 'core/paragraph' ) );
	}
}
