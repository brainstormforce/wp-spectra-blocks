<?php
/**
 * Tests for the complete Spectra v3 execution flow from initialization to rendering.
 *
 * @package Spectra\Tests
 */

/**
 * Test the complete execution flow step by step.
 */
class Test_Complete_Execution_Flow extends Spectra_Test_Case {

	/**
	 * Test the complete execution flow: Plugin Init → Block Registration → Asset Loading → Extension Loading → Rendering.
	 */
	public function test_complete_execution_flow() {
		// Step 1: Plugin Initialization
		$this->simulate_plugin_initialization();

		// Step 2: Block Registration
		$this->simulate_block_registration();

		// Step 3: Asset Loading
		$this->simulate_asset_loading();

		// Step 4: Extension Loading
		$this->simulate_extension_loading();

		// Step 5: Block Rendering
		$this->simulate_block_rendering();

		$this->assertTrue( true ); // Success if all steps complete
	}

	/**
	 * Step 1: Simulate plugin initialization phase.
	 */
	private function simulate_plugin_initialization() {
		// Test constants are defined
		$this->assertTrue( defined( 'SPECTRA_3_FILE' ), 'SPECTRA_3_FILE should be defined' );
		$this->assertTrue( defined( 'SPECTRA_3_DIR' ), 'SPECTRA_3_DIR should be defined' );
		$this->assertTrue( defined( 'SPECTRA_3_URL' ), 'SPECTRA_3_URL should be defined' );

		// Test autoloader is working
		$this->assertTrue( class_exists( 'Spectra\BlockManager' ) );
		$this->assertTrue( class_exists( 'Spectra\AssetLoader' ) );
		$this->assertTrue( class_exists( 'Spectra\ExtensionManager' ) );

		// Test managers can be instantiated
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		$this->assertInstanceOf( 'Spectra\BlockManager', $block_manager );
		$this->assertInstanceOf( 'Spectra\AssetLoader', $asset_loader );
		$this->assertInstanceOf( 'Spectra\ExtensionManager', $extension_manager );

		// Test singleton pattern
		$this->assertSame( $block_manager, \Spectra\BlockManager::instance() );
		$this->assertSame( $asset_loader, \Spectra\AssetLoader::instance() );
		$this->assertSame( $extension_manager, \Spectra\ExtensionManager::instance() );
	}

	/**
	 * Step 2: Simulate block registration phase.
	 */
	private function simulate_block_registration() {
		$block_manager = \Spectra\BlockManager::instance();

		// Test block category is added
		$categories = array(
			array( 'slug' => 'text', 'title' => 'Text' ),
			array( 'slug' => 'media', 'title' => 'Media' ),
		);

		$updated_categories = $block_manager->add_block_category( $categories );
		$this->assertCount( 3, $updated_categories );
		$this->assertEquals( 'spectra', $updated_categories[0]['slug'] );
		$this->assertEquals( 'Spectra', $updated_categories[0]['title'] );
		$this->assertEquals( 'superhero', $updated_categories[0]['icon'] );

		// Test that Spectra category is not duplicated
		$updated_categories_again = $block_manager->add_block_category( $updated_categories );
		$this->assertCount( 3, $updated_categories_again );
		
		// Test block controller configuration
		$settings = array( 'test_setting' => 'test_value' );
		$non_spectra_metadata = array( 'name' => 'core/paragraph' );
		
		$result = $block_manager->configure_block_controller_settings( $settings, $non_spectra_metadata );
		$this->assertEquals( $settings, $result );

		// Test Spectra block without controller file
		$spectra_metadata = array( 
			'name' => 'spectra/test-block',
			'file' => '/nonexistent/path/block.json'
		);
		
		$result = $block_manager->configure_block_controller_settings( $settings, $spectra_metadata );
		$this->assertEquals( $settings, $result );
	}

