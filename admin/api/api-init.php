<?php
/**
 * Api Init.
 *
 * @package uag
 */

namespace SpectraBlocksAdmin\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Api_Init.
 */
class Api_Init {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 0.0.1
	 */
	private static $instance;

	/**
	 * Dynamic properties container
	 *
	 * @since 0.0.1
	 * @var array
	 */
	private $dynamic_properties = array();

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
		$this->initialize_hooks();
	}

	/**
	 * Init Hooks.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function initialize_hooks() {

		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Init dynamic property setter
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function __set( $name, $value ) {
		$this->dynamic_properties[ $name ] = $value;
	}

	/**
	 * Init dynamic property getter
	 *
	 * @param string $name Property name.
	 *
	 * @since 0.0.1
	 * @return mixed Property value if set, null otherwise.
	 */
	public function __get( $name ) {
		return $this->dynamic_properties[ $name ] ? $this->dynamic_properties[ $name ] : null;
	}

	/**
	 * Register API routes.
	 */
	public function register_routes() {

		$controllers = array(
			'SpectraBlocksAdmin\Api\Common_Settings',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = $controller::get_instance();
			$this->{$controller}->register_routes();
		}
	}
}

Api_Init::get_instance();
