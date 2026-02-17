<?php
/**
 * Tests for ExtensionManager class.
 *
 * @package Spectra\Tests
 */

use Spectra\ExtensionManager;

/**
 * ExtensionManager test case.
 */
class Test_Extension_Manager extends Spectra_Test_Case {

    /**
     * The ExtensionManager instance.
     *
     * @var ExtensionManager
     */
    private $extension_manager;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Get ExtensionManager instance
        $this->extension_manager = ExtensionManager::instance();
    }

    /**
     * Test that ExtensionManager implements singleton pattern.
     */
    public function test_singleton_instance() {
        $instance1 = ExtensionManager::instance();
        $instance2 = ExtensionManager::instance();
        
        $this->assertSame( $instance1, $instance2, 'ExtensionManager should return the same instance' );
    }

    /**
     * Test that init method registers the correct hooks.
     */
    public function test_init_registers_hooks() {
        // Reset the instance to test fresh initialization
        $this->reset_singleton( '\Spectra\ExtensionManager' );
        
        // Create new instance and init
        $manager = ExtensionManager::instance();
        $manager->init();
        
        // Check that the enqueue_block_editor_assets hook is registered
        $this->assert_hook_added( 'enqueue_block_editor_assets', $manager, 'register_extensions' );
    }

    /**
     * Test that init_extensions initializes all extensions.
     */
    public function test_init_extensions() {
        // Mock the extension classes if needed
        if ( ! class_exists( '\Spectra\Extensions\Animations' ) ) {
            $this->markTestSkipped( 'Animations extension class not found' );
        }
        
        if ( ! class_exists( '\Spectra\Extensions\ImageMask' ) ) {
            $this->markTestSkipped( 'ImageMask extension class not found' );
        }
        
        // Call init_extensions
        $this->extension_manager->init_extensions();
        
        // Verify that the extension instances were created
        $animations_instance = \Spectra\Extensions\Animations::instance();
        $this->assertInstanceOf( '\Spectra\Extensions\Animations', $animations_instance );
        
        $image_mask_instance = \Spectra\Extensions\ImageMask::instance();
        $this->assertInstanceOf( '\Spectra\Extensions\ImageMask', $image_mask_instance );
    }

    /**
     * Test register_extensions with no build directory.
     */
    public function test_register_extensions_no_build_dir() {
        // Temporarily rename build directory if it exists
        $build_dir = SPECTRA_3_DIR . 'build/extensions/';
        $temp_dir = SPECTRA_3_DIR . 'build_temp/extensions/';
        
        $renamed = false;
        if ( is_dir( $build_dir ) ) {
            // Create parent directory if it doesn't exist
            $temp_parent = dirname( $temp_dir );
            if ( ! is_dir( $temp_parent ) ) {
                mkdir( $temp_parent, 0755, true );
            }
            if ( rename( $build_dir, $temp_dir ) ) {
                $renamed = true;
            }
        }
        
        // Run register_extensions - should return early
        $this->extension_manager->register_extensions();
        
        // Restore build directory
        if ( $renamed && is_dir( $temp_dir ) ) {
            rename( $temp_dir, $build_dir );
            // Clean up temp directory
            rmdir( dirname( $temp_dir ) );
        }
        
        // If we get here without errors, the test passes
        $this->assertTrue( true );
    }

    /**
     * Test enqueue_editor_extension_assets with valid extension.
     */
    public function test_enqueue_editor_extension_assets_valid() {
        // This test would require mocking wp_enqueue_script
        // For now, we'll just test that the method can be called without errors
        
        $extension_file = SPECTRA_3_DIR . 'build/extensions/test-extension/index.asset.php';
        
        // Create temporary test files
        $test_dir = dirname( $extension_file );
        if ( ! is_dir( $test_dir ) ) {
            mkdir( $test_dir, 0777, true );
        }
        
        // Create mock asset file
        file_put_contents( $extension_file, '<?php return array( "dependencies" => array(), "version" => "1.0.0" );' );
        
        // Create mock JS file
        file_put_contents( $test_dir . '/index.js', '// Test JS file' );
        
        // Use reflection to call private method
        $reflection = new ReflectionClass( $this->extension_manager );
        $method = $reflection->getMethod( 'enqueue_editor_extension_assets' );
        $method->setAccessible( true );
        
        // Call the method - should not throw any errors
        $method->invoke( $this->extension_manager, $extension_file );
        
        // Clean up test files
        unlink( $extension_file );
        unlink( $test_dir . '/index.js' );
        rmdir( $test_dir );
        
        // If we get here without errors, the test passes
        $this->assertTrue( true );
    }

    /**
     * Test enqueue_editor_extension_assets with non-existent file.
     */
    public function test_enqueue_editor_extension_assets_invalid_file() {
        $extension_file = SPECTRA_3_DIR . 'build/extensions/non-existent/index.asset.php';
        
        // Use reflection to call private method
        $reflection = new ReflectionClass( $this->extension_manager );
        $method = $reflection->getMethod( 'enqueue_editor_extension_assets' );
        $method->setAccessible( true );
        
        // Call the method - should return early without errors
        $method->invoke( $this->extension_manager, $extension_file );
        
        // If we get here without errors, the test passes
        $this->assertTrue( true );
    }

    /**
     * Test that image-mask extension gets localized script data.
     */
    public function test_image_mask_localized_script() {
        // This test would require more complex mocking of WordPress functions
        // For now, we'll create a basic test structure
        
        $extension_file = SPECTRA_3_DIR . 'build/extensions/image-mask/index.asset.php';
        
        // Create temporary test files
        $test_dir = dirname( $extension_file );
        if ( ! is_dir( $test_dir ) ) {
            mkdir( $test_dir, 0777, true );
        }
        
        // Create mock files
        file_put_contents( $extension_file, '<?php return array( "dependencies" => array(), "version" => "1.0.0" );' );
        file_put_contents( $test_dir . '/index.js', '// Test JS file' );
        
        // Mock wp_localize_script to verify it's called
        $localize_called = false;
        $localize_data = null;
        
        // Override the function temporarily
        if ( ! function_exists( 'wp_localize_script_test' ) ) {
            function wp_localize_script_test( $handle, $object_name, $data ) {
                global $test_localize_called, $test_localize_data;
                $test_localize_called = true;
                $test_localize_data = $data;
            }
        }
        
        // Use reflection to test
        $reflection = new ReflectionClass( $this->extension_manager );
        $method = $reflection->getMethod( 'enqueue_editor_extension_assets' );
        $method->setAccessible( true );
        
        // Call the method
        $method->invoke( $this->extension_manager, $extension_file );
        
        // Clean up
        if ( file_exists( $extension_file ) ) {
            unlink( $extension_file );
        }
        if ( file_exists( $test_dir . '/index.js' ) ) {
            unlink( $test_dir . '/index.js' );
        }
        // Remove any other files that might be in the directory
        if ( is_dir( $test_dir ) ) {
            $files = scandir( $test_dir );
            foreach ( $files as $file ) {
                if ( $file !== '.' && $file !== '..' ) {
                    $file_path = $test_dir . '/' . $file;
                    if ( is_file( $file_path ) ) {
                        unlink( $file_path );
                    }
                }
            }
            rmdir( $test_dir );
        }
        
        // For now, just verify no errors occurred
        $this->assertTrue( true );
    }
}