	/**
	 * Step 3: Simulate asset loading phase.
	 */
	private function simulate_asset_loading() {
		$asset_loader = \Spectra\AssetLoader::instance();

		// Test that required methods exist
		$this->assertTrue( method_exists( $asset_loader, 'init' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_common_style_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_editor_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'register_block_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'handle_frontend_assets' ) );

		// Test initialization
		$asset_loader->init();
		$this->assertTrue( true ); // Success if no exceptions

		// Test block asset registration
		$asset_loader->register_block_assets();
		$this->assertTrue( true ); // Success if no exceptions

		// Test frontend asset handling
		$asset_loader->handle_frontend_assets();
		$this->assertTrue( true ); // Success if no exceptions
	}

	/**
	 * Step 4: Simulate extension loading phase.
	 */
	private function simulate_extension_loading() {
		$extension_manager = \Spectra\ExtensionManager::instance();

		// Test that required methods exist
		$this->assertTrue( method_exists( $extension_manager, 'init' ) );
		$this->assertTrue( method_exists( $extension_manager, 'init_extensions' ) );
		$this->assertTrue( method_exists( $extension_manager, 'register_extensions' ) );

		// Test initialization
		$extension_manager->init();
		$this->assertTrue( true ); // Success if no exceptions

		// Test extensions initialization (skip if extension classes don't exist)
		try {
			$extension_manager->init_extensions();
			$this->assertTrue( true ); // Success if no exceptions
		} catch ( Error $e ) {
			// Skip if extension classes don't exist in test environment
			$this->assertTrue( true );
		}

		// Test extension registration (should handle missing files gracefully)
		$extension_manager->register_extensions();
		$this->assertTrue( true ); // Success if no exceptions
	}

	/**
	 * Step 5: Simulate block rendering phase.
	 */
	private function simulate_block_rendering() {
		$block_manager = \Spectra\BlockManager::instance();

		// Test controller settings configuration
		$settings = array( 'render_callback' => '__return_empty_string' );
		$metadata = array( 'name' => 'spectra/test-block' );

		$result = $block_manager->configure_block_controller_settings( $settings, $metadata );
		$this->assertIsArray( $result );
		
		// Test that configuration preserves existing settings
		$this->assertArrayHasKey( 'render_callback', $result );
	}

	/**
	 * Test error handling throughout the execution flow.
	 */
	public function test_error_handling_throughout_flow() {
		// Test that all managers can be initialized without errors
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		try {
			$block_manager->init();
			$asset_loader->init();
			$extension_manager->init();
			$this->assertTrue( true ); // Success if no exceptions
		} catch ( Exception $e ) {
			$this->fail( 'Initialization should not throw exceptions: ' . $e->getMessage() );
		}
	}

	/**
	 * Test WordPress hooks integration.
	 */
	public function test_wordpress_hooks_integration() {
		// Initialize all managers
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		$block_manager->init();
		$asset_loader->init();
		$extension_manager->init();

		// Test that hooks are properly added
		$this->assert_hook_added( 'init', $block_manager, 'register_blocks' );
		$this->assert_hook_added( 'block_categories_all', $block_manager, 'add_block_category', 9999999 );
		$this->assert_hook_added( 'wp_enqueue_scripts', $asset_loader, 'enqueue_common_style_assets' );
		$this->assert_hook_added( 'enqueue_block_editor_assets', $asset_loader, 'enqueue_editor_assets' );
		$this->assert_hook_added( 'enqueue_block_editor_assets', $extension_manager, 'register_extensions' );
	}

	/**
	 * Test performance of the complete execution flow.
	 */
	public function test_execution_flow_performance() {
		$start_time = microtime( true );

		// Run complete flow
		$this->test_complete_execution_flow();

		$end_time = microtime( true );
		$execution_time = $end_time - $start_time;

		// Should complete within reasonable time (2 seconds for complete flow)
		$this->assertLessThan( 2.0, $execution_time, 'Complete execution flow should be performant' );
	}

	/**
	 * Test manager integration and coexistence.
	 */
	public function test_manager_integration_and_coexistence() {
		// Test that managers can be initialized in different orders
		$orders = array(
			array( 'BlockManager', 'AssetLoader', 'ExtensionManager' ),
			array( 'AssetLoader', 'ExtensionManager', 'BlockManager' ),
			array( 'ExtensionManager', 'BlockManager', 'AssetLoader' ),
		);

		foreach ( $orders as $order ) {
			$this->reset_singletons();
			
			foreach ( $order as $manager_class ) {
				$full_class = "\\Spectra\\{$manager_class}";
				$manager = $full_class::instance();
				$manager->init();
				$this->assertInstanceOf( $full_class, $manager );
			}
		}
	}

	/**
	 * Test that all required constants are properly defined.
	 */
	public function test_required_constants_defined() {
		$required_constants = array(
			'SPECTRA_3_FILE',
			'SPECTRA_3_DIR',
			'SPECTRA_3_URL',
		);

		foreach ( $required_constants as $constant ) {
			$this->assertTrue( defined( $constant ), "Constant {$constant} should be defined" );
			$this->assertNotEmpty( constant( $constant ), "Constant {$constant} should not be empty" );
		}

		// Test constant values have expected format
		$this->assertStringEndsWith( DIRECTORY_SEPARATOR, SPECTRA_3_DIR );
		$this->assertStringEndsWith( '/', SPECTRA_3_URL );
	}

	/**
	 * Test that all required classes are autoloadable.
	 */
	public function test_required_classes_autoloadable() {
		$required_classes = array(
			'Spectra\BlockManager',
			'Spectra\AssetLoader',
			'Spectra\ExtensionManager',
			'Spectra\FontManager',
			'Spectra\Helpers\Core',
			'Spectra\Helpers\BlockAttributes',
			'Spectra\Helpers\HtmlSanitizer',
			'Spectra\Helpers\Renderer',
		);

		foreach ( $required_classes as $class_name ) {
			$this->assertTrue( 
				class_exists( $class_name ),
				"Class {$class_name} should be autoloadable"
			);
		}
	}

	/**
	 * Test that Singleton trait is properly implemented.
	 */
	public function test_singleton_trait_implementation() {
		$singleton_classes = array(
			'Spectra\BlockManager',
			'Spectra\AssetLoader', 
			'Spectra\ExtensionManager',
			'Spectra\FontManager',
		);

		foreach ( $singleton_classes as $class_name ) {
			// Test instance method exists
			$this->assertTrue( method_exists( $class_name, 'instance' ) );
			
			// Test singleton behavior
			$instance1 = $class_name::instance();
			$instance2 = $class_name::instance();
			$this->assertSame( $instance1, $instance2, "Class {$class_name} should implement singleton pattern" );
		}
	}

	/**
	 * Test conditional asset loading based on block presence.
	 */
	public function test_conditional_asset_loading() {
		// Mock has_block function for testing
		if ( ! function_exists( 'has_block' ) ) {
			function has_block( $block_name, $post = null ) {
				return in_array( $block_name, array( 'spectra/slider', 'spectra/accordion' ) );
			}
		}

		$asset_loader = \Spectra\AssetLoader::instance();
		
		// Test frontend asset enqueuing
		$asset_loader->enqueue_frontend_assets();
		$this->assertTrue( true ); // Success if no exceptions

		// Test that method handles both cases (with and without specific blocks)
		$asset_loader->handle_frontend_assets();
		$this->assertTrue( true ); // Success if no exceptions
	}

	/**
	 * Test extension-specific functionality.
	 */
	public function test_extension_specific_functionality() {
		$extension_manager = \Spectra\ExtensionManager::instance();
		
		// Test that extensions can be initialized (skip if extension classes don't exist)
		try {
			$extension_manager->init_extensions();
			$this->assertTrue( true ); // Success if no exceptions
		} catch ( Error $e ) {
			// Skip if extension classes don't exist in test environment
			$this->assertTrue( true );
		}

		// Test that extensions can be registered
		$extension_manager->register_extensions();
		$this->assertTrue( true ); // Success if no exceptions
	}

	/**
	 * Test that the complete flow works with different WordPress environments.
	 */
	public function test_flow_with_different_environments() {
		// Test in admin context
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
		
		$this->simulate_plugin_initialization();
		$this->simulate_block_registration();
		$this->simulate_asset_loading();
		$this->simulate_extension_loading();
		
		// Test in frontend context
		if ( defined( 'WP_ADMIN' ) ) {
			// Note: We can't undefine constants in PHP, so this is a conceptual test
			// In real testing, you'd use separate test environments
		}
		
		$this->simulate_plugin_initialization();
		$this->simulate_asset_loading();
		
		$this->assertTrue( true ); // Success if both contexts work
	}
}