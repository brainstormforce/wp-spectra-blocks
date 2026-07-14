<?php
/**
 * Utility functions for Spectra v3.
 *
 * @package Spectra
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- utility file intentionally combines helper functions with the CSS generator class they support

/**
 * Get complete CSS content for post preview from both Spectra v3 and Pro v2 blocks.
 * Optimized architecture with plugin registry system for extensibility.
 *
 * @since 3.0.0
 * @param int $post_id Post ID to generate CSS for.
 * @return string Complete CSS content for pattern block preview.
 */
function spectra_get_v3_blocks_css_for_preview( $post_id ) {
	if ( ! $post_id ) {
		return '';
	}

	$post = get_post( $post_id );
	if ( ! $post || empty( $post->post_content ) ) {
		return '';
	}

	$blocks = parse_blocks( $post->post_content );
	if ( empty( $blocks ) ) {
		return '';
	}

	$css_generator = new Spectra_CSS_Generator();

	\SpectraBlocks\Extensions\ResponsiveControls::$is_pattern_preview = true;
	$css = $css_generator->generate_preview_css( $post_id, $blocks );
	\SpectraBlocks\Extensions\ResponsiveControls::$is_pattern_preview = false;

	return $css;
}

/**
 * CSS Generator class for handling both Spectra v3 and Pro v2 blocks.
 * Provides extensible architecture for adding future plugin support.
 *
 * @since 3.0.0
 */
class Spectra_CSS_Generator {

	/**
	 * Registered plugin handlers for CSS generation.
	 *
	 * @var array
	 */
	private $plugin_handlers = array();


	/**
	 * Constructor - registers default plugin handlers.
	 */
	public function __construct() {
		$this->register_default_handlers();
	}

	/**
	 * Generate complete CSS for post preview.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $blocks Parsed blocks.
	 * @return string Generated CSS.
	 */
	public function generate_preview_css( $post_id, $blocks ) {
		$used_blocks = $this->get_used_block_types( $blocks );

		$css_parts = array(
			$this->generate_static_css( $used_blocks ),
			$this->generate_editor_css( $used_blocks ),
			$this->generate_extensions_css( $blocks ),
			$this->generate_layout_css( $blocks ),
			$this->generate_responsive_css_from_blocks( $blocks ),
			spectra_process_blocks_for_attribute_css( $blocks ),
		);

		return implode( "\n", array_filter( $css_parts ) );
	}

	/**
	 * Register plugin handler for CSS generation.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @param array  $config Plugin configuration.
	 */
	public function register_plugin_handler( $plugin_key, $config ) {
		$this->plugin_handlers[ $plugin_key ] = wp_parse_args(
			$config,
			array(
				'namespace'        => '',
				'blocks_dir'       => '',
				'responsive_class' => null,
				'enabled'          => true,
			)
		);
	}

	/**
	 * Register default plugin handlers.
	 */
	private function register_default_handlers() {
		// Spectra v3 handler.
		$this->register_plugin_handler(
			'spectra-blocks',
			array(
				'namespace'        => 'spectra/',
				'blocks_dir'       => SPECTRA_BLOCKS_DIR . 'build/blocks/',
				'responsive_class' => '\SpectraBlocks\Extensions\ResponsiveControls',
				'enabled'          => true,
			)
		);

		// Spectra Pro v2 handler (if available).
		if ( defined( 'SPECTRA_PRO_2_DIR' ) ) {
			$this->register_plugin_handler(
				'spectra-pro-v2',
				array(
					'namespace'        => 'spectra-pro/',
					'blocks_dir'       => SPECTRA_PRO_2_DIR . 'build/blocks/',
					'responsive_class' => '\SpectraPro\Extensions\ResponsiveControls',
					'enabled'          => true,
				)
			);
		}

		// Allow other plugins to register their handlers.
		do_action( 'spectra_css_generator_register_handlers', $this );
	}

	/**
	 * Generate static CSS for all registered plugins.
	 *
	 * @param array $used_blocks Used block types.
	 * @return string Combined static CSS.
	 */
	public function generate_static_css( $used_blocks ) {
		if ( empty( $used_blocks ) ) {
			return '';
		}

		$css_content = '';

		foreach ( $this->plugin_handlers as $plugin_key => $config ) {
			if ( ! $config['enabled'] || empty( $config['blocks_dir'] ) ) {
				continue;
			}

			$plugin_css = $this->load_plugin_static_css( $plugin_key, $config, $used_blocks );
			if ( $plugin_css ) {
				$css_content .= "\n/* {$plugin_key} Static CSS */\n" . $plugin_css;
			}
		}

		return $css_content;
	}

	/**
	 * Load static CSS for a specific plugin.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @param array  $config Plugin configuration.
	 * @param array  $used_blocks Used block types.
	 * @return string Plugin static CSS.
	 */
	private function load_plugin_static_css( $plugin_key, $config, $used_blocks ) {
		$blocks_dir = $config['blocks_dir'];
		$namespace  = $config['namespace'];

		if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
			return '';
		}

		$css_content   = '';
		$block_folders = scandir( $blocks_dir );

		if ( false === $block_folders ) {
			return '';
		}

		foreach ( $block_folders as $block_folder ) {
			if ( '.' === $block_folder || '..' === $block_folder || ! is_dir( $blocks_dir . $block_folder ) ) {
				continue;
			}

			$full_block_name = $namespace . $block_folder;
			if ( ! in_array( $full_block_name, $used_blocks, true ) ) {
				continue;
			}

			// Load style-index.css (base styles).
			$style_file = $blocks_dir . $block_folder . '/style-index.css';
			if ( file_exists( $style_file ) ) {
				$block_css = $this->read_css_file( $style_file );
				if ( $block_css ) {
					$css_content .= "\n/* Block: {$block_folder} */\n" . $block_css;
				}
			}
		}

