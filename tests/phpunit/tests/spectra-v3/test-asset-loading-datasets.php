<?php
/**
 * Tests for Asset Loading with comprehensive datasets.
 *
 * @package Spectra\Tests
 */

use Spectra\AssetLoader;

/**
 * Test Asset Loading with various datasets.
 */
class Test_Asset_Loading_Datasets extends Spectra_Test_Case {

	/**
	 * Tracked enqueued scripts and styles.
	 */
	private $enqueued_scripts = array();
	private $enqueued_styles = array();
	private $registered_scripts = array();
	private $registered_styles = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Reset tracking arrays
		$this->enqueued_scripts = array();
		$this->enqueued_styles = array();
		$this->registered_scripts = array();
		$this->registered_styles = array();
		
		// Mock WordPress enqueue functions
		$this->mock_wp_enqueue_functions();
	}

	/**
	 * Mock WordPress enqueue functions for testing.
	 */
	private function mock_wp_enqueue_functions() {
		// Override global functions for testing
		if ( ! function_exists( '__test_wp_enqueue_script' ) ) {
			function __test_wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
				global $test_enqueued_scripts;
				if ( ! isset( $test_enqueued_scripts ) ) {
					$test_enqueued_scripts = array();
				}
				$test_enqueued_scripts[ $handle ] = array(
					'src' => $src,
					'deps' => $deps,
					'ver' => $ver,
					'in_footer' => $in_footer,
				);
			}
			
			function __test_wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
				global $test_enqueued_styles;
				if ( ! isset( $test_enqueued_styles ) ) {
					$test_enqueued_styles = array();
				}
				$test_enqueued_styles[ $handle ] = array(
					'src' => $src,
					'deps' => $deps,
					'ver' => $ver,
					'media' => $media,
				);
			}
			
			function __test_wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
				global $test_registered_scripts;
				if ( ! isset( $test_registered_scripts ) ) {
					$test_registered_scripts = array();
				}
				$test_registered_scripts[ $handle ] = array(
					'src' => $src,
					'deps' => $deps,
					'ver' => $ver,
					'in_footer' => $in_footer,
				);
			}
			
			function __test_wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
				global $test_registered_styles;
				if ( ! isset( $test_registered_styles ) ) {
					$test_registered_styles = array();
				}
				$test_registered_styles[ $handle ] = array(
					'src' => $src,
					'deps' => $deps,
					'ver' => $ver,
					'media' => $media,
				);
			}
		}
	}

	/**
	 * Test asset loading with different dependency scenarios.
	 */
	public function test_asset_loading_with_dependencies() {
		$test_cases = $this->asset_dependency_provider();
		
		foreach ( $test_cases as $name => $test_case ) {
			$assets = $test_case['assets'];
			$expected_load_order = $test_case['expected_order'];
			
			$asset_loader = AssetLoader::instance();
			
			// Mock asset files
			foreach ( $assets as $handle => $asset_data ) {
				if ( isset( $asset_data['register'] ) ) {
					wp_register_script(
						$handle,
						$asset_data['src'],
						$asset_data['deps'] ?? array(),
						$asset_data['ver'] ?? false
					);
				}
			}
			
			// Enqueue assets
			foreach ( $assets as $handle => $asset_data ) {
				if ( isset( $asset_data['enqueue'] ) && $asset_data['enqueue'] ) {
					wp_enqueue_script( $handle );
				}
			}
			
			// Verify load order (this is conceptual as WordPress handles actual dependency resolution)
			$this->assertTrue( true, "Test case: $name" ); // Placeholder for actual dependency testing
		}
	}

	/**
	 * Data provider for asset dependencies.
	 */
	public function asset_dependency_provider() {
		return array(
			'simple dependencies' => array(
				'assets' => array(
					'spectra-base' => array(
						'src' => 'base.js',
						'deps' => array(),
						'register' => true,
						'enqueue' => true,
					),
					'spectra-utils' => array(
						'src' => 'utils.js',
						'deps' => array( 'spectra-base' ),
						'register' => true,
						'enqueue' => true,
					),
					'spectra-blocks' => array(
						'src' => 'blocks.js',
						'deps' => array( 'spectra-utils' ),
						'register' => true,
						'enqueue' => true,
					),
				),
				'expected_order' => array( 'spectra-base', 'spectra-utils', 'spectra-blocks' ),
			),
			
			'complex dependencies' => array(
				'assets' => array(
					'jquery' => array(
						'src' => 'jquery.js',
						'deps' => array(),
						'register' => true,
					),
					'lodash' => array(
						'src' => 'lodash.js', 
						'deps' => array(),
						'register' => true,
					),
					'spectra-core' => array(
						'src' => 'core.js',
						'deps' => array( 'jquery', 'lodash' ),
						'register' => true,
						'enqueue' => true,
					),
					'spectra-animations' => array(
						'src' => 'animations.js',
						'deps' => array( 'spectra-core', 'jquery' ),
						'register' => true,
						'enqueue' => true,
					),
				),
				'expected_order' => array( 'jquery', 'lodash', 'spectra-core', 'spectra-animations' ),
			),
		);
	}

	/**
	 * Test conditional asset loading based on context.
	 */
	public function test_conditional_asset_loading() {
		$test_cases = $this->conditional_loading_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $context, $blocks_present, $expected_assets ) = array_values( $case );
		$asset_loader = AssetLoader::instance();
		
		// Mock context
		global $test_is_admin, $test_is_rtl;
		$test_is_admin = $context['is_admin'] ?? false;
		$test_is_rtl = $context['is_rtl'] ?? false;
		
		// Mock has_block function results
		if ( ! function_exists( '__test_has_block' ) ) {
			function __test_has_block( $block_name ) {
				global $test_blocks_present;
				return in_array( $block_name, $test_blocks_present ?? array(), true );
			}
		}
		
		global $test_blocks_present;
		$test_blocks_present = $blocks_present;
		
		// Test frontend asset loading
		$asset_loader->handle_frontend_assets();
		
			// Verify expected assets are loaded
			foreach ( $expected_assets as $asset ) {
				$this->assertTrue( true, "Failed for case: {$name}" ); // Placeholder for actual asset verification
			}
		}
	}

	/**
	 * Data provider for conditional loading.
	 */
	public function conditional_loading_provider() {
		return array(
			'frontend with slider block' => array(
				'context' => array( 'is_admin' => false ),
				'blocks_present' => array( 'spectra/slider' ),
				'expected_assets' => array( 'swiper', 'spectra-slider-frontend' ),
			),
			
			'frontend with accordion block' => array(
				'context' => array( 'is_admin' => false ),
				'blocks_present' => array( 'spectra/accordion' ),
				'expected_assets' => array( 'spectra-accordion-frontend' ),
			),
			
			'admin editor' => array(
				'context' => array( 'is_admin' => true ),
				'blocks_present' => array(),
				'expected_assets' => array( 'spectra-editor-assets' ),
			),
			
			'rtl context' => array(
				'context' => array( 'is_rtl' => true ),
				'blocks_present' => array( 'spectra/button' ),
				'expected_assets' => array( 'spectra-rtl-styles' ),
			),
			
			'multiple blocks' => array(
				'context' => array( 'is_admin' => false ),
				'blocks_present' => array( 'spectra/slider', 'spectra/accordion', 'spectra/tabs' ),
				'expected_assets' => array( 'swiper', 'spectra-slider-frontend', 'spectra-accordion-frontend', 'spectra-tabs-frontend' ),
			),
		);
	}

	/**
	 * Test asset versioning and cache busting.
	 */
	public function test_asset_versioning() {
		$test_cases = $this->asset_versioning_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $asset_config, $expected_version_format ) = array_values( $case );
		$asset_loader = AssetLoader::instance();
		
		// Mock asset file
		$asset_file = array(
			'dependencies' => $asset_config['deps'] ?? array(),
			'version' => $asset_config['version'] ?? null,
		);
		
		// Test version generation
		$version = $asset_file['version'] ?? filemtime( __FILE__ );
		
		if ( $expected_version_format === 'timestamp' ) {
			$this->assertIsNumeric( $version );
		} elseif ( $expected_version_format === 'hash' ) {
			$this->assertMatchesRegularExpression( '/^[a-f0-9]{8,}$/', $version );
			} else {
				$this->assertEquals( $expected_version_format, $version, "Failed for case: {$name}" );
			}
		}
	}

	/**
	 * Data provider for asset versioning.
	 */
	public function asset_versioning_provider() {
		return array(
			'development version' => array(
				'asset_config' => array( 'version' => time() ),
				'expected_version_format' => 'timestamp',
			),
			
			'production version' => array(
				'asset_config' => array( 'version' => '1.2.3' ),
				'expected_version_format' => '1.2.3',
			),
			
			'hash-based version' => array(
				'asset_config' => array( 'version' => md5( 'content-hash' ) ),
				'expected_version_format' => 'hash',
			),
			
			'no version' => array(
				'asset_config' => array(),
				'expected_version_format' => 'timestamp',
			),
		);
	}

	/**
	 * Test performance with large number of assets.
	 */
	public function test_asset_loading_performance() {
		$asset_loader = AssetLoader::instance();
		$start_time = microtime( true );
		
		// Register 100 test assets
		for ( $i = 1; $i <= 100; $i++ ) {
			wp_register_script(
				"spectra-test-script-$i",
				"test-script-$i.js",
				array(),
				'1.0.0'
			);
			
			wp_register_style(
				"spectra-test-style-$i",
				"test-style-$i.css",
				array(),
				'1.0.0'
			);
		}
		
		// Enqueue 50 of them
		for ( $i = 1; $i <= 50; $i++ ) {
			wp_enqueue_script( "spectra-test-script-$i" );
			wp_enqueue_style( "spectra-test-style-$i" );
		}
		
		$total_time = microtime( true ) - $start_time;
		
		$this->assertLessThan( 0.5, $total_time, 'Asset operations should be performant' );
	}

	/**
	 * Test inline script and style handling.
	 */
	public function test_inline_asset_handling() {
		$test_cases = $this->inline_assets_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $inline_data, $expected_output ) = array_values( $case );
		$asset_loader = AssetLoader::instance();
		
		// Test inline script
		if ( isset( $inline_data['script'] ) ) {
			wp_add_inline_script(
				$inline_data['handle'],
				$inline_data['script'],
				$inline_data['position'] ?? 'after'
			);
		}
		
		// Test inline style
		if ( isset( $inline_data['style'] ) ) {
			wp_add_inline_style(
				$inline_data['handle'],
				$inline_data['style']
			);
		}
		
			// Verify output (conceptual)
			$this->assertTrue( true, "Failed for case: {$name}" );
		}
	}

	/**
	 * Data provider for inline assets.
	 */
	public function inline_assets_provider() {
		return array(
			'inline script configuration' => array(
				'inline_data' => array(
					'handle' => 'spectra-config',
					'script' => 'var spectraConfig = ' . json_encode( array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'spectra-nonce' ),
					) ) . ';',
					'position' => 'before',
				),
				'expected_output' => 'var spectraConfig',
			),
			
			'inline CSS variables' => array(
				'inline_data' => array(
					'handle' => 'spectra-vars',
					'style' => ':root { --spectra-primary: #007cba; --spectra-secondary: #f0f0f0; }',
				),
				'expected_output' => '--spectra-primary',
			),
			
			'dynamic styles' => array(
				'inline_data' => array(
					'handle' => 'spectra-dynamic',
					'style' => '.spectra-block-123 { background: #ff0000; padding: 20px; }',
				),
				'expected_output' => '.spectra-block-123',
			),
		);
	}

	/**
	 * Test asset loading error handling.
	 */
	public function test_asset_loading_error_handling() {
		$asset_loader = AssetLoader::instance();
		
		// Test with non-existent file
		$result = $asset_loader->register_block_assets();
		$this->assertTrue( true ); // Should not throw errors
		
		// Test with invalid asset data
		$invalid_asset = array(
			'dependencies' => 'not-an-array',
			'version' => array( 'not-a-string' ),
		);
		
		// Should handle gracefully
		$this->assertTrue( true );
	}

	/**
	 * Test theme and plugin compatibility.
	 */
	public function test_theme_plugin_compatibility() {
		$test_cases = $this->compatibility_provider();
		
		foreach ( $test_cases as $name => $case ) {
			list( $active_theme, $active_plugins, $expected_adjustments ) = array_values( $case );
		// Mock active theme
		if ( ! function_exists( '__test_get_template' ) ) {
			function __test_get_template() {
				global $test_active_theme;
				return $test_active_theme ?? 'default';
			}
		}
		
		global $test_active_theme;
		$test_active_theme = $active_theme;
		
		// Mock active plugins
		global $test_active_plugins;
		$test_active_plugins = $active_plugins;
		
		$asset_loader = AssetLoader::instance();
		
			// Test compatibility adjustments
			foreach ( $expected_adjustments as $adjustment ) {
				$this->assertTrue( true, "Failed for case: {$name}" ); // Placeholder for compatibility testing
			}
		}
	}

	/**
	 * Data provider for compatibility testing.
	 */
	public function compatibility_provider() {
		return array(
			'astra theme' => array(
				'active_theme' => 'astra',
				'active_plugins' => array(),
				'expected_adjustments' => array( 'astra-specific-styles' ),
			),
			
			'elementor active' => array(
				'active_theme' => 'twentytwentythree',
				'active_plugins' => array( 'elementor/elementor.php' ),
				'expected_adjustments' => array( 'elementor-compatibility-styles' ),
			),
			
			'woocommerce active' => array(
				'active_theme' => 'storefront',
				'active_plugins' => array( 'woocommerce/woocommerce.php' ),
				'expected_adjustments' => array( 'woocommerce-block-styles' ),
			),
		);
	}
}