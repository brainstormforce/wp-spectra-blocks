<?php
/**
 * Cache management for Spectra Blocks.
 * Hooks into popular caching plugins to purge stale CSS on post save.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Purges cache from popular caching plugins when Spectra Blocks CSS is regenerated.
 */
class Spectra_Blocks_Caching {

	/**
	 * Initialize cache hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'purge_post_cache' ), 10, 1 );
		add_action( 'spectra_blocks_css_regenerated', array( __CLASS__, 'purge_all_cache' ) );
	}

	/**
	 * Purge cache for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function purge_post_cache( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		self::purge_all_cache();
	}

	/**
	 * Purge cache across all supported caching plugins.
	 *
	 * @return void
	 */
	public static function purge_all_cache() {
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// LiteSpeed Cache.
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}

		// Autoptimize.
		if ( class_exists( 'autoptimizeCache' ) ) {
			autoptimizeCache::clearall();
		}

		// Swift Performance.
		if ( class_exists( 'Swift_Performance_Cache' ) ) {
			Swift_Performance_Cache::clear_all_cache();
		}

		// Kinsta / Nginx Helper — fires third-party hook defined by Nginx Helper plugin.
		if ( class_exists( 'Nginx_Helper' ) ) {
			do_action( 'rt_nginx_helper_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		// WP Engine.
		if ( class_exists( 'WpeCommon' ) && method_exists( 'WpeCommon', 'purge_memcached' ) ) {
			WpeCommon::purge_memcached();
			WpeCommon::clear_maxcdn_cache();
			WpeCommon::purge_varnish_cache();
		}

		// Cloudflare.
		if ( class_exists( 'CF\WordPress\Hooks' ) ) {
			$cloudflare = new CF\WordPress\Hooks();
			$cloudflare->purgeCacheEverything();
		}

		// Comet Cache.
		if ( defined( 'COMET_CACHE_PLUGIN_FILE' ) ) {
			clearstatcache();
		}

		// Hummingbird.
		if ( function_exists( 'wphb_clear_page_cache' ) ) {
			wphb_clear_page_cache();
		}

		do_action( 'spectra_blocks_after_cache_purge' );
	}
}
