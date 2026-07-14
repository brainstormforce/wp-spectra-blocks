<?php
/**
 * Class to manage Spectra font operations.
 *
 * @package Spectra
 */

namespace SpectraBlocks;

use Exception;
use SpectraBlocks\Traits\Singleton;
use WP_Error;
use WP_Font_Collection;
use WP_Font_Library;
use WP_Font_Utils;
use WP_Query;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage font caching, registration, and deletion.
 *
 * @since 3.0.0
 */
class FontManager {

	use Singleton;

	/**
	 * The key used to store the cached font data in the transients API.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const FONT_CACHE_KEY = 'spectra_blocks_google_fonts_cache';

	/**
	 * Initializes the font manager by hooking into the 'wp_enqueue_scripts' action.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'wp_theme_json_data_user', array( $this, 'filter_theme_json' ) );
		// Run AFTER any other filter that adds fontFace entries so we can
		// normalize every font src URL — including ones already stored in
		// the user's wp_global_styles post under a different scheme — to
		// match the current request. Without this, font files saved with
		// `https://...` srcs fail to load inside the editor's `blob:`
		// iframe canvas when the admin request is `http://...` (and vice
		// versa) because the cross-scheme fetch is blocked.
		add_filter( 'wp_theme_json_data_user', array( $this, 'normalize_font_face_urls' ), 99 );
		add_filter( 'wp_theme_json_data_theme', array( $this, 'normalize_font_face_urls' ), 99 );
		add_filter( 'wp_theme_json_data_default', array( $this, 'normalize_font_face_urls' ), 99 );
		add_action( 'updated_option', array( $this, 'handle_option_update' ), 10, 3 );
	}

	/**
	 * Normalize the URL scheme of every font-face `src` entry in the
	 * theme.json data so it matches the current request. Handles the
	 * common dev / staging gotcha where font URLs were saved under one
	 * scheme (e.g. `https://`) but the runtime serves another (`http://`).
	 *
	 * Walks `settings.typography.fontFamilies.custom[].fontFace[].src`
	 * (which can be a string or an array per the theme.json schema)
	 * and rewrites the scheme via `set_url_scheme()`. Same-origin URLs
	 * only — external font URLs (Google Fonts CDN etc.) are left
	 * untouched.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json Theme JSON data container.
	 * @return \WP_Theme_JSON_Data
	 */
	public function normalize_font_face_urls( $theme_json ) {
		if ( ! is_object( $theme_json ) || ! method_exists( $theme_json, 'get_data' ) || ! method_exists( $theme_json, 'update_with' ) ) {
			return $theme_json;
		}

		$data = $theme_json->get_data();
		if ( empty( $data['settings']['typography']['fontFamilies'] ) ) {
			return $theme_json;
		}

		$site_host = wp_parse_url( site_url(), PHP_URL_HOST );
		$changed   = false;

		foreach ( $data['settings']['typography']['fontFamilies'] as $bucket => $families ) {
			if ( ! is_array( $families ) ) {
				continue;
			}
			foreach ( $families as $i => $family ) {
				if ( empty( $family['fontFace'] ) || ! is_array( $family['fontFace'] ) ) {
					continue;
				}
				foreach ( $family['fontFace'] as $j => $face ) {
					if ( empty( $face['src'] ) ) {
						continue;
					}
					$srcs = is_array( $face['src'] ) ? $face['src'] : array( $face['src'] );
					$new  = array();
					foreach ( $srcs as $src ) {
						if ( ! is_string( $src ) ) {
							$new[] = $src;
							continue;
						}
						$src_host = wp_parse_url( $src, PHP_URL_HOST );
						// Only normalize same-origin URLs.
						if ( $src_host && $site_host && $src_host === $site_host ) {
							$normalized = set_url_scheme( $src );
							if ( $normalized !== $src ) {
								$src     = $normalized;
								$changed = true;
							}
						}
						$new[] = $src;
					}
					$data['settings']['typography']['fontFamilies'][ $bucket ][ $i ]['fontFace'][ $j ]['src'] =
						is_array( $face['src'] ) ? $new : $new[0];
				}
			}
		}

		if ( $changed ) {
			$theme_json->update_with( $data );
		}

		return $theme_json;
	}