		return $css_content;
	}

	/**
	 * Read CSS file content safely.
	 *
	 * @param string $file_path Path to CSS file.
	 * @return string|false File content or false on failure.
	 */
	private function read_css_file( $file_path ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem ? $wp_filesystem->get_contents( $file_path ) : false;
	}

	/**
	 * Generate editor-specific CSS from static files.
	 * Loads editor.css files that contain editor-specific styles.
	 *
	 * @param array $used_blocks Used block types.
	 * @return string Generated editor CSS.
	 */
	public function generate_editor_css( $used_blocks ) {
		if ( empty( $used_blocks ) ) {
			return '';
		}

		$css_content = '';

		foreach ( $this->plugin_handlers as $plugin_key => $config ) {
			if ( ! $config['enabled'] || empty( $config['blocks_dir'] ) ) {
				continue;
			}

			$plugin_css = $this->load_plugin_editor_css( $plugin_key, $config, $used_blocks );
			if ( $plugin_css ) {
				$css_content .= "\n/* {$plugin_key} Editor CSS */\n" . $plugin_css;
			}
		}

		return $css_content;
	}

	/**
	 * Load editor CSS for a specific plugin.
	 *
	 * @param string $plugin_key Plugin identifier.
	 * @param array  $config Plugin configuration.
	 * @param array  $used_blocks Used block types.
	 * @return string Plugin editor CSS.
	 */
	private function load_plugin_editor_css( $plugin_key, $config, $used_blocks ) {
		$blocks_dir = $config['blocks_dir'];
		$namespace  = $config['namespace'];

		if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
			return '';
		}

		$css_content   = '';
		$block_folders = scandir( $blocks_dir );

		if ( false === $block_folders ) {
			return '';
		}

		foreach ( $block_folders as $block_folder ) {
			if ( '.' === $block_folder || '..' === $block_folder || ! is_dir( $blocks_dir . $block_folder ) ) {
				continue;
			}

			$full_block_name = $namespace . $block_folder;
			if ( ! in_array( $full_block_name, $used_blocks, true ) ) {
				continue;
			}

			// Try both editor.css and index.css (for editor styles).
			$editor_files = array( 'editor.css', 'index.css' );
			foreach ( $editor_files as $editor_file ) {
				$editor_css_file = $blocks_dir . $block_folder . '/' . $editor_file;
				if ( ! file_exists( $editor_css_file ) ) {
					continue;
				}

				$block_css = $this->read_css_file( $editor_css_file );
				if ( $block_css ) {
					$css_content .= "\n/* Block Editor: {$block_folder} ({$editor_file}) */\n" . $block_css;
				}
				break; // Use first available file.
			}
		}

		return $css_content;
	}

	/**
	 * Generate extension CSS based on blocks that actually use extensions.
	 * Only loads extension CSS when needed (e.g., image-mask for core/image blocks with mask attributes).
	 *
	 * @since 3.0.0
	 *
	 * @param array $blocks Parsed blocks to check for extension usage.
	 * @return string Generated extension CSS.
	 */
	public function generate_extensions_css( $blocks ) {
		$css_content = '';

		// Check if any blocks use extensions.
		$has_z_index = $this->has_extension_usage( $blocks, 'z-index' );

		// Only load z-index CSS if it's actually used.
		if ( $has_z_index ) {
			$z_index_css_file = SPECTRA_BLOCKS_DIR . 'build/styles/extensions/z-index.css';
			if ( file_exists( $z_index_css_file ) ) {
				$css = $this->read_css_file( $z_index_css_file );
				if ( $css ) {
					$css_content .= "\n/* Z-Index Extension CSS */\n" . $css;
				}
			}
		}

		// Check if any blocks use extensions.
		$has_image_mask = $this->has_extension_usage( $blocks, 'image-mask' );

		// Only load image-mask CSS if it's actually used.
		if ( $has_image_mask ) {
			$image_mask_css_file = SPECTRA_BLOCKS_DIR . 'build/styles/extensions/image-mask.css';
			if ( file_exists( $image_mask_css_file ) ) {
				$css = $this->read_css_file( $image_mask_css_file );
				if ( $css ) {
					$css_content .= "\n/* Image Mask Extension CSS */\n" . $css;
				}
			}
		}

		/**
		 * Filter the generated extension CSS.
		 *
		 * @since 3.0.0
		 *
		 * @param string $css_content The generated extension CSS content.
		 * @param array  $blocks The parsed blocks being processed.
		 * @return string Filtered CSS content.
		 */
		$css_content = apply_filters( 'spectra_extensions_css', $css_content, $blocks );

		return $css_content;
	}

	/**
	 * Check if any blocks use a specific extension.
	 * Recursively checks all blocks and inner blocks.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $blocks Parsed blocks to check.
	 * @param string $extension Extension name to check for (e.g., 'image-mask').
	 * @return bool True if extension is used, false otherwise.
	 */
	private function has_extension_usage( $blocks, $extension ) {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				// Check inner blocks if no block name.
				if ( ! empty( $block['innerBlocks'] ) && $this->has_extension_usage( $block['innerBlocks'], $extension ) ) {
					return true;
				}
				continue;
			}

			$attrs = $block['attrs'] ?? array();

			// Check for image-mask extension usage.
			if ( 'image-mask' === $extension ) {
				// Check core/image blocks with mask attributes.
				if ( 'core/image' === $block['blockName'] ) {
					// Check if spectraMask attribute exists and has a shape (not 'none').
					if ( ! empty( $attrs['spectraMask']['shape'] ) && 'none' !== $attrs['spectraMask']['shape'] ) {
						return true;
					}
				}
			}

			// Check for z-index extension usage.
			if ( 'z-index' === $extension ) {
				// Check if spectraZIndex attribute exists.
				if ( ! empty( $attrs['spectraZIndex'] ) ) {
					return true;
				}
			}

			// Check inner blocks recursively.
			if ( ! empty( $block['innerBlocks'] ) && $this->has_extension_usage( $block['innerBlocks'], $extension ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate layout CSS for WordPress core and Spectra blocks.
	 * This includes dynamic container CSS and layout-specific styles.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return string Generated layout CSS.
	 */
	public function generate_layout_css( $blocks ) {
		static $base_layout_css_added = false;
		$css_content                  = '';

		// Add base WordPress layout CSS once.
		if ( ! $base_layout_css_added ) {
			$css_content          .= $this->get_base_wordpress_layout_css();
			$base_layout_css_added = true;
		}

		// Process blocks for layout-specific CSS.
		$css_content .= $this->process_blocks_for_layout_css( $blocks );

		return $css_content;
	}

	/**
	 * Get base WordPress layout CSS that should be included once.
	 *
	 * @return string Base layout CSS.
	 */
	private function get_base_wordpress_layout_css() {
		return "\n/* WordPress Core Layout CSS - Block Specific Only */\n" .
			".is-layout-grid { display: grid; }\n" .
			".is-layout-grid { grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1.25rem; }\n" .
			".is-layout-flex { display: flex; }\n" .
			".is-layout-flex { flex-wrap: wrap; align-items: center; }\n";
	}

	/**
	 * Process blocks recursively for layout-specific CSS.
	 *
	 * @param array $blocks Blocks to process.
	 * @return string Generated layout CSS.
	 */
	private function process_blocks_for_layout_css( $blocks ) {
		$css_content            = '';
		static $container_count = 0;

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				if ( ! empty( $block['innerBlocks'] ) ) {
					$css_content .= $this->process_blocks_for_layout_css( $block['innerBlocks'] );
				}
				continue;
			}

			$attrs      = $block['attrs'] ?? array();
			$block_name = $block['blockName'];

			// Generate dynamic container CSS for grid/flex layouts.
			if ( strpos( $block_name, 'spectra/container' ) === 0 || strpos( $block_name, 'core/group' ) === 0 ) {
				$layout = $attrs['layout'] ?? array();

				if ( isset( $layout['type'] ) && 'grid' === $layout['type'] ) {
					++$container_count;
					$container_class = "wp-container-{$this->get_block_slug( $block_name )}-is-layout-{$container_count}";

					$css_content .= ".{$container_class} {\n";

					if ( isset( $layout['columnCount'] ) ) {
						$columns      = (int) $layout['columnCount'];
						$css_content .= "    grid-template-columns: repeat({$columns}, minmax(0, 1fr));\n";
					} else {
						$css_content .= "    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));\n";
					}

					$css_content .= "}\n";
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$css_content .= $this->process_blocks_for_layout_css( $block['innerBlocks'] );
			}
		}

		return $css_content;
	}

	/**
	 * Get block slug from block name for CSS class generation.
	 *
	 * @param string $block_name Block name (e.g., 'spectra/container').
	 * @return string Block slug (e.g., 'spectra-container').
	 */
	private function get_block_slug( $block_name ) {
		return str_replace( '/', '-', $block_name );
	}

	/**
	 * Generate comprehensive responsive CSS by leveraging existing ResponsiveControls instances.
	 * This generates CSS for all devices (mobile, tablet, desktop) with proper media queries.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return string Generated responsive CSS.
	 */
	private function generate_responsive_css_from_blocks( $blocks ) {
		$css_content = '';

		// Get ResponsiveControls instances.
		$v3_responsive  = null;
		$pro_responsive = null;

		if ( class_exists( '\SpectraBlocks\Extensions\ResponsiveControls' ) ) {
			$v3_responsive = \SpectraBlocks\Extensions\ResponsiveControls::instance();
		}

		if ( class_exists( '\SpectraPro\Extensions\ResponsiveControls' ) ) {
			$pro_responsive = \SpectraPro\Extensions\ResponsiveControls::instance();
		}

		if ( ! $v3_responsive ) {
			return '';
		}

		// Process all blocks and let ResponsiveControls generate the CSS.
		$css_content .= $this->process_blocks_for_responsive_css( $blocks, $v3_responsive, $pro_responsive );

		return $css_content;
	}

	/**
	 * Process blocks recursively to generate responsive CSS.
	 * Uses ResponsiveControls to generate CSS for all devices (mobile, tablet, desktop).
	 *
	 * @param array  $blocks Blocks to process.
	 * @param object $v3_responsive Spectra v3 ResponsiveControls instance.
	 * @param object $pro_responsive Spectra Pro v2 ResponsiveControls instance (optional).
	 * @return string Generated responsive CSS.
	 */
	private function process_blocks_for_responsive_css( $blocks, $v3_responsive, $pro_responsive = null ) {
		$css_content = '';

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				if ( ! empty( $block['innerBlocks'] ) ) {
					$css_content .= $this->process_blocks_for_responsive_css( $block['innerBlocks'], $v3_responsive, $pro_responsive );
				}
				continue;
			}

			$attrs      = $block['attrs'] ?? array();
			$block_name = $block['blockName'];
			$spectra_id = \SpectraBlocks\Helpers\Core::sanitize_spectra_id( $attrs['spectraId'] ?? '' );
			if ( empty( $spectra_id ) ) {
				$spectra_id = 'spectra-preview-' . wp_generate_uuid4();
			}

			// Determine which ResponsiveControls instance to use.
			// This checks for Spectra blocks, Pro blocks, AND any core blocks that have responsive control support.
			$responsive_instance = null;
			if ( strpos( $block_name, 'spectra-pro/' ) === 0 && $pro_responsive ) {
				$responsive_instance = $pro_responsive;
			} elseif ( strpos( $block_name, 'spectra/' ) === 0 && $v3_responsive ) {
				$responsive_instance = $v3_responsive;
			} elseif ( $v3_responsive ) {
				// Check if this block should have responsive controls applied.
				// This handles core blocks like core/image that are in the supported_blocks list.
				$test_block = array(
					'blockName' => $block_name,
					'attrs'     => $attrs,
				);
				if ( method_exists( $v3_responsive, 'should_apply_responsive_controls' ) &&
					$v3_responsive->should_apply_responsive_controls( $test_block ) ) {
					$responsive_instance = $v3_responsive;
				}
			}

			// Let ResponsiveControls generate all the CSS.
			if ( $responsive_instance && method_exists( $responsive_instance, 'generate_responsive_css' ) ) {
				$responsive_controls_data = $attrs['responsiveControls'] ?? array();

				// If no responsive controls, try to extract from regular attributes.
				if ( empty( $responsive_controls_data ) ) {
					$responsive_controls_data = $this->convert_attrs_to_responsive_controls( $attrs, $block_name );
				}

				// Apply default layout if no layout is defined for the block.
				if ( empty( $responsive_controls_data['lg']['layout'] ) && method_exists( $responsive_instance, 'get_block_default_layout' ) ) {
					$default_layout = $responsive_instance->get_block_default_layout( $block_name );
					if ( ! empty( $default_layout['layout'] ) ) {
						if ( ! isset( $responsive_controls_data['lg'] ) ) {
							$responsive_controls_data['lg'] = array();
						}
						$responsive_controls_data['lg']['layout'] = $default_layout['layout'];
					}
				}

				$block_css = $responsive_instance->generate_responsive_css( $spectra_id, $responsive_controls_data, $block_name, $attrs );
				if ( $block_css && is_string( $block_css ) ) {
					$css_content .= $block_css;
				}
			}

			// Process inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$css_content .= $this->process_blocks_for_responsive_css( $block['innerBlocks'], $v3_responsive, $pro_responsive );
			}
		}

		return $css_content;
	}

	/**
	 * Get all block types used recursively.
	 *
	 * @param array $blocks Blocks array.
	 * @return array Unique block names.
	 */
	public function get_used_block_types( $blocks ) {
		$used_blocks = array();

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$used_blocks[] = $block['blockName'];
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_blocks = $this->get_used_block_types( $block['innerBlocks'] );
				$used_blocks  = array_merge( $used_blocks, $inner_blocks );
			}
		}

		return array_unique( $used_blocks );
	}




	/**
	 * Convert block attributes to responsive controls (simplified version).
	 *
	 * @param array  $attrs Block attributes.
	 * @param string $block_name Block name.
	 * @return array Responsive controls.
	 */
	public function convert_attrs_to_responsive_controls( $attrs, $block_name = '' ) {
		if ( empty( $attrs ) || ! is_array( $attrs ) ) {
			return array( 'lg' => array() );
		}

		$responsive_controls = array( 'lg' => array() );

		// Generic responsive keys that apply to most blocks.
		$responsive_keys = array( 'layout', 'fontSize', 'fontFamily', 'borderColor', 'style' );

		foreach ( $responsive_keys as $key ) {
			if ( isset( $attrs[ $key ] ) && null !== $attrs[ $key ] && '' !== $attrs[ $key ] ) {
				$responsive_controls['lg'][ $key ] = $attrs[ $key ];
			}
		}

		// Add dimension attributes.
		$dimension_attrs = array( 'width', 'maxWidth', 'height', 'maxHeight' );
		foreach ( $dimension_attrs as $attr ) {
			if ( isset( $attrs[ $attr ] ) && null !== $attrs[ $attr ] && '' !== $attrs[ $attr ] ) {
				$responsive_controls['lg'][ $attr ] = $attrs[ $attr ];
			}
		}

		// Add common attributes.
		$common_attrs = array( 'backgroundColor', 'textColor', 'gradient', 'className' );
		foreach ( $common_attrs as $attr ) {
			if ( isset( $attrs[ $attr ] ) && null !== $attrs[ $attr ] && '' !== $attrs[ $attr ] ) {
				$responsive_controls['lg'][ $attr ] = $attrs[ $attr ];
			}
		}

		// Add block-specific responsive attributes using ResponsiveAttributeCSS.
		if ( ! empty( $block_name ) && class_exists( '\SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS' ) ) {
			$block_responsive_attrs = \SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS::get_responsive_attributes( $block_name );

			foreach ( $block_responsive_attrs as $attr ) {
				if ( isset( $attrs[ $attr ] ) && null !== $attrs[ $attr ] && '' !== $attrs[ $attr ] ) {
					$responsive_controls['lg'][ $attr ] = $attrs[ $attr ];
				}
			}
		}

		return $responsive_controls;
	}

	/**
	 * Merge responsive controls with priority to existing.
	 *
	 * @param array $existing Existing responsive controls.
	 * @param array $comprehensive Comprehensive responsive controls.
	 * @return array Merged controls.
	 */
	private function merge_responsive_controls( $existing, $comprehensive ) {
		if ( empty( $existing ) ) {
			return $comprehensive;
		}

		if ( empty( $comprehensive ) ) {
			return $existing;
		}

		$merged = $existing;

		foreach ( $comprehensive as $breakpoint => $breakpoint_data ) {
			if ( ! isset( $merged[ $breakpoint ] ) ) {
				$merged[ $breakpoint ] = $breakpoint_data;
			} else {
				foreach ( $breakpoint_data as $key => $value ) {
					if ( ! isset( $merged[ $breakpoint ][ $key ] ) ) {
						$merged[ $breakpoint ][ $key ] = $value;
					} elseif ( 'style' === $key && is_array( $value ) ) {
						if ( ! isset( $merged[ $breakpoint ]['style'] ) ) {
							$merged[ $breakpoint ]['style'] = array();
						}

						foreach ( $value as $style_prop => $style_value ) {
							if ( ! isset( $merged[ $breakpoint ]['style'][ $style_prop ] ) ) {
								$merged[ $breakpoint ]['style'][ $style_prop ] = $style_value;
							}
						}
					}
				}
			}
		}

		return $merged;
	}
}

