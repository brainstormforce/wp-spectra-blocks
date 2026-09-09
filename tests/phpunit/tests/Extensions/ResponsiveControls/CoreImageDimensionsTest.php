<?php
/**
 * Where core renders viewport states, Spectra leaves core/image's dimensions to core.
 *
 * On WordPress 7.1 the image's per-device width, height, aspect ratio and
 * object-fit live in core's own `style['@tablet'].dimensions` shape and core
 * renders them, banded. Spectra's flat `width` / `height` / `aspectRatio` /
 * `scale` keys never receive a per-device value there, so painting them wrote
 * the DESKTOP dimensions into the tablet and mobile bands — a duplicate of
 * core's output that was also wrong, and only invisible because core's rules
 * carry `!important`. Spectra also stripped the img's inline style, which on
 * 7.1 is core's own base output.
 *
 * These tests run where core renders states (the 7.1 test library); the
 * legacy path, where Spectra paints the flat keys itself, is unchanged and is
 * exercised by the 7.0.4 site.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;
use WP_UnitTestCase;

/**
 * CoreImageDimensionsTest test case.
 *
 * @since x.x.x
 */
class CoreImageDimensionsTest extends WP_UnitTestCase {

	/**
	 * A core/image with a desktop size and a core-shaped tablet size.
	 *
	 * @since x.x.x
	 */
	private const MARKUP = '<!-- wp:image {"spectraId":"cimg","width":"500px","height":"300px","scale":"cover","style":{"@tablet":{"dimensions":{"width":"350px","height":"220px"}}}} --><figure class="wp-block-image"><img src="http://x/a.png" alt="" style="width:500px;height:300px;object-fit:cover"/></figure><!-- /wp:image -->';

	/**
	 * Render the fixture and return Spectra's inline CSS and the HTML.
	 *
	 * @since x.x.x
	 * @return array{0: string, 1: string} CSS, HTML.
	 */
	private function render(): array {
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		$html = do_blocks( self::MARKUP );
		$css  = preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );

		return array( (string) $css, $html );
	}

	/**
	 * Spectra emits no dimension rules for the image.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_no_dimension_rules_are_emitted_where_core_renders_states() {
		if ( ! ViewportSupport::renders_states() ) {
			$this->markTestSkipped( 'Core renders no viewport states here; Spectra paints the image itself on this path.' );
		}

		list( $css ) = $this->render();

		$this->assertDoesNotMatchRegularExpression( "/\\[data-spectra-id='cimg'\\] img\\{[^}]*(width|height|object-fit|aspect-ratio)/", $css, 'Core paints the image\'s dimensions per band; Spectra must not paint a desktop copy into every band.' );
	}

	/**
	 * The img keeps core's inline base style.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_core_inline_style_is_left_alone_where_core_renders_states() {
		if ( ! ViewportSupport::renders_states() ) {
			$this->markTestSkipped( 'Core renders no viewport states here.' );
		}

		list( , $html ) = $this->render();

		$this->assertMatchesRegularExpression( '/<img[^>]*style="[^"]*width:500px/', $html, 'The inline base dimensions are core\'s output and must survive.' );
	}
}