	/**
	 * Filters theme.json to add custom fonts.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON object.
	 * @return WP_Theme_JSON_Data Updated theme JSON.
	 */
	public function filter_theme_json( $theme_json ) {
		$fonts_to_add = $this->get_cached_google_fonts();

		// Get the current theme.json and fontFamilies defined (if any).
		$theme_json_path = get_stylesheet_directory() . '/theme.json';
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local theme.json file.
		$theme_json_raw = file_exists( $theme_json_path ) ? json_decode( file_get_contents( $theme_json_path ), true ) : array();
		$font_data      = isset( $theme_json_raw['settings']['typography']['fontFamilies'] ) ? $theme_json_raw['settings']['typography']['fontFamilies'] : array();
		if ( empty( $fonts_to_add ) ) {
			$fonts_to_add = self::get_default_google_fonts();
		}

		// Check if current theme is Twenty Twenty-Four and adjust slugs accordingly.
		$current_theme = wp_get_theme()->get_stylesheet();
		$is_2024_theme = ( 'twentytwentyfour' === $current_theme );

		if ( $is_2024_theme ) {
			foreach ( $fonts_to_add as &$font ) {
				if ( 'Inter' === $font['name'] ) {
					$font['slug'] = 'body';
				} elseif ( 'Cardo' === $font['name'] ) {
					$font['slug'] = 'heading';
				}
			}
			unset( $font );
		}

		// Step 1: Collect all slugs from theme fonts.
		$theme_slugs = array_map(
			function ( $font ) {
				return $font['slug'];
			},
			$font_data
		);

		// Step 2: Filter out default fonts that are already present in theme fonts.
		$fonts_to_add = array_filter(
			$fonts_to_add,
			function ( $font ) use ( $theme_slugs ) {
				return ! in_array( $font['slug'], $theme_slugs, true );
			}
		);

		$theme_data = $theme_json->get_data();
		$version    = isset( $theme_data['version'] ) ? $theme_data['version'] : 3;

		$font_data = array();
		if ( isset(
			$theme_data['settings'],
			$theme_data['settings']['typography'],
			$theme_data['settings']['typography']['fontFamilies'],
			$theme_data['settings']['typography']['fontFamilies']['custom']
		) ) {
			$font_data = $theme_data['settings']['typography']['fontFamilies']['custom'];
		}

		$existing_fonts = array_column( $font_data, 'name' );
		$font_data      = array_merge(
			$font_data,
			array_filter( $fonts_to_add, fn( $font ) => ! in_array( $font['name'], $existing_fonts, true ) )
		);

		usort(
			$font_data,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		$theme_json->update_with(
			array(
				'version'  => $version,
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							'custom' => $font_data,
						),
					),
				),
			)
		);

		return $theme_json;
	}

	/**
	 * Clears the transient cache for the google fonts when the `FONT_CACHE_KEY` option is updated.
	 *
	 * @since 3.0.0
	 *
	 * @param string $option   The name of the option that was updated.
	 * @param mixed  $old_value The old value of the option.
	 * @param mixed  $new_value The new value of the option.
	 * @return void
	 */
	public function handle_option_update( $option, $old_value, $new_value ) {
		wp_raise_memory_limit();

		if ( 'spectra_blocks_load_gfonts_locally' === $option ) {
			$fonts = self::get_spectra_selected_font_names();

			if ( 'disabled' === $new_value ) {
				$this->delete_font_locally( $fonts );
			}

			$fonts         = $this->get_font_data( $fonts );
			$updated_fonts = $this::is_enabled_load_locally() ? $this->register_fonts_locally( $fonts ) : $fonts;
			set_transient( self::FONT_CACHE_KEY, $updated_fonts );
		}

		if ( 'spectra_blocks_select_font_globally' !== $option ) {
			return;
		}

		$old_fonts = is_array( $old_value ) ? array_column( $old_value, 'value' ) : array();
		$new_fonts = is_array( $new_value ) ? array_column( $new_value, 'value' ) : array();

		$fonts_to_delete = array_diff( $old_fonts, $new_fonts );
		$fonts_to_add    = array_diff( $new_fonts, $old_fonts );

		if ( empty( $fonts_to_delete ) && empty( $fonts_to_add ) ) {
			return;
		}

		$cached_fonts  = $this->get_cached_google_fonts();
		$updated_fonts = array_filter(
			array_map(
				function ( $font ) use ( $fonts_to_delete ) {
					if ( in_array( $font['name'], $fonts_to_delete, true ) ) {
						return null;
					}

					return $font;
				},
				$cached_fonts
			)
		);

		if ( ! empty( $fonts_to_delete ) ) {
			$this->delete_font_locally( $fonts_to_delete );
		}

		if ( ! empty( $fonts_to_add ) ) {
			$fonts_to_add  = $this->get_font_data( $fonts_to_add );
			$new_font_data = self::is_enabled_load_locally() ? $this->register_fonts_locally( $fonts_to_add ) : $fonts_to_add;

			if ( ! is_wp_error( $new_font_data ) ) {
				$updated_fonts = array_merge( $updated_fonts, $new_font_data );
			}
		}

		set_transient( self::FONT_CACHE_KEY, $updated_fonts );
	}

	/**
	 * Deletes a list of fonts from the local storage.
	 *
	 * @since 3.0.0
	 *
	 * @param array $fonts List of fonts to delete. Each font should have a 'slug' key with the font slug.
	 * @return void
	 */
	private function delete_font_locally( $fonts ) {
		if ( empty( $fonts ) ) {
			return;
		}

		global $wpdb;
		$font_slugs = array_map( fn( $font ) => str_replace( ' ', '-', strtolower( $font ) ), $fonts );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$font_family_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} 
					WHERE post_type = %s 
					AND post_name IN (" . implode( ',', array_fill( 0, count( $font_slugs ), '%s' ) ) . ')',
				array_merge( array( 'wp_font_family' ), $font_slugs )
			)
		);

		$font_dir = wp_get_font_dir();
		if ( empty( $font_family_ids ) || empty( $font_dir['path'] ) ) {
			return;
		}

		foreach ( $font_family_ids as $font_family_id ) {
			if ( '1' !== get_post_meta( $font_family_id, '_is_spectra_font_family', true ) ) {
				continue;
			}

			$font_face_ids = get_posts(
				array(
					'post_type'      => 'wp_font_face',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post_parent'    => $font_family_id,
					'no_found_rows'  => true,
				)
			);

			if ( empty( $font_face_ids ) ) {
				continue;
			}

			foreach ( $font_face_ids as $font_face_id ) {
				$font_file_name = get_post_meta( $font_face_id, '_wp_font_face_file', true );

				if ( ! empty( $font_file_name ) ) {
					$font_path = trailingslashit( $font_dir['path'] ) . $font_file_name;

					if ( file_exists( $font_path ) ) {
						wp_delete_file( $font_path );
					}
				}

				wp_delete_post( $font_face_id, true );
			}

			wp_delete_post( $font_family_id, true );
		}
	}

	/**
	 * Registers the given fonts locally using the `/wp/v2/font-families` REST endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @param array $fonts The list of font families to register, where each item is an associative array.
	 * @return array|WP_Error An array of registered font families or a WP_Error object on failure.
	 */
	private function register_fonts_locally( $fonts ) {
		wp_raise_memory_limit();

		if ( empty( $fonts ) ) {
			return array();
		}

		$registered_fonts = array();
		foreach ( $fonts as $font ) {
			try {
				$font_family_id = $this->is_font_registered( $font['slug'] );
				if ( ! $font_family_id ) {
					$font_family_id = $this->create_font_family( $font );
				}

				if ( $font_family_id && ! empty( $font['fontFace'] ) ) {
					$registered_font_faces = $this->register_font_faces( $font_family_id, $font['fontFace'] );
					$registered_fonts[]    = array_merge( $font, array( 'fontFace' => $registered_font_faces ) );
				}
			} catch ( Exception $e ) {
				spectra_log( "Font registration error: {$e->getMessage()}" );
				continue;
			}
		}

		return $registered_fonts;
	}

	/**
	 * Create font family via REST API
	 *
	 * @since 3.0.0
	 *
	 * @param array $font Font data.
	 * @return int|WP_Error
	 */
	private function create_font_family( $font ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/font-families' );
		$request->set_param(
			'font_family_settings',
			wp_json_encode(
				array(
					'name'       => $font['name'],
					'slug'       => $font['slug'],
					'fontFamily' => $font['fontFamily'],
					'preview'    => isset( $font['preview'] ) ? $font['preview'] : '',
				)
			)
		);

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return new WP_Error( 'font_create_failed', __( 'Font creation failed', 'spectra-blocks' ) );
		}

		$data = $response->get_data();
		if ( isset( $data['id'] ) ) {
			update_post_meta( $data['id'], '_is_spectra_font_family', '1' );
			return $data['id'];
		}

		return new WP_Error( 'font_create_failed', __( 'Invalid response from font creation', 'spectra-blocks' ) );
	}

	/**
	 * Register font faces for a font family
	 *
	 * @since 3.0.0
	 *
	 * @param int   $font_family_id Font family ID.
	 * @param array $font_faces Font face data.
	 * @return array
	 */
	private function register_font_faces( $font_family_id, $font_faces ) {
		$registered_font_faces = array();
		$font_dir              = wp_get_font_dir();

		foreach ( $font_faces as $font_face ) {
			try {

				$original_src = is_array( $font_face['src'] ) ? $font_face['src'][0] : $font_face['src'];

				$font_face_id = $this->is_font_face_registered( $font_face );
				if ( $font_face_id ) {
					$font_file = get_post_meta( $font_face_id, '_wp_font_face_file', true );
					if ( $font_file ) {
						$font_face['src']        = trailingslashit( $font_dir['url'] ) . $font_file;
						$registered_font_faces[] = $font_face;

						continue;
					}
				}

				$file = $this->download_file( $original_src );
				if ( is_wp_error( $file['tmp_name'] ) ) {
					continue;
				}

				$uploaded_file = $this->handle_font_file_upload( $file );
				if ( ! isset( $uploaded_file['url'] ) ) {
					continue;
				}

				$font_face['src'] = $this->sanitize_url( $uploaded_file['url'] );
				$face_request     = new WP_REST_Request( 'POST', "/wp/v2/font-families/{$font_family_id}/font-faces" );
				$face_request->set_param( 'font_face_settings', wp_json_encode( $font_face ) );

				$face_response = rest_do_request( $face_request );
				if ( $face_response->is_error() ) {
					continue;
				}

				$face_data = $face_response->get_data();
				if ( isset( $face_data['id'] ) ) {
					update_post_meta( $face_data['id'], '_wp_font_face_file', basename( $font_face['src'] ) );
					$registered_font_faces[] = $font_face;
				}
			} catch ( Exception $e ) {
				spectra_log( "Font face registration error: {$e->getMessage()}" );
				continue;
			}
		}
		return $registered_font_faces;
	}

	/**
	 * Checks if a font is already registered.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The font slug.
	 * @return int|false The font family ID if the font is registered, false otherwise.
	 */
	private function is_font_registered( $slug ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'wp_font_family',
				'posts_per_page'         => 1,
				'name'                   => $slug,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return ! empty( $query->posts ) ? current( $query->posts ) : false;
	}

	/**
	 * Checks if a font face is already registered.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The font face settings used to generate the title.
	 * @return int|false The font face ID if the font face is registered, false otherwise.
	 */
	private function is_font_face_registered( $settings ) {
		$query = new WP_Query(
			array(
				'post_type'              => 'wp_font_face',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'title'                  => WP_Font_Utils::get_font_face_slug( $settings ),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return ! empty( $query->posts ) ? current( $query->posts ) : false;
	}

	/**
	 * Downloads a file from a URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file_url URL of the file.
	 * @return array File array with name and tmp_name.
	 */
	private function download_file( $file_url ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$allowed_extensions = array( 'ttf', 'otf', 'woff', 'woff2', 'eot' );
		preg_match( '/[^\?]+\.(' . implode( '|', $allowed_extensions ) . ')\b/i', $file_url, $matches );

		return array(
			'name'     => wp_basename( $matches[0] ? $matches[0] : '' ),
			'tmp_name' => download_url( $file_url ),
		);
	}

	/**
	 * Handles font file uploads.
	 *
	 * @since 3.0.0
	 *
	 * @param array $file Single file item from $_FILES.
	 * @return array Array containing uploaded file attributes on success, or error on failure.
	 */
	private function handle_font_file_upload( $file ) {
		add_filter( 'upload_mimes', array( 'WP_Font_Utils', 'get_allowed_font_mime_types' ) ); // phpcs:ignore
		$font_dir       = wp_get_font_dir();
		$set_upload_dir = function () use ( $font_dir ) {
			return $font_dir;
		};
		add_filter( 'upload_dir', $set_upload_dir );

		$uploaded_file = wp_handle_upload(
			$file,
			array(
				'upload_error_handler' => array( $this, 'handle_font_file_upload_error' ),
				'action'               => 'wp_handle_font_upload',
				'test_form'            => false,
				'test_type'            => true,
				'mimes'                => WP_Font_Utils::get_allowed_font_mime_types(),
			)
		);

		remove_filter( 'upload_dir', $set_upload_dir );
		remove_filter( 'upload_mimes', array( 'WP_Font_Utils', 'get_allowed_font_mime_types' ) );

		return $uploaded_file;
	}

	/**
	 * Sanitizes font face src.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value Source value.
	 * @return string Sanitized src.
	 */
	private function sanitize_url( $value ) {
		$value = ltrim( $value );
		return wp_http_validate_url( $value ) ? esc_url_raw( $value ) : $value;
	}

	/**
	 * Handles font upload errors.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $file File data.
	 * @param string $message Error message.
	 * @return void
	 */
	public function handle_font_file_upload_error( $file, $message ) {
		spectra_log( sprintf( 'Font upload error for %s: %s', $file['name'], $message ) );
	}

	/**
	 * Get an array of all Google font families.
	 *
	 * @since 3.0.0
	 *
	 * @return array An array of Google font families.
	 */
	public static function get_google_font_families() {
		if ( ! class_exists( 'WP_Font_Library' ) ) {
			return array();
		}

		$font_library = WP_Font_Library::get_instance();

		$google_fonts_collection = $font_library->get_font_collection( 'google-fonts' );

		if ( ! $google_fonts_collection instanceof WP_Font_Collection || is_wp_error( $google_fonts_collection ) ) {
			return array();
		}

		$data = $google_fonts_collection->get_data();

		if ( isset( $data['font_families'] ) ) {
			return $data['font_families'];
		}

		return array();
	}

	/**
	 * Get font names if they exist in the font collection.
	 *
	 * @since 3.0.0
	 *
	 * @param string|array $font_names Single font name or an array of font names.
	 * @return array List of matched font names.
	 */
	public function get_font_data( $font_names ) {
		if ( empty( $font_names ) ) {
			return array();
		}

		$font_lookup = array_flip( $font_names );
		$font_data   = array();

		foreach ( $this->get_google_font_families() as $font ) {
			if ( isset( $font['font_family_settings']['name'] )
				&& isset( $font_lookup[ $font['font_family_settings']['name'] ] )
			) {
				$font_data[] = $font['font_family_settings'];
			}
		}

		return $font_data;
	}

	/**
	 * Get the names of all selected fonts in the Spectra global settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array List of font names.
	 */
	public static function get_spectra_selected_font_names() {
		// Check if the setting to load Google fonts globally is enabled.
		// If not, return an empty array.
		if ( 'enabled' !== \Spectra_Blocks_Settings::get( 'load_select_font_globally', 'disabled' ) ) {
			return array();
		}

		$selected_fonts = \Spectra_Blocks_Settings::get( 'select_font_globally', array() );

		if ( ! is_array( $selected_fonts ) ) {
			return array();
		}

		return array_filter( array_column( $selected_fonts, 'label' ) );
	}

	/**
	 * Check if the Load Google Fonts Locally setting is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if the setting is enabled, false otherwise.
	 */
	public static function is_enabled_load_locally() {
		return 'enabled' === \Spectra_Blocks_Settings::get( 'load_gfonts_locally', 'disabled' );
	}

	/**
	 * Retrieve cached Google fonts or fetch and cache them if not available.
	 *
	 * @since 3.0.0
	 *
	 * @return array The cached font data.
	 */
	public function get_cached_google_fonts() {
		$selected_fonts = self::get_spectra_selected_font_names();

		if ( empty( $selected_fonts ) ) {
			return array();
		}

		$fonts = get_transient( self::FONT_CACHE_KEY );

		if ( false === $fonts ) {
			$fonts = $this->get_font_data( $selected_fonts );
			$fonts = self::is_enabled_load_locally() ? $this->register_fonts_locally( $fonts ) : $fonts;
			set_transient( self::FONT_CACHE_KEY, $fonts );
		}

		return $fonts;
	}

	/**
	 * Retrieve the default Google fonts used in Spectra.
	 *
	 * @since 3.0.0
	 *
	 * @return array The default font data.
	 */
	public static function get_default_google_fonts() {
		return array(
			array(
				'fontFamily' => '"Inter", sans-serif',
				'name'       => 'Inter',
				'slug'       => 'inter',
				'fontFace'   => array(
					array(
						'fontFamily'  => 'Inter',
						'fontStretch' => 'normal',
						'fontStyle'   => 'normal',
						'fontWeight'  => '300 900',
						'src'         => array( SPECTRA_BLOCKS_URL . '/assets/fonts/Inter-VariableFont_slnt,wght.woff2' ),
					),
				),
			),
			array(
				'fontFamily' => 'Cardo',
				'name'       => 'Cardo',
				'slug'       => 'cardo',
				'fontFace'   => array(
					array(
						'fontFamily' => 'Cardo',
						'fontStyle'  => 'normal',
						'fontWeight' => '400',
						'src'        => array( SPECTRA_BLOCKS_URL . '/assets/fonts/cardo_normal_400.woff2' ),
					),
				),
			),
		);
	}
}
