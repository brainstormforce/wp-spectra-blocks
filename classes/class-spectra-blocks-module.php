<?php
/**
 * Spectra Block Module (V2 Stub).
 *
 * This is a stub class to maintain backward compatibility after v2 removal.
 * All v2 blocks have been removed, so these methods return empty values.
 *
 * @since 0.0.1
 * @package uagb
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Block_Module' ) ) {

	/**
	 * Spectra Block Module Stub Class
	 *
	 * Provides empty implementations of v2 block methods for compatibility.
	 * V3 blocks use a different asset management system.
	 */
	class Spectra_Block_Module {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Block Attributes
		 *
		 * @var block_attributes
		 */
		public static $block_attributes = null;

		/**
		 * Block Assets
		 *
		 * @var array<mixed> block_assets
		 */
		public static $block_assets = null;

		/**
		 * Initiator
		 *
		 * @return Spectra_Block_Module
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
			// V2 blocks removed - no initialization needed
		}

		/**
		 * Get blocks info (stub).
		 *
		 * @since 0.0.1
		 * @return array Empty array - v2 blocks removed
		 */
		public static function get_blocks_info() {
			return array();
		}

		/**
		 * Get block dependencies (stub).
		 *
		 * @since 0.0.1
		 * @return array Empty array - v2 blocks removed
		 */
		public static function get_block_dependencies() {
			return array();
		}

		/**
		 * Get frontend CSS (stub).
		 *
		 * @since 0.0.1
		 * @param string $slug Block slug.
		 * @param array  $attr Block attributes.
		 * @param string $id Block id.
		 * @param bool   $is_gbs Is Global Block Style.
		 * @return array Empty array - v2 blocks removed
		 */
		public static function get_frontend_css( $slug, $attr, $id, $is_gbs = false ) {
			return array();
		}

		/**
		 * Get frontend JS (stub).
		 *
		 * @since 0.0.1
		 * @param string $slug Block slug.
		 * @param array  $attr Block attributes.
		 * @param string $id Block id.
		 * @param string $type Asset type.
		 * @return string Empty string - v2 blocks removed
		 */
		public static function get_frontend_js( $slug, $attr, $id, $type = 'js' ) {
			return '';
		}

		/**
		 * Get frontend assets (stub).
		 *
		 * @since 0.0.1
		 * @param string $slug Block slug.
		 * @param array  $attr Block attributes.
		 * @param string $id Block id.
		 * @param string $type Asset type.
		 * @param bool   $is_gbs Is Global Block Style.
		 * @return mixed Empty array or string - v2 blocks removed
		 */
		public static function get_frontend_assets( $slug, $attr, $id, $type = 'css', $is_gbs = false ) {
			return ( 'css' === $type ) ? array() : '';
		}
	}
}
