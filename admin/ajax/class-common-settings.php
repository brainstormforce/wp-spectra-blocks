<?php
/**
 * Common Settings.
 *
 * @package spectra-blocks
 */

namespace SpectraBlocksAdmin\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SpectraBlocksAdmin\Ajax\Ajax_Base;
use SpectraBlocksAdmin\Inc\Admin_Helper;

use ZipAI\Classes\Helper as Zip_Ai_Helper;
use ZipAI\Classes\Module as Zip_Ai_Module;

/**
 * Class Common_Settings.
 */
class Common_Settings extends Ajax_Base {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 *
	 * @since 2.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @return object initialized object of class.
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register_ajax_events.
	 *
	 * @return void
	 */
	public function register_ajax_events() {

		$ajax_events = array(
			'enable_templates_button',
			'enable_block_responsive',
			'enable_dynamic_content',
			'enable_animations_extension',
			'enable_gbs_extension',
			'blocks_activation_and_deactivation',
			'load_select_font_globally',
			'load_fse_font_globally',
			'fse_font_globally',
			'fse_font_globally_delete',
			'select_font_globally',
			'load_gfonts_locally',
			'preload_local_fonts',
			'container_global_padding',
			'container_global_elements_gap',
			'recaptcha_site_key_v2',
			'recaptcha_secret_key_v2',
			'recaptcha_site_key_v3',
			'recaptcha_secret_key_v3',
			'pro_activate',
			'btn_inherit_from_theme',
			'zip_ai_module_status',
			'zip_ai_verify_authenticity',
			'enable_bsf_analytics_option',
			'clear_v3_cache',
			'disable_css_cache',
		);

		$this->init_ajax_events( $ajax_events );
	}