/**
 * Regenerate CSS for all patterns to fix compatibility with updated CSS generation.
 * Call this function once to fix patterns created with older development versions.
 *
 * @since 3.0.0
 * @return array Results of the regeneration process.
 */
function spectra_regenerate_pattern_css() {
	$results = array(
		'success'            => false,
		'patterns_processed' => 0,
		'patterns_updated'   => 0,
		'errors'             => array(),
	);

	// Get all patterns/posts that might contain Spectra blocks.
	// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	$posts = get_posts(
		array(
			'post_type'   => array( 'wp_block', 'post', 'page' ), // Include patterns (wp_block) and posts/pages.
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => '_spectra_has_blocks',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'post_content',
					'value'   => 'spectra/',
					'compare' => 'LIKE',
				),
			),
		)
	);
	// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

	foreach ( $posts as $post ) {
		++$results['patterns_processed'];

		// Check if post content contains Spectra blocks.
		if ( strpos( $post->post_content, 'wp:spectra/' ) !== false || strpos( $post->post_content, 'wp:spectra-pro/' ) !== false ) {
			// Force regenerate CSS by clearing any cached CSS and regenerating.
			delete_post_meta( $post->ID, '_spectra_css_cache' );

			// Trigger fresh CSS generation.
			$new_css = spectra_get_v3_blocks_css_for_preview( $post->ID );

			if ( ! empty( $new_css ) ) {
				// Store the regenerated CSS if needed.
				update_post_meta( $post->ID, '_spectra_css_regenerated', current_time( 'mysql' ) );
				++$results['patterns_updated'];
			} else {
				$results['errors'][] = "No CSS generated for post ID: {$post->ID} ({$post->post_title})";
			}
		}
	}

	$results['success'] = $results['patterns_updated'] > 0;

	return $results;
}

