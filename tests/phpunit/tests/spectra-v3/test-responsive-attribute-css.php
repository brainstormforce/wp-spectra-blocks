<?php
/**
 * ResponsiveAttributeCSS Test
 *
 * @package Spectra\Tests
 * @since 0.0.1
 */

use Spectra\Extensions\ResponsiveControls\ResponsiveAttributeCSS;

/**
 * Test the ResponsiveAttributeCSS functionality.
 * 
 * @since 0.0.1
 * 
 * @coversDefaultClass \Spectra\Extensions\ResponsiveControls\ResponsiveAttributeCSS
 */
class Test_ResponsiveAttributeCSS extends Spectra_Test_Case {

	/**
	 * Set up test environment.
	 * 
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test get_responsive_attributes for existing block.
	 * 
	 * @covers ::get_responsive_attributes
	 * @return void
	 */
	public function test_get_responsive_attributes_existing_block() {
		// Test container block.
		$attrs = ResponsiveAttributeCSS::get_responsive_attributes( 'spectra/container' );
		
		$expected = array( 'minWidth', 'minHeight', 'maxWidth', 'maxHeight', 'width', 'height', 'background' );
		$this->assertEquals( $expected, $attrs );
	}

	/**
	 * Test get_responsive_attributes for non-existing block.
	 * 
	 * @covers ::get_responsive_attributes
	 * @return void
	 */
	public function test_get_responsive_attributes_non_existing_block() {
		// Test non-existing block.
		$attrs = ResponsiveAttributeCSS::get_responsive_attributes( 'core/paragraph' );
		
		$this->assertEquals( array(), $attrs );
	}

