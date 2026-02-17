<?php
/**
 * Base test case for Spectra V3 tests.
 *
 * @package Spectra\Tests
 */

/**
 * Spectra_Test_Case base class.
 */
class Spectra_Test_Case extends WP_UnitTestCase {

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Reset any singleton instances between tests
        $this->reset_singletons();
        
        // Reset global scripts and styles
        global $wp_scripts, $wp_styles;
        if ( isset( $wp_scripts ) ) {
            $wp_scripts->queue = array();
        }
        if ( isset( $wp_styles ) ) {
            $wp_styles->queue = array();
        }
    }

    /**
     * Tear down after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Clean up any test data
        $this->clean_test_data();
    }

    /**
     * Reset singleton instances for testing.
     */
    protected function reset_singletons() {
        // Reset ExtensionManager singleton
        $this->reset_singleton( '\Spectra\ExtensionManager' );
        
        // Reset extension singletons
        $this->reset_singleton( '\Spectra\Extensions\Animations' );
        $this->reset_singleton( '\Spectra\Extensions\ImageMask' );
    }

    /**
     * Reset a specific singleton class.
     *
     * @param string $class_name The fully qualified class name.
     */
    protected function reset_singleton( $class_name ) {
        if ( ! class_exists( $class_name ) ) {
            return;
        }

        $reflection = new ReflectionClass( $class_name );
        $instance_property = $reflection->getProperty( 'instance' );
        $instance_property->setAccessible( true );
        $instance_property->setValue( null, null );
    }

    /**
     * Clean up test data.
     */
    protected function clean_test_data() {
        // Clean up any test-specific data if needed
    }

    /**
     * Assert that a hook has been added.
     *
     * @param string $hook_name The hook name.
     * @param object|string $class The class or object containing the callback.
     * @param string $method The method name.
     * @param int $priority Optional. The priority. Default 10.
     */
    protected function assert_hook_added( $hook_name, $class, $method, $priority = 10 ) {
        global $wp_filter;
        
        $this->assertArrayHasKey( $hook_name, $wp_filter, "Hook {$hook_name} was not registered" );
        
        $found = false;
        if ( isset( $wp_filter[ $hook_name ][ $priority ] ) ) {
            foreach ( $wp_filter[ $hook_name ][ $priority ] as $hook ) {
                if ( is_array( $hook['function'] ) && 
                     ( $hook['function'][0] === $class || ( is_object( $class ) && get_class( $hook['function'][0] ) === get_class( $class ) ) ) &&
                     $hook['function'][1] === $method ) {
                    $found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue( $found, "Method {$method} was not found in hook {$hook_name} at priority {$priority}" );
    }
    

    /**
     * Mock a function for testing.
     *
     * @param string $function_name The function name to mock.
     * @param callable $callback The callback to use instead.
     */
    protected function mock_function( $function_name, $callback ) {
        // This is a placeholder for function mocking
        // In real implementation, you would use a mocking library
        // For now, we'll use runkit or namespace tricks
        
        // Store original function if exists
        if ( ! isset( $this->mocked_functions ) ) {
            $this->mocked_functions = array();
        }
        
        $this->mocked_functions[ $function_name ] = $callback;
    }
    
    /**
     * Mock include result.
     *
     * @var mixed
     */
    protected $mock_include_result = null;
    
    /**
     * Mocked functions storage.
     *
     * @var array
     */
    protected $mocked_functions = array();
    
    /**
     * Mock file system for testing.
     *
     * @param array $files Array of file paths to mock as existing.
     */
    protected function mock_file_system( $files ) {
        // This would be implemented with a proper file system mocking library
        // For now, it's a placeholder for future implementation
    }
    
    /**
     * Go to a specific URL (mock for testing).
     *
     * @param string $url The URL to go to.
     */
    protected function go_to( $url ) {
        // Mock implementation - in real WordPress tests this would set up the query
        global $wp_query, $wp_the_query;
        
        if ( ! isset( $wp_query ) ) {
            $wp_query = new stdClass();
        }
        
        if ( ! isset( $wp_the_query ) ) {
            $wp_the_query = $wp_query;
        }
        
        // Set basic query vars
        $wp_query->is_home = ( $url === '/' );
        $wp_query->is_single = strpos( $url, '?p=' ) !== false;
        $wp_query->is_page = strpos( $url, 'page_id=' ) !== false;
        $wp_query->is_archive = strpos( $url, 'category' ) !== false || strpos( $url, 'tag' ) !== false;
    }
}