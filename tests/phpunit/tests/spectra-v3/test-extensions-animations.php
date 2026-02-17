<?php
/**
 * Tests for Animations Extension.
 *
 * @package Spectra\Tests
 */

use Spectra\Extensions\Animations;

/**
 * Animations Extension test case.
 */
class Test_Extensions_Animations extends Spectra_Test_Case {

    /**
     * The Animations instance.
     *
     * @var Animations
     */
    private $animations;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Define required constants if not defined
        if ( ! defined( 'SPECTRA_URL' ) ) {
            define( 'SPECTRA_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg/' );
        }
        if ( ! defined( 'SPECTRA_VER' ) ) {
            define( 'SPECTRA_VER', '1.0.0' );
        }
        if ( ! defined( 'SPECTRA_3_URL' ) ) {
            define( 'SPECTRA_3_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg/spectra-v3/' );
        }
        
        // Get Animations instance
        $this->animations = Animations::instance();
    }

    /**
     * Test that Animations implements singleton pattern correctly.
     */
    public function test_singleton_returns_same_instance() {
        // Arrange & Act
        $instance1 = Animations::instance();
        $instance2 = Animations::instance();
        
        // Assert
        $this->assertSame( 
            $instance1, 
            $instance2,
            'Animations should return the same instance'
        );
    }

    /**
     * Test that init method registers required hooks.
     */
    public function test_init_registers_required_hooks() {
        // Arrange
        $this->reset_singleton( '\Spectra\Extensions\Animations' );
        $animations = Animations::instance();
        
        // Act
        $animations->init();
        
        // Assert - check actual hooks from implementation
        $this->assert_hook_added( 
            'render_block', 
            $animations, 
            'add_animation_attributes_to_blocks',
            10
        );
        
        $this->assert_hook_added( 
            'enqueue_block_assets', 
            $animations, 
            'enqueue_block_assets',
            10
        );
        
        $this->assert_hook_added( 
            'wp_footer', 
            $animations, 
            'handle_frontend_assets',
            10
        );
    }

    /**
     * Test that handle_frontend_assets is public and callable.
     */
    public function test_handle_frontend_assets_method_exists() {
        $this->assertTrue( 
            method_exists( $this->animations, 'handle_frontend_assets' ),
            'handle_frontend_assets method should exist'
        );
    }

    /**
     * Test that enqueue_block_assets is public and callable.
     */
    public function test_enqueue_block_assets_method_exists() {
        $this->assertTrue( 
            method_exists( $this->animations, 'enqueue_block_assets' ),
            'enqueue_block_assets method should exist'
        );
    }

    /**
     * Test add_animation_attributes_to_blocks returns unmodified content without animation type.
     */
    public function test_block_without_animation_type_returns_unmodified() {
        // Arrange
        $block_content = '<div class="test-block">Test content</div>';
        $block = array(
            'blockName' => 'spectra/test-block',
            'attrs' => array(
                // No spectraAnimationType
            ),
        );
        
        // Act
        $result = $this->animations->add_animation_attributes_to_blocks( $block_content, $block );
        
        // Assert
        $this->assertEquals( $block_content, $result, 'Block without animation type should not be modified' );
    }

    /**
     * Test add_animation_attributes_to_blocks returns unmodified content for non-allowed blocks.
     */
    public function test_non_allowed_block_returns_unmodified() {
        // Arrange
        $block_content = '<div class="test-block">Test content</div>';
        $block = array(
            'blockName' => 'some-other/block',
            'attrs' => array(
                'spectraAnimationType' => 'fade-in',
            ),
        );
        
        // Act
        $result = $this->animations->add_animation_attributes_to_blocks( $block_content, $block );
        
        // Assert
        $this->assertEquals( $block_content, $result, 'Non-allowed blocks should not be modified' );
    }

    /**
     * Test add_animation_attributes_to_blocks with empty content.
     */
    public function test_empty_content_returns_empty() {
        // Arrange
        $block = array(
            'blockName' => 'spectra/test-block',
            'attrs' => array(
                'spectraAnimationType' => 'fade-in',
            ),
        );
        
        // Act
        $result = $this->animations->add_animation_attributes_to_blocks( '', $block );
        
        // Assert
        $this->assertEquals( '', $result, 'Empty content should return empty' );
    }

    /**
     * Test add_animation_attributes_to_blocks with valid block (if WP_HTML_Tag_Processor available).
     */
    public function test_add_animation_attributes_to_valid_block() {
        // Check if WP_HTML_Tag_Processor exists
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            $this->markTestSkipped( 'WP_HTML_Tag_Processor not available in test environment' );
        }
        
        // Arrange
        $block_content = '<div class="test-block">Test content</div>';
        $block = array(
            'blockName' => 'spectra/test-block',
            'attrs' => array(
                'spectraAnimationType' => 'fade-in',
                'spectraAnimationTime' => 600,
                'spectraAnimationDelay' => 100,
                'spectraAnimationEasing' => 'ease-in',
                'spectraAnimationOnce' => true,
            ),
        );
        
        // Act
        $result = $this->animations->add_animation_attributes_to_blocks( $block_content, $block );
        
        // Assert
        $this->assertStringContainsString( 'data-aos="fade-in"', $result );
        $this->assertStringContainsString( 'data-aos-duration="600"', $result );
        $this->assertStringContainsString( 'data-aos-delay="100"', $result );
        $this->assertStringContainsString( 'data-aos-easing="ease-in"', $result );
        $this->assertStringContainsString( 'data-aos-once="false"', $result ); // true means play repeatedly, so aos-once is false
    }

    /**
     * Test private method checks using reflection.
     */
    public function test_private_methods_exist() {
        $reflection = new ReflectionClass( $this->animations );
        
        // Check that these private methods exist
        $expected_methods = array(
            'should_process_block',
            'get_animation_attributes', 
            'apply_attributes',
            'is_allowed_block',
            'register_animation_assets',
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
     * Test allowed blocks pattern matching.
     */
    public function test_allowed_block_patterns() {
        $reflection = new ReflectionClass( $this->animations );
        
        if ( $reflection->hasMethod( 'is_allowed_block' ) ) {
            $method = $reflection->getMethod( 'is_allowed_block' );
            $method->setAccessible( true );
            
            // Test allowed patterns
            $allowed_patterns = array(
                'spectra/test-block' => true,
                'spectra-pro/advanced-block' => true,
                'core/paragraph' => true,
                'core/heading' => true,
                'other/block' => false,
                'woocommerce/product' => false,
            );
            
            foreach ( $allowed_patterns as $block_name => $expected ) {
                $result = $method->invoke( $this->animations, $block_name );
                $this->assertEquals(
                    $expected,
                    $result,
                    "Block '{$block_name}' should " . ( $expected ? 'be' : 'not be' ) . ' allowed'
                );
            }
        }
    }

    /**
     * Test default animation attribute values.
     */
    public function test_get_animation_attributes_defaults() {
        $reflection = new ReflectionClass( $this->animations );
        
        if ( $reflection->hasMethod( 'get_animation_attributes' ) ) {
            $method = $reflection->getMethod( 'get_animation_attributes' );
            $method->setAccessible( true );
            
            // Test with minimal attributes
            $attrs = array(
                'spectraAnimationType' => 'fade-in',
            );
            
            $result = $method->invoke( $this->animations, $attrs );
            
            // Check defaults
            $this->assertEquals( 'fade-in', $result['type'] );
            $this->assertEquals( 400, $result['time'] ); // default time
            $this->assertEquals( 0, $result['delay'] ); // default delay
            $this->assertEquals( 'ease', $result['easing'] ); // default easing
            $this->assertFalse( $result['once'] ); // default once
        }
    }
}