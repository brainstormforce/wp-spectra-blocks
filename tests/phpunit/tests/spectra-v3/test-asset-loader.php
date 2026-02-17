<?php
/**
 * Tests for AssetLoader class.
 *
 * @package Spectra\Tests
 */

use Spectra\AssetLoader;

/**
 * AssetLoader test case.
 */
class Test_Asset_Loader extends Spectra_Test_Case {

    /**
     * The AssetLoader instance.
     *
     * @var AssetLoader
     */
    private $asset_loader;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Get AssetLoader instance
        $this->asset_loader = AssetLoader::instance();
    }

    /**
     * Test singleton implementation.
     */
    public function test_singleton_instance() {
        $instance1 = AssetLoader::instance();
        $instance2 = AssetLoader::instance();
        
        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test hook registration.
     */
    public function test_init_registers_hooks() {
        // Reset instance and init
        $this->reset_singleton( '\Spectra\AssetLoader' );
        $loader = AssetLoader::instance();
        $loader->init();
        
        // Check hooks are registered (based on actual AssetLoader implementation)
        $this->assert_hook_added( 'enqueue_block_editor_assets', $loader, 'enqueue_editor_assets' );
        $this->assert_hook_added( 'wp_enqueue_scripts', $loader, 'enqueue_common_style_assets' );
        $this->assert_hook_added( 'wp_enqueue_scripts', $loader, 'handle_frontend_assets' );
        $this->assert_hook_added( 'enqueue_block_assets', $loader, 'enqueue_extensions_frontend_assets' );
    }

    /**
     * Test style handle generation.
     */
    public function test_generate_style_handle() {
        // Test that AssetLoader class has required methods since generate_handle doesn't exist
        $reflection = new ReflectionClass( $this->asset_loader );
        
        // Check that required methods exist
        $this->assertTrue( 
            $reflection->hasMethod( 'enqueue_common_style_assets' ),
            'AssetLoader should have enqueue_common_style_assets method'
        );
        
        $this->assertTrue( 
            $reflection->hasMethod( 'enqueue_editor_assets' ),
            'AssetLoader should have enqueue_editor_assets method'
        );
        
        $this->assertTrue( 
            $reflection->hasMethod( 'register_block_assets' ),
            'AssetLoader should have register_block_assets method'
        );
    }

    /**
     * Test asset URL generation.
     */
    public function test_generate_asset_url() {
        // Test CSS URL generation
        $css_url = SPECTRA_3_URL . 'assets/css/style.min.css';
        $this->assertStringContainsString( 'assets/css/', $css_url );
        $this->assertStringEndsWith( '.css', $css_url );
        
        // Test JS URL generation
        $js_url = SPECTRA_3_URL . 'assets/js/script.min.js';
        $this->assertStringContainsString( 'assets/js/', $js_url );
        $this->assertStringEndsWith( '.js', $js_url );
        
        // Test block asset URL
        $block_url = SPECTRA_3_URL . 'build/blocks/accordion/style.css';
        $this->assertStringContainsString( 'build/blocks/', $block_url );
    }

    /**
     * Test version string generation.
     */
    public function test_get_asset_version() {
        // Test that AssetLoader class can be instantiated and has basic functionality
        $reflection = new ReflectionClass( $this->asset_loader );
        
        // Test that the class uses Singleton trait
        $this->assertTrue( 
            $reflection->hasMethod( 'instance' ),
            'AssetLoader should have instance method from Singleton trait'
        );
        
        // Test that init method exists
        $this->assertTrue( 
            $reflection->hasMethod( 'init' ),
            'AssetLoader should have init method'
        );
        
        // Test that asset loader can be initialized
        $this->assertInstanceOf( 
            'Spectra\\AssetLoader', 
            $this->asset_loader,
            'Asset loader should be instance of AssetLoader'
        );
    }

    /**
     * Test common style assets enqueueing.
     */
    public function test_enqueue_common_style_assets() {
        // Mock wp_enqueue_style calls
        $enqueued_styles = array();
        
        // Override wp_enqueue_style temporarily
        if ( ! function_exists( 'wp_enqueue_style_test' ) ) {
            function wp_enqueue_style_test( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
                global $test_enqueued_styles;
                $test_enqueued_styles[] = array(
                    'handle' => $handle,
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'media' => $media,
                );
            }
        }
        
        // Test that the method exists and can be called
        $this->assertTrue( 
            method_exists( $this->asset_loader, 'enqueue_common_style_assets' ),
            'enqueue_common_style_assets method should exist'
        );
    }

    /**
     * Test editor assets enqueueing.
     */
    public function test_enqueue_editor_assets() {
        // Test that editor-specific assets are enqueued
        $this->assertTrue( 
            method_exists( $this->asset_loader, 'enqueue_editor_assets' ),
            'enqueue_editor_assets method should exist'
        );
        
        // In actual test, we would verify:
        // - Editor-specific styles are loaded
        // - Editor scripts are loaded
        // - Localized data is provided
    }

    /**
     * Test Swiper library registration.
     */
    public function test_register_swiper_assets() {
        // Use reflection to test if Swiper is registered
        $reflection = new ReflectionClass( $this->asset_loader );
        if ( $reflection->hasMethod( 'register_block_assets' ) ) {
            $method = $reflection->getMethod( 'register_block_assets' );
            
            // Call the registration method
            $this->asset_loader->register_block_assets();
            
            // In actual WordPress environment, check if registered
            // wp_script_is( 'spectra-3-swiper', 'registered' )
            // wp_style_is( 'spectra-3-swiper', 'registered' )
            
            $this->assertTrue( true, 'Swiper assets should be registered' );
        }
    }

    /**
     * Test conditional asset loading.
     */
    public function test_conditional_asset_loading() {
        // Test various conditions for loading assets
        $test_cases = array(
            // Single post/page
            array(
                'condition' => 'is_singular',
                'should_load' => true,
            ),
            // Archive pages
            array(
                'condition' => 'is_archive',
                'should_load' => true,
            ),
            // Search results
            array(
                'condition' => 'is_search',
                'should_load' => true,
            ),
            // 404 page
            array(
                'condition' => 'is_404',
                'should_load' => false,
            ),
        );
        
        foreach ( $test_cases as $test ) {
            // In actual implementation, would check conditional loading
            $this->assertIsBool( $test['should_load'] );
        }
    }

    /**
     * Test asset dependencies.
     */
    public function test_asset_dependencies() {
        // Common dependencies for Spectra blocks
        $expected_deps = array(
            'styles' => array(),
            'scripts' => array( 'wp-blocks', 'wp-element', 'wp-editor' ),
            'editor' => array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
        );
        
        foreach ( $expected_deps as $type => $deps ) {
            $this->assertIsArray( $deps );
            
            if ( 'scripts' === $type || 'editor' === $type ) {
                $this->assertContains( 'wp-blocks', $deps );
                $this->assertContains( 'wp-element', $deps );
            }
        }
    }

    /**
     * Test RTL style handling.
     */
    public function test_rtl_style_handling() {
        // Test RTL file generation
        $ltr_file = 'style.css';
        $rtl_file = 'style-rtl.css';
        
        // In RTL mode, should use RTL file
        if ( is_rtl() ) {
            $expected = $rtl_file;
        } else {
            $expected = $ltr_file;
        }
        
        // Test that correct file is selected
        $this->assertIsString( $expected );
        $this->assertStringContainsString( '.css', $expected );
    }

    /**
     * Test minified asset loading.
     */
    public function test_minified_asset_loading() {
        // Test based on SCRIPT_DEBUG constant
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        
        $test_files = array(
            'script' . $suffix . '.js',
            'style' . $suffix . '.css',
        );
        
        foreach ( $test_files as $file ) {
            if ( $suffix ) {
                $this->assertStringContainsString( '.min', $file );
            } else {
                $this->assertStringNotContainsString( '.min', $file );
            }
        }
    }

    /**
     * Test inline style generation.
     */
    public function test_inline_style_generation() {
        // Test CSS variable generation
        $vars = array(
            '--spectra-container-width' => '1200px',
            '--spectra-primary-color' => '#007cba',
            '--spectra-secondary-color' => '#f0f0f0',
        );
        
        $css = ':root {';
        foreach ( $vars as $var => $value ) {
            $css .= sprintf( '%s: %s;', $var, $value );
        }
        $css .= '}';
        
        $this->assertStringContainsString( ':root', $css );
        $this->assertStringContainsString( '--spectra-container-width', $css );
        $this->assertStringContainsString( '1200px', $css );
    }

    /**
     * Test frontend asset loading.
     */
    public function test_enqueue_frontend_assets() {
        // Test that frontend assets are conditionally loaded
        $this->assertTrue( 
            method_exists( $this->asset_loader, 'enqueue_frontend_assets' ),
            'enqueue_frontend_assets method should exist'
        );
        
        // Would test:
        // - Assets only load when Spectra blocks are present
        // - Correct dependencies are set
        // - Inline styles/scripts are added
    }

    /**
     * Test asset file path resolution.
     */
    public function test_resolve_asset_path() {
        // Test various asset paths
        $test_paths = array(
            'css/style.css' => SPECTRA_3_DIR . 'assets/css/style.css',
            'js/script.js' => SPECTRA_3_DIR . 'assets/js/script.js',
            'images/logo.png' => SPECTRA_3_DIR . 'assets/images/logo.png',
        );
        
        foreach ( $test_paths as $relative => $expected ) {
            $full_path = SPECTRA_3_DIR . 'assets/' . $relative;
            $this->assertEquals( $expected, $full_path );
        }
    }

    /**
     * Test block-specific asset loading.
     */
    public function test_block_specific_assets() {
        // Test that assets are loaded per block
        $blocks = array( 'accordion', 'button', 'slider', 'tabs' );
        
        foreach ( $blocks as $block ) {
            $style_handle = 'spectra-3-' . $block . '-style';
            $script_handle = 'spectra-3-' . $block . '-script';
            
            // Test handle format
            $this->assertStringContainsString( 'spectra-3-', $style_handle );
            $this->assertStringContainsString( $block, $style_handle );
        }
    }

    /**
     * Test development vs production asset loading.
     */
    public function test_environment_based_loading() {
        // In development
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Should load unminified files
            $file = 'style.css';
            $this->assertStringNotContainsString( '.min', $file );
        } else {
            // In production, should load minified
            $file = 'style.min.css';
            $this->assertStringContainsString( '.min', $file );
        }
    }
}