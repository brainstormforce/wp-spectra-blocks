<?php
/**
 * Spectra FSE Fonts Compatibility.
 *
 * @since 0.0.1
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_FSE_Fonts_Compatibility' ) ) {

	/**
	 * Class Spectra_FSE_Fonts_Compatibility.
	 *
	 * @since 0.0.1
	 */
	final class Spectra_FSE_Fonts_Compatibility {

		/**
		 * Member Variable
		 *
		 * @since 0.0.1
		 * @var instance
		 */
		private static $instance;

		/**
		 * Base path.
		 *
		 * @access protected
		 * @since 0.0.1
		 * @var string
		 */
		protected $base_path;

		/**
		 * Base URL.
		 *
		 * @access protected
		 * @since 0.0.1
		 * @var string
		 */
		protected $base_url;

		/**
		 * The remote CSS.
		 *
		 * @access protected
		 * @since 0.0.1
		 * @var string
		 */
		protected $remote_styles;

		/**
		 * The font-format.
		 *
		 * Use "woff" or "woff2".
		 * This will change the user-agent user to make the request.
		 *
		 * @access protected
		 * @since 0.0.1
		 * @var string
		 */
		protected $font_format = 'woff2';

		/**
		 *  Initiator
		 *
		 * @return object instance.
		 * @since 0.0.1
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function __construct() {
			$this->base_path = SPECTRA_UPLOAD_DIR . 'assets/';

			$this->base_url = SPECTRA_UPLOAD_URL . 'assets/';

			if ( empty( $_GET['page'] ) || 'spectra' !== $_GET['page'] || empty( $_GET['path'] ) || 'settings' !== $_GET['path'] || empty( $_GET['settings'] ) || 'fse-support' !== $_GET['settings'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['settings'] does not provide nonce.
				return;
			}

			$spectra_filesystem   = spectra_filesystem();
			$fonts_folder_path = get_stylesheet_directory() . '/assets/fonts/spectra';

			if ( file_exists( $fonts_folder_path ) ) {
				$spectra_filesystem->delete( $fonts_folder_path, true, 'd' );
			}

			self::delete_all_theme_font_family();

			$load_fse_font_globally = Spectra_Admin_Helper::get_admin_settings_option( 'uag_load_fse_font_globally', 'disabled' );

			if ( 'disabled' !== $load_fse_font_globally ) {

				add_action( 'admin_init', array( $this, 'save_google_fonts_to_theme' ) );
			}
		}

		/**
		 * Get, add and update font family in Spectra for ST.
		 *
		 * @param array $families font family.
		 * @since 0.0.1
		 * @return void
		 */
		public function get_font_family_for_starter_template( $families ) {
			if ( Spectra_Admin_Helper::get_admin_settings_option( 'uag_load_fse_font_globally', 'disabled' ) ) { // if Load FSE Fonts Globaly is disabled then enabled it.
				Spectra_Admin_Helper::update_admin_settings_option( 'uag_load_fse_font_globally', 'enabled' );
			}
			$new_font_families = array();
			$new_font_faces    = array();
			if ( empty( $families ) || ! is_array( $families ) ) {
				return;
			}
			foreach ( $families as $family ) {
				$font_name        = ! empty( $family ) ? $family : '';
				$font_name_string = explode( ',', $font_name );
				$font_family      = trim( $font_name_string[0], "'" );
				$font_slug        = $this->get_font_slug( $font_family );
				$font_weight      = 'Default';
				$font_style       = 'normal';
				$final_font_files = $this->get_fonts_file_url( $font_family, $font_weight, $font_style );
				// Loop through each font file and create a font face for it.
				foreach ( $final_font_files as $src ) {
					$new_font_faces[] = array(
						'fontFamily' => $font_family,
						'fontStyle'  => $font_style,
						'fontWeight' => $font_weight,
						'src'        => array( $src ),
					);
				}
				$this->add_or_update_theme_font_faces( $font_family, $font_slug, $new_font_faces );
			}

			$theme_json_raw = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );

			$all_font_families = $theme_json_raw['settings']['typography']['fontFamilies'];
			
			if ( empty( $all_font_families ) || ! is_array( $all_font_families ) ) {
				return;
			}

			foreach ( $all_font_families as $font_families_item ) {
				$new_font_families_item = array(
					'value'   => $font_families_item['fontFamily'],
					'label'   => $font_families_item['fontFamily'],
					'weights' => array(
						array(
							'value' => 'Default',
							'label' => __( 'Default', 'spectra' ),
						),
						array(
							'value' => '400',
							'label' => __( '400', 'spectra' ),
						),
					),
					'styles'  => array(
						array(
							'value' => 'normal',
							'label' => __( 'normal', 'spectra' ),
						),
					),
					'weight'  => array(
						array(
							'value' => '400',
							'label' => __( '400', 'spectra' ),
						),
					),
					'style'   => array(
						array(
							'value' => 'normal',
							'label' => __( 'normal', 'spectra' ),
						),
					),
				);

				$new_font_families[] = $new_font_families_item;
			}

			Spectra_Admin_Helper::update_admin_settings_option( 'spectra_global_fse_fonts', $new_font_families );
		}

		/**
		 * Save Google Fonts to the FSE Theme.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public function save_google_fonts_to_theme() {

			$spectra_global_fse_fonts = \Spectra_Admin_Helper::get_admin_settings_option( 'spectra_global_fse_fonts', array() );

			if ( empty( $spectra_global_fse_fonts ) || ! is_array( $spectra_global_fse_fonts ) ) {
				return;
			}

			foreach ( $spectra_global_fse_fonts as $font ) {
				$font_family    = ! empty( $font['value'] ) ? $font['value'] : '';
				$font_slug      = $this->get_font_slug( $font_family );
				$new_font_faces = array();
				foreach ( $font['weight'] as $weight ) {
					$font_weight = ! empty( $weight['value'] ) ? $weight['value'] : '';
					foreach ( $font['style'] as $style ) {
						$font_style = ! empty( $style['value'] ) ? $style['value'] : '';

						$final_font_files = $this->get_fonts_file_url( $font_family, $font_weight, $font_style );
						// Loop through each font file and create a font face for it.
						foreach ( $final_font_files as $src ) {
							$new_font_faces[] = array(
								'fontFamily' => $font_family,
								'fontStyle'  => $font_style,
								'fontWeight' => $font_weight,
								'src'        => array( $src ),
							);
						}
					}
				}
				$this->add_or_update_theme_font_faces( $font_family, $font_slug, $new_font_faces );
			}
		}

		/**
		 * Get Font Slug.
		 *
		 * @return string slug.
		 * @param string $name Font Family.
		 * @since 0.0.1
		 */
		public function get_font_slug( $name ) {
			$slug = sanitize_title( $name );
			$slug = preg_replace( '/\s+/', '', $slug ); // Remove spaces.
			return $slug;
		}

		/**
		 * Get Font URl.
		 *
		 * @param string $font_family Font Family.
		 * @param string $font_weight Font Weight.
		 * @param string $font_style Font Style.
		 * @return array final font files.
		 * @since 0.0.1
		 */
		public function get_fonts_file_url( $font_family, $font_weight, $font_style ) {

			$font_family_key = sanitize_key( strtolower( str_replace( ' ', '-', $font_family ) ) );
			$fonts_attr      = str_replace( ' ', '+', $font_family );
			$fonts_file_name = $font_family_key;
			if ( ! empty( $font_weight ) ) {
				$fonts_attr      .= ':' . $font_weight;
				$fonts_file_name .= '-' . $font_weight;
				if ( ! empty( $font_style ) ) {
					$fonts_attr      .= ',' . $font_weight . $font_style;
					$fonts_file_name .= '-' . $font_style;
				}
			}
			$fonts_link = 'https://fonts.googleapis.com/css?family=' . esc_attr( $fonts_attr );

			// Get the remote URL contents.
			$this->remote_styles = $this->get_remote_url_contents( $fonts_link );
			$font_files          = $this->get_remote_files_from_css();

			$fonts_folder_path = get_stylesheet_directory() . '/assets/fonts/spectra/';

			// If the fonts folder don't exist, create it.
			if ( ! file_exists( $fonts_folder_path ) ) {

				wp_mkdir_p( $fonts_folder_path );

				if ( ! file_exists( $fonts_folder_path ) ) {
					$this->get_filesystem()->mkdir( $fonts_folder_path, FS_CHMOD_DIR );
				}
			}
			$final_font_files = array();

			if ( ! is_array( $font_files ) && empty( $font_files ) && empty( $font_family_key ) ) {
				return;
			}
			foreach ( $font_files[ $font_family_key ] as $key => $font_file ) {

				// require file.php if the download_url function doesn't exist.
				if ( ! function_exists( 'download_url' ) ) {
					require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
				}
				// Download file to temporary location.
				$tmp_path = download_url( $font_file );

				// Make sure there were no errors.
				if ( is_wp_error( $tmp_path ) ) {
					return array();
				}

				$fonts_file_name_final = $fonts_file_name . $key . '.' . $this->font_format;

				// SECURITY: Validate filename to prevent path traversal.
				if ( preg_match( '/[\.\/\\\\]/', $fonts_file_name_final ) ) {
					Spectra_Security_Helper::log_security_event(
						'path_traversal_attempt_font',
						array(
							'filename'     => $fonts_file_name_final,
							'font_family'  => $font_family,
						)
					);
					@unlink( $tmp_path );
					continue;
				}

				// SECURITY: Ensure target directory exists and is within allowed path.
				$target_dir = get_stylesheet_directory() . '/assets/fonts/spectra/';
				if ( ! is_dir( $target_dir ) || false === realpath( $target_dir ) ) {
					Spectra_Security_Helper::log_security_event(
						'invalid_font_target_directory',
						array(
							'target_dir' => $target_dir,
						)
					);
					@unlink( $tmp_path );
					continue;
				}

				// SECURITY: Construct and validate final path.
				$target_path = $target_dir . basename( $fonts_file_name_final );

				// Use Spectra_Security_Helper to validate the path.
				$validated_path = Spectra_Security_Helper::validate_file_path( $target_path, $target_dir );

				if ( false === $validated_path ) {
					Spectra_Security_Helper::log_security_event(
						'font_path_validation_failed',
						array(
							'target_path' => $target_path,
							'target_dir'  => $target_dir,
						)
					);
					@unlink( $tmp_path );
					continue;
				}

				// Move font asset to theme assets folder.
				if ( rename( $tmp_path, $validated_path ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename
					Spectra_Security_Helper::log_security_event(
						'font_file_downloaded',
						array(
							'file'        => basename( $fonts_file_name_final ),
							'font_family' => $font_family,
						)
					);
					$final_font_files[] = 'file:./assets/fonts/spectra/' . $fonts_file_name_final;
				} else {
					@unlink( $tmp_path );
				}
			}

			return $final_font_files;
		}

		/**
		 * Get the filesystem.
		 *
		 * @access protected
		 * @since 0.0.1
		 * @return WP_Filesystem
		 */
		protected function get_filesystem() {
			global $wp_filesystem;

			// If the filesystem has not been instantiated yet, do it here.
			if ( ! $wp_filesystem ) {
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
				}
				WP_Filesystem();
			}
			return $wp_filesystem;
		}

		/**
		 * Get remote file contents.
		 *
		 * @access public
		 * @param string $url URL.
		 * @since 0.0.1
		 * @return string Returns the remote URL contents.
		 */
		public function get_remote_url_contents( $url ) {

			/**
			 * The user-agent we want to use.
			 *
			 * The default user-agent is the only one compatible with woff (not woff2)
			 * which also supports unicode ranges.
			 */
			$user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8';

			// Switch to a user-agent supporting woff2 if we don't need to support IE.
			if ( 'woff2' === $this->font_format ) {
				$user_agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0';
			}

			// Get the response.
			$response = wp_remote_get( $url, array( 'user-agent' => $user_agent ) );

			// Early exit if there was an error.
			if ( is_wp_error( $response ) ) {
				return '';
			}

			// Get the CSS from our response.
			$contents = wp_remote_retrieve_body( $response );

			// Early exit if there was an error.
			if ( is_wp_error( $contents ) ) {
				return '';
			}

			return $contents;
		}

		/**
		 * Get font files from the CSS.
		 *
		 * @access public
		 * @since 0.0.1
		 * @return array Returns an array of font-families and the font-files used.
		 */
		public function get_remote_files_from_css() {

			// Return early if remote styles is not a string, or is empty.
			if ( ! is_string( $this->remote_styles ) || empty( $this->remote_styles ) ) {
				return array();
			}

			$font_faces = explode( '@font-face', $this->remote_styles );

			// Return early if font faces is not an array, or is empty.
			if ( ! is_array( $font_faces ) || empty( $font_faces ) ) {
				return array();
			}

			$result = array();

			// Loop all our font-face declarations.
			foreach ( $font_faces as $font_face ) {

				// Continue the loop if the current font face is not a string, or is empty.
				if ( ! is_string( $font_face ) || empty( $font_face ) ) {
					continue;
				}

				// Get the styles based on the font face.
				$style_array = explode( '}', $font_face );

				// Continue the loop if the current font face is not a string, or is empty.
				if ( ! is_string( $style_array[0] ) || empty( $style_array[0] ) ) {
					continue;
				}

				// Make sure we only process styles inside this declaration.
				$style = $style_array[0];

				// Sanity check.
				if ( false === strpos( $style, 'font-family' ) ) {
					continue;
				}

				// Get an array of our font-families.
				preg_match_all( '/font-family.*?\;/', $style, $matched_font_families );

				// Get an array of our font-files.
				preg_match_all( '/url\(.*?\)/i', $style, $matched_font_files );

				// Get the font-family name.
				$font_family = 'unknown';
				if ( isset( $matched_font_families[0] ) && isset( $matched_font_families[0][0] ) ) {
					$font_family = rtrim( ltrim( $matched_font_families[0][0], 'font-family:' ), ';' );
					$font_family = trim( str_replace( array( "'", ';' ), '', $font_family ) );
					$font_family = sanitize_key( strtolower( str_replace( ' ', '-', $font_family ) ) );
				}

				// Make sure the font-family is set in our array.
				if ( ! isset( $result[ $font_family ] ) ) {
					$result[ $font_family ] = array();
				}

				// Get files for this font-family and add them to the array.
				foreach ( $matched_font_files as $match ) {

					// Sanity check.
					if ( ! isset( $match[0] ) ) {
						continue;
					}

					// Add the file URL.
					$result[ $font_family ][] = rtrim( ltrim( $match[0], 'url(' ), ')' );
				}

				// Make sure we have unique items.
				// We're using array_flip here instead of array_unique for improved performance.
				$result[ $font_family ] = array_flip( array_flip( $result[ $font_family ] ) );
			}
			return $result;
		}

		/**
		 * Get font files from the CSS.
		 *
		 * @access public
		 * @param string $font_name Font Name.
		 * @param string $font_slug Font Slug.
		 * @param array  $font_faces Font Faces.
		 * @return void
		 * @since 0.0.1
		 */
		public function add_or_update_theme_font_faces( $font_name, $font_slug, $font_faces ) {
			// Get the current theme.json and fontFamilies defined (if any).
			$theme_json_raw      = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );
			$theme_font_families = isset( $theme_json_raw['settings']['typography']['fontFamilies'] ) ? $theme_json_raw['settings']['typography']['fontFamilies'] : null;

			$existent_family = $theme_font_families ? array_values(
				array_filter(
					$theme_font_families,
					function ( $font_family ) use ( $font_slug ) {
						return $font_family['slug'] === $font_slug; }
				)
			) : null;

			// Add the new font faces.
			if ( empty( $existent_family ) ) { // If the new font family doesn't exist in the theme.json font families, add it to the exising font families.
				$theme_font_families[] = array(
					'fontFamily' => $font_name,
					'slug'       => $font_slug,
					'fontFace'   => $font_faces,
					'isSpectra'  => true,
				);
			} else { // If the new font family already exists in the theme.json font families, add the new font faces to the existing font family.
				$theme_font_families            = array_values(
					array_filter(
						$theme_font_families,
						function ( $font_family ) use ( $font_slug ) {
							return $font_family['slug'] !== $font_slug; }
					)
				);
				$existent_family[0]['fontFace'] = $font_faces;
				$theme_font_families            = array_merge( $theme_font_families, $existent_family );
			}

			// Overwrite the previous fontFamilies with the new ones.
			$theme_json_raw['settings']['typography']['fontFamilies'] = $theme_font_families;

			$theme_json        = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

			// SECURITY: Write theme.json with atomic operations and backup.
			$theme_json_path = get_stylesheet_directory() . '/theme.json';

			// Create backup before modifying.
			$backup_path = $theme_json_path . '.backup-' . time();
			if ( file_exists( $theme_json_path ) ) {
				copy( $theme_json_path, $backup_path );
			}

			// Write to temporary file first (atomic operation).
			$temp_path = $theme_json_path . '.tmp';
			$bytes_written = file_put_contents( $temp_path, $theme_json_string, LOCK_EX ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions

			if ( false === $bytes_written ) {
				Spectra_Security_Helper::log_security_event(
					'theme_json_write_failed',
					array(
						'temp_path' => $temp_path,
						'action'    => 'temporary_write',
					)
				);
				// Cleanup backup.
				if ( file_exists( $backup_path ) ) {
					@unlink( $backup_path );
				}
				return;
			}

			// Validate JSON before replacing.
			$test_json = json_decode( file_get_contents( $temp_path ), true );
			if ( null === $test_json ) {
				Spectra_Security_Helper::log_security_event(
					'theme_json_invalid',
					array(
						'temp_path' => $temp_path,
						'action'    => 'validation',
					)
				);
				@unlink( $temp_path );
				// Cleanup backup.
				if ( file_exists( $backup_path ) ) {
					@unlink( $backup_path );
				}
				return;
			}

			// Atomic replace.
			if ( ! rename( $temp_path, $theme_json_path ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename
				Spectra_Security_Helper::log_security_event(
					'theme_json_rename_failed',
					array(
						'temp_path'  => $temp_path,
						'theme_path' => $theme_json_path,
						'action'     => 'rename',
					)
				);
				// Restore backup.
				if ( file_exists( $backup_path ) ) {
					copy( $backup_path, $theme_json_path );
					@unlink( $backup_path );
				}
				return;
			}

			// Success - cleanup backup.
			if ( file_exists( $backup_path ) ) {
				@unlink( $backup_path );
			}

			Spectra_Security_Helper::log_security_event(
				'theme_json_updated',
				array(
					'font_slug' => $font_slug,
					'action'    => 'success',
				)
			);

		}

		/**
		 * Save setting - Sanitizes form inputs.
		 *
		 * @param array $input_settings setting data.
		 * @return array    The sanitized form inputs.
		 *
		 * @since 0.0.1
		 */
		public static function sanitize_form_inputs( $input_settings = array() ) {
			$new_settings = array();

			if ( ! empty( $input_settings ) ) {
				foreach ( $input_settings as $key => $value ) {

					$new_key = sanitize_text_field( $key );

					if ( is_array( $value ) ) {
						$new_settings[ $new_key ] = self::sanitize_form_inputs( $value );
					} else {
						$new_settings[ $new_key ] = sanitize_text_field( $value );
					}
				}
			}

			return $new_settings;
		}

		/**
		 * Delete all Spectra font files from the theme JSON.
		 *
		 * @return void
		 * @since 0.0.1
		 */
		public static function delete_all_theme_font_family() {

			// Construct updated theme.json.
			$theme_json_raw = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );
			if ( empty( $theme_json_raw['settings']['typography']['fontFamilies'] ) ) { // Added condition to resolve an issue of PHP Notice:  Undefined index: fontFamilies.
				return;
			}
			// Overwrite the previous fontFamilies with the new ones.
			$font_families = $theme_json_raw['settings']['typography']['fontFamilies'];

			if ( ! empty( $font_families ) && is_array( $font_families ) ) {
				$font_families = array_values(
					array_filter(
						$font_families,
						function( $value ) {
							if ( ! empty( $value['isSpectra'] ) ) {
								return false;
							}
							return true;
						}
					)
				);
			}
			$theme_json_raw['settings']['typography']['fontFamilies'] = $font_families;

			$theme_json        = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

			// Write the new theme.json to the theme folder.
			file_put_contents( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
				get_stylesheet_directory() . '/theme.json',
				$theme_json_string
			);
		}
		/**
		 * Delete font files from the CSS.
		 *
		 * @return void
		 * @param array $font Font Data.
		 * @since 0.0.1
		 */
		public static function delete_theme_font_family( $font ) {
			// Construct updated theme.json.
			$theme_json_raw = json_decode( file_get_contents( get_stylesheet_directory() . '/theme.json' ), true );

			// Overwrite the previous fontFamilies with the new ones.
			$font_families = $theme_json_raw['settings']['typography']['fontFamilies'];
			if ( ! empty( $font_families ) && is_array( $font_families ) ) {
				foreach ( $font_families as $key => $value ) {
					if ( $font['fontFamily'] === $value['fontFamily'] && ! empty( $value['fontFace'] ) && is_array( $value['fontFace'] ) ) {
						unset( $font_families[ $key ] );
						break;
					}
				}
			}
			$theme_json_raw['settings']['typography']['fontFamilies'] = $font_families;

			$theme_json        = wp_json_encode( $theme_json_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$theme_json_string = preg_replace( '~(?:^|\G)\h{4}~m', "\t", $theme_json );

			// Write the new theme.json to the theme folder.
			file_put_contents( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions
				get_stylesheet_directory() . '/theme.json',
				$theme_json_string
			);

			$spectra_global_fse_fonts = \Spectra_Admin_Helper::get_admin_settings_option( 'spectra_global_fse_fonts', array() );

			if ( ! is_array( $spectra_global_fse_fonts ) ) {
				$response_data = array(
					'messsage' => __( 'There was some error in deleting the font.', 'spectra' ),
				);
				wp_send_json_error( $response_data );
			}

			$spectra_global_fse_fonts = array_values(
				array_filter(
					$spectra_global_fse_fonts,
					function( $value ) use ( $font ) {
						if ( $font['fontFamily'] === $value['value'] ) {
							return false;
						}
						return true;
					}
				)
			);

			foreach ( $spectra_global_fse_fonts as $key => $value ) {
				if ( $font['fontFamily'] === $value['value'] ) {
					array_splice( $spectra_global_fse_fonts, $key, $key );
				}
			}
			$spectra_global_fse_fonts = self::sanitize_form_inputs( $spectra_global_fse_fonts );
			Spectra_Admin_Helper::update_admin_settings_option( 'spectra_global_fse_fonts', $spectra_global_fse_fonts );

			$response_data = array(
				'messsage' => __( 'Successfully deleted font.', 'spectra' ),
			);
			wp_send_json_success( $response_data );
		}
	}
}
Spectra_FSE_Fonts_Compatibility::get_instance();
