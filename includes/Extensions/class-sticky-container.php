<?php
/**
 * Sticky Container Extension
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Sticky Container class.
 *
 * @since 3.0.0-beta.2
 */
class StickyContainer {

	use Singleton;

	/**
	 * Flag indicating if sticky container assets are needed.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @var bool
	 */
	private $needs_assets = false;

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block to add sticky positioning styles to blocks
	 * and enqueue frontend styles and scripts when needed.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'add_sticky_styles_to_blocks' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_frontend_assets_if_needed' ) );
	}

	/**
	 * Add sticky positioning styles to the output of supported blocks.
	 *
	 * Ensures the block has the 'stickyContainer' attribute defined and injects
	 * the sticky positioning styles and classes into the block's wrapper tag using WP_HTML_Tag_Processor.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content with sticky styles added.
	 */
	public function add_sticky_styles_to_blocks( $block_content, $block ) {
		// If the block should not be processed, return the original content.
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$sticky_settings = $this->get_sticky_settings( $block['attrs'] );

		// If the block does not have sticky enabled, return the original content.
		if ( empty( $sticky_settings ) || ! is_array( $sticky_settings ) ) {
			return $block_content;
		}

		// Mark that we need assets.
		$this->needs_assets = true;

		// Apply sticky styles to the block content.
		$modified_content = $this->apply_sticky_styles( $block_content, $sticky_settings );

		return false !== $modified_content ? $modified_content : $block_content;
	}

	/**
	 * Determine whether the block should be processed for sticky container.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& isset( $block['attrs']['stickyContainer'] )
			&& $this->is_allowed_block( $block['blockName'] );
	}

	/**
	 * Retrieve sticky container settings from block attributes.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Sticky settings if enabled, null otherwise.
	 */
	private function get_sticky_settings( $attrs ) {
		$sticky_container = $attrs['stickyContainer'] ?? array();

		if ( ! is_array( $sticky_container ) ) {
			return null;
		}

		// Only process if sticky is enabled.
		$sticky_enabled = $sticky_container['stickyEnabled'] ?? false;
		if ( ! $sticky_enabled ) {
			return null;
		}

		// Get sticky position (top or bottom).
		$stick_at = $sticky_container['stickAt'] ?? 'top';

		// Ensure sticky position is only 'top' or 'bottom'.
		$stick_at = in_array( $stick_at, array( 'top', 'bottom' ), true ) ? $stick_at : 'top';

		// Get offset value.
		$offset = $sticky_container['offset'] ?? '0px';

		// Get keepInsideParent value (only applies to top sticky).
		$keep_inside_parent = $sticky_container['keepInsideParent'] ?? false;

		return array(
			'stickAt'          => $stick_at,
			'offset'           => $offset,
			'keepInsideParent' => $keep_inside_parent,
		);
	}

	/**
	 * Apply sticky positioning styles to block content.
	 *
	 * Uses WP_HTML_Tag_Processor to safely inject sticky classes and CSS custom properties.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @param string $content Block content.
	 * @param array  $sticky_settings Sticky positioning settings.
	 * @return string|false Modified content or false on failure.
	 */
	private function apply_sticky_styles( $content, $sticky_settings ) {
		if ( empty( $content ) || empty( $sticky_settings ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		$stick_at           = $sticky_settings['stickAt'];
		$offset             = $sticky_settings['offset'];
		$keep_inside_parent = $sticky_settings['keepInsideParent'];

		// Get existing class attribute.
		$existing_class = $processor->get_attribute( 'class' ) ? $processor->get_attribute( 'class' ) : '';

		// Add sticky container class.
		$new_classes   = array_filter( explode( ' ', $existing_class ) );
		$new_classes[] = 'spectra-sticky-container';

		// Add bottom class if sticking to bottom.
		if ( 'bottom' === $stick_at ) {
			$new_classes[] = 'spectra-sticky-bottom';
		}

		$new_class = implode( ' ', array_unique( $new_classes ) );
		$processor->set_attribute( 'class', $new_class );

		// Get existing style attribute.
		$existing_style = $processor->get_attribute( 'style' ) ? $processor->get_attribute( 'style' ) : '';

		// Add sticky offset CSS custom property (offset already includes unit).
		$offset_style = "--spectra-sticky-offset: {$offset};";

		// Add keepInsideParent CSS custom property.
		$keep_inside_parent_style = '--spectra-sticky-keep-inside-parent: ' . ( $keep_inside_parent ? '1' : '0' ) . ';';

		// Combine with existing styles. rtrim handles WP stripping the trailing semicolon in get_block_wrapper_attributes.
		$new_style = $existing_style ? rtrim( trim( $existing_style ), ';' ) . '; ' . $offset_style . ' ' . $keep_inside_parent_style : $offset_style . ' ' . $keep_inside_parent_style;

		$processor->set_attribute( 'style', $new_style );

		return $processor->get_updated_html();
	}

	/**
	 * Check if a block is allowed for sticky container.
	 *
	 * Only container blocks are allowed.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( $block_name ) {
		// Only allow sticky positioning on container blocks.
		return 'spectra/container' === $block_name;
	}

	/**
	 * Register frontend JavaScript for sticky container without enqueuing.
	 *
	 * This registers the frontend script but doesn't enqueue it yet,
	 * allowing conditional loading only when sticky container is actually used.
	 * All styling is handled via JavaScript inline styles for better performance.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @return void
	 */
	public function register_frontend_assets() {
		// Register JS.
		$js_file       = SPECTRA_BLOCKS_DIR . 'build/extensions/sticky-container/view.js';
		$js_url        = SPECTRA_BLOCKS_URL . 'build/extensions/sticky-container/view.js';
		$js_asset_file = SPECTRA_BLOCKS_DIR . 'build/extensions/sticky-container/view.asset.php';

		if ( file_exists( $js_file ) ) {
			$js_asset = file_exists( $js_asset_file ) ? require $js_asset_file : array(
				'dependencies' => array(),
				'version'      => SPECTRA_BLOCKS_VER,
			);

			wp_register_script(
				'spectra-blocks-sticky-container',
				$js_url,
				$js_asset['dependencies'],
				$js_asset['version'],
				true
			);
		}
	}

	/**
	 * Enqueue frontend JavaScript if sticky container is being used on the page.
	 *
	 * This runs on wp_footer hook, which executes after all blocks have been rendered
	 * and processed, ensuring the script is only loaded when actually needed.
	 *
	 * @since 3.0.0-beta.2
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets_if_needed() {
		if ( ! $this->needs_assets ) {
			return;
		}

		// Enqueue JS if registered and not already enqueued.
		if ( wp_script_is( 'spectra-blocks-sticky-container', 'registered' ) &&
		! wp_script_is( 'spectra-blocks-sticky-container', 'enqueued' ) ) {
			wp_enqueue_script( 'spectra-blocks-sticky-container' );
		}
	}
}
