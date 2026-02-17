<?php
/**
 * Spectra Twenty Sixteen Compatibility.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Twenty_Sixteen_Compatibility' ) ) {

	/**
	 * Class Spectra_Twenty_Sixteen_Compatibility.
	 */
	final class Spectra_Twenty_Sixteen_Compatibility {

		/**
		 * Member Variable
		 *
		 * @var Spectra_Twenty_Sixteen_Compatibility
		 */
		private static $instance;

		/**
		 *  Initiator
		 *
		 * @since 0.0.1
		 * @return Spectra_Twenty_Sixteen_Compatibility
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
		
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'generate_stylesheet' ), 101 );
		}
		/**
		 * Generates stylesheet and appends in head tag.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function generate_stylesheet() {

			if ( is_home() ) {
				$post_id             = get_the_ID();
				$current_post_assets = new Spectra_Post_Assets( intval( $post_id ) );
				
				if ( is_object( $current_post_assets ) ) {
					$current_post_assets->enqueue_scripts();
				}
			}

		}
	}
}
Spectra_Twenty_Sixteen_Compatibility::get_instance();
