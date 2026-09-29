<?php
/**
 * core/image paints border AND shadow on the img, never on the figure.
 *
 * A block may declare in `block.json` that a support belongs to an inner
 * element rather than to its wrapper. `core/image` does exactly that: both
 * `border` and `shadow` resolve to `.wp-block-image img`, because the picture
 * is what carries the radius and the frame — the `<figure>` is just a box
 * around it at content width.
 *
 * Spectra's instance selector targets that wrapper. Border was routed to the
 * `img` by a hardcoded suffix, but shadow was swept along with everything else
 * onto the figure, so the page painted the shadow TWICE: once correctly on the
 * 200px rounded picture, and once on the 645px square-cornered figure, which
 * read as a hard black line running off to the side of the image. The editor
 * showed one shadow and the front end showed two, because this CSS is emitted
 * in PHP on `render_block` and a static block's editor canvas never sees it.
 *
 * The regression these tests guard is narrow and easy to reintroduce: any
 * future support added to the element-level group, or any refactor of the
 * grouping, can silently put a declaration back on the wrapper. Asserting that
 * the bare wrapper selector never receives `box-shadow` is the assertion that
 * actually fails when that happens — asserting only that the `img` rule exists
 * would keep passing while the duplicate came back.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use ReflectionMethod;
use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * CoreImageShadowSelectorTest test case.
 *
 * @since 1.0.10
 */
class CoreImageShadowSelectorTest extends WP_UnitTestCase {

	/**
	 * Build a core/image fixture carrying the given `style` attribute.
	 *
	 * @since 1.0.10
	 *
	 * @param string $spectra_id The block instance id to target in assertions.
	 * @param array  $style      The block's `style` attribute.
	 * @return string Block markup.
	 */
	private function image( $spectra_id, $style ) {
		$attrs = wp_json_encode(
			array(
				'spectraId' => $spectra_id,
				'style'     => $style,
			)
		);

		return '<!-- wp:image ' . $attrs . ' --><figure class="wp-block-image">' .
			'<img src="http://example.org/a.png" alt=""/></figure><!-- /wp:image -->';
	}

	/**
	 * Render markup and return the responsive CSS Spectra emitted for it.
	 *
	 * @since 1.0.10
	 *
	 * @param string $markup Block markup to render.
	 * @return string Whitespace-collapsed CSS.
	 */
	private function render( $markup ) {
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		do_blocks( $markup );

		$data = wp_styles()->get_data( 'spectra-responsive-styles', 'after' );

		return (string) preg_replace( '/\s+/', ' ', implode( '', (array) ( $data ? $data : array() ) ) );
	}

	/**
	 * Assert the shadow reached the img and never the bare wrapper.
	 *
	 * @since 1.0.10
	 *
	 * @param string $css        The emitted CSS.
	 * @param string $spectra_id The instance id under test.
	 * @return void
	 */
	private function assertShadowOnImgOnly( $css, $spectra_id ) {
		$this->assertMatchesRegularExpression(
			'/\[data-spectra-id=\'' . $spectra_id . '\'\] img[^{]*\{[^}]*box-shadow/',
			$css,
			'The shadow must be emitted against the selector core declares for it, `.wp-block-image img`.'
		);

		$this->assertDoesNotMatchRegularExpression(
			'/\[data-spectra-id=\'' . $spectra_id . '\'\]\{[^}]*box-shadow/',
			$css,
			'The figure must not also paint the shadow — that is the duplicate, square-cornered one beside the picture.'
		);
	}

