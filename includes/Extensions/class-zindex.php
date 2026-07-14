<?php
/**
 * Z-Index Extension
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Z-Index class.
 *
 * @since 3.0.0
 */
class ZIndex {

	use Singleton;

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block to add z-index styles to blocks.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'add_z_index_styles_to_blocks' ), 3, 2 );
	}

	/**
	 * Add z-index styles to the output of supported blocks.
	 *
	 * Ensures the block has the 'spectraZIndex' attribute defined and injects
	 * the z-index styles into the block's wrapper tag using WP_HTML_Tag_Processor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content with z-index styles added.
	 */
	public function add_z_index_styles_to_blocks( $block_content, $block ) {
		// If the block should not be processed, return the original content.
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$z_index = $this->get_z_index_value( $block['attrs'] );

		// If the block does not have a valid z-index value, return the original content.
		if ( null === $z_index ) {
			return $block_content;
		}

		// Apply z-index styles to the block content.
		$modified_content = $this->apply_z_index_styles( $block_content, $z_index );

		return false !== $modified_content ? $modified_content : $block_content;
	}

	/**
	 * Determine whether the block should be processed for z-index.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& isset( $block['attrs']['spectraZIndex'] )
			&& $this->is_allowed_block( $block['blockName'] );
	}

	/**
	 * Retrieve sanitized z-index value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attrs Block attributes.
	 * @return int|null Z-index value or null if not valid.
	 */
	private function get_z_index_value( $attrs ) {
		// Get z-index directly from attributes.
		$z_index = $attrs['spectraZIndex'] ?? null;

		// Return null if z-index is not set.
		if ( null === $z_index ) {
			return null;
		}

		// Ensure it's an integer and within reasonable bounds.
		return intval( $z_index );
	}

	/**
	 * Apply z-index styles to block content.
	 *
	 * Uses WP_HTML_Tag_Processor to safely inject z-index styles into the first tag.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content Block content.
	 * @param int    $z_index Z-index value.
	 * @return string|false Modified content or false on failure.
	 */
	private function apply_z_index_styles( $content, $z_index ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		// Get existing style attribute.
		$existing_style = $processor->get_attribute( 'style' ) ? $processor->get_attribute( 'style' ) : '';

		// Add z-index CSS custom property.
		$z_index_style = "--spectra-z-index: {$z_index};";

		// Combine with existing styles. rtrim handles WP stripping the trailing semicolon in get_block_wrapper_attributes.
		$new_style = $existing_style ? rtrim( trim( $existing_style ), ';' ) . '; ' . $z_index_style : $z_index_style;

		$processor->set_attribute( 'style', $new_style );

		// Add has-z-index class.
		$existing_class = $processor->get_attribute( 'class' ) ? $processor->get_attribute( 'class' ) : '';
		$new_class      = trim( $existing_class . ' has-z-index' );
		$processor->set_attribute( 'class', $new_class );

		return $processor->get_updated_html();
	}

	/**
	 * Check if a block is allowed for z-index.
	 *
	 * Uses allowed prefixes to determine if a block should receive z-index styles.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( $block_name ) {
		// Excluded blocks that shouldn't have z-index.
		$excluded_blocks = array(
			// Legacy Blocks.
			'uagb/cf7-styler',
			'uagb/wp-search',
			'uagb/gf-styler',
			'uagb/columns',
			'uagb/section',
			'spectra/popup-builder',
			// Child blocks that inherit from parent.
			'spectra/accordion-child-details',
			'spectra/accordion-child-header',
			'spectra/accordion-child-header-content',
			'spectra/accordion-child-header-icon',
			'spectra/accordion-child-item',
			'spectra/countdown-child-day',
			'spectra/countdown-child-hour',
			'spectra/countdown-child-label',
			'spectra/countdown-child-minute',
			'spectra/countdown-child-number',
			'spectra/countdown-child-second',
			'spectra/countdown-child-separator',
			'spectra/list-child-icon',
			'spectra/list-child-item',
			'spectra/modal-child-popup',
			'spectra/modal-child-popup-close-icon',
			'spectra/modal-child-popup-content',
			'spectra/modal-child-trigger',
			'spectra/modal-child-trigger-button',
			'spectra/modal-child-trigger-content',
			'spectra/modal-child-trigger-icon',
			'spectra/slider-child',
			'spectra/tabs-child-tab-button',
			'spectra/tabs-child-tab-wrapper',
			'spectra/tabs-child-tabpanel',
		);

		// Check if block is excluded.
		if ( in_array( $block_name, $excluded_blocks, true ) ) {
			return false;
		}

		// Allow blocks with specific prefixes.
		return preg_match( '/^(spectra\/|spectra-pro\/|core\/)/', $block_name );
	}
}
