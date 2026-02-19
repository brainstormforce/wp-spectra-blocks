<?php
/**
 * Spectra Blocks Admin.
 *
 * @package Spectra_Blocks
 */

namespace SpectraBlocksAdmin;

use SpectraBlocksAdmin\Api\Api_Init;
use SpectraBlocksAdmin\Ajax\Ajax_Init;
use SpectraBlocksAdmin\Inc\Admin_Menu;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class Admin_Loader.
 */
class Admin_Loader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 2.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 2.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class_name class name.
	 */
	public function autoload( $class_name ) {

		if ( 0 !== strpos( $class_name, __NAMESPACE__ ) ) {
			return;
		}

		$class_to_load = $class_name;

		if ( ! class_exists( $class_to_load ) ) {
			$filename = strtolower(
				preg_replace(
					array( '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ),
					array( '', '$1-$2', '-', DIRECTORY_SEPARATOR ),
					$class_to_load
				)
			);

			$dir  = dirname( $filename );
			$base = basename( $filename );
			$file = SPECTRA_BLOCKS_ADMIN_DIR . ( '.' === $dir ? '' : $dir . DIRECTORY_SEPARATOR ) . 'class-' . $base . '.php';

			// if the file redable, include it.
			if ( is_readable( $file ) ) {
				include $file;
			}
		}
	}

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->define_constants();
		$this->setup_classes();
	}

	/**
	 * Include required classes.
	 */
	public function define_constants() {
		define( 'SPECTRA_BLOCKS_ADMIN_DIR', SPECTRA_BLOCKS_DIR . 'admin/' );
		define( 'SPECTRA_BLOCKS_ADMIN_URL', SPECTRA_BLOCKS_URL . 'admin/' );
	}

	/**
	 * Include required classes.
	 */
	public function setup_classes() {

		/* Init API */
		Api_Init::get_instance();

		if ( is_admin() ) {
			/* Setup Menu */
			Admin_Menu::get_instance();

			/* Ajax init */
			Ajax_Init::get_instance();
		}
	}
}
Admin_Loader::get_instance();
