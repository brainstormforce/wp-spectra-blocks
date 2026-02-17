<?php
/**
 * Tests for Image Mask Extension.
 *
 * @package Spectra\Tests
 */

use Spectra\Extensions\ImageMask;

/**
 * Image Mask Extension test case.
 */
class Test_Extensions_Image_Mask extends Spectra_Test_Case {

    /**
     * The ImageMask instance.
     *
     * @var ImageMask
     */
    private $image_mask;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Define required constants if not defined
        if ( ! defined( 'SPECTRA_3_DIR' ) ) {
            define( 'SPECTRA_3_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/spectra-v3/' );
        }
        if ( ! defined( 'SPECTRA_3_URL' ) ) {
            define( 'SPECTRA_3_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg/spectra-v3/' );
        }
        
        // Get ImageMask instance
        $this->image_mask = ImageMask::instance();
    }

    /**
     * Test that ImageMask implements singleton pattern correctly.
     */
    public function test_singleton_instance_is_consistent() {
        // Arrange & Act
        $instance1 = ImageMask::instance();
        $instance2 = ImageMask::instance();
        
        // Assert
        $this->assertSame( 
            $instance1, 
            $instance2,
            'ImageMask::instance() should always return the same object'
        );
    }

    /**
     * Test that init method registers necessary hooks.
     */
    public function test_init_registers_required_hooks() {
        // Arrange
        $this->reset_singleton( '\Spectra\Extensions\ImageMask' );
        $image_mask = ImageMask::instance();
        
        // Act
        $image_mask->init();
        
        // Assert
        $this->assert_hook_added( 
            'render_block', 
            $image_mask, 
            'add_mask_styles',
            10
        );
    }

    /**
     * Test that render_block filter properly applies masks.
     */
    public function test_render_block_applies_mask_to_images() {
        // Check if WP_HTML_Tag_Processor exists
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $this->markTestSkipped( 'WP_HTML_Tag_Processor not available in test environment' );
        }
        
        // Arrange
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" class="wp-image-123" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'circle',
                    'size' => 'contain',
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert that style attributes were added
        $this->assertStringContainsString( 
            '--spectra-mask-image:url(',
            $filtered_content,
            'Filtered content should include mask image CSS variable'
        );
        $this->assertStringContainsString( 
            '--spectra-mask-size:contain',
            $filtered_content,
            'Filtered content should include mask size CSS variable'
        );
        $this->assertStringContainsString( 
            '--spectra-mask-position:50% 50%',
            $filtered_content,
            'Filtered content should include mask position CSS variable'
        );
        $this->assertStringContainsString( 
            '--spectra-mask-repeat:no-repeat',
            $filtered_content,
            'Filtered content should include mask repeat CSS variable'
        );
    }

    /**
     * Test that masks are not applied to non-image blocks.
     */
    public function test_mask_not_applied_to_non_image_blocks() {
        // Arrange
        $block_content = '<p>This is a paragraph block</p>';
        $block = array(
            'blockName' => 'core/paragraph',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'circle',
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertEquals( 
            $block_content,
            $filtered_content,
            'Non-image blocks should not be modified'
        );
    }

    /**
     * Test that mask is not applied when shape is 'none'.
     */
    public function test_mask_not_applied_when_shape_is_none() {
        // Arrange
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'none',
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertEquals( 
            $block_content,
            $filtered_content,
            'Block with shape "none" should not be modified'
        );
    }

    /**
     * Test that mask is not applied when spectraMask attribute is missing.
     */
    public function test_mask_not_applied_without_spectra_mask() {
        // Arrange
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertEquals( 
            $block_content,
            $filtered_content,
            'Block without spectraMask attribute should not be modified'
        );
    }

    /**
     * Test custom mask shape with custom image.
     */
    public function test_custom_mask_shape_with_image() {
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $this->markTestSkipped( 'WP_HTML_Tag_Processor not available in test environment' );
        }
        
        // Arrange
        $custom_image_url = 'https://example.com/custom-mask.svg';
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'custom',
                    'image' => array(
                        'url' => $custom_image_url,
                    ),
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertStringContainsString( 
            esc_url( $custom_image_url ),
            $filtered_content,
            'Custom mask should use provided image URL'
        );
    }

    /**
     * Test mask position handling.
     */
    public function test_mask_position_values() {
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $this->markTestSkipped( 'WP_HTML_Tag_Processor not available in test environment' );
        }
        
        // Test with specific position
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'circle',
                    'position' => array(
                        'x' => 0.25,
                        'y' => 0.75,
                    ),
                ),
            ),
        );
        
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        $this->assertStringContainsString( 
            '--spectra-mask-position:25% 75%',
            $filtered_content,
            'Mask position should be calculated correctly'
        );
    }

    /**
     * Test mask with all attributes.
     */
    public function test_mask_with_all_attributes() {
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $this->markTestSkipped( 'WP_HTML_Tag_Processor not available in test environment' );
        }
        
        // Arrange
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'hexagon',
                    'size' => 'cover',
                    'position' => array(
                        'x' => 0.5,
                        'y' => 0.5,
                    ),
                    'repeat' => 'repeat',
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertStringContainsString( 'hexagon.svg', $filtered_content );
        $this->assertStringContainsString( '--spectra-mask-size:cover', $filtered_content );
        $this->assertStringContainsString( '--spectra-mask-position:50% 50%', $filtered_content );
        $this->assertStringContainsString( '--spectra-mask-repeat:repeat', $filtered_content );
    }

    /**
     * Test allowed shapes via reflection.
     */
    public function test_allowed_shapes_property() {
        $reflection = new ReflectionClass( $this->image_mask );
        
        // Check that allowed_shapes property exists
        $this->assertTrue(
            $reflection->hasProperty( 'allowed_shapes' ),
            'ImageMask should have allowed_shapes property'
        );
        
        // Access the property to verify it contains expected shapes
        $property = $reflection->getProperty( 'allowed_shapes' );
        $property->setAccessible( true );
        $allowed_shapes = $property->getValue( $this->image_mask );
        
        // Check for some expected shapes
        $expected_shapes = array( 'blob1', 'circle', 'diamond', 'hexagon', 'rounded', 'custom' );
        foreach ( $expected_shapes as $shape ) {
            $this->assertContains(
                $shape,
                $allowed_shapes,
                "Shape '{$shape}' should be in allowed shapes"
            );
        }
    }

    /**
     * Test private methods exist.
     */
    public function test_private_methods_exist() {
        $reflection = new ReflectionClass( $this->image_mask );
        
        // Check that private methods exist
        $expected_methods = array(
            'get_mask_url',
            'get_position_value',
        );
        
        foreach ( $expected_methods as $method_name ) {
            $this->assertTrue(
                $reflection->hasMethod( $method_name ),
                "Private method '{$method_name}' should exist"
            );
            
            if ( $reflection->hasMethod( $method_name ) ) {
                $method = $reflection->getMethod( $method_name );
                $this->assertTrue(
                    $method->isPrivate(),
                    "Method '{$method_name}' should be private"
                );
            }
        }
    }

    /**
     * Test mask handles invalid shape gracefully.
     */
    public function test_mask_handles_invalid_shape() {
        // Arrange
        $block_content = '<figure class="wp-block-image"><img src="test.jpg" /></figure>';
        $block = array(
            'blockName' => 'core/image',
            'attrs' => array(
                'spectraMask' => array(
                    'shape' => 'invalid-shape',
                ),
            ),
        );
        
        // Act
        $filtered_content = $this->image_mask->add_mask_styles( $block_content, $block );
        
        // Assert
        $this->assertEquals( 
            $block_content,
            $filtered_content,
            'Invalid shape should result in no modification'
        );
    }

    /**
     * Test edge cases for position values.
     */
    public function test_position_edge_cases() {
        $reflection = new ReflectionClass( $this->image_mask );
        
        if ( $reflection->hasMethod( 'get_position_value' ) ) {
            $method = $reflection->getMethod( 'get_position_value' );
            $method->setAccessible( true );
            
            // Test null position
            $result = $method->invoke( $this->image_mask, null );
            $this->assertEquals( '50% 50%', $result, 'Null position should return default' );
            
            // Test empty array
            $result = $method->invoke( $this->image_mask, array() );
            $this->assertEquals( '50% 50%', $result, 'Empty array should return default' );
            
            // Test out of bounds values
            $result = $method->invoke( $this->image_mask, array( 'x' => 2, 'y' => -1 ) );
            $this->assertEquals( '100% 0%', $result, 'Out of bounds values should be clamped' );
        }
    }
}