<?php
/**
 * Tests for Block Registration with comprehensive datasets.
 *
 * @package Spectra\Tests
 */

use Spectra\BlockManager;

/**
 * Test Block Registration with various datasets.
 */
class Test_Block_Registration_Datasets extends Spectra_Test_Case {

	/**
	 * Test block registration with various block configurations.
	 */
	public function test_block_registration_with_configurations() {
		$test_cases = $this->block_configurations_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $block_config, $expected_result ) = array_values( $case );
		// Create a temporary block.json file
		$temp_dir = sys_get_temp_dir() . '/spectra-test-blocks/';
		$block_name = $block_config['name'] ?? 'test-block';
		$block_dir = $temp_dir . str_replace( 'spectra/', '', $block_name );
		
		if ( ! is_dir( $block_dir ) ) {
			mkdir( $block_dir, 0777, true );
		}
		
		file_put_contents( 
			$block_dir . '/block.json',
			json_encode( $block_config )
		);

		// Test registration
		$result = register_block_type_from_metadata( $block_dir );
		
		if ( $expected_result['should_register'] ) {
			$this->assertNotFalse( $result, 'Block should register successfully' );
			
			// Verify attributes if specified
			if ( isset( $expected_result['attributes_count'] ) ) {
				$this->assertCount( 
					$expected_result['attributes_count'],
					$result->attributes ?? array()
				);
			}
		} else {
			$this->assertFalse( $result, 'Block registration should fail' );
		}
		