/**
 * Process blocks recursively to generate comprehensive responsive CSS.
 * Handles ALL block attributes including style, responsive controls, and block-level attributes.
 *
 * @since 3.0.0
 * @param array  $blocks The blocks to process.
 * @param object $responsive_controls The Spectra v3 ResponsiveControls instance.
 * @param object $pro_responsive_controls The Spectra Pro v2 ResponsiveControls instance (optional).
 * @return string Generated CSS for all blocks.
 */
function spectra_process_blocks_for_comprehensive_css( $blocks, $responsive_controls, $pro_responsive_controls = null ) {
	static $core_layout_css_added = false;
	$css_content                  = '';

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}

		$attrs      = $block['attrs'] ?? array();
		$block_name = $block['blockName'];

		// Generate CSS for Spectra blocks (v3 and Pro v2).
		if ( strpos( $block_name, 'spectra/' ) === 0 || strpos( $block_name, 'spectra-pro/' ) === 0 ) {
			$spectra_id = \SpectraBlocks\Helpers\Core::sanitize_spectra_id( $attrs['spectraId'] ?? '' );

			// Ensure block has a spectraId for CSS generation.
			if ( empty( $spectra_id ) ) {
				// Generate temporary ID for pattern preview.
				$spectra_id = 'spectra-preview-' . wp_generate_uuid4();
			}

			// Get existing responsive controls.
			$responsive_controls_data = $attrs['responsiveControls'] ?? array();

			// Convert all block attributes to comprehensive responsive controls.
			$comprehensive_responsive_data = spectra_convert_all_attrs_to_responsive_controls( $attrs, $block_name );

			// Merge existing with comprehensive data (comprehensive takes precedence for missing properties).
			$final_responsive_data = spectra_merge_responsive_controls_comprehensive( $responsive_controls_data, $comprehensive_responsive_data );

			// Skip if no responsive data to process.
			if ( empty( $final_responsive_data ) ) {
				$final_responsive_data = array( 'lg' => array() );
			}

			// Determine which controls instance to use.
			$controls_instance = $responsive_controls; // Default to v3.
			$use_pro_v2        = false;

			// Check for Pro v2 blocks and valid Pro v2 controls instance.
			if ( strpos( $block_name, 'spectra-pro/' ) === 0 &&
				$pro_responsive_controls &&
				method_exists( $pro_responsive_controls, 'generate_responsive_css' ) ) {
				$controls_instance = $pro_responsive_controls;
				$use_pro_v2        = true;
			}

			$block_css = $controls_instance->generate_responsive_css( $spectra_id, $final_responsive_data, $block_name, $attrs );
			if ( $block_css && is_string( $block_css ) ) {
				$css_content .= $block_css . ' ';
			}
		}

		// Generate CSS for WordPress core blocks.
		if ( strpos( $block_name, 'core/' ) === 0 ) {
			$css_content .= spectra_generate_wordpress_core_css( $block_name, $attrs, $core_layout_css_added );
		}

		// Generate layout CSS for Spectra blocks that use WordPress core layout support.
		if ( strpos( $block_name, 'spectra/' ) === 0 || strpos( $block_name, 'spectra-pro/' ) === 0 ) {
			$css_content .= spectra_generate_spectra_layout_css( $attrs, $block_name );
		}

		// Process inner blocks recursively.
		if ( ! empty( $block['innerBlocks'] ) ) {
			$css_content .= spectra_process_blocks_for_comprehensive_css( $block['innerBlocks'], $responsive_controls, $pro_responsive_controls );
		}
	}

	return $css_content;
}