	/**
	 * The reported bug: shadow alongside a border went to the figure.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_shadow_is_emitted_on_the_img_not_the_figure() {
		$css = $this->render(
			$this->image(
				'sbshadow1',
				array(
					'shadow' => 'var:preset|shadow|crisp',
					'border' => array(
						'width'  => '6px',
						'radius' => '10px',
					),
				)
			)
		);

		$this->assertShadowOnImgOnly( $css, 'sbshadow1' );
	}

	/**
	 * A shadow with no border at all must be scoped the same way.
	 *
	 * This is the case the old code could not reach: the branch that routed
	 * anything to the `img` was entered only when a border was present, so an
	 * image styled with a shadow alone fell through to the generic path and
	 * painted on the figure every time.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_shadow_without_a_border_is_still_scoped_to_the_img() {
		$css = $this->render(
			$this->image( 'sbshadow2', array( 'shadow' => 'var:preset|shadow|natural' ) )
		);

		$this->assertShadowOnImgOnly( $css, 'sbshadow2' );
	}

	/**
	 * Border keeps the scoping it already had.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_border_remains_scoped_to_the_img() {
		$css = $this->render(
			$this->image(
				'sbshadow3',
				array(
					'border' => array(
						'width' => '6px',
						'color' => '#f00',
					),
				)
			)
		);

		$this->assertMatchesRegularExpression(
			'/\[data-spectra-id=\'sbshadow3\'\] img[^{]*\{[^}]*border-width/',
			$css,
			'Border belongs to the img and must stay there.'
		);

		$this->assertDoesNotMatchRegularExpression(
			'/\[data-spectra-id=\'sbshadow3\'\]\{[^}]*border-width/',
			$css,
			'Border must not leak back onto the figure.'
		);
	}

	/**
	 * Only the element-level supports move; everything else stays on the figure.
	 *
	 * Spacing is the counter-case that keeps the grouping honest. Margin is the
	 * figure's own box, so widening the element-level group far enough to take
	 * it would be a new bug of the same shape, in the other direction.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_wrapper_level_styles_stay_on_the_figure() {
		$css = $this->render(
			$this->image(
				'sbshadow4',
				array(
					'shadow'  => 'var:preset|shadow|crisp',
					'spacing' => array( 'margin' => array( 'top' => '40px' ) ),
				)
			)
		);

		$this->assertMatchesRegularExpression(
			'/\[data-spectra-id=\'sbshadow4\'\]\{[^}]*margin-top/',
			$css,
			'Margin is the figure\'s own box and belongs on the wrapper.'
		);

		$this->assertDoesNotMatchRegularExpression(
			'/\[data-spectra-id=\'sbshadow4\'\] img[^{]*\{[^}]*margin-top/',
			$css,
			'Margin must not be dragged onto the img with the element-level supports.'
		);
	}

	/**
	 * No breakpoint paints the shadow on the figure.
	 *
	 * The CSS is generated per device band, so a selector fixed in the base band
	 * alone would still ship the duplicate on tablet and mobile.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_no_band_paints_the_shadow_on_the_figure() {
		$css = $this->render(
			$this->image(
				'sbshadow5',
				array(
					'shadow'  => 'var:preset|shadow|natural',
					'@tablet' => array( 'shadow' => 'var:preset|shadow|deep' ),
					'@mobile' => array( 'shadow' => 'var:preset|shadow|sharp' ),
				)
			)
		);

		$this->assertDoesNotMatchRegularExpression(
			'/\[data-spectra-id=\'sbshadow5\'\]\{[^}]*box-shadow/',
			$css,
			'Every band must scope the shadow to the img, not just the base one.'
		);

		$this->assertGreaterThan(
			1,
			preg_match_all( '/\[data-spectra-id=\'sbshadow5\'\] img[^{]*\{[^}]*box-shadow/', $css ),
			'The per-device shadows should each produce an img-scoped rule.'
		);
	}

	/**
	 * A block that declares no shadow selector keeps the wrapper selector.
	 *
	 * This is the property that leaves every Spectra block untouched: none of
	 * them declare `selectors` in block.json, so the resolver must hand back the
	 * instance selector unchanged rather than inventing a descendant that
	 * matches nothing.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public function test_a_block_declaring_no_shadow_selector_keeps_the_wrapper_selector() {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'core/paragraph' );

		$this->assertNotNull( $block_type, 'core/paragraph should be registered.' );
		$this->assertNull(
			wp_get_block_css_selector( $block_type, 'shadow' ),
			'This test is only meaningful while core/paragraph declares no shadow selector.'
		);

		$method = new ReflectionMethod( ResponsiveControls::class, 'scope_selector_to_target' );
		$method->setAccessible( true );

		$selector = ".wp-block-paragraph[data-spectra-id='sbshadow6']";

		$this->assertSame(
			$selector,
			$method->invoke(
				( new \ReflectionClass( ResponsiveControls::class ) )->newInstanceWithoutConstructor(),
				'core/paragraph',
				$selector,
				'shadow'
			),
			'Blocks that declare no element selector must keep the wrapper selector.'
		);
	}
}
