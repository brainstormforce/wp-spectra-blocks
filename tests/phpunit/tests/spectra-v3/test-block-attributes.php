<?php
/**
 * Tests for BlockAttributes helper class.
 *
 * @package Spectra\Tests
 */

use Spectra\Helpers\BlockAttributes;

/**
 * BlockAttributes test case.
 */
class Test_Block_Attributes extends Spectra_Test_Case {

    /**
     * Test kebab case conversion.
     */
    public function test_to_kebab_case() {
        // Use reflection to test private method
        $reflection = new ReflectionClass( BlockAttributes::class );
        $method = $reflection->getMethod( 'to_kebab_case' );
        $method->setAccessible( true );
        
        // Test cases
        $test_cases = array(
            'backgroundColor' => 'background-color',
            'BorderRadius' => 'border-radius',
            'margin-top' => 'margin-top',
            'padding2xl' => 'padding-2xl',
            'boxShadowHorizontalOffset' => 'box-shadow-horizontal-offset',
            'color' => 'color',
            '' => '',
            'text_align' => 'text-align',
            'some_mixedCase' => 'some-mixed-case',
        );
        
        foreach ( $test_cases as $input => $expected ) {
            $result = $method->invoke( null, $input );
            $this->assertEquals( $expected, $result, "Failed for input: {$input}" );
        }
    }

    /**
     * Data provider for kebab case tests.
     *
     * @return array
     */
    public function kebab_case_provider() {
        return array(
            'camelCase'           => array( 'backgroundColor', 'background-color' ),
            'PascalCase'          => array( 'BorderRadius', 'border-radius' ),
            'already-kebab'       => array( 'margin-top', 'margin-top' ),
            'with numbers'        => array( 'padding2xl', 'padding2xl' ),
            'multiple words'      => array( 'boxShadowHorizontalOffset', 'box-shadow-horizontal-offset' ),
            'single word'         => array( 'color', 'color' ),
            'empty string'        => array( '', '' ),
            'with underscore'     => array( 'text_align', 'text-align' ),
            'mixed separators'    => array( 'some_mixedCase', 'some-mixed-case' ),
        );
    }

    /**
     * Test style and class generation.
     */
    public function test_generate_styles_and_classes_basic() {
        // Arrange
        $attributes = array(
            'color' => '#ff0000',
            'backgroundColor' => '#ffffff',
            'padding' => '20px',
            'margin' => '10px',
        );
        
        $configs = array(
            'color',
            'backgroundColor',
            'padding',
            'margin',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );
        
        list( $styles, $classes ) = $result;
        
        $this->assertIsArray( $styles );
        $this->assertIsArray( $classes );
        
        // Check that styles were generated
        $this->assertArrayHasKey( '--spectra-color', $styles );
        $this->assertEquals( '#ff0000', $styles['--spectra-color'] );
    }

    /**
     * Test responsive attribute handling.
     */
    public function test_generate_styles_with_responsive_values() {
        // Arrange
        $attributes = array(
            'padding' => array(
                'desktop' => '40px',
                'tablet' => '30px',
                'mobile' => '20px',
            ),
            'fontSize' => array(
                'desktop' => '18px',
                'tablet' => '16px',
                'mobile' => '14px',
            ),
        );
        
        $configs = array(
            'padding',
            'fontSize',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );
        
        list( $styles, $classes ) = $result;
        
        // The method only returns desktop styles, not responsive styles
        $this->assertArrayHasKey( '--spectra-padding', $styles );
        $this->assertArrayHasKey( '--spectra-font-size', $styles );
    }