/**
 * Merge responsive controls with comprehensive fallback support.
 * Prioritizes existing responsive controls but fills in missing properties from comprehensive data.
 *
 * @since 3.0.0
 * @param array $existing_responsive Existing responsive controls.
 * @param array $comprehensive_responsive Comprehensive responsive controls from all attributes.
 * @return array Merged responsive controls.
 */
function spectra_merge_responsive_controls_comprehensive( $existing_responsive, $comprehensive_responsive ) {
	if ( empty( $existing_responsive ) ) {
		return $comprehensive_responsive;
	}

	if ( empty( $comprehensive_responsive ) ) {
		return $existing_responsive;
	}

	// Deep merge with existing taking priority.
	$merged = $existing_responsive;

	foreach ( $comprehensive_responsive as $breakpoint => $breakpoint_data ) {
		if ( ! isset( $merged[ $breakpoint ] ) ) {
			$merged[ $breakpoint ] = $breakpoint_data;
		} else {
			// Merge at breakpoint level.
			foreach ( $breakpoint_data as $key => $value ) {
				if ( ! isset( $merged[ $breakpoint ][ $key ] ) ) {
					$merged[ $breakpoint ][ $key ] = $value;
				} elseif ( 'style' === $key && is_array( $value ) ) {
					// Special handling for style properties - merge deeper.
					if ( ! isset( $merged[ $breakpoint ]['style'] ) ) {
						$merged[ $breakpoint ]['style'] = array();
					}

					foreach ( $value as $style_prop => $style_value ) {
						if ( ! isset( $merged[ $breakpoint ]['style'][ $style_prop ] ) ) {
							$merged[ $breakpoint ]['style'][ $style_prop ] = $style_value;
						}
					}
				}
			}
		}
	}

	return $merged;
}


/**
 * Process blocks recursively to generate block-specific attribute CSS.
 *
 * @since 3.0.0
 * @param array $blocks The blocks to process.
 * @return string Generated attribute CSS for all blocks.
 */
