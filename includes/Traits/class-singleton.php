<?php
/**
 * Singleton class trait.
 *
 * @package Spectra\Traits
 */

namespace Spectra\Traits;

defined( 'ABSPATH' ) || exit;


/**
 * Singleton trait.
 */
trait Singleton {
	/**
	 * Instances indexed by class name.
	 *
	 * Using an array keyed by class name prevents child classes that share a
	 * common parent (e.g. AbstractAbility) from overwriting each other's instance.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, object>
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
	 * Get class instance.
	 *
	 * @since 3.0.0
	 *
	 * @return static Instance.
	 */
	final public static function instance() {
		$class = static::class;
		if ( ! isset( static::$instances[ $class ] ) ) {
			static::$instances[ $class ] = new static();
		}
		return static::$instances[ $class ];
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
