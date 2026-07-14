<?php
/**
 * Uninstall handler for Spectra Blocks.
 *
 * Fires when the plugin is deleted from WordPress admin. Removes plugin-owned
 * options, transients, post-meta, user-meta, and the cache directory under
 * wp-content/uploads/. Does NOT touch user-generated content (published posts,
 * custom post types) or shared-library state (`bsf_*`, `ast_block_templates_*`,
 * `zip_ai_*`, etc.) — those libraries are reused across other BSF products
 * (Astra, Starter Templates, SureForms) and remain owned by whichever plugin
 * is still active.
 *
 * @package Spectra_Blocks
 */

// Only run when invoked by WordPress as part of plugin deletion.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Delete plugin-owned options.
 *
 * The plugin uses two literal option keys plus a `spectra_blocks_*` prefix
 * for ~30 admin settings (block enable/disable, dashboard layout, on-page
 * CSS toggle, etc.). Wildcard-delete every key starting with the prefix
 * rather than enumerating each one — that way new options added in the
 * future are also cleaned up.
 */
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'spectra_blocks_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_spectra_blocks_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Discrete options that don't share the prefix.
$spectra_blocks_extra_options = array(
	'spectra_global_block_styles',
);

foreach ( $spectra_blocks_extra_options as $spectra_blocks_option ) {
	delete_option( $spectra_blocks_option );

	if ( is_multisite() ) {
		delete_site_option( $spectra_blocks_option );
	}
}

/**
 * Delete plugin-owned transients.
 *
 * Plugin transients use the `spectra_*` prefix. Wildcard-delete the
 * `_transient_spectra_*` and `_transient_timeout_spectra_*` rows.
 */
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_spectra_%', '_transient_timeout_spectra_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

if ( is_multisite() ) {
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", '_site_transient_spectra_%', '_site_transient_timeout_spectra_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
}

// Legacy geolocation transient from the bundled zipwp-images library — uses a
// `zipwp_` prefix so it is not covered by the wildcard above.
delete_transient( 'zipwp_images_server_country_code' );
delete_site_transient( 'zipwp_images_server_country_code' );

/**
 * Delete plugin-owned post-meta across all posts.
 *
 *  - spectra-blocks-popup-enabled, spectra-blocks-popup-repetition, spectra-blocks-popup-type
 *      Popup-builder block per-post settings.
 *  - _is_spectra_font_family
 *      Marks fonts registered via Spectra Blocks (vs imported from a theme).
 *  - _spectra_css_regenerated
 *      Timestamp of last dynamic-CSS rebuild.
 *  - _spectra_css_cache
 *      Per-post dynamic-CSS cache payload.
 *  - _spectra_blocks_css_file_name, _spectra_blocks_js_file_name
 *      Filenames of generated per-post CSS/JS assets in the cache directory.
 *  - _spectra_blocks_page_assets
 *      Cached list of block assets needed for a given post.
 */
$spectra_blocks_post_meta_keys = array(
	'spectra-blocks-popup-enabled',
	'spectra-blocks-popup-repetition',
	'spectra-blocks-popup-type',
	'_is_spectra_font_family',
	'_spectra_css_regenerated',
	'_spectra_css_cache',
	'_spectra_blocks_css_file_name',
	'_spectra_blocks_js_file_name',
	'_spectra_blocks_page_assets',
);

foreach ( $spectra_blocks_post_meta_keys as $spectra_blocks_meta_key ) {
	delete_post_meta_by_key( $spectra_blocks_meta_key );
}

/**
 * Delete plugin-owned user-meta across all users.
 *
 *  - spectra_learn_progress : per-user admin-dashboard tutorial progress.
 *
 * WordPress core has no delete_user_meta_by_key() helper, so the rows are
 * removed directly from the usermeta table. Only literal meta_key strings
 * defined in this file reach the query — no user input.
 */
$spectra_blocks_user_meta_keys = array(
	'spectra_learn_progress',
);

foreach ( $spectra_blocks_user_meta_keys as $spectra_blocks_user_meta_key ) {
	$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $spectra_blocks_user_meta_key ), array( '%s' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

/**
 * Remove the plugin's cache directory under wp-content/uploads/.
 *
 * Per-post dynamic CSS is written by includes/class-asset-loader.php to
 * `{uploads}/spectra-blocks/spectra-blocks-{post_id}.css` (plus a global
 * `spectra-blocks.css`). The directory is scoped to this plugin, so it is
 * safe to delete wholesale on uninstall.
 */
$spectra_blocks_upload_dir = wp_upload_dir();
if ( empty( $spectra_blocks_upload_dir['error'] ) ) {
	$spectra_blocks_cache_dir = trailingslashit( $spectra_blocks_upload_dir['basedir'] ) . 'spectra-blocks';

	if ( is_dir( $spectra_blocks_cache_dir ) ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( ! empty( $wp_filesystem ) ) {
			$wp_filesystem->delete( $spectra_blocks_cache_dir, true );
		}
	}
}
