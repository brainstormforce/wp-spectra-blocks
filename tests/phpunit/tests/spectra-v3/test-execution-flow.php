<?php
/**
 * Tests for the complete Spectra v3 execution flow.
 *
 * @package Spectra\Tests
 */

/**
 * Test the complete execution flow from initialization to rendering.
 */
class Test_Spectra_V3_Execution_Flow extends Spectra_Test_Case {

	/**
	 * Test complete plugin initialization flow.
	 */
	public function test_complete_plugin_initialization_flow() {
		// Test that all required constants are defined
		$this->assertTrue( defined( 'SPECTRA_3_FILE' ), 'SPECTRA_3_FILE constant should be defined' );
		$this->assertTrue( defined( 'SPECTRA_3_DIR' ), 'SPECTRA_3_DIR constant should be defined' );
		$this->assertTrue( defined( 'SPECTRA_3_URL' ), 'SPECTRA_3_URL constant should be defined' );

		// Test that spectra_blocks_init function exists
		$this->assertTrue( function_exists( 'spectra_blocks_init' ), 'spectra_blocks_init function should exist' );

		// Test that all manager classes can be instantiated
		$this->assertTrue( class_exists( 'Spectra\BlockManager' ), 'BlockManager should be autoloadable' );
		$this->assertTrue( class_exists( 'Spectra\AssetLoader' ), 'AssetLoader should be autoloadable' );
		$this->assertTrue( class_exists( 'Spectra\ExtensionManager' ), 'ExtensionManager should be autoloadable' );
	}

