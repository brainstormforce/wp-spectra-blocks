<?php
/**
 * Singleton class trait.
 *
 * @package Spectra\Traits
 */

namespace Spectra\Traits;

/**
 * Singleton trait.
 */
trait Singleton {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 * @throws \Error Throws error when attempting to clone singleton instance.
	 */
	public function __clone() {
		throw new \Error( 'Cannot clone singleton' );
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.0.0
	 * @throws \Error Throws error when attempting to unserialize singleton instance.
	 */
	public function __wakeup() {
		throw new \Error( 'Cannot unserialize singleton' );
	}
}