    /**
     * Test unit value handling.
     */
    public function test_generate_styles_with_unit_values() {
        // Arrange
        $attributes = array(
            'width' => array(
                'value' => 100,
                'unit' => '%',
            ),
            'height' => array(
                'value' => 500,
                'unit' => 'px',
            ),
            'borderRadius' => array(
                'value' => 1.5,
                'unit' => 'rem',
            ),
        );
        
        $configs = array(
            'width',
            'height',
            'borderRadius',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        // Check CSS variables are set (not direct CSS properties)
        $this->assertArrayHasKey( '--spectra-width', $styles );
        $this->assertArrayHasKey( '--spectra-height', $styles );
        $this->assertArrayHasKey( '--spectra-border-radius', $styles );
    }

    /**
     * Test special class generation.
     */
    public function test_generate_styles_creates_special_classes() {
        // Arrange
        $attributes = array(
            'align' => 'center',
            'textAlign' => 'right',
            'verticalAlign' => 'middle',
        );
        
        $configs = array(
            'align',
            'textAlign',
            'verticalAlign',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        // Check classes are generated
        $this->assertContains( 'spectra-align', $classes );
        $this->assertContains( 'spectra-text-align', $classes );
        $this->assertContains( 'spectra-vertical-align', $classes );
    }

    /**
     * Test empty and invalid values.
     */
    public function test_generate_styles_handles_empty_values() {
        // Arrange
        $attributes = array(
            'color' => '',
            'padding' => null,
            'margin' => false,
            'backgroundColor' => 'transparent',
        );
        
        $configs = array(
            'color',
            'padding',
            'margin',
            'backgroundColor',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        // Empty values should not generate CSS variables
        $this->assertArrayNotHasKey( '--spectra-color', $styles );
        $this->assertArrayNotHasKey( '--spectra-padding', $styles );
        $this->assertArrayNotHasKey( '--spectra-margin', $styles );
        // But backgroundColor with 'transparent' should be included
        $this->assertArrayHasKey( '--spectra-background-color', $styles );
        $this->assertEquals( 'transparent', $styles['--spectra-background-color'] );
    }

    /**
     * Test wrapper attributes generation.
     */
    public function test_get_wrapper_attributes() {
        // Arrange
        $block_attributes = array(
            'className' => 'custom-class',
            'anchor' => 'section-1',
            'align' => 'wide',
            'backgroundColor' => '#f0f0f0',
            'textColor' => '#333333',
            'padding' => '20px',
        );
        
        $configs = array(
            'backgroundColor',
            'textColor',
            'padding',
        );
        
        $wrapper_config = array(
            'id' => $block_attributes['anchor'] ?? '',
            'class' => $block_attributes['className'] ?? '',
        );
        
        $custom_classes = array( 'spectra-block' );
        
        // Act
        $wrapper_attrs = BlockAttributes::get_wrapper_attributes( $block_attributes, $configs, $wrapper_config, $custom_classes );
        
        // Assert - method returns string of HTML attributes
        $this->assertIsString( $wrapper_attrs );
        
        // Check that attributes are in the string
        $this->assertStringContainsString( 'class=', $wrapper_attrs );
        $this->assertStringContainsString( 'id=', $wrapper_attrs );
        $this->assertStringContainsString( 'style=', $wrapper_attrs );
        
        $this->assertStringContainsString( 'spectra-block', $wrapper_attrs );
        $this->assertStringContainsString( 'custom-class', $wrapper_attrs );
        $this->assertStringContainsString( 'section-1', $wrapper_attrs );
        
        // CSS variables should be in the style attribute
        $this->assertStringContainsString( '--spectra-background-color', $wrapper_attrs );
        $this->assertStringContainsString( '--spectra-text-color', $wrapper_attrs );
    }

    /**
     * Test gradient background handling.
     */
    public function test_generate_styles_with_gradient() {
        // Arrange
        $attributes = array(
            'backgroundGradient' => 'linear-gradient(90deg, #ff0000 0%, #0000ff 100%)',
        );
        
        $configs = array(
            'backgroundGradient',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        $this->assertArrayHasKey( '--spectra-background-gradient', $styles );
        $this->assertEquals( 'linear-gradient(90deg, #ff0000 0%, #0000ff 100%)', $styles['--spectra-background-gradient'] );
    }

    /**
     * Test border attributes.
     */
    public function test_generate_styles_with_border_attributes() {
        // Arrange
        $attributes = array(
            'borderWidth' => '2px',
            'borderColor' => '#000000',
            'borderStyle' => 'solid',
            'borderRadius' => '8px',
        );
        
        $configs = array(
            'borderWidth',
            'borderColor',
            'borderStyle',
            'borderRadius',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        $this->assertArrayHasKey( '--spectra-border-width', $styles );
        $this->assertArrayHasKey( '--spectra-border-color', $styles );
        $this->assertArrayHasKey( '--spectra-border-style', $styles );
        $this->assertArrayHasKey( '--spectra-border-radius', $styles );
    }

    /**
     * Test shadow attributes.
     */
    public function test_generate_styles_with_shadow() {
        // Arrange
        $attributes = array(
            'boxShadow' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'textShadow' => '2px 2px 4px rgba(0, 0, 0, 0.5)',
        );
        
        $configs = array(
            'boxShadow',
            'textShadow',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        $this->assertArrayHasKey( '--spectra-box-shadow', $styles );
        $this->assertArrayHasKey( '--spectra-text-shadow', $styles );
    }

    /**
     * Test complex nested attributes.
     */
    public function test_generate_styles_with_complex_attributes() {
        // Arrange
        $attributes = array(
            'typography' => array(
                'fontSize' => '16px',
                'lineHeight' => '1.5',
                'fontWeight' => 'bold',
                'fontFamily' => 'Arial, sans-serif',
            ),
            'spacing' => array(
                'margin' => array(
                    'top' => '10px',
                    'right' => '20px',
                    'bottom' => '10px',
                    'left' => '20px',
                ),
                'padding' => array(
                    'top' => '15px',
                    'right' => '25px',
                    'bottom' => '15px',
                    'left' => '25px',
                ),
            ),
        );
        
        $configs = array(
            'typography',
            'spacing',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        // Complex nested attributes should be stored as CSS variables
        $this->assertArrayHasKey( '--spectra-typography', $styles );
        $this->assertArrayHasKey( '--spectra-spacing', $styles );
    }

    /**
     * Test CSS variable generation.
     */
    public function test_generate_styles_with_css_variables() {
        // Arrange
        $attributes = array(
            '--spectra-primary-color' => '#007cba',
            '--spectra-secondary-color' => '#f0f0f0',
            'color' => 'var(--spectra-primary-color)',
            'backgroundColor' => 'var(--spectra-secondary-color)',
        );
        
        $configs = array(
            '--spectra-primary-color',
            '--spectra-secondary-color',
            'color',
            'backgroundColor',
        );
        
        // Act
        $result = BlockAttributes::generate_styles_and_classes( $attributes, $configs );
        
        // Assert - method returns array( $styles, $classes )
        list( $styles, $classes ) = $result;
        
        // CSS variables should pass through
        $this->assertArrayHasKey( '--spectra-color', $styles );
        $this->assertArrayHasKey( '--spectra-background-color', $styles );
    }
}