function spectra_process_blocks_for_attribute_css( $blocks ) {
	$css_content = '';

	// Check if ResponsiveAttributeCSS class exists.
	if ( ! class_exists( '\SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS' ) ) {
		return '';
	}

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) || strpos( $block['blockName'], 'spectra/' ) !== 0 ) {
			// Still process inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$css_content .= spectra_process_blocks_for_attribute_css( $block['innerBlocks'] );
			}
			continue;
		}

		$attrs      = $block['attrs'] ?? array();
		$spectra_id = \SpectraBlocks\Helpers\Core::sanitize_spectra_id( $attrs['spectraId'] ?? '' );
		$block_name = $block['blockName'];

		if ( empty( $spectra_id ) ) {
			// Generate temporary ID for CSS generation.
			$spectra_id = 'spectra-preview-' . wp_generate_uuid4();
		}

		// Generate CSS selectors.
		$block_class               = '.wp-block-' . str_replace( '/', '-', $block_name );
		$high_specificity_selector = "{$block_class}{$block_class}[data-spectra-id='{$spectra_id}']";
		$low_specificity_selector  = "{$block_class}:where([data-spectra-id='{$spectra_id}'])";

		// Get device-specific attributes with fallback (simulating lg device).
		$device_attrs = spectra_get_preview_device_attributes( $block_name, $attrs );

		// Generate block-specific attribute CSS.
		$attr_css = \SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS::generate_css(
			$block_name,
			$device_attrs,
			$high_specificity_selector,
			$low_specificity_selector,
			$attrs
		);

		if ( ! empty( $attr_css ) ) {
			$css_content .= "\n/* Block Attribute CSS: " . $block_name . " */\n" . $attr_css . "\n";
		}

		// Process inner blocks recursively.
		if ( ! empty( $block['innerBlocks'] ) ) {
			$css_content .= spectra_process_blocks_for_attribute_css( $block['innerBlocks'] );
		}
	}

	return $css_content;
}

/**
 * Get device-specific attributes for pattern preview (assumes lg device).
 *
 * @since 3.0.0
 * @param string $block_name The block name.
 * @param array  $attrs Block attributes.
 * @return array Device-specific attributes.
 */
