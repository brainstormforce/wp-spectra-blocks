<?php
/**
 * Per-post JIT cache + global version stamp.
 *
 * JIT-compiled CSS is stored in post meta keyed by a version stamp so global
 * invalidations (Style Guide change, user-CSS change, class registry
 * invalidation) are effectively free: the stamp bumps, and the next render of
 * any post re-compiles lazily.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JitCache.
 *
 * @since 1.0.0
 */
class JitCache {

	/**
	 * Post meta key for the compiled CSS payload.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY = '_spectra_gs_jit_css';

	/**
	 * WP option storing the global cache version stamp.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION_OPTION = 'spectra_blocks_gs_jit_version';

	/**
	 * Retrieve cached CSS for a post; compile + persist on a miss or stamp mismatch.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_for_post( int $post_id ): string {
		if ( $post_id <= 0 ) {
			return '';
		}

		$expected_version = self::get_version();
		$stored           = get_post_meta( $post_id, self::META_KEY, true );

		if ( is_array( $stored )
			&& isset( $stored['version'], $stored['css'] )
			&& (string) $stored['version'] === $expected_version
			&& is_string( $stored['css'] )
		) {
			return $stored['css'];
		}

		return self::rebuild( $post_id );
	}

	/**
	 * Recompile and persist CSS for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string Compiled CSS (may be empty).
	 */
	public static function rebuild( int $post_id ): string {
		if ( $post_id <= 0 ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$class_strings = JitCompiler::collect_class_strings_from_content( (string) $post->post_content );
		$css           = JitCompiler::compile( $class_strings );

		// update_post_meta() unslashes input; pre-slash so CSS-escape backslashes
		// (from JitCompiler::escape_selector, e.g. `.p-\[120px\]`) survive round-trip.
		update_post_meta(
			$post_id,
			self::META_KEY,
			array(
				'version' => self::get_version(),
				'css'     => wp_slash( $css ),
			)
		);

		return $css;
	}

	/**
	 * Clear the cache entry for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function clear_for_post( int $post_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}
		delete_post_meta( $post_id, self::META_KEY );
	}

	/**
	 * Current global cache version stamp.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_version(): string {
		$version = get_option( self::VERSION_OPTION, '' );
		if ( ! is_string( $version ) || '' === $version ) {
			$version = (string) time();
			update_option( self::VERSION_OPTION, $version, false );
		}
		return $version;
	}

	/**
	 * Bump the global cache version stamp (lazy rebuild on next read).
	 *
	 * Clears the in-memory compiler memo so re-compilations within the same
	 * request see fresh resolution.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function bump_version(): void {
		update_option( self::VERSION_OPTION, (string) microtime( true ), false );
		JitCompiler::reset_memo();
	}
}
