<?php
/**
 * Responsive Conditions Extension
 *
 * @package Spectra\Extensions
 */

namespace Spectra\Extensions;

use Spectra\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Responsive Conditions class.
 *
 * @since 0.0.1
 */
class ResponsiveConditions {

	use Singleton;

	/**
	 * Flag indicating if responsive conditions assets are needed.
	 *
	 * @since 0.0.1
	 *
	 * @var bool
	 */
	private $needs_assets = false;

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block to add responsive visibility classes to blocks
	 * and enqueue frontend styles when needed.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'add_responsive_classes_to_blocks' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_css' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_frontend_css_if_needed' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Add responsive visibility classes to the output of supported blocks.
	 *
	 * Ensures the block has the 'responsiveConditions' attribute defined and injects
	 * the responsive visibility classes into the block's wrapper tag using WP_HTML_Tag_Processor.
	 *
	 * @since 0.0.1
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content with responsive classes added.
	 */
	public function add_responsive_classes_to_blocks( $block_content, $block ) {
		// If the block should not be processed, return the original content.
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$responsive_conditions = $this->get_responsive_conditions( $block['attrs'] );

		// If the block does not have active responsive conditions, return the original content.
		if ( empty( $responsive_conditions ) ) {
			return $block_content;
		}

		// Mark that we need assets.
		$this->needs_assets = true;

		// Apply responsive classes to the block content.
		$modified_content = $this->apply_responsive_classes( $block_content, $responsive_conditions );

		return false !== $modified_content ? $modified_content : $block_content;
	}

	/**
	 * Determine whether the block should be processed for responsive conditions.
	 *
	 * @since 0.0.1
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& isset( $block['attrs']['responsiveConditions'] )
			&& $this->is_allowed_block( $block['blockName'] );
	}

	/**
	 * Retrieve active responsive conditions from block attributes.
	 *
	 * @since 0.0.1
	 *
	 * @param array $attrs Block attributes.
	 * @return array Active responsive conditions.
	 */
	private function get_responsive_conditions( $attrs ) {
		$responsive_conditions = $attrs['responsiveConditions'] ?? array();

		if ( ! is_array( $responsive_conditions ) ) {
			return array();
		}

		$active_conditions = array();

		if ( isset( $responsive_conditions['hideOnDesktop'] ) && true === $responsive_conditions['hideOnDesktop'] ) {
			$active_conditions[] = 'spectra-hide-desktop';
		}

		if ( isset( $responsive_conditions['hideOnTablet'] ) && true === $responsive_conditions['hideOnTablet'] ) {
			$active_conditions[] = 'spectra-hide-tablet';
		}

		if ( isset( $responsive_conditions['hideOnMobile'] ) && true === $responsive_conditions['hideOnMobile'] ) {
			$active_conditions[] = 'spectra-hide-mobile';
		}

		return $active_conditions;
	}

	/**
	 * Apply responsive visibility classes to block content.
	 *
	 * Uses WP_HTML_Tag_Processor to safely inject responsive classes into the first tag.
	 *
	 * @since 0.0.1
	 *
	 * @param string $content Block content.
	 * @param array  $responsive_classes Array of responsive classes to add.
	 * @return string|false Modified content or false on failure.
	 */
	private function apply_responsive_classes( $content, $responsive_classes ) {
		if ( empty( $content ) || empty( $responsive_classes ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		// Get existing class attribute.
		$existing_class = $processor->get_attribute( 'class' ) ? $processor->get_attribute( 'class' ) : '';

		// Add responsive classes.
		$new_classes = array_merge(
			array_filter( explode( ' ', $existing_class ) ),
			$responsive_classes
		);

		$new_class = implode( ' ', array_unique( $new_classes ) );
		$processor->set_attribute( 'class', $new_class );

		return $processor->get_updated_html();
	}

	/**
	 * Check if a block is allowed for responsive conditions.
	 *
	 * Uses the same filtering logic as the JavaScript side to ensure consistency.
	 *
	 * @since 0.0.1
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( $block_name ) {
		/**
		 * Filter to allow or exclude specific blocks from responsive conditions.
		 * This filter allows developers to customize which blocks support responsive conditions.
		 *
		 * @since 0.0.1
		 *
		 * @param array  $excluded_blocks Array of block names to exclude.
		 * @param string $block_name      The current block name being checked.
		 * @return array Modified array of excluded block names.
		 */
		$excluded_blocks = apply_filters( 'spectra_excluded_responsive_conditions_blocks', array(), $block_name );

		// Check if block is excluded.
		if ( in_array( $block_name, $excluded_blocks, true ) ) {
			return false;
		}

		/**
		 * Filter to specify which blocks explicitly support responsive conditions.
		 * This filter allows developers to add support for additional blocks.
		 *
		 * @since 0.0.1
		 *
		 * @param array  $supported_blocks Array of block names that support responsive conditions.
		 * @param string $block_name       The current block name being checked.
		 * @return array Modified array of supported block names.
		 */
		$supported_blocks = apply_filters( 'spectra_supported_responsive_conditions_blocks', array( 'core/image' ), $block_name );

		// Check if block is explicitly supported.
		if ( in_array( $block_name, $supported_blocks, true ) ) {
			return true;
		}

		/**
		 * Filter to modify the allowed block prefixes for responsive conditions.
		 * This filter allows developers to add or remove prefixes.
		 *
		 * @since 0.0.1
		 *
		 * @param array  $allowed_prefixes Array of block name prefixes to allow.
		 * @param string $block_name       The current block name being checked.
		 * @return array Modified array of allowed prefixes.
		 */
		$allowed_prefixes = apply_filters( 'spectra_allowed_responsive_conditions_prefixes', array( 'spectra/', 'spectra-pro/' ), $block_name );

		// Check if block has an allowed prefix.
		foreach ( $allowed_prefixes as $prefix ) {
			if ( strpos( $block_name, $prefix ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register frontend CSS for responsive conditions without enqueuing.
	 *
	 * This registers the frontend stylesheet but doesn't enqueue it yet,
	 * allowing conditional loading only when responsive conditions are actually used.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_frontend_css() {
		// Check if CSS file exists.
		$css_file = SPECTRA_DIR . 'build/extensions/responsive-conditions/style-index.css';
		$css_url  = SPECTRA_URL . 'build/extensions/responsive-conditions/style-index.css';

		if ( file_exists( $css_file ) ) {
			wp_register_style(
				'spectra-responsive-conditions',
				$css_url,
				array(),
				filemtime( $css_file ),
				'all'
			);
		}
	}

	/**
	 * Enqueue frontend CSS if responsive conditions are being used on the page.
	 *
	 * This runs on wp_footer hook, which executes after all blocks have been rendered
	 * and processed, ensuring the CSS is only loaded when actually needed.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function enqueue_frontend_css_if_needed() {
		// Only enqueue if needed and not already enqueued.
		if ( $this->needs_assets &&
		wp_style_is( 'spectra-responsive-conditions', 'registered' ) &&
		! wp_style_is( 'spectra-responsive-conditions', 'enqueued' ) ) {
			wp_enqueue_style( 'spectra-responsive-conditions' );
		}
	}

	/**
	 * Enqueue editor assets for responsive conditions.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_style( 'spectra-extensions-responsive-conditions' );
	}
}