	/**
	 * Test get_responsive_attributes with filter applied.
	 * 
	 * @covers ::get_responsive_attributes
	 * @return void
	 */
	public function test_get_responsive_attributes_with_filter() {
		// Add filter to modify definitions.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			$defs['custom/block'] = array(
				'customAttr' => array( 'property' => 'custom-prop' ),
			);
			return $defs;
		} );

		// Test custom block.
		$attrs = ResponsiveAttributeCSS::get_responsive_attributes( 'custom/block' );
		
		$this->assertEquals( array( 'customAttr' ), $attrs );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css for non-existing block.
	 * 
	 * @covers ::generate_css
	 * @return void
	 */
	public function test_generate_css_non_existing_block() {
		// Test non-existing block.
		$css = ResponsiveAttributeCSS::generate_css(
			'core/paragraph',
			array(),
			'.wp-block-paragraph'
		);
		
		$this->assertEquals( '', $css );
	}

	/**
	 * Test generate_css with basic property.
	 * 
	 * @covers ::generate_css
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_generate_css_basic_property() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test container width.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/container',
			array( 'width' => '100px' ),
			'.test-selector'
		);
		
		// CSS should contain width property.
		$this->assertStringContainsString( 'width', $css );
		$this->assertStringContainsString( '100px', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with default value.
	 * 
	 * @covers ::generate_css
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_generate_css_default_value() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test container height with no value (should use default).
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/container',
			array(), // No height attribute.
			'.test-selector'
		);
		
		// CSS should contain default height value.
		$this->assertStringContainsString( 'height', $css );
		$this->assertStringContainsString( 'auto', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with SVG formatter.
	 * 
	 * @covers ::generate_css
	 * @covers ::format_svg_size
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_generate_css_svg_formatter() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test icon size.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/icon',
			array( 'size' => '24px' ),
			'.test-selector'
		);
		
		// CSS should contain width and height for SVG.
		$this->assertStringContainsString( 'width', $css );
		$this->assertStringContainsString( 'height', $css );
		$this->assertStringContainsString( '24px', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with array property.
	 * 
	 * @covers ::generate_css
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_generate_css_array_property() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test slider navigation size.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/slider',
			array( 'navigationSize' => '40px' ),
			'.test-selector'
		);
		
		// CSS should contain navigation button styles.
		$this->assertStringContainsString( 'swiper-button', $css );
		$this->assertStringContainsString( '40px', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with text shadow (spectra/content).
	 * 
	 * @covers ::generate_css
	 * @return void
	 */
	public function test_generate_css_text_shadow() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test text shadow enabled.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/content',
			array(
				'enableTextShadow'  => true,
				'textShadowColor'   => '#000000',
				'textShadowBlur'    => 3,
				'textShadowOffsetX' => 2,
				'textShadowOffsetY' => 2,
			),
			'.test-selector'
		);
		
		// CSS should contain text-shadow.
		$this->assertStringContainsString( 'text-shadow', $css );
		$this->assertStringContainsString( '2px 2px 3px #000000', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with text shadow disabled.
	 * 
	 * @covers ::generate_css
	 * @return void
	 */
	public function test_generate_css_text_shadow_disabled() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test text shadow disabled.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/content',
			array(
				'enableTextShadow'  => false,
				'textShadowColor'   => '#000000',
			),
			'.test-selector'
		);
		
		// CSS should not contain text-shadow when disabled.
		$this->assertStringNotContainsString( 'text-shadow', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with modal popup content.
	 * 
	 * @covers ::generate_css
	 * @return void
	 */
	public function test_generate_css_modal_popup_content() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test custom content height.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/modal-popup-content',
			array(
				'contentHeight'   => 'custom',
				'containerWidth'  => '800px',
				'containerHeight' => '500px',
			),
			'.test-selector'
		);
		
		// CSS should contain width and height.
		$this->assertStringContainsString( 'width', $css );
		$this->assertStringContainsString( '800px', $css );
		$this->assertStringContainsString( 'height', $css );
		$this->assertStringContainsString( '500px', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with background formatter - null value.
	 * 
	 * @covers ::generate_css
	 * @covers ::format_background
	 * @return void
	 */
	public function test_generate_css_background_null() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test null background.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/container',
			array( 'background' => null ),
			'.test-selector'
		);
		
		// CSS should hide video wrapper for null background.
		$this->assertStringContainsString( 'display: none !important', $css );
		$this->assertStringContainsString( 'spectra-background-video__wrapper', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test generate_css with background formatter - image type.
	 * 
	 * @covers ::generate_css
	 * @covers ::format_background
	 * @return void
	 */
	public function test_generate_css_background_image() {
		// Add filter to ensure definitions are available.
		add_filter( 'spectra_responsive_attr_definitions', function( $defs ) {
			return ResponsiveAttributeCSS::ATTR_DEFINITIONS;
		} );

		// Test image background.
		$css = ResponsiveAttributeCSS::generate_css(
			'spectra/container',
			array(
				'background' => array(
					'type'  => 'image',
					'media' => array(
						'url' => 'https://example.com/image.jpg',
					),
				),
			),
			'.test-selector'
		);
		
		// Should contain background-image and hide video wrapper.
		$this->assertStringContainsString( 'background-image', $css );
		$this->assertStringContainsString( 'display: none !important', $css );
		
		// Clean up filter.
		remove_all_filters( 'spectra_responsive_attr_definitions' );
	}

	/**
	 * Test build_full_selector with simple selector.
	 * 
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_build_full_selector_simple() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'build_full_selector' );
		$method->setAccessible( true );

		$def = array(
			'selector' => ' .child',
		);

		$result = $method->invoke( null, '.parent', $def );
		
		$this->assertEquals( '.parent .child', $result );
	}

	/**
	 * Test build_full_selector with state.
	 * 
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_build_full_selector_with_state() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'build_full_selector' );
		$method->setAccessible( true );

		$def = array(
			'selector' => ' .child',
			'state'    => ':hover',
		);

		$result = $method->invoke( null, '.parent', $def );
		
		$this->assertEquals( '.parent .child:hover', $result );
	}

	/**
	 * Test build_full_selector with comma-separated selectors.
	 * 
	 * @covers ::build_full_selector
	 * @return void
	 */
	public function test_build_full_selector_comma_separated() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'build_full_selector' );
		$method->setAccessible( true );

		$def = array(
			'selector' => ' .prev, .next',
		);

		$result = $method->invoke( null, '.parent', $def );
		
		$this->assertEquals( '.parent .prev, .parent .next', $result );
	}

	/**
	 * Test format_svg_size formatter.
	 * 
	 * @covers ::format_svg_size
	 * @return void
	 */
	public function test_format_svg_size() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'format_svg_size' );
		$method->setAccessible( true );

		// Test with value.
		$result = $method->invoke( null, '32px' );
		$this->assertEquals( array( 'width' => '32px', 'height' => '32px' ), $result );

		// Test with null.
		$result = $method->invoke( null, null );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test format_slider_arrow_distance formatter.
	 * 
	 * @covers ::format_slider_arrow_distance
	 * @return void
	 */
	public function test_format_slider_arrow_distance() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'format_slider_arrow_distance' );
		$method->setAccessible( true );

		// Test with value.
		$result = $method->invoke( null, '10px' );
		
		$expected = array(
			array(
				'selector'     => ' .swiper-button-prev',
				'declarations' => array( 'left' => '10px' ),
			),
			array(
				'selector'     => ' .swiper-button-next',
				'declarations' => array( 'right' => '10px' ),
			),
		);
		
		$this->assertEquals( $expected, $result );

		// Test with null (should use default).
		$result = $method->invoke( null, null );
		$this->assertEquals( '1px', $result[0]['declarations']['left'] );
	}

	/**
	 * Test format_slider_pagination_top_margin formatter.
	 * 
	 * @covers ::format_slider_pagination_top_margin
	 * @return void
	 */
	public function test_format_slider_pagination_top_margin() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( ResponsiveAttributeCSS::class );
		$method     = $reflection->getMethod( 'format_slider_pagination_top_margin' );
		$method->setAccessible( true );

		// Test with value.
		$result = $method->invoke( null, '20px' );
		
		$this->assertCount( 2, $result );
		$this->assertEquals( '20px', $result[0]['declarations']['bottom'] );
		$this->assertEquals( 'transform: translateY(20px);', $result[1]['style_attr'] );

		// Test with null.
		$result = $method->invoke( null, null );
		$this->assertEquals( '6px', $result[0]['declarations']['bottom'] );
		$this->assertEquals( 'transform: translateY(0%);', $result[1]['style_attr'] );
	}
}