	/**
	 * Save global option of button to inherit from theme.
	 *
	 * @since 2.6.2
	 * @return void
	 */
	public function btn_inherit_from_theme() {

		$this->check_permission_nonce( 'spectra_blocks_btn_inherit_from_theme' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_btn_inherit_from_theme', sanitize_text_field( $value ) );
	}

	/**
	 * Checks if the user has the permission to perform the requested action and verifies the nonce.
	 *
	 * @param string $option The name of the option to check the nonce against.
	 * @param string $scope The capability required to perform the action. Default is 'manage_options'.
	 * @param string $security The security to check the nonce against. Default is 'security'.
	 * @return void
	 *
	 * @since 2.5.0
	 */
	private function check_permission_nonce( $option, $scope = 'manage_options', $security = 'security' ) {

		if ( ! current_user_can( $scope ) ) {
			wp_send_json_error( array( 'messsage' => $this->get_error_msg( 'permission' ) ) );
		}

		/**
		 * Nonce verification
		 */
		if ( ! check_ajax_referer( $option, $security, false ) ) {
			wp_send_json_error( array( 'messsage' => $this->get_error_msg( 'nonce' ) ) );
		}
	}

	/**
	 * Saves the success message after successfully updating admin settings option.
	 *
	 * @param string $option The name of the option to update.
	 * @param mixed  $value The value to be updated.
	 * @return void
	 *
	 * @since 2.5.0
	 */
	private function save_admin_settings( $option, $value ) {
		\Spectra_Blocks_Admin_Helper::update_admin_settings_option( $option, $value );

		$response_data = array(
			'messsage' => __( 'Successfully saved data!', 'spectra-blocks' ),
		);
		wp_send_json_success( $response_data );
	}

	/**
	 * Checks if the specified key exists in the $_POST array and returns the corresponding value.
	 *
	 * @param string $key The key to check in the $_POST array. Default value is 'value'.
	 * @return mixed The value of the specified key in the $_POST array if it exists, otherwise sends a JSON error response.
	 *
	 *  @since 2.5.0
	 */
	private function check_post_value( $key = 'value' ) {
		// Nonce verification done in check_permission_nonce() which must be called before this method.
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( array( 'messsage' => __( 'No post data found!', 'spectra-blocks' ) ) );
		}
		// Apply base sanitization; callers should still sanitize per their own data type.
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Required Spectra Pro Plugin Activate
	 *
	 * @return void
	 */
	public function pro_activate() {
		$this->check_permission_nonce( 'spectra_blocks_pro_activate', 'activate_plugins' );

		// Return redirect URL instead of activating directly — activation should be done from the Plugins page.
		wp_send_json_success(
			array(
				'redirect' => admin_url( 'plugins.php' ),
				'message'  => __( 'Please activate the plugin from the Plugins page.', 'spectra-blocks' ),
			)
		);
	}

	/**
	 * Save settings - Saves google recaptcha v3 secret key.
	 *
	 * @return void
	 */
	public function recaptcha_secret_key_v3() {
		$this->check_permission_nonce( 'spectra_blocks_recaptcha_secret_key_v3' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_recaptcha_secret_key_v3', sanitize_text_field( $value ) );
	}

	/**
	 * Save settings - Saves google recaptcha v2 secret key.
	 *
	 * @return void
	 */
	public function recaptcha_secret_key_v2() {
		$this->check_permission_nonce( 'spectra_blocks_recaptcha_secret_key_v2' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_recaptcha_secret_key_v2', sanitize_text_field( $value ) );
	}

	/**
	 * Save settings - Saves google recaptcha v2 site key.
	 *
	 * @return void
	 */
	public function recaptcha_site_key_v2() {
		$this->check_permission_nonce( 'spectra_blocks_recaptcha_site_key_v2' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_recaptcha_site_key_v2', sanitize_text_field( $value ) );
	}

	/**
	 * Save settings - Saves google recaptcha v3 site key.
	 *
	 * @return void
	 */
	public function recaptcha_site_key_v3() {
		$this->check_permission_nonce( 'spectra_blocks_recaptcha_site_key_v3' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_recaptcha_site_key_v3', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Saves container global padding.
	 *
	 * @return void
	 */
	public function container_global_padding() {
		$this->check_permission_nonce( 'spectra_blocks_container_global_padding' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_container_global_padding', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Saves container global elements gap.
	 *
	 * @return void
	 */
	public function container_global_elements_gap() {
		$this->check_permission_nonce( 'spectra_blocks_container_global_elements_gap' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_container_global_elements_gap', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Loads selected font globally.
	 *
	 * @return void
	 */
	public function load_select_font_globally() {
		$this->check_permission_nonce( 'spectra_blocks_load_select_font_globally' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_load_select_font_globally', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Loads selected font globally.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	public function load_fse_font_globally() {
		$this->check_permission_nonce( 'spectra_blocks_load_fse_font_globally' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_load_fse_font_globally', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Saves selected font globally.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	public function select_font_globally() {
		$this->check_permission_nonce( 'spectra_blocks_select_font_globally' );
		$value = $this->check_post_value();
		$value = json_decode( stripslashes( $value ), true );
		$this->save_admin_settings( 'spectra_blocks_select_font_globally', $this->sanitize_form_inputs( $value ) );
	}

	/**
	 * Save setting - Saves selected font globally.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	public function fse_font_globally_delete() {
		$this->check_permission_nonce( 'spectra_blocks_fse_font_globally_delete' );
		$value = $this->check_post_value();
		$value = json_decode( stripslashes( $value ), true );
		$value = $this->sanitize_form_inputs( $value );
		\Spectra_Blocks_FSE_Fonts_Compatibility::delete_theme_font_family( $value );
	}

	/**
	 * Save setting - Saves selected font globally.
	 *
	 * @since 2.5.1
	 * @return void
	 */
	public function fse_font_globally() {
		$this->check_permission_nonce( 'spectra_blocks_fse_font_globally' );
		$value = $this->check_post_value();
		$value = json_decode( stripslashes( $value ), true );

		// Note: 'spectra_global_fse_fonts' is an intentional shared cross-plugin option key,
		// allowing FSE font data to be shared between Spectra Blocks and Spectra Pro.
		$spectra_global_fse_fonts = \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_global_fse_fonts', array() );

		if ( ! is_array( $spectra_global_fse_fonts ) ) {
			$spectra_global_fse_fonts = array();
		}

		$spectra_global_fse_fonts[] = $value;

		$this->save_admin_settings( 'spectra_global_fse_fonts', $this->sanitize_form_inputs( $spectra_global_fse_fonts ) );
	}

	/**
	 * Save setting - Loads gfonts locally.
	 *
	 * @return void
	 */
	public function load_gfonts_locally() {
		$this->check_permission_nonce( 'spectra_blocks_load_gfonts_locally' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_load_gfonts_locally', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Preloads local fonts.
	 *
	 * @return void
	 */
	public function preload_local_fonts() {
		$this->check_permission_nonce( 'spectra_blocks_preload_local_fonts' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_preload_local_fonts', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Enables block responsiveness.
	 *
	 * @return void
	 */
	public function enable_block_responsive() {
		$this->check_permission_nonce( 'spectra_blocks_enable_block_responsive' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_enable_block_responsive', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Enables dynamic content.
	 *
	 * @return void
	 *
	 * @since 2.1.0
	 */
	public function enable_dynamic_content() {
		$this->check_permission_nonce( 'spectra_blocks_enable_dynamic_content' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_enable_dynamic_content', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Enables animation extension.
	 *
	 * @return void
	 *
	 * @since 2.6.0
	 */
	public function enable_animations_extension() {
		$this->check_permission_nonce( 'spectra_blocks_enable_animations_extension' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_enable_animations_extension', sanitize_text_field( $value ) );
	}

	/**
	 * Save settings - Enables templates button.
	 *
	 * @return void
	 */
	public function enable_templates_button() {
		$this->check_permission_nonce( 'spectra_blocks_enable_templates_button' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_enable_templates_button', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Activates and deactivates blocks .
	 *
	 * @return void
	 */
	public function blocks_activation_and_deactivation() {
		$this->check_permission_nonce( 'spectra_blocks_blocks_activation_and_deactivation' );
		$value  = $this->check_post_value();
		$status = $this->check_post_value( 'status' );
		if ( '' !== $status ) {
			$status_value = 'disabled' === $status ? 'disabled' : 'enabled';
		}
		$value = json_decode( stripslashes( $value ), true );
		$value = $this->sanitize_form_inputs( $value );

		if ( '' !== $status ) {
			// Update all extensions.
			$update_all_extensions = array(
				'spectra_blocks_enable_animations_extension',
				'spectra_blocks_enable_dynamic_content',
				'spectra_blocks_enable_block_responsive',
				'spectra_blocks_enable_gbs_extension',
				'_spectra_blocks_blocks',
			);
			// Create an array with the new status for each extension.
			$change_extension = array();
			// Iterate over the array and set the new status for each item.
			foreach ( $update_all_extensions as $item ) {
				if ( '_spectra_blocks_blocks' === $item ) {
					$change_extension[ $item ] = $value;
					continue;
				}
				$change_extension[ $item ] = $status_value;
			}
			// Iterate over the array and call save_admin_settings for each item.
			foreach ( $change_extension as $key => $val ) {
				if ( '_spectra_blocks_blocks' === $key ) {
					\Spectra_Blocks_Admin_Helper::update_admin_settings_option( '_spectra_blocks_blocks', $val );
					continue;
				}
				// Update all extensions.
				\Spectra_Blocks_Admin_Helper::update_admin_settings_option( $key, $val );
			}
		} else {
			$this->save_admin_settings( '_spectra_blocks_blocks', $this->sanitize_form_inputs( $value ) );
		}
	}

	/**
	 * Save setting - Sanitizes form inputs.
	 *
	 * @param array $input_settings setting data.
	 * @return array    The sanitized form inputs.
	 */
	public function sanitize_form_inputs( $input_settings = array() ) {
		$new_settings = array();

		if ( ! empty( $input_settings ) ) {
			foreach ( $input_settings as $key => $value ) {

				$new_key = sanitize_text_field( $key );

				if ( is_array( $value ) ) {
					$new_settings[ $new_key ] = $this->sanitize_form_inputs( $value );
				} else {
					$new_settings[ $new_key ] = sanitize_text_field( $value );
				}
			}
		}

		return $new_settings;
	}

	/**
	 * Save setting - Enables GBS extension.
	 *
	 * @since 2.9.0
	 * @return void
	 */
	public function enable_gbs_extension() {
		$this->check_permission_nonce( 'spectra_blocks_enable_gbs_extension' );
		$value = $this->check_post_value();

		$value = 'enabled' === $value ? 'enabled' : 'disabled';
		$this->save_gbs_default_in_upload_folder( $value );

		$this->save_admin_settings( 'spectra_blocks_enable_gbs_extension', $value );
	}

	/**
	 * Generate or delete default block css files.
	 * These generated files will be used in frontend.
	 * when user will disable GBS extension.
	 *
	 * @param string $value value will be enabled or disabled.
	 * @since 2.9.0
	 * @return void
	 */
	public function save_gbs_default_in_upload_folder( $value ) {
		// Note: 'spectra_global_block_styles' is an intentional shared cross-plugin option key,
		// used to share Global Block Styles data between Spectra Blocks and Spectra Pro.
		$spectra_global_block_styles = get_option( 'spectra_global_block_styles', array() );

		if ( empty( $spectra_global_block_styles ) || ! is_array( $spectra_global_block_styles ) ) {
			return;
		}

		$create_block_array = array();

		foreach ( $spectra_global_block_styles as $styles ) {
			if ( empty( $styles['blockName'] ) || ! is_string( $styles['blockName'] ) ) {
				continue;
			}

			$create_block_array[ $styles['blockName'] ] = true;
		}

		// Remove assets if css available.
		if ( 'enabled' === $value ) {
			// Store all post ids in array.
			$post_ids = array();

			foreach ( $spectra_global_block_styles as $styles_get ) {
				if ( empty( $styles_get['post_ids'] ) ) {
					continue;
				}

				foreach ( $styles_get['post_ids'] as $post_id ) {
					if ( ! $post_id || in_array( $post_id, $post_ids, true ) ) {
						continue;
					}

					delete_post_meta( $post_id, '_spectra_blocks_page_assets' );
					delete_post_meta( $post_id, '_spectra_blocks_css_file_name' );
					delete_post_meta( $post_id, '_spectra_blocks_js_file_name' );

					$post_ids[] = $post_id;
				}
			}
		}

		foreach ( $create_block_array as $block_name => $index ) {
			// Check if spectra-blocks string exist in $block_name or not.
			if ( ! is_string( $block_name ) || 0 !== strpos( $block_name, 'spectra/' ) ) {
				continue;
			}

			$_block_slug = str_replace( 'spectra/', '', $block_name );

			// This is class name and file name.
			$class_name = 'spectra-blocks-gbs-default-' . $_block_slug;

			$wp_upload_dir = \Spectra_Blocks_Helper::get_upload_dir_path();

			$path_and_file_name = $wp_upload_dir . $class_name . '.css';

			// If $value is enabled then only remove css default files.
			if ( 'enabled' === $value ) {
				\Spectra_Blocks_Helper::remove_file( $path_and_file_name );
				continue;
			}

			// For default GBS id we are assigning default GBS id attr globalBlockStyleId = $class_name.
			$dummy_attr = array( 'globalBlockStyleId' => $class_name );

			$_block_css = \Spectra_Blocks_Block_Module::get_frontend_css( $_block_slug, $dummy_attr, '', true );

			$tab_styling_css = '';
			$mob_styling_css = '';
			$desktop         = $_block_css['desktop'];

			if ( ! empty( $_block_css['tablet'] ) ) {
				$tab_styling_css .= '@media only screen and (max-width: ' . SPECTRA_BLOCKS_TABLET_BREAKPOINT . 'px) {';
				$tab_styling_css .= $_block_css['tablet'];
				$tab_styling_css .= '}';
			}

			if ( ! empty( $_block_css['mobile'] ) ) {
				$mob_styling_css .= '@media only screen and (max-width: ' . SPECTRA_BLOCKS_MOBILE_BREAKPOINT . 'px) {';
				$mob_styling_css .= $_block_css['mobile'];
				$mob_styling_css .= '}';
			}
			$_block_css = $desktop . $tab_styling_css . $mob_styling_css;

			$wp_filesystem = spectra_blocks_filesystem();
			$wp_filesystem->put_contents( $path_and_file_name, $_block_css, FS_CHMOD_FILE );
		}
	}

	/**
	 * Save setting - Enables or Disables the given Zip AI Module.
	 *
	 * @since 2.10.2
	 * @return void
	 */
	public function zip_ai_module_status() {
		// Check permission.
		$this->check_permission_nonce( 'spectra_blocks_zip_ai_module_status' );
		// Check the post value.
		$value = $this->check_post_value();
		// Check the post module.
		$module = $this->check_post_value( 'module' );

		// If module is not a string, then abandon ship.
		if ( ! is_string( $module ) ) {
			// Since the module was not a string, set it to a blank string and send an error message as the response.
			$module = '';
			wp_send_json_error( array( 'messsage' => __( 'Module not found!', 'spectra-blocks' ) ) );
		}

		// Sanitize the module.
		$module = sanitize_text_field( $module );

		// Replace the underscores in the module name with spaces, make the word AI capital, and capitalize the first letter of each word.
		$module_name = ucwords( str_replace( '_', ' ', str_replace( 'ai', 'AI', $module ) ) );

		// Check if the Zip AI Module is available.
		if ( class_exists( '\ZipAI\Classes\Module' ) ) {
			// If the value is 'disabled', disable the Zip AI Module - else enable it.
			if ( 'disabled' === $value ) {
				if ( Zip_Ai_Module::disable( $module ) ) {
					wp_send_json_success(
						array(
							'messsage' => sprintf(
							// Translators: %s is the module name.
								__( '%s disabled!', 'spectra-blocks' ),
								$module_name
							),
						)
					);
				} else {
					wp_send_json_error(
						array(
							'messsage' => sprintf(
							// Translators: %s is the module name.
								__( 'Unable to disable %s', 'spectra-blocks' ),
								$module_name
							),
						)
					);
				}
			} elseif ( Zip_Ai_Module::enable( $module ) ) {
					wp_send_json_success(
						array(
							'messsage' => sprintf(
							// Translators: %s is the module name.
								__( '%s enabled!', 'spectra-blocks' ),
								$module_name
							),
						)
					);
			} else {
				wp_send_json_error(
					array(
						'messsage' => sprintf(
						// Translators: %s is the module name.
							__( 'Unable to enable %s', 'spectra-blocks' ),
							$module_name
						),
					)
				);
			}
		} else {
			wp_send_json_error( array( 'messsage' => __( 'Unable to save setting.', 'spectra-blocks' ) ) );
		}
	}

	/**
	 * Ajax Request - Verify if Zip AI is authorized.
	 *
	 * @since 2.10.2
	 * @return void
	 */
	public function zip_ai_verify_authenticity() {
		// Check permission.
		$this->check_permission_nonce( 'spectra_blocks_zip_ai_verify_authenticity' );

		// If the Zip AI Helper Class exists, return a success based on the authorizatoin status, else return an error.
		if ( class_exists( '\ZipAI\Classes\Helper' ) ) {
			// Send a boolean based on whether the auth token has been added.
			wp_send_json_success( array( 'is_authorized' => Zip_Ai_Helper::is_authorized() ) );
		} else {
			wp_send_json_error( array( 'messsage' => __( 'Unable to verify authenticity.', 'spectra-blocks' ) ) );
		}
	}

	/**
	 * Save setting - Usage data.
	 *
	 * @since 2.19.5
	 * @return void
	 */
	public function enable_bsf_analytics_option() {
		$this->check_permission_nonce( 'spectra_blocks_enable_bsf_analytics_option' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_analytics_optin', sanitize_text_field( $value ) );
	}

	/**
	 * Save setting - Disable CSS Cache.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function disable_css_cache() {
		$this->check_permission_nonce( 'spectra_blocks_disable_css_cache' );
		$value = $this->check_post_value();
		$this->save_admin_settings( 'spectra_blocks_disable_css_cache', sanitize_text_field( $value ) );
	}

	/**
	 * Clear Cached Styles - Clears all Spectra responsive CSS cache.
	 *
	 * This function clears the cached responsive CSS for Spectra blocks by:
	 * 1. Deleting all transients matching the pattern 'spectra_responsive_css_*'
	 * 2. Clearing object cache for Spectra data
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function clear_v3_cache() {
		global $wpdb;

		$this->check_permission_nonce( 'spectra_blocks_clear_v3_cache' );

		// Delete all Spectra responsive CSS transients.
		// These transients follow the pattern: spectra_responsive_css_{$spectra_id}_{version}.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk deletion of transients requires direct query for performance; caching not applicable for DELETE operations.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_spectra_responsive_css_' ) . '%'
			)
		);

		// Delete corresponding transient timeout entries.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk deletion of transients requires direct query for performance; caching not applicable for DELETE operations.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_spectra_responsive_css_' ) . '%'
			)
		);

		// Clear object cache for Spectra data.
		wp_cache_flush();

		$response_data = array(
			'messsage' => __( 'Cached styles cleared successfully!', 'spectra-blocks' ),
		);
		wp_send_json_success( $response_data );
	}
}