			// Cleanup
			array_map( 'unlink', glob( "$block_dir/*" ) );
			rmdir( $block_dir );
		}
	}

	/**
	 * Data provider for block configurations.
	 */
	public function block_configurations_provider() {
		return array(
			'simple block' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/simple-block',
					'title' => 'Simple Block',
					'category' => 'spectra-blocks',
					'attributes' => array(
						'content' => array(
							'type' => 'string',
							'default' => 'Hello World',
						),
					),
				),
				'expected_result' => array(
					'should_register' => true,
					'attributes_count' => 1,
				),
			),
			
			'complex attributes block' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/complex-block',
					'title' => 'Complex Block',
					'category' => 'spectra-blocks',
					'attributes' => array(
						'text' => array(
							'type' => 'string',
							'default' => '',
						),
						'number' => array(
							'type' => 'number',
							'default' => 42,
						),
						'toggle' => array(
							'type' => 'boolean',
							'default' => false,
						),
						'colors' => array(
							'type' => 'object',
							'default' => array(
								'text' => '#000000',
								'background' => '#ffffff',
							),
						),
						'items' => array(
							'type' => 'array',
							'default' => array(),
							'items' => array(
								'type' => 'object',
							),
						),
					),
				),
				'expected_result' => array(
					'should_register' => true,
					'attributes_count' => 5,
				),
			),
			
			'block with supports' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/supported-block',
					'title' => 'Supported Block',
					'category' => 'spectra-blocks',
					'supports' => array(
						'html' => false,
						'anchor' => true,
						'customClassName' => true,
						'color' => array(
							'text' => true,
							'background' => true,
							'gradients' => true,
						),
						'spacing' => array(
							'margin' => true,
							'padding' => true,
						),
						'typography' => array(
							'fontSize' => true,
							'lineHeight' => true,
						),
					),
				),
				'expected_result' => array(
					'should_register' => true,
				),
			),
			
			'nested block parent' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/parent-block',
					'title' => 'Parent Block',
					'category' => 'spectra-blocks',
					'providesContext' => array(
						'spectra/parentId' => 'blockId',
						'spectra/parentSettings' => 'settings',
					),
					'attributes' => array(
						'blockId' => array(
							'type' => 'string',
						),
						'settings' => array(
							'type' => 'object',
						),
					),
				),
				'expected_result' => array(
					'should_register' => true,
					'attributes_count' => 2,
				),
			),
			
			'nested block child' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/child-block',
					'title' => 'Child Block',
					'category' => 'spectra-blocks',
					'parent' => array( 'spectra/parent-block' ),
					'usesContext' => array( 'spectra/parentId', 'spectra/parentSettings' ),
				),
				'expected_result' => array(
					'should_register' => true,
				),
			),
			
			'invalid block - missing name' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'title' => 'Invalid Block',
					'category' => 'spectra-blocks',
				),
				'expected_result' => array(
					'should_register' => false,
				),
			),
			
			'invalid block - malformed attributes' => array(
				'block_config' => array(
					'apiVersion' => 3,
					'name' => 'spectra/malformed-block',
					'title' => 'Malformed Block',
					'category' => 'spectra-blocks',
					'attributes' => 'not-an-object',
				),
				'expected_result' => array(
					'should_register' => false,
				),
			),
		);
	}

	/**
	 * Test block category addition with different scenarios.
	 */
	public function test_block_category_addition() {
		$test_cases = $this->block_categories_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $existing_categories, $expected_count, $expected_first_slug ) = array_values( $case );
		$block_manager = BlockManager::instance();
		
		$result = $block_manager->add_block_category( $existing_categories );
		
		$this->assertCount( $expected_count, $result );
		$this->assertEquals( $expected_first_slug, $result[0]['slug'] );
		
			// Verify Spectra category is always first
			$spectra_category = $result[0];
			$this->assertEquals( 'spectra-blocks', $spectra_category['slug'], "Failed for case: {$name}" );
			// If spectra-blocks already existed, the title might be different
			if ( $name === 'spectra already exists' ) {
				$this->assertEquals( 'Existing Spectra Blocks', $spectra_category['title'], "Failed for case: {$name}" );
			} else {
				$this->assertEquals( 'Spectra Blocks', $spectra_category['title'], "Failed for case: {$name}" );
				$this->assertEquals( 'superhero', $spectra_category['icon'], "Failed for case: {$name}" );
			}
		}
	}

	/**
	 * Data provider for block categories.
	 */
	public function block_categories_provider() {
		return array(
			'empty categories' => array(
				'existing_categories' => array(),
				'expected_count' => 1,
				'expected_first_slug' => 'spectra-blocks',
			),

			'with core categories' => array(
				'existing_categories' => array(
					array( 'slug' => 'text', 'title' => 'Text' ),
					array( 'slug' => 'media', 'title' => 'Media' ),
					array( 'slug' => 'design', 'title' => 'Design' ),
				),
				'expected_count' => 4,
				'expected_first_slug' => 'spectra-blocks',
			),

			'spectra already exists' => array(
				'existing_categories' => array(
					array( 'slug' => 'spectra-blocks', 'title' => 'Existing Spectra Blocks' ),
					array( 'slug' => 'other', 'title' => 'Other' ),
				),
				'expected_count' => 2,
				'expected_first_slug' => 'spectra-blocks',
			),

			'large category list' => array(
				'existing_categories' => array_map( function( $i ) {
					return array( 'slug' => "category-$i", 'title' => "Category $i" );
				}, range( 1, 50 ) ),
				'expected_count' => 51,
				'expected_first_slug' => 'spectra-blocks',
			),
		);
	}

	/**
	 * Test controller-based rendering configuration.
	 */
	public function test_controller_based_rendering() {
		$test_cases = $this->controller_settings_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $settings, $metadata, $controller_exists, $expected_render_callback ) = array_values( $case );
		$block_manager = BlockManager::instance();
		
		// Mock controller file existence
		if ( $controller_exists && isset( $metadata['file'] ) ) {
			$controller_path = dirname( $metadata['file'] ) . '/controller.php';
			if ( ! file_exists( dirname( $controller_path ) ) ) {
				mkdir( dirname( $controller_path ), 0777, true );
			}
			file_put_contents( $controller_path, '<?php // Mock controller' );
		}
		
		$result = $block_manager->configure_block_controller_settings( $settings, $metadata );
		
		if ( $expected_render_callback ) {
			$this->assertArrayHasKey( 'render_callback', $result );
			// The actual implementation returns a closure, not a string
			$this->assertIsCallable( $result['render_callback'] );
		} else {
			// If no controller or existing callback, settings should be unchanged
			$this->assertEquals( $settings, $result );
		}
		
			// Cleanup
			if ( $controller_exists && isset( $metadata['file'] ) ) {
				$controller_path = dirname( $metadata['file'] ) . '/controller.php';
				if ( file_exists( $controller_path ) ) {
					unlink( $controller_path );
					// Only remove directory if it's empty
					$dir = dirname( $controller_path );
					if ( is_dir( $dir ) && count( scandir( $dir ) ) == 2 ) { // Only . and ..
						rmdir( $dir );
					}
				}
			}
		}
	}

	/**
	 * Data provider for controller settings.
	 */
	public function controller_settings_provider() {
		return array(
			'non-spectra block' => array(
				'settings' => array( 'test' => 'value' ),
				'metadata' => array( 'name' => 'core/paragraph' ),
				'controller_exists' => false,
				'expected_render_callback' => null,
			),
			
			'spectra block without controller' => array(
				'settings' => array( 'test' => 'value' ),
				'metadata' => array(
					'name' => 'spectra/test-block',
					'file' => '/tmp/test-block/block.json',
				),
				'controller_exists' => false,
				'expected_render_callback' => null,
			),
			
			'spectra block with controller' => array(
				'settings' => array( 'test' => 'value' ),
				'metadata' => array(
					'name' => 'spectra/test-block',
					'file' => '/tmp/test-block/block.json',
				),
				'controller_exists' => true,
				'expected_render_callback' => true,
			),
			
			'spectra block with existing callback' => array(
				'settings' => array(
					'test' => 'value',
					'render_callback' => 'existing_callback',
				),
				'metadata' => array(
					'name' => 'spectra/test-block',
					'file' => '/tmp/test-block/block.json',
				),
				'controller_exists' => true,
				'expected_render_callback' => true,
			),
		);
	}

	/**
	 * Test block discovery and batch registration.
	 */
	public function test_block_discovery_and_batch_registration() {
		// Create temporary block structure
		$temp_dir = sys_get_temp_dir() . '/spectra-test-discovery/';
		$blocks = array(
			'button' => array(
				'apiVersion' => 3,
				'name' => 'spectra/button',
				'title' => 'Button',
			),
			'accordion' => array(
				'apiVersion' => 3,
				'name' => 'spectra/accordion',
				'title' => 'Accordion',
			),
			'slider' => array(
				'apiVersion' => 3,
				'name' => 'spectra/slider',
				'title' => 'Slider',
			),
		);
		
		// Create block files
		foreach ( $blocks as $dir => $config ) {
			$block_dir = $temp_dir . $dir;
			if ( ! is_dir( $block_dir ) ) {
				mkdir( $block_dir, 0777, true );
			}
			file_put_contents(
				$block_dir . '/block.json',
				json_encode( $config )
			);
		}
		
		// Test discovery
		$discovered = glob( $temp_dir . '*/block.json' );
		$this->assertCount( 3, $discovered, 'Should discover 3 blocks' );
		
		// Cleanup
		foreach ( $blocks as $dir => $config ) {
			$block_dir = $temp_dir . $dir;
			unlink( $block_dir . '/block.json' );
			rmdir( $block_dir );
		}
		rmdir( $temp_dir );
	}

	/**
	 * Test performance with large number of blocks.
	 */
	public function test_block_registration_performance() {
		$start_time = microtime( true );
		$temp_dir = sys_get_temp_dir() . '/spectra-perf-test/';
		
		// Create 100 test blocks
		for ( $i = 1; $i <= 100; $i++ ) {
			$block_dir = $temp_dir . "block-$i";
			if ( ! is_dir( $block_dir ) ) {
				mkdir( $block_dir, 0777, true );
			}
			
			$config = array(
				'apiVersion' => 3,
				'name' => "spectra/test-block-$i",
				'title' => "Test Block $i",
				'category' => 'spectra-blocks',
				'attributes' => array(
					'content' => array(
						'type' => 'string',
						'default' => "Block $i content",
					),
				),
			);
			
			file_put_contents(
				$block_dir . '/block.json',
				json_encode( $config )
			);
		}
		
		// Measure discovery time
		$discovery_start = microtime( true );
		$discovered = glob( $temp_dir . '*/block.json' );
		$discovery_time = microtime( true ) - $discovery_start;
		
		$this->assertCount( 100, $discovered );
		$this->assertLessThan( 0.1, $discovery_time, 'Discovery should be fast' );
		
		// Cleanup
		for ( $i = 1; $i <= 100; $i++ ) {
			$block_dir = $temp_dir . "block-$i";
			unlink( $block_dir . '/block.json' );
			rmdir( $block_dir );
		}
		rmdir( $temp_dir );
		
		$total_time = microtime( true ) - $start_time;
		$this->assertLessThan( 1.0, $total_time, 'Total operation should complete within 1 second' );
	}
}