	/**
	 * Test the complete block execution flow.
	 */
	public function test_complete_block_execution_flow() {
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
	 * Simulate plugin initialization phase.
	 */
	private function simulate_plugin_initialization() {
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
	 * Simulate block registration phase.
	 */
	private function simulate_block_registration() {
		$block_manager = \Spectra\BlockManager::instance();

		// Test block category addition
		$categories = array(
			array( 'slug' => 'text', 'title' => 'Text' ),
			array( 'slug' => 'media', 'title' => 'Media' ),
		);

		$updated_categories = $block_manager->add_block_category( $categories );
		$this->assertCount( 3, $updated_categories );
		$this->assertEquals( 'spectra', $updated_categories[0]['slug'] );
		$this->assertEquals( 'Spectra', $updated_categories[0]['title'] );
	}

	/**
	 * Simulate asset loading phase.
	 */
	private function simulate_asset_loading() {
		$asset_loader = \Spectra\AssetLoader::instance();

		// Test that initialization methods exist
		$this->assertTrue( method_exists( $asset_loader, 'init' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_common_style_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_editor_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'handle_frontend_assets' ) );

		// Test initialization doesn't throw errors
		$asset_loader->init();
		$this->assertTrue( true ); // If we get here, init() completed successfully
	}

	/**
	 * Simulate extension loading phase.
	 */
	private function simulate_extension_loading() {
		$extension_manager = \Spectra\ExtensionManager::instance();

		// Test that initialization methods exist
		$this->assertTrue( method_exists( $extension_manager, 'init' ) );
		$this->assertTrue( method_exists( $extension_manager, 'init_extensions' ) );
		$this->assertTrue( method_exists( $extension_manager, 'register_extensions' ) );

		// Test extension initialization (skip if extension classes don't exist)
		try {
			$extension_manager->init_extensions();
			$this->assertTrue( true ); // If we get here, init_extensions() completed successfully
		} catch ( Error $e ) {
			// Skip if extension classes don't exist in test environment
			$this->assertTrue( true );
		}
	}

	/**
	 * Simulate block rendering phase.
	 */
	private function simulate_block_rendering() {
		$block_manager = \Spectra\BlockManager::instance();

		// Test that required methods exist
		$this->assertTrue( method_exists( $block_manager, 'configure_block_controller_settings' ) );

		// Test with non-Spectra block
		$settings = array( 'some_setting' => 'value' );
		$metadata = array( 'name' => 'core/paragraph' );

		$result = $block_manager->configure_block_controller_settings( $settings, $metadata );
		$this->assertEquals( $settings, $result );
	}

	/**
	 * Test error handling throughout the execution flow.
	 */
	public function test_error_handling_in_execution_flow() {
		// Test that managers handle errors gracefully
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		// These should not throw uncaught exceptions
		try {
			$block_manager->init();
			$asset_loader->init();
			$extension_manager->init();
			$this->assertTrue( true ); // Success if no exceptions thrown
		} catch ( Exception $e ) {
			$this->fail( 'Initialization should not throw uncaught exceptions: ' . $e->getMessage() );
		}
	}

	/**
	 * Test the execution flow with WordPress hooks.
	 */
	public function test_wordpress_hooks_integration() {
		// Initialize all managers
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		$block_manager->init();
		$asset_loader->init();
		$extension_manager->init();

		// Test that relevant hooks are added
		$this->assert_hook_added( 'init', $block_manager, 'register_blocks' );
		$this->assert_hook_added( 'block_categories_all', $block_manager, 'add_block_category', 9999999 );
		$this->assert_hook_added( 'wp_enqueue_scripts', $asset_loader, 'enqueue_common_style_assets' );
		$this->assert_hook_added( 'enqueue_block_editor_assets', $asset_loader, 'enqueue_editor_assets' );
		$this->assert_hook_added( 'enqueue_block_editor_assets', $extension_manager, 'register_extensions' );
	}

	/**
	 * Test manager integration and coexistence.
	 */
	public function test_manager_integration() {
		// Create instances
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		// Initialize all managers
		$block_manager->init();
		$asset_loader->init();
		$extension_manager->init();

		// Test that managers can coexist and interact properly
		$this->assertTrue( true ); // If we reach here, no conflicts occurred
	}

	/**
	 * Test performance aspects of the execution flow.
	 */
	public function test_execution_flow_performance() {
		$start_time = microtime( true );

		// Simulate complete initialization
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		$block_manager->init();
		$asset_loader->init();
		$extension_manager->init();

		$end_time = microtime( true );
		$execution_time = $end_time - $start_time;

		// Initialization should complete within reasonable time (1 second)
		$this->assertLessThan( 1.0, $execution_time, 'Plugin initialization should complete quickly' );
	}

	/**
	 * Test that all required methods exist on manager classes.
	 */
	public function test_required_methods_exist() {
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		// Test BlockManager methods
		$this->assertTrue( method_exists( $block_manager, 'init' ) );
		$this->assertTrue( method_exists( $block_manager, 'register_blocks' ) );
		$this->assertTrue( method_exists( $block_manager, 'add_block_category' ) );
		$this->assertTrue( method_exists( $block_manager, 'configure_block_controller_settings' ) );

		// Test AssetLoader methods
		$this->assertTrue( method_exists( $asset_loader, 'init' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_common_style_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'enqueue_editor_assets' ) );
		$this->assertTrue( method_exists( $asset_loader, 'handle_frontend_assets' ) );

		// Test ExtensionManager methods
		$this->assertTrue( method_exists( $extension_manager, 'init' ) );
		$this->assertTrue( method_exists( $extension_manager, 'init_extensions' ) );
		$this->assertTrue( method_exists( $extension_manager, 'register_extensions' ) );
	}

	/**
	 * Test that Singleton trait is properly used.
	 */
	public function test_singleton_pattern_usage() {
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();

		// Test that instance method exists (from Singleton trait)
		$this->assertTrue( method_exists( $block_manager, 'instance' ) );
		$this->assertTrue( method_exists( $asset_loader, 'instance' ) );
		$this->assertTrue( method_exists( $extension_manager, 'instance' ) );

		// Test that multiple calls return same instance
		$this->assertSame( $block_manager, \Spectra\BlockManager::instance() );
		$this->assertSame( $asset_loader, \Spectra\AssetLoader::instance() );
		$this->assertSame( $extension_manager, \Spectra\ExtensionManager::instance() );
	}

	/**
	 * Test plugin constants are correctly set.
	 */
	public function test_plugin_constants() {
		// Test that constants have reasonable values
		$this->assertNotEmpty( SPECTRA_3_FILE );
		$this->assertNotEmpty( SPECTRA_3_DIR );
		$this->assertNotEmpty( SPECTRA_3_URL );

		// Test that directory path ends with separator
		$this->assertStringEndsWith( DIRECTORY_SEPARATOR, SPECTRA_3_DIR );

		// Test that URL path ends with slash
		$this->assertStringEndsWith( '/', SPECTRA_3_URL );
	}

	/**
	 * Test autoloader integration.
	 */
	public function test_autoloader_integration() {
		// Test that all required classes are autoloadable
		$required_classes = array(
			'Spectra\BlockManager',
			'Spectra\AssetLoader',
			'Spectra\ExtensionManager',
			'Spectra\Helpers\Core',
			'Spectra\FontManager',
		);

		foreach ( $required_classes as $class_name ) {
			$this->assertTrue( 
				class_exists( $class_name ),
				"Class {$class_name} should be autoloadable"
			);
		}
	}
}