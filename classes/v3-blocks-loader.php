<?php
/**
 * V3 Blocks Loader Helper
 *
 * Temporary file to load v3 blocks list into Spectra_Helper::$block_list
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get list of v3 blocks from block.json files and populate Spectra_Helper::$block_list
 *
 * @since 0.0.1
 */
function spectra_load_v3_blocks_list() {
	if ( ! defined( 'SPECTRA_3_DIR' ) ) {
		return;
	}

	$blocks = array();
	$blocks_dir = SPECTRA_3_DIR . 'build/blocks/';

	if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
		return;
	}

	$block_files = glob( $blocks_dir . '*/block.json' );

	if ( false === $block_files || empty( $block_files ) ) {
		return;
	}

	// Map blocks to preview image names (for blocks without direct image match)
	$icon_map = array(
		'buttons'       => 'button',
		'icons'         => 'icon',
		'counter'       => 'container', // Use container as fallback
		'popup-builder' => 'modal',
	);

	foreach ( $block_files as $block_file ) {
		$block_json = json_decode( file_get_contents( $block_file ), true );

		if ( ! $block_json || ! isset( $block_json['name'] ) ) {
			continue;
		}

		$block_name = $block_json['name'];
		$block_title = isset( $block_json['title'] ) ? $block_json['title'] : '';

		// Extract block slug from name (e.g., "spectra/container" -> "container")
		$slug_parts = explode( '/', $block_name );
		$slug = end( $slug_parts );

		// Use mapped icon name if available, otherwise use slug
		$icon_name = isset( $icon_map[ $slug ] ) ? $icon_map[ $slug ] : $slug;

		$blocks[ $block_name ] = array(
			'slug'     => $slug,
			'title'    => $block_title,
			'icon'     => $icon_name, // Used for preview image: /admin/assets/block-previews/{icon_name}.webp
			'default'  => true,
			'is_child' => strpos( $slug, '-child' ) !== false,
		);
	}

	Spectra_Helper::$block_list = $blocks;
}

// Load blocks immediately when file is included, before any hooks
spectra_load_v3_blocks_list();
