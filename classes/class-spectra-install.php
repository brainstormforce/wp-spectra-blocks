<?php
/**
 * Spectra Install
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Install.
 */
class Spectra_Install {

	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Run database migration on admin init
		add_action( 'admin_init', array( $this, 'migrate_database_options' ) );
	}

	/**
	 * Migrate database options from uagb_* to spectra_*
	 *
	 * @since 0.0.1
	 */
	public function migrate_database_options() {
		global $wpdb;

		// Check if migration already done
		if ( get_option( 'spectra_rebrand_complete' ) ) {
			return;
		}

		// Get all uagb_* and _uagb_* options
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options}
				WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( 'uagb_' ) . '%',
				$wpdb->esc_like( '_uagb_' ) . '%'
			)
		);

		// Copy to new spectra_* options
		foreach ( $options as $option ) {
			$new_name = str_replace( 'uagb_', 'spectra_', $option->option_name );
			$new_name = str_replace( '_uagb_', '_spectra_', $new_name );

			// Only copy if the new option doesn't already exist
			if ( false === get_option( $new_name, false ) ) {
				update_option( $new_name, $option->option_value );
			}
		}

		// Mark migration complete
		update_option( 'spectra_rebrand_complete', '1' );
	}

	/**
	 * Create files/directories.
	 */
	public function create_files() {

		if ( ! defined( 'SPECTRA_UPLOAD_DIR_NAME' ) ) {
			define( 'SPECTRA_UPLOAD_DIR_NAME', 'spectra-plugin' );
		}

		if ( ! defined( 'SPECTRA_UPLOAD_DIR' ) ) {
			$upload_dir = wp_upload_dir( null, false );
			define( 'SPECTRA_UPLOAD_DIR', $upload_dir['basedir'] . '/' . SPECTRA_UPLOAD_DIR_NAME . '/' );
		}

		$files = array(
			array(
				'base'    => SPECTRA_UPLOAD_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => SPECTRA_UPLOAD_DIR . 'assets',
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base' => SPECTRA_UPLOAD_DIR . 'assets/fonts',
			),
		);

		foreach ( $files as $file ) {

			if ( wp_mkdir_p( $file['base'] ) && ! empty( $file['file'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore

				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}
	}
}

/**
 *  Prepare if class 'Spectra_Install' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Install::get_instance();

/**
 * Install class
 *
 * @since 0.0.1
 *
 * @return object
 */
function spectra_install() {
	return Spectra_Install::get_instance();
}

