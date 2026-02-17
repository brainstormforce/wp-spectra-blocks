<?php
/**
 * Spectra Visibility.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Visibility.
 */
class Spectra_Visibility {

	/**
	 * Member Variable
	 *
	 * @since 0.0.1
	 * @var Spectra_Visibility|null
	 */
	private static $instance;

	/**
	 *  Initiator
	 *
	 * @since 0.0.1
	 * @return Spectra_Visibility
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) || null === self::$instance ) {
			self::$instance = new self();

		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$visibility         = Spectra_Admin_Helper::get_admin_settings_option( 'uag_visibility_mode', 'disabled' );
		$visibility_page_id = Spectra_Admin_Helper::get_admin_settings_option( 'uag_visibility_page', false );

		if ( 'disabled' !== $visibility && ! is_user_logged_in() && false !== $visibility_page_id && isset( $visibility_page_id ) && ! empty( $visibility_page_id ) ) {
			add_action( 'template_redirect', array( $this, 'set_visibility_page' ), 99 );
			add_filter( 'template_include', array( $this, 'set_visibility_template' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_asset_files' ) );
		}
	}

	/**
	 * Set Visibility Template.
	 * 
	 * @since 0.0.1
	 * 
	 * @return string Template file path.
	 */
	public function set_visibility_template() {
		return SPECTRA_DIR . 'templates/visibility-template.php';
	}

	/**
	 * Set Visibility Page.
	 *
	 * @since 0.0.1
	 * 
	 * @return void
	 */
	public function set_visibility_page() {
		$visibility_page_id = intval( Spectra_Admin_Helper::get_admin_settings_option( 'uag_visibility_page', false ) );

		$current_page_id = get_the_ID();

		if ( $visibility_page_id !== $current_page_id && 'publish' === get_post_status( $visibility_page_id ) ) {
			$maintenance = Spectra_Admin_Helper::get_admin_settings_option( 'uag_visibility_mode', 'disabled' );
			if ( 'maintenance' === $maintenance ) {
				status_header( 503 );
			}

			// Output JavaScript for redirection.
			echo '<script type="text/javascript">window.location.href = "' . esc_url( get_page_link( $visibility_page_id ) ) . '";</script>';

			// Exit to prevent further processing.
			exit();
		}
	}

	/**
	 * Enqueue asset files.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_asset_files() {
		// Check if assets should be excluded for the current post type.
		if ( Spectra_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}

		$current_page_id    = get_the_ID();
		$visibility_page_id = intval( Spectra_Admin_Helper::get_admin_settings_option( 'uag_visibility_page', false ) );

		if ( $visibility_page_id === $current_page_id ) {
			wp_enqueue_style(
				'uagb-style-visibility', // Handle.
				SPECTRA_URL . 'assets/css/visibility.min.css',
				array(),
				SPECTRA_VER
			);
		}
	}
}

/**
 *  Prepare if class 'Spectra_Visibility' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Visibility::get_instance();
