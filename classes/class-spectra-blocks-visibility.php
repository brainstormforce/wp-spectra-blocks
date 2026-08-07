<?php
/**
 * Spectra Blocks Visibility — Coming Soon & Maintenance Mode.
 *
 * @package Spectra_Blocks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Spectra_Blocks_Visibility.
 *
 * Handles coming-soon and maintenance-mode redirection.
 * Defers to UAGB when UAGB is active and has its own visibility enabled,
 * so the two plugins never double-redirect on the same site.
 *
 * @since 1.0.3
 */
class Spectra_Blocks_Visibility {

	/**
	 * Instance.
	 *
	 * @since 1.0.3
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.3
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.3
	 */
	private function __construct() {
		$mode    = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_mode', 'disabled' );
		$page_id = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_page', '' );

		if ( 'disabled' === $mode || empty( $page_id ) ) {
			return;
		}

		// Defer to UAGB if it owns visibility on this site.
		if ( defined( 'UAGB_FILE' ) ) {
			$uagb_mode = get_option( 'uag_visibility_mode', 'disabled' );
			if ( 'disabled' !== $uagb_mode ) {
				return;
			}
		}

		add_action( 'template_redirect', array( $this, 'handle_redirect' ), 99 );
		add_filter( 'template_include', array( $this, 'use_visibility_template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Redirect non-logged-in visitors to the visibility page.
	 * Sets 503 header for maintenance mode.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public function handle_redirect() {
		if ( is_user_logged_in() ) {
			return;
		}

		$page_id  = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_page', '' );
		$page_url = get_permalink( $page_id );

		if ( ! $page_url ) {
			return;
		}

		// Already on the visibility page — just set status and let it render.
		if ( is_page( $page_id ) ) {
			$mode = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_mode', 'disabled' );
			if ( 'maintenance' === $mode ) {
				status_header( 503 );
				nocache_headers();
			}
			return;
		}

		wp_safe_redirect( $page_url );
		exit;
	}

	/**
	 * Replace the active theme template with a minimal visibility template
	 * so the page renders without the site header/footer.
	 *
	 * @since 1.0.3
	 * @param string $template Current template path.
	 * @return string
	 */
	public function use_visibility_template( $template ) {
		if ( is_user_logged_in() ) {
			return $template;
		}

		$page_id = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_page', '' );

		if ( is_page( $page_id ) ) {
			$custom = SPECTRA_BLOCKS_DIR . 'templates/visibility-template.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}

		return $template;
	}

	/**
	 * Enqueue minimal CSS that hides theme header/footer on the visibility page.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public function enqueue_styles() {
		if ( is_user_logged_in() ) {
			return;
		}

		$page_id = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_page', '' );

		if ( is_page( $page_id ) ) {
			wp_enqueue_style(
				'spectra-blocks-visibility',
				SPECTRA_BLOCKS_URL . 'assets/css/visibility.css',
				array(),
				SPECTRA_BLOCKS_VER
			);
		}
	}
}
