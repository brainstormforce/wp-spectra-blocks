<?php
/**
 * Image Mask Extension.
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;

/**
 * ImageMask class.
 *
 * @since 3.0.0
 */
class ImageMask {

	use Singleton;

	/**
	 * Allowed mask shapes.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $allowed_shapes = array(
		'blob1',
		'blob2',
		'blob3',
		'blob4',
		'circle',
		'custom',
		'diamond',
		'hexagon',
		'rounded',
	);

	/**
	 * Initialize the class
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'add_mask_styles' ), 10, 2 );
	}

	/**
	 * Add mask styles to image block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content  Block content for the image block.
	 * @param array  $block          Block for the image block.
	 * @return string Block content for the image block.
	 */
	public function add_mask_styles( $block_content, $block ) {
		// If this is not a core image block, abandon ship.
		if ( 'core/image' !== $block['blockName'] ) {
			return $block_content;
		}

		// Get the mask settings from the block attributes.
		$mask_settings = $block['attrs']['spectraMask'] ?? null;

		// If no mask settings or shape is none, abandon ship.
		if ( ! $mask_settings || ! isset( $mask_settings['shape'] ) || 'none' === $mask_settings['shape'] ) {
			return $block_content;
		}

		// Early return if WP_HTML_Tag_Processor is not available (WordPress < 6.2).
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		$mask_shape    = $mask_settings['shape'];
		$mask_url      = $this->get_mask_url( $mask_shape, $mask_settings['image'] ?? array() );
		$mask_size     = $mask_settings['size'] ?? 'contain';
		$mask_position = $this->get_position_value( $mask_settings['position'] ?? null );
		$mask_repeat   = $mask_settings['repeat'] ?? 'no-repeat';

		// If the mask URL is not set, abandon ship.
		if ( ! $mask_url ) {
			return $block_content;
		}

		// Build the style string for the mask.
		$style = sprintf(
			'--spectra-mask-image:url(%s);--spectra-mask-size:%s;--spectra-mask-position:%s;--spectra-mask-repeat:%s',
			esc_url( $mask_url ),
			esc_attr( $mask_size ),
			esc_attr( $mask_position ),
			esc_attr( $mask_repeat )
		);

		// Process the block content to add the mask styles in the image block main wrapper which is a figure tag.
		$processor = new \WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag( 'figure' ) ) {
			$existing_style = $processor->get_attribute( 'style' );
			$new_style      = $existing_style ? $existing_style . ';' . $style : $style;
			$processor->set_attribute( 'style', $new_style );
			return $processor->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Get mask URL based on shape.
	 *
	 * @since 3.0.0
	 *
	 * @param string $shape        Mask shape from the image-block attributes.
	 * @param array  $custom_mask  Custom mask from the image-block attributes.
	 * @return string Mask URL from the image-block attributes.
	 */
	private function get_mask_url( $shape, $custom_mask = array() ) {
		// Validate shape against allowed shapes.
		if ( ! in_array( $shape, $this->allowed_shapes, true ) ) {
			return '';
		}

		// Check if custom mask is provided and valid.
		if ( 'custom' === $shape && is_array( $custom_mask ) && ! empty( $custom_mask['url'] ) ) {
			return esc_url( $custom_mask['url'] );
		}

		// Generate path for predefined mask.
		$mask_path = SPECTRA_BLOCKS_DIR . 'assets/masks/' . $shape . '.svg';

		// Check if mask file exists.
		if ( file_exists( $mask_path ) ) {
			// Use wp_make_link_relative for proper relative URL generation.
			// This handles subdirectory installs and is the WordPress standard way.
			$mask_url = SPECTRA_BLOCKS_URL . 'assets/masks/' . $shape . '.svg';
			return wp_make_link_relative( $mask_url );
		}
		return '';
	}

	/**
	 * Get position value from coordinates.
	 *
	 * @since 3.0.0
	 *
	 * @param array|null $position Position object from attributes.
	 * @return string CSS position value.
	 */
	private function get_position_value( $position ) {
		if ( ! is_array( $position ) || ! isset( $position['x'] ) || ! isset( $position['y'] ) ) {
			return '50% 50%';
		}

		$x = floatval( $position['x'] );
		$y = floatval( $position['y'] );

		// Ensure values are between 0 and 1.
		$x = max( 0, min( 1, $x ) );
		$y = max( 0, min( 1, $y ) );
		// Return the position value as a string.
		return sprintf( '%s%% %s%%', $x * 100, $y * 100 );
	}
}
