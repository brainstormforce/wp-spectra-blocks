<?php
/**
 * Ajax Errors.
 *
 * @package uag
 */

namespace SpectraBlocksAdmin\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ajax_Errors
 */
class Ajax_Errors {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 0.0.1
	 */
	private static $instance;

	/**
	 * Errors
	 *
	 * @access private
	 * @var array Errors strings.
	 * @since 0.0.1
	 */
	private static $errors = array();

	/**
	 * Initiator
	 *
	 * @since 0.0.1
	 * @return object initialized object of class.
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
	 * @since 0.0.1
	 */
	public function __construct() {

		/**
		 * Hooks the method to initialize error messages.
		 *
		 * @uses add_action()
		 * @uses self::initialize_errors()
		 * @since 0.0.1
		 */
		add_action(
			'init',
			array( $this, 'initialize_errors' ),
			10, // priority - run after WordPress has finished loading but before any output is sent.
			0   // number of arguments - default is 1.
		);
	}

	/**
	 * Initializes error messages.
	 *
	 * @since 0.0.1
	 * @access public
	 * @return void
	 */
	public function initialize_errors() {
		self::$errors = array(
			'permission' => __( 'Sorry, you are not allowed to do this operation.', 'spectra' ),
			'nonce'      => __( 'Nonce validation failed', 'spectra' ),
			'default'    => __( 'Sorry, something went wrong.', 'spectra' ),
		);
	}

	/**
	 * Get error message.
	 *
	 * @param string $type Message type.
	 * @return string
	 */
	public function get_error_msg( $type ) {

		if ( ! isset( self::$errors[ $type ] ) ) {
			$type = 'default';
		}

		return self::$errors[ $type ];
	}
}

Ajax_Errors::get_instance();
