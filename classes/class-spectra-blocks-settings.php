<?php
/**
 * Settings manager for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings manager - wraps WordPress options with spectra_blocks_ prefix.
 */
class Spectra_Blocks_Settings {

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Option key (without prefix).
	 * @param mixed  $fallback Default value.
	 * @return mixed
	 */
	public static function get( $key, $fallback = false ) {
		$option_name = 'spectra_blocks_' . $key;
		$value       = get_option( $option_name, null );
		if ( null === $value && is_multisite() ) {
			$value = get_site_option( $option_name, $fallback );
		} elseif ( null === $value ) {
			$value = $fallback;
		}
		return $value;
	}

	/**
	 * Update a setting value.
	 *
	 * @param string $key   Option key (without prefix).
	 * @param mixed  $value Value to store.
	 * @return bool
	 */
	public static function update( $key, $value ) {
		return update_option( 'spectra_blocks_' . $key, $value );
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key Option key (without prefix).
	 * @return bool
	 */
	public static function delete( $key ) {
		return delete_option( 'spectra_blocks_' . $key );
	}
}
