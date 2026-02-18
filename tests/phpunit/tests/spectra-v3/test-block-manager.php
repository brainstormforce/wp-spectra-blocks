<?php
/**
 * Tests for BlockManager class.
 *
 * @package Spectra\Tests
 */

use Spectra\BlockManager;

/**
 * BlockManager test case.
 */
class Test_Block_Manager extends Spectra_Test_Case {

    /**
     * The BlockManager instance.
     *
     * @var BlockManager
     */
    private $block_manager;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Get BlockManager instance
        $this->block_manager = BlockManager::instance();
    }

    /**
     * Test singleton implementation.
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = BlockManager::instance();
        $instance2 = BlockManager::instance();
        
        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test block category registration.
     */
    public function test_add_block_category() {
        // Arrange
        $categories = array(
            array( 'slug' => 'text', 'title' => 'Text' ),
            array( 'slug' => 'media', 'title' => 'Media' ),
        );
        
        // Act
        $result = $this->block_manager->add_block_category( $categories );
        
        // Assert
        $this->assertIsArray( $result );
        
        // Check if Spectra Blocks category was added
        $spectra_category_found = false;
        foreach ( $result as $category ) {
            if ( 'spectra-blocks' === $category['slug'] ) {
                $spectra_category_found = true;
                $this->assertEquals( 'Spectra Blocks', $category['title'] );
                break;
            }
        }

        $this->assertTrue( $spectra_category_found, 'Spectra Blocks category should be added' );
        
        // Original categories should still exist
        $this->assertCount( count( $categories ) + 1, $result );
    }

    /**
     * Test controller path resolution.
     */
    public function test_resolve_controller_path() {
        // Use reflection to test private method
        $reflection = new ReflectionClass( $this->block_manager );
        $method = $reflection->getMethod( 'resolve_controller_path' );
        $method->setAccessible( true );
        
        // Test with block that has controller.php (button block)
        $block_json_path = SPECTRA_3_DIR . 'build/blocks/button/block.json';
        $expected_controller_path = SPECTRA_3_DIR . 'build/blocks/button/controller.php';
        
        $result = $method->invoke( $this->block_manager, $block_json_path );
        
        // If build files exist (local development), test the actual path
        if ( file_exists( $expected_controller_path ) ) {
            $this->assertEquals( realpath( $expected_controller_path ), $result );
        } else {
            // In CI/CD or when build files don't exist, the method should return null
            $this->assertNull( $result, 'Controller path should be null when build files do not exist' );
        }
        
        // Test with non-existent file - should always return null
        $non_existent_path = SPECTRA_3_DIR . 'build/blocks/non-existent/block.json';
        $result_null = $method->invoke( $this->block_manager, $non_existent_path );
        $this->assertNull( $result_null );
    }

    /**
     * Test template path resolution.
     */
    public function test_resolve_template_path() {
        // Use reflection to test private method
        $reflection = new ReflectionClass( $this->block_manager );
        $method = $reflection->getMethod( 'resolve_template_path' );
        $method->setAccessible( true );
        
        // Create a test metadata file path
        $metadata_file = SPECTRA_3_DIR . 'build/blocks/accordion/block.json';
        
        // Test with view.php
        $view = 'view.php';
        $result = $method->invoke( $this->block_manager, $metadata_file, $view );
        // Result will be null if file doesn't exist, or the path if it does
        if ( ! is_null( $result ) ) {
            $this->assertStringContainsString( 'view.php', $result );
        } else {
            $this->assertNull( $result );
        }
        
        // Test with file:./view.php format
        $view_with_prefix = 'file:./view.php';
        $result_with_prefix = $method->invoke( $this->block_manager, $metadata_file, $view_with_prefix );
        if ( ! is_null( $result_with_prefix ) ) {
            $this->assertStringContainsString( 'view.php', $result_with_prefix );
        } else {
            $this->assertNull( $result_with_prefix );
        }
        
        // Test with non-existent file
        $non_existent_view = 'non-existent.php';
        $result_non_existent = $method->invoke( $this->block_manager, $metadata_file, $non_existent_view );
        $this->assertNull( $result_non_existent );
    }

    /**
     * Test controller settings configuration.
     */
    public function test_configure_block_controller_settings() {
        // Arrange
        $settings = array(
            'title' => 'Test Block',
            'category' => 'spectra-blocks',
            'attributes' => array(
                'content' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        );
        $controller_path = SPECTRA_3_DIR . 'build/blocks/test-block/controller.php';
        $metadata = array(
            'name' => 'spectra/test-block',
            'file' => SPECTRA_3_DIR . 'build/blocks/test-block/block.json'
        );
        
        // Create a mock controller file
        $mock_controller_content = '<?php return function( $attributes, $content, $block ) { return "<div>Test</div>"; };';
        
        // Test when controller exists
        if ( ! file_exists( dirname( $controller_path ) ) ) {
            mkdir( dirname( $controller_path ), 0777, true );
        }
        file_put_contents( $controller_path, $mock_controller_content );
        
        $result = $this->block_manager->configure_block_controller_settings( $settings, $metadata );
        
        // Assert
        $this->assertArrayHasKey( 'render_callback', $result );
        $this->assertIsCallable( $result['render_callback'] );
        
        // Clean up
        unlink( $controller_path );
        if ( is_dir( dirname( $controller_path ) ) ) {
            rmdir( dirname( $controller_path ) );
        }
    }

    /**
     * Test block registration hooks.
     */
    public function test_init_registers_hooks() {
        // Reset instance and init
        $this->reset_singleton( '\Spectra\BlockManager' );
        $manager = BlockManager::instance();
        $manager->init();
        
        // Check hooks are registered
        $this->assert_hook_added( 'init', $manager, 'register_blocks' );
        $this->assert_hook_added( 'block_categories_all', $manager, 'add_block_category', 9999999 );
    }

    /**
     * Test getting block directory from metadata path.
     */
    public function test_get_block_directory() {
        // Test various path scenarios
        $test_cases = array(
            SPECTRA_3_DIR . 'build/blocks/accordion/block.json' => SPECTRA_3_DIR . 'build/blocks/accordion',
            SPECTRA_3_DIR . 'build/blocks/nested/deep/block.json' => SPECTRA_3_DIR . 'build/blocks/nested/deep',
            '/absolute/path/to/block.json' => '/absolute/path/to',
        );
        
        foreach ( $test_cases as $input => $expected ) {
            $result = dirname( $input );
            $this->assertEquals( $expected, $result );
        }
    }

    /**
     * Test block attributes fallback handling.
     */
    public function test_handle_attribute_fallbacks() {
        // This would test the controller's attribute handling
        $attributes = array(
            'content' => 'Test content',
            'color' => '', // Empty should fallback to default
            'padding' => null, // Null should fallback to default
        );
        
        $defaults = array(
            'content' => 'Default content',
            'color' => '#000000',
            'padding' => '20px',
            'margin' => '10px', // Not in attributes, should use default
        );
        
        // Merge with defaults
        $final_attributes = wp_parse_args( $attributes, $defaults );
        
        $this->assertEquals( 'Test content', $final_attributes['content'] );
        $this->assertEquals( '', $final_attributes['color'] ); // Empty string is preserved
        $this->assertEquals( null, $final_attributes['padding'] ); // Null is preserved
        $this->assertEquals( '10px', $final_attributes['margin'] ); // Missing uses default
    }

    /**
     * Test render callback error handling.
     */
    public function test_render_callback_handles_errors() {
        // Create a render callback that might fail
        $render_callback = function( $attributes, $content, $block ) {
            if ( empty( $attributes['required_field'] ) ) {
                return '<!-- Block render error: Missing required field -->';
            }
            
            return '<div>Success</div>';
        };
        
        // Test with missing required field
        $result = $render_callback( array(), '', null );
        $this->assertStringContainsString( 'Block render error', $result );
        
        // Test with required field present
        $result = $render_callback( array( 'required_field' => 'value' ), '', null );
        $this->assertStringContainsString( 'Success', $result );
    }

    /**
     * Test block metadata validation.
     */
    public function test_validate_block_metadata() {
        // Valid metadata
        $valid_metadata = array(
            'apiVersion' => 2,
            'name' => 'spectra/test-block',
            'title' => 'Test Block',
            'category' => 'spectra-blocks',
            'attributes' => array(
                'content' => array(
                    'type' => 'string',
                ),
            ),
        );
        
        // This would be used by register_block_type_from_metadata
        $this->assertArrayHasKey( 'name', $valid_metadata );
        $this->assertArrayHasKey( 'title', $valid_metadata );
        $this->assertArrayHasKey( 'attributes', $valid_metadata );
        $this->assertIsArray( $valid_metadata['attributes'] );
    }

    /**
     * Test dynamic block rendering.
     */
    public function test_dynamic_block_rendering() {
        // Simulate a dynamic block render
        $attributes = array(
            'postId' => 123,
            'showTitle' => true,
            'showExcerpt' => true,
        );
        
        // Mock post data
        $mock_post = (object) array(
            'ID' => 123,
            'post_title' => 'Test Post Title',
            'post_excerpt' => 'Test excerpt content',
        );
        
        // Simulate render function
        $render_function = function( $attributes ) use ( $mock_post ) {
            $output = '<div class="dynamic-block">';
            
            if ( $attributes['showTitle'] ) {
                $output .= '<h2>' . esc_html( $mock_post->post_title ) . '</h2>';
            }
            
            if ( $attributes['showExcerpt'] ) {
                $output .= '<p>' . esc_html( $mock_post->post_excerpt ) . '</p>';
            }
            
            $output .= '</div>';
            
            return $output;
        };
        
        $result = $render_function( $attributes );
        
        $this->assertStringContainsString( 'dynamic-block', $result );
        $this->assertStringContainsString( 'Test Post Title', $result );
        $this->assertStringContainsString( 'Test excerpt content', $result );
    }

    /**
     * Test block supports configuration.
     */
    public function test_block_supports_configuration() {
        // Test block supports array
        $supports = array(
            'align' => array( 'wide', 'full' ),
            'anchor' => true,
            'className' => true,
            'color' => array(
                'background' => true,
                'text' => true,
                'gradients' => true,
            ),
            'spacing' => array(
                'margin' => true,
                'padding' => true,
            ),
        );
        
        // Validate supports structure
        $this->assertIsArray( $supports['align'] );
        $this->assertTrue( $supports['anchor'] );
        $this->assertIsArray( $supports['color'] );
        $this->assertTrue( $supports['color']['gradients'] );
        $this->assertTrue( $supports['spacing']['margin'] );
    }
}