function spectra_get_preview_device_attributes( $block_name, $attrs ) {
	$device_attrs = array();

	// Use responsive controls if available.
	if ( ! empty( $attrs['responsiveControls']['lg'] ) ) {
		$device_attrs = $attrs['responsiveControls']['lg'];
	}

	// Get block-specific responsive attributes from ResponsiveAttributeCSS.
	if ( ! empty( $block_name ) && class_exists( '\SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS' ) ) {
		$block_responsive_attrs = \SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS::get_responsive_attributes( $block_name );

		// Add all block-specific responsive attributes from root level if not in device_attrs.
		foreach ( $block_responsive_attrs as $attr ) {
			if ( isset( $attrs[ $attr ] ) && ! isset( $device_attrs[ $attr ] ) ) {
				$device_attrs[ $attr ] = $attrs[ $attr ];
			}
		}
	}

	// Also add common root-level attributes that might be needed.
	$common_root_attrs = array( 'background', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight', 'size', 'gap', 'enableTextShadow', 'textShadowColor', 'textShadowBlur', 'textShadowOffsetX', 'textShadowOffsetY' );

	foreach ( $common_root_attrs as $attr ) {
		if ( isset( $attrs[ $attr ] ) && ! isset( $device_attrs[ $attr ] ) ) {
			$device_attrs[ $attr ] = $attrs[ $attr ];
		}
	}

	return $device_attrs;
}



/**
 * Generate CSS for WordPress core group blocks based on actual WordPress class names.
 * Uses the same class-based approach as WordPress core for better compatibility.
 *
 * @since 3.0.0
 * @param array $attrs Block attributes.
 * @param bool  &$base_css_added Reference to track if base CSS was added.
 * @return string Generated CSS for the group block.
 */
function spectra_generate_core_group_css( $attrs, &$base_css_added ) {
	$css_content = '';

	$layout = $attrs['layout'] ?? array();
	$style  = $attrs['style'] ?? array();

	// Add base layout CSS only once.
	if ( ! $base_css_added ) {
		$css_content .= "\n/* WordPress Core Layout CSS */\n";
		$css_content .= "body .is-layout-flex {\n";
		$css_content .= "    display: flex;\n";
		$css_content .= "}\n";

		$css_content .= ".is-layout-flex {\n";
		$css_content .= "    flex-wrap: wrap;\n";
		$css_content .= "    align-items: center;\n";
		$css_content .= "}\n";

		// Add grid layout support.
		$css_content .= "body .is-layout-grid {\n";
		$css_content .= "    display: grid;\n";
		$css_content .= "}\n";

		$css_content .= ".is-layout-grid {\n";
		$css_content .= "    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));\n";
		$css_content .= "    gap: 1.25rem;\n";
		$css_content .= "}\n";

		$base_css_added = true;
	}

	// Generate CSS based on WordPress class names for flex layout.
	if ( isset( $layout['type'] ) && 'flex' === $layout['type'] ) {

		// CSS for vertical orientation.
		if ( isset( $layout['orientation'] ) && 'vertical' === $layout['orientation'] ) {
			$css_content .= ".wp-block-group.is-vertical {\n";
			$css_content .= "    flex-direction: column;\n";
			$css_content .= "}\n";
		}

		// CSS for content justification.
		$justify_content = $layout['justifyContent'] ?? 'left';
		$css_content    .= ".wp-block-group.is-content-justification-{$justify_content} {\n";
		switch ( $justify_content ) {
			case 'center':
				$css_content .= "    align-items: center;\n";
				break;
			case 'right':
				$css_content .= "    align-items: flex-end;\n";
				break;
			case 'space-between':
				$css_content .= "    justify-content: space-between;\n";
				break;
			case 'left':
			default:
				$css_content .= "    align-items: flex-start;\n";
				break;
		}
		$css_content .= "}\n";

		$css_content .= ".is-content-justification-{$justify_content} {\n";
		switch ( $justify_content ) {
			case 'center':
				$css_content .= "    align-items: center;\n";
				break;
			case 'right':
				$css_content .= "    align-items: flex-end;\n";
				break;
			case 'space-between':
				$css_content .= "    justify-content: space-between;\n";
				break;
			case 'left':
			default:
				$css_content .= "    align-items: flex-start;\n";
				break;
		}
		$css_content .= "}\n";

		// CSS for gap using WordPress container pattern.
		if ( isset( $style['spacing']['blockGap'] ) ) {
			$gap = $style['spacing']['blockGap'];

			// Generate container-specific CSS using WordPress pattern.
			// This targets the actual container classes WordPress generates.
			for ( $i = 1; $i <= 10; $i++ ) {
				if ( $i > 1 ) {
					$css_content .= ",\n";
				}
				$css_content .= ".wp-container-core-group-is-layout-{$i}";
			}
			$css_content .= " {\n";
			$css_content .= "    gap: {$gap};\n";
			$css_content .= "}\n";
		}

		// Generic container CSS for common layouts.
		$css_content .= ".wp-block-group-is-layout-flex {\n";
		$css_content .= "    display: flex;\n";
		$css_content .= "}\n";
	}

	// Handle grid layout.
	if ( isset( $layout['type'] ) && 'grid' === $layout['type'] ) {
		$css_content .= ".wp-block-group-is-layout-grid {\n";
		$css_content .= "    display: grid;\n";
		$css_content .= "}\n";

		if ( isset( $layout['columnCount'] ) ) {
			$column_count = $layout['columnCount'];
			$css_content .= ".has-{$column_count}-columns {\n";
			$css_content .= "    grid-template-columns: repeat({$column_count}, 1fr);\n";
			$css_content .= "}\n";
		}
	}

	return $css_content;
}

/**
 * Generate CSS for all WordPress core blocks comprehensively.
 * Handles wp:group, wp:columns, wp:image, and other core blocks.
 *
 * @since 3.0.0
 * @param string $block_name Block name (e.g., 'core/group').
 * @param array  $attrs Block attributes.
 * @param bool   &$base_css_added Reference to track if base CSS was added.
 * @return string Generated CSS for the core block.
 */
function spectra_generate_wordpress_core_css( $block_name, $attrs, &$base_css_added ) {
	$css_content = '';

	switch ( $block_name ) {
		case 'core/group':
			$css_content .= spectra_generate_core_group_css( $attrs, $base_css_added );
			break;

		case 'core/columns':
			$css_content .= spectra_generate_core_columns_css( $attrs, $base_css_added );
			break;

		case 'core/column':
			$css_content .= spectra_generate_core_column_css( $attrs );
			break;

		case 'core/image':
			$css_content .= spectra_generate_core_image_css( $attrs );
			break;

		case 'core/gallery':
			$css_content .= spectra_generate_core_gallery_css( $attrs );
			break;

		default:
			// Handle any other core block with layout support.
			if ( isset( $attrs['layout'] ) ) {
				$css_content .= spectra_generate_generic_core_layout_css( $block_name, $attrs, $base_css_added );
			}
			break;
	}

	return $css_content;
}

/**
 * Generate CSS for WordPress core columns block.
 *
 * @since 3.0.0
 * @param array $attrs Block attributes.
 * @param bool  &$base_css_added Reference to track if base CSS was added.
 * @return string Generated CSS.
 */
function spectra_generate_core_columns_css( $attrs, &$base_css_added ) {
	$css_content = '';

	// Add base columns CSS only once.
	if ( ! $base_css_added ) {
		$css_content .= "\n/* WordPress Columns CSS */\n";
		$css_content .= ".wp-block-columns {\n";
		$css_content .= "    display: flex;\n";
		$css_content .= "    flex-wrap: wrap;\n";
		$css_content .= "}\n";
	}

	// Handle specific column configurations.
	if ( isset( $attrs['isStackedOnMobile'] ) && $attrs['isStackedOnMobile'] ) {
		$css_content .= ".wp-block-columns.is-stacked-on-mobile {\n";
		$css_content .= "    flex-direction: column;\n";
		$css_content .= "}\n";
	}

	return $css_content;
}

/**
 * Generate CSS for WordPress core column block.
 *
 * @since 3.0.0
 * @param array $attrs Block attributes.
 * @return string Generated CSS.
 */
function spectra_generate_core_column_css( $attrs ) {
	$css_content = '';

	// Handle column width.
	if ( isset( $attrs['width'] ) ) {
		$width        = $attrs['width'];
		$css_content .= ".wp-block-column {\n";
		$css_content .= "    flex-basis: {$width};\n";
		$css_content .= "    flex-grow: 0;\n";
		$css_content .= "}\n";
	}

	return $css_content;
}

/**
 * Generate CSS for WordPress core image block.
 *
 * @since 3.0.0
 * @param array $attrs Block attributes.
 * @return string Generated CSS.
 */
function spectra_generate_core_image_css( $attrs ) {
	$css_content = '';

	// Handle image alignment.
	if ( isset( $attrs['align'] ) ) {
		$align        = $attrs['align'];
		$css_content .= ".wp-block-image.align{$align} {\n";
		switch ( $align ) {
			case 'center':
				$css_content .= "    text-align: center;\n";
				break;
			case 'left':
				$css_content .= "    margin-right: 1em;\n";
				break;
			case 'right':
				$css_content .= "    margin-left: 1em;\n";
				break;
		}
		$css_content .= "}\n";
	}

	// Handle width and height.
	if ( isset( $attrs['width'] ) || isset( $attrs['height'] ) ) {
		$css_content .= ".wp-block-image img {\n";
		if ( isset( $attrs['width'] ) ) {
			$css_content .= '    width: ' . $attrs['width'] . ";\n";
		}
		if ( isset( $attrs['height'] ) ) {
			$css_content .= '    height: ' . $attrs['height'] . ";\n";
		}
		$css_content .= "}\n";
	}

	return $css_content;
}

/**
 * Generate CSS for WordPress core gallery block.
 *
 * @since 3.0.0
 * @param array $attrs Block attributes.
 * @return string Generated CSS.
 */
function spectra_generate_core_gallery_css( $attrs ) {
	$css_content = '';

	// Handle gallery columns.
	if ( isset( $attrs['columns'] ) ) {
		$columns      = $attrs['columns'];
		$css_content .= ".wp-block-gallery.has-{$columns}-columns {\n";
		$css_content .= "    grid-template-columns: repeat({$columns}, 1fr);\n";
		$css_content .= "}\n";
	}

	return $css_content;
}

/**
 * Generate generic layout CSS for any core block with layout support.
 *
 * @since 3.0.0
 * @param string $block_name Block name.
 * @param array  $attrs Block attributes.
 * @param bool   &$base_css_added Reference to track if base CSS was added.
 * @return string Generated CSS.
 */
function spectra_generate_generic_core_layout_css( $block_name, $attrs, &$base_css_added ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$css_content = '';
	$layout      = $attrs['layout'] ?? array();
	$block_class = '.wp-block-' . str_replace( 'core/', '', $block_name );

	if ( isset( $layout['type'] ) && 'flex' === $layout['type'] ) {
		$css_content .= "{$block_class}.is-layout-flex {\n";
		$css_content .= "    display: flex;\n";
		$css_content .= "}\n";

		// Handle orientation.
		if ( isset( $layout['orientation'] ) && 'vertical' === $layout['orientation'] ) {
			$css_content .= "{$block_class}.is-vertical {\n";
			$css_content .= "    flex-direction: column;\n";
			$css_content .= "}\n";
		}
	}

	return $css_content;
}

/**
 * Generate layout CSS for Spectra blocks that use WordPress core layout support.
 * Handles flex, grid, and other layout types used by Spectra blocks.
 *
 * @since 3.0.0
 * @param array  $attrs Block attributes.
 * @param string $block_name Block name.
 * @return string Generated layout CSS for Spectra blocks.
 */
function spectra_generate_spectra_layout_css( $attrs, $block_name ) {
	static $spectra_base_css_added = false;
	$css_content                   = '';

	$layout = $attrs['layout'] ?? array();
	$style  = $attrs['style'] ?? array();

	// Add base Spectra layout CSS only once.
	if ( ! $spectra_base_css_added ) {
		$css_content .= "\n/* Spectra Layout Support CSS */\n";

		// Base flex layout for Spectra blocks.
		$css_content .= "body .wp-block-spectra-container.is-layout-flex,\n";
		$css_content .= "body .wp-block-spectra-buttons.is-layout-flex,\n";
		$css_content .= "body .wp-block-spectra-icons.is-layout-flex,\n";
		$css_content .= "body .wp-block-spectra-accordion.is-layout-flex {\n";
		$css_content .= "    display: flex;\n";
		$css_content .= "}\n";

		// Base grid layout for Spectra blocks.
		$css_content .= "body .wp-block-spectra-container.is-layout-grid {\n";
		$css_content .= "    display: grid;\n";
		$css_content .= "}\n";

		$spectra_base_css_added = true;
	}

	// Generate layout-specific CSS based on block type and layout.
	if ( ! empty( $layout ) && isset( $layout['type'] ) ) {
		$block_class = '.wp-block-' . str_replace( '/', '-', $block_name );

		// Handle flex layout.
		if ( 'flex' === $layout['type'] ) {

			// Vertical/horizontal orientation.
			if ( isset( $layout['orientation'] ) ) {
				$orientation  = $layout['orientation'];
				$css_content .= "{$block_class}.is-{$orientation} {\n";
				$css_content .= '    flex-direction: ' . ( 'vertical' === $orientation ? 'column' : 'row' ) . ";\n";
				$css_content .= "}\n";
			}

			// Flex wrap.
			if ( isset( $layout['flexWrap'] ) ) {
				$flex_wrap    = $layout['flexWrap'];
				$css_content .= "{$block_class}.is-flex-wrap-{$flex_wrap} {\n";
				$css_content .= "    flex-wrap: {$flex_wrap};\n";
				$css_content .= "}\n";
			}

			// Justify content.
			if ( isset( $layout['justifyContent'] ) ) {
				$justify_content = $layout['justifyContent'];
				$css_content    .= "{$block_class}.is-content-justification-{$justify_content} {\n";
				switch ( $justify_content ) {
					case 'center':
						$css_content .= "    justify-content: center;\n";
						break;
					case 'right':
						$css_content .= "    justify-content: flex-end;\n";
						break;
					case 'space-between':
						$css_content .= "    justify-content: space-between;\n";
						break;
					case 'stretch':
						$css_content .= "    align-items: stretch;\n";
						break;
					case 'left':
					default:
						$css_content .= "    justify-content: flex-start;\n";
						break;
				}
				$css_content .= "}\n";
			}

			// Vertical alignment.
			if ( isset( $layout['verticalAlignment'] ) ) {
				$vertical_alignment = $layout['verticalAlignment'];
				$css_content       .= "{$block_class}.is-vertical-alignment-{$vertical_alignment} {\n";
				switch ( $vertical_alignment ) {
					case 'center':
						$css_content .= "    align-items: center;\n";
						break;
					case 'bottom':
						$css_content .= "    align-items: flex-end;\n";
						break;
					case 'top':
					default:
						$css_content .= "    align-items: flex-start;\n";
						break;
				}
				$css_content .= "}\n";
			}
		}

		// Handle grid layout.
		if ( 'grid' === $layout['type'] ) {

			// Grid columns.
			if ( isset( $layout['columnCount'] ) ) {
				$column_count = $layout['columnCount'];
				$css_content .= "{$block_class}.has-{$column_count}-columns {\n";
				$css_content .= "    grid-template-columns: repeat({$column_count}, 1fr);\n";
				$css_content .= "}\n";
			}

			// Minimum column width.
			if ( isset( $layout['minimumColumnWidth'] ) ) {
				$min_width    = $layout['minimumColumnWidth'];
				$css_content .= "{$block_class}.has-min-column-width {\n";
				$css_content .= "    grid-template-columns: repeat(auto-fit, minmax({$min_width}, 1fr));\n";
				$css_content .= "}\n";
			}
		}

		// Handle spacing/gap.
		if ( isset( $style['spacing']['blockGap'] ) ) {
			$gap          = $style['spacing']['blockGap'];
			$css_content .= "{$block_class}-is-layout-flex,\n";
			$css_content .= "{$block_class}-is-layout-grid {\n";
			$css_content .= "    gap: {$gap};\n";
			$css_content .= "}\n";
		}
	}

	return $css_content;
}
