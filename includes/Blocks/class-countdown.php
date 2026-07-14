<?php
/**
 * Countdown Block
 *
 * @package Spectra\Blocks
 */

namespace SpectraBlocks\Blocks;

use SpectraBlocks\Traits\Singleton;

/**
 * Countdown class.
 *
 * @since 3.0.0
 */
class Countdown {

	use Singleton;

	/**
	 * Initialize the countdown block by adding necessary filters.
	 *
	 * This function adds a filter to modify the separator countdown block data
	 * to ensure separators are hidden when adjacent time units are not displayed.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		add_filter( 'render_block_data', array( $this, 'modify_the_separator_countdown_block_data' ) );
	}

	/**
	 * Modifies the parsed countdown block data to determine visibility of separator blocks
	 * based on adjacent time unit blocks (days, hours, minutes, seconds).
	 *
	 * This function ensures that separators are only shown when they are between two visible
	 * time unit blocks, and only the first separator between any two units is shown.
	 *
	 * Hooked into the `render_block_data` filter.
	 *
	 * @since 3.0.0
	 *
	 * @param array $parsed_block The parsed block data for the countdown block.
	 * @return array The modified parsed block data with updated separator visibility.
	 */
	public function modify_the_separator_countdown_block_data( $parsed_block ) {
		// Only modify the block if it's the parent countdown block.
		if ( 'spectra/countdown' !== $parsed_block['blockName'] ) {
			return $parsed_block;
		}

		// Ensure innerBlocks array exists, but only if we need to process inner blocks.
		$has_inner_blocks = isset( $parsed_block['innerBlocks'] );
		$inner_blocks     = $has_inner_blocks ? $parsed_block['innerBlocks'] : array();

		// Retrieve visibility settings from the parent block's attributes.
		$attributes = $parsed_block['attrs'] ?? array();

		// Handle showSeparator attribute logic.
		$show_separator = $attributes['showSeparator'] ?? true;

		// If showSeparator is false, set separatorType to empty string.
		if ( false === $show_separator ) {
			$parsed_block['attrs']['separatorType'] = '';
		}

		// Handle time unit visibility for inner blocks.
		$show_days    = $attributes['showDays'] ?? true;
		$show_hours   = $attributes['showHours'] ?? true;
		$show_minutes = $attributes['showMinutes'] ?? true;
		$show_seconds = $attributes['showSeconds'] ?? true;

		// First pass: Record positions of visible time unit blocks.
		$time_unit_blocks = array(
			'spectra/countdown-child-day'    => $show_days,
			'spectra/countdown-child-hour'   => $show_hours,
			'spectra/countdown-child-minute' => $show_minutes,
			'spectra/countdown-child-second' => $show_seconds,
		);

		$visible_units = array();

		// Only process inner blocks if they exist.
		if ( ! empty( $inner_blocks ) ) {
			// First pass: Collect all visible time units and their positions.
			foreach ( $inner_blocks as $index => $block ) {
				if ( isset( $time_unit_blocks[ $block['blockName'] ] ) && $time_unit_blocks[ $block['blockName'] ] ) {
					$visible_units[ $index ] = $block['blockName'];
				}
			}

			// Second pass: Determine visibility for each separator block.
			foreach ( $inner_blocks as $index => &$block ) {
				// Skip non-separator blocks.
				if ( 'spectra/countdown-child-separator' !== ( $block['blockName'] ?? '' ) ) {
					continue;
				}

				// Search for the previous visible time unit block.
				$prev_visible_index = null;
				for ( $i = $index - 1; $i >= 0; $i-- ) {
					if ( isset( $visible_units[ $i ] ) ) {
						$prev_visible_index = $i;
						break;
					}
				}

				// Search for the next visible time unit block.
				$next_visible_index = null;
				$inner_blocks_count = count( $inner_blocks );
				for ( $i = $index + 1; $i < $inner_blocks_count; $i++ ) {
					if ( isset( $visible_units[ $i ] ) ) {
						$next_visible_index = $i;
						break;
					}
				}

				// Decide whether to show the separator.
				$should_show = false;

				// Only show if it is between two visible units and is the first such separator.
				if ( null !== $prev_visible_index && null !== $next_visible_index ) {
					$is_first_separator = true;

					// Check for earlier separator blocks between the same two visible units.
					for ( $i = $prev_visible_index + 1; $i < $next_visible_index; $i++ ) {
						if ( isset( $inner_blocks[ $i ]['blockName'] ) &&
							'spectra/countdown-child-separator' === $inner_blocks[ $i ]['blockName'] &&
							$i < $index ) {
							$is_first_separator = false;
							break;
						}
					}

					$should_show = $is_first_separator;
				}

				// Update the 'show' attribute on the separator block.
				$block['attrs']['show'] = $should_show;
			}
		}

		// Save the updated inner blocks back to the parsed block only if they originally existed.
		if ( $has_inner_blocks ) {
			$parsed_block['innerBlocks'] = $inner_blocks;
		}

		return $parsed_block;
	}
}
