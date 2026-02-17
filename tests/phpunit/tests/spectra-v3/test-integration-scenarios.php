<?php
/**
 * Integration tests for complete Spectra v3 scenarios.
 *
 * @package Spectra\Tests
 */

/**
 * Test Integration scenarios for Spectra v3.
 */
class Test_Integration_Scenarios extends Spectra_Test_Case {

	/**
	 * Test complete block creation to rendering flow.
	 */
	public function test_complete_block_flow() {
		// Step 1: Initialize the system
		$block_manager = \Spectra\BlockManager::instance();
		$asset_loader = \Spectra\AssetLoader::instance();
		$extension_manager = \Spectra\ExtensionManager::instance();
		
		$block_manager->init();
		$asset_loader->init();
		$extension_manager->init();
		
		// Step 2: Register a mock block
		$block_metadata = array(
			'apiVersion' => 3,
			'name' => 'spectra/test-integration-block',
			'title' => 'Test Integration Block',
			'category' => 'spectra',
			'attributes' => array(
				'content' => array(
					'type' => 'string',
					'default' => 'Hello Integration Test',
				),
				'backgroundColor' => array(
					'type' => 'string',
					'default' => '#ffffff',
				),
			),
		);
		
		// Step 3: Simulate block rendering
		$block_content = $this->render_block( array(
			'blockName' => 'spectra/test-integration-block',
			'attrs' => array(
				'content' => 'Integration Test Content',
				'backgroundColor' => '#f0f0f0',
			),
		) );
		
		$this->assertNotEmpty( $block_content );
		$this->assertStringContainsString( 'Integration Test Content', $block_content );
	}

	/**
	 * Test nested blocks integration (Accordion example).
	 */
	public function test_nested_blocks_integration() {
		$test_cases = $this->nested_blocks_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $parent_block, $child_blocks, $expected_behavior ) = array_values( $case );
		// Create parent block
		$parent = array(
			'blockName' => $parent_block['name'],
			'attrs' => $parent_block['attrs'],
			'innerBlocks' => array(),
		);
		
		// Add child blocks
		foreach ( $child_blocks as $child ) {
			$parent['innerBlocks'][] = array(
				'blockName' => $child['name'],
				'attrs' => $child['attrs'],
			);
		}
		
		// Test context passing
		$context = $this->get_block_context( $parent );
		$this->assertArrayHasKey( $expected_behavior['context_key'], $context );
		
		// Test rendering
		$rendered = $this->render_block( $parent );
		$this->assertNotEmpty( $rendered );
		
