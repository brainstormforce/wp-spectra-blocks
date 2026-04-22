<?php
/**
 * Uninstall handler for Spectra Blocks.
 *
 * Fires when the plugin is deleted from WordPress admin. Removes plugin-specific
 * options, transients, and post-meta. Does NOT touch user-generated content
 * (published posts, custom post types) or shared-library state (options
 * prefixed with bsf_*, ast_block_templates_*, etc.) — those libraries are
 * reused across other BSF products (Astra, Starter Templates, SureForms) and
 * remain owned by whichever plugin is still active.
 *
 * @package Spectra_Blocks
 */

// Security: only run when invoked by WordPress as part of plugin deletion.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin-specific options.
 *
 * Keep this list in sync with keys created via get/update/add_option() in
 * includes/, classes/, admin/. Shared-library options are intentionally
 * excluded.
 */
$spectra_blocks_options = array(
	'spectra_blocks_active_blocks',
	'spectra_blocks_analytics_optin',
	'spectra_blocks_disable_css_cache',
	'spectra_blocks_global_fonts',
	'spectra_blocks_load_fonts_locally',
	'spectra_blocks_load_select_font_globally',
);

foreach ( $spectra_blocks_options as $spectra_blocks_option ) {
	delete_option( $spectra_blocks_option );

	// Multisite: also clear site option in case the plugin was network-activated.
	if ( is_multisite() ) {
		delete_site_option( $spectra_blocks_option );
	}
}

/**
 * Delete plugin-specific post meta across all posts.
 *
 * Post-meta keys used by Spectra Blocks:
 *  - spectra-popup-enabled          : popup-builder on/off state
 *  - _is_spectra_font_family        : marks fonts registered via Spectra Blocks
 *  - _spectra_css_regenerated       : timestamp of last dynamic-CSS rebuild
 */
$spectra_blocks_post_meta_keys = array(
	'spectra-popup-enabled',
	'_is_spectra_font_family',
	'_spectra_css_regenerated',
);

foreach ( $spectra_blocks_post_meta_keys as $spectra_blocks_meta_key ) {
	delete_post_meta_by_key( $spectra_blocks_meta_key );
}

/**
 * Clean orphaned transients created by the plugin.
 *
 * Historically the bundled gutenberg-templates / zipwp-images libraries wrote
 * a `zipwp_images_server_country_code` transient for geolocation-based image
 * engine selection. Geolocation has been removed for WP.org compliance; any
 * residual transient value is now unused and is cleaned up here so uninstall
 * leaves no trace.
 */
delete_transient( 'zipwp_images_server_country_code' );
delete_site_transient( 'zipwp_images_server_country_code' );
