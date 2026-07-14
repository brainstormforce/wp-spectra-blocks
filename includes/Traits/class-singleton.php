<?php
/**
 * Singleton class trait.
 *
 * @package Spectra\Traits
 */

namespace SpectraBlocks\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton trait.
 *
 * Stores instances in an array keyed by the late-static-bound class name so
 * that subclasses each get their own instance. Using a single static $instance
 * with static::$instance works for direct users of the trait but breaks the
 * moment a class extends another that uses the trait — both classes would
 * share the parent's static property and overwrite each other.
 */
trait Singleton {
	/**
	 * Class instances keyed by called-class name.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string,object>
	 */
	protected static $instances = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance for the late-static-bound calling class.
	 *
	 * @since 3.0.0
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		$class = static::class;

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 3.0.0
	 * @throws \Error Throws error when attempting to clone singleton instance.
	 */
	public function __clone() {
		throw new \Error( 'Cannot clone singleton' );
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 3.0.0
	 * @throws \Error Throws error when attempting to unserialize singleton instance.
	 */
	public function __wakeup() {
		throw new \Error( 'Cannot unserialize singleton' );
	}
}