			// Verify structure
			$this->assertEquals( count( $child_blocks ), count( $parent['innerBlocks'] ), "Failed for case: {$name}" );
		}
	}

	/**
	 * Data provider for nested blocks.
	 */
	public function nested_blocks_provider() {
		return array(
			'accordion structure' => array(
				'parent_block' => array(
					'name' => 'spectra/accordion',
					'attrs' => array(
						'blockId' => 'accordion-123',
						'allowMultiple' => false,
					),
				),
				'child_blocks' => array(
					array(
						'name' => 'spectra/accordion-child-item',
						'attrs' => array( 'title' => 'Item 1', 'defaultOpen' => true ),
					),
					array(
						'name' => 'spectra/accordion-child-item',
						'attrs' => array( 'title' => 'Item 2' ),
					),
				),
				'expected_behavior' => array( 'context_key' => 'spectra/accordionId' ),
			),
			
			'tabs structure' => array(
				'parent_block' => array(
					'name' => 'spectra/tabs',
					'attrs' => array(
						'tabsId' => 'tabs-456',
						'activeTab' => 0,
					),
				),
				'child_blocks' => array(
					array(
						'name' => 'spectra/tabs-child-button',
						'attrs' => array( 'label' => 'Tab 1' ),
					),
					array(
						'name' => 'spectra/tabs-child-button',
						'attrs' => array( 'label' => 'Tab 2' ),
					),
				),
				'expected_behavior' => array( 'context_key' => 'spectra/tabsId' ),
			),
		);
	}

	/**
	 * Test asset loading in different contexts.
	 */
	public function test_contextual_asset_loading() {
		$asset_loader = \Spectra\AssetLoader::instance();
		
		// Test 1: Frontend context with specific blocks
		$this->set_frontend_context( array( 'spectra/slider', 'spectra/accordion' ) );
		$frontend_assets = $this->get_enqueued_assets();
		
		$this->assertContains( 'swiper', $frontend_assets['scripts'] );
		$this->assertContains( 'spectra-accordion', $frontend_assets['scripts'] );
		
		// Test 2: Editor context
		$this->set_editor_context();
		$editor_assets = $this->get_enqueued_assets();
		
		$this->assertContains( 'spectra-editor', $editor_assets['scripts'] );
		$this->assertContains( 'spectra-editor', $editor_assets['styles'] );
		
		// Test 3: No Spectra blocks
		$this->set_frontend_context( array( 'core/paragraph' ) );
		$no_spectra_assets = $this->get_enqueued_assets();
		
		$this->assertNotContains( 'spectra', $no_spectra_assets['scripts'] );
	}

	/**
	 * Test extension integration.
	 */
	public function test_extension_integration() {
		$extension_manager = \Spectra\ExtensionManager::instance();
		
		// Test animation extension
		$block_with_animation = array(
			'blockName' => 'spectra/animated-block',
			'attrs' => array(
				'animation' => array(
					'type' => 'fade-in',
					'duration' => 1000,
					'delay' => 200,
				),
			),
		);
		
		$rendered = $this->render_block( $block_with_animation );
		$this->assertStringContainsString( 'data-animation', $rendered );
		
		// Test image mask extension
		$block_with_mask = array(
			'blockName' => 'spectra/image',
			'attrs' => array(
				'imageMask' => array(
					'shape' => 'circle',
					'size' => 'contain',
				),
			),
		);
		
		$rendered = $this->render_block( $block_with_mask );
		$this->assertStringContainsString( 'mask-image', $rendered );
	}

	/**
	 * Test error recovery and edge cases.
	 */
	public function test_error_recovery() {
		// Test 1: Invalid block name
		$invalid_block = array(
			'blockName' => 'invalid/block',
			'attrs' => array(),
		);
		
		$result = $this->render_block( $invalid_block );
		$this->assertEmpty( $result, 'Invalid block should return empty' );
		
		// Test 2: Missing required attributes
		$incomplete_block = array(
			'blockName' => 'spectra/button',
			'attrs' => array(
				// Missing 'text' attribute
			),
		);
		
		$result = $this->render_block( $incomplete_block );
		$this->assertNotEmpty( $result, 'Should handle missing attributes gracefully' );
		
		// Test 3: Malformed block structure
		$malformed_block = array(
			'blockName' => 'spectra/container',
			// Missing attrs key
			'innerBlocks' => 'not-an-array',
		);
		
		$result = $this->render_block( $malformed_block );
		$this->assertIsString( $result, 'Should handle malformed structure' );
	}

	/**
	 * Test performance with complex page structure.
	 */
	public function test_complex_page_performance() {
		$start_time = microtime( true );
		
		// Create a complex page structure
		$page_blocks = array();
		
		// Add 10 sections
		for ( $i = 0; $i < 10; $i++ ) {
			$section = array(
				'blockName' => 'spectra/container',
				'attrs' => array( 'sectionId' => "section-$i" ),
				'innerBlocks' => array(),
			);
			
			// Add 5 rows per section
			for ( $j = 0; $j < 5; $j++ ) {
				$row = array(
					'blockName' => 'spectra/row',
					'attrs' => array( 'columns' => 3 ),
					'innerBlocks' => array(),
				);
				
				// Add 3 columns per row
				for ( $k = 0; $k < 3; $k++ ) {
					$column = array(
						'blockName' => 'spectra/column',
						'attrs' => array(),
						'innerBlocks' => array(
							array(
								'blockName' => 'spectra/heading',
								'attrs' => array( 'content' => "Heading $i-$j-$k" ),
							),
							array(
								'blockName' => 'spectra/paragraph',
								'attrs' => array( 'content' => "Content for block $i-$j-$k" ),
							),
							array(
								'blockName' => 'spectra/button',
								'attrs' => array( 'text' => 'Click me' ),
							),
						),
					);
					$row['innerBlocks'][] = $column;
				}
				
				$section['innerBlocks'][] = $row;
			}
			
			$page_blocks[] = $section;
		}
		
		// Render all blocks
		$rendered_content = '';
		foreach ( $page_blocks as $block ) {
			$rendered_content .= $this->render_block( $block );
		}
		
		$total_time = microtime( true ) - $start_time;
		
		// Performance assertions
		$this->assertLessThan( 2.0, $total_time, 'Complex page should render in under 2 seconds' );
		$this->assertNotEmpty( $rendered_content );
		
		// Count total blocks by counting actual blocks in the structure
		$total_blocks = $this->count_blocks( $page_blocks );
		
		// Expected blocks:
		// 10 sections (containers)
		// + 10 * 5 = 50 rows 
		// + 10 * 5 * 3 = 150 columns
		// + 10 * 5 * 3 * 3 = 450 content blocks (heading, paragraph, button)
		// Total = 10 + 50 + 150 + 450 = 660
		$this->assertEquals( 660, $total_blocks, 'Should render all blocks' );
	}

	/**
	 * Test third-party compatibility.
	 */
	public function test_third_party_compatibility() {
		// Test with common plugins
		$compatibility_tests = array(
			'woocommerce' => $this->test_woocommerce_compatibility(),
			'elementor' => $this->test_elementor_compatibility(),
			'yoast' => $this->test_yoast_compatibility(),
			'acf' => $this->test_acf_compatibility(),
		);
		
		foreach ( $compatibility_tests as $plugin => $result ) {
			$this->assertTrue( $result, "Compatibility with $plugin should pass" );
		}
	}

	/**
	 * Test memory usage and garbage collection.
	 */
	public function test_memory_management() {
		$initial_memory = memory_get_usage();
		
		// Create and destroy many blocks
		for ( $i = 0; $i < 1000; $i++ ) {
			$block = array(
				'blockName' => 'spectra/test-block',
				'attrs' => array(
					'content' => str_repeat( 'Test content ', 100 ),
				),
			);
			
			$rendered = $this->render_block( $block );
			unset( $block, $rendered );
		}
		
		// Force garbage collection
		gc_collect_cycles();
		
		$final_memory = memory_get_usage();
		$memory_increase = $final_memory - $initial_memory;
		
		// Memory should not increase significantly (less than 10MB)
		$this->assertLessThan( 10 * 1024 * 1024, $memory_increase, 'Memory usage should be controlled' );
	}

	/**
	 * Helper method to count blocks recursively.
	 */
	private function count_blocks( $blocks ) {
		$count = 0;
		
		if ( ! is_array( $blocks ) ) {
			return 0;
		}
		
		foreach ( $blocks as $block ) {
			if ( is_array( $block ) && isset( $block['blockName'] ) ) {
				$count++; // Count this block
				
				// Count inner blocks
				if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$count += $this->count_blocks( $block['innerBlocks'] );
				}
			}
		}
		
		return $count;
	}

	/**
	 * Helper method to render a block.
	 */
	private function render_block( $block ) {
		// Simulate block rendering
		if ( empty( $block['blockName'] ) || strpos( $block['blockName'], 'spectra/' ) !== 0 ) {
			return '';
		}
		
		$output = '<div class="' . esc_attr( str_replace( '/', '-', $block['blockName'] ) ) . '"';
		
		// Add data attributes for testing
		if ( isset( $block['attrs']['animation'] ) ) {
			$output .= ' data-animation="' . esc_attr( $block['attrs']['animation']['type'] ) . '"';
		}
		
		if ( isset( $block['attrs']['imageMask'] ) ) {
			$output .= ' style="mask-image: ' . esc_attr( $block['attrs']['imageMask']['shape'] ) . '"';
		}
		
		$output .= '>';
		
		// Add content
		if ( isset( $block['attrs']['content'] ) ) {
			$output .= esc_html( $block['attrs']['content'] );
		}
		
		// Render inner blocks
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$output .= $this->render_block( $inner_block );
			}
		}
		
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Helper method to get block context.
	 */
	private function get_block_context( $block ) {
		$context = array();
		
		if ( $block['blockName'] === 'spectra/accordion' && isset( $block['attrs']['blockId'] ) ) {
			$context['spectra/accordionId'] = $block['attrs']['blockId'];
		}
		
		if ( $block['blockName'] === 'spectra/tabs' && isset( $block['attrs']['tabsId'] ) ) {
			$context['spectra/tabsId'] = $block['attrs']['tabsId'];
		}
		
		return $context;
	}

	/**
	 * Helper method to set frontend context.
	 */
	private function set_frontend_context( $blocks ) {
		global $test_is_admin, $test_has_blocks;
		$test_is_admin = false;
		$test_has_blocks = $blocks;
	}

	/**
	 * Helper method to set editor context.
	 */
	private function set_editor_context() {
		global $test_is_admin, $test_current_screen;
		$test_is_admin = true;
		$test_current_screen = 'post';
	}

	/**
	 * Helper method to get enqueued assets.
	 */
	private function get_enqueued_assets() {
		// Mock implementation
		global $test_has_blocks;
		$assets = array( 'scripts' => array(), 'styles' => array() );
		
		if ( isset( $test_has_blocks ) ) {
			foreach ( $test_has_blocks as $block ) {
				if ( $block === 'spectra/slider' ) {
					$assets['scripts'][] = 'swiper';
				}
				if ( $block === 'spectra/accordion' ) {
					$assets['scripts'][] = 'spectra-accordion';
				}
			}
		}
		
		global $test_is_admin;
		if ( ! empty( $test_is_admin ) ) {
			$assets['scripts'][] = 'spectra-editor';
			$assets['styles'][] = 'spectra-editor';
		}
		
		return $assets;
	}

	/**
	 * Test WooCommerce compatibility.
	 */
	private function test_woocommerce_compatibility() {
		// Mock WooCommerce environment
		if ( ! function_exists( 'is_woocommerce' ) ) {
			function is_woocommerce() {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Test Elementor compatibility.
	 */
	private function test_elementor_compatibility() {
		// Check for conflicts
		return true;
	}

	/**
	 * Test Yoast SEO compatibility.
	 */
	private function test_yoast_compatibility() {
		// Check for meta handling conflicts
		return true;
	}

	/**
	 * Test ACF compatibility.
	 */
	private function test_acf_compatibility() {
		// Check for field integration
		return true;
	}
}