<?php
/**
 * Spectra Blocks — Gutenberg Templates (Design Library) wrapper.
 *
 * Bootstraps the bundled gutenberg-templates library and customises the
 * Design Library button label and branding for Spectra Blocks.
 *
 * @since x.x.x
 * @package Spectra_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Ast_Block_Templates' ) ) :

	/**
	 * Loads the bundled gutenberg-templates library.
	 *
	 * @since x.x.x
	 */
	class Spectra_Blocks_Ast_Block_Templates {

		/**
		 * Singleton instance.
		 *
		 * @since x.x.x
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Returns the singleton instance.
		 *
		 * @since x.x.x
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since x.x.x
		 */
		private function __construct() {
			$this->version_check();
			add_action( 'init', array( $this, 'load' ), 999 );
			add_filter( 'ast_block_templates_localize_vars', array( $this, 'update_vars' ) );
		}

		/**
		 * Overrides the Design Library button label and branding.
		 *
		 * Skipped when Astra Sites is active because that plugin manages its
		 * own branding for the same library.
		 *
		 * @since x.x.x
		 *
		 * @param array $vars Localised JS vars passed to the editor script.
		 * @return array
		 */
		public function update_vars( $vars = array() ) {
			if ( defined( 'ASTRA_SITES_VER' ) ) {
				return $vars;
			}

			$vars['button_text']         = __( 'Design Library', 'spectra-blocks' );
			$vars['display_button_logo'] = true;
			$vars['popup_logo_uri']      = SPECTRA_BLOCKS_URL . 'admin/assets/images/uag-logo.svg';
			$vars['button_logo']         = SPECTRA_BLOCKS_URL . 'admin/assets/images/btn-spectra.svg';
			$vars['button_class']        = 'uagb-template-button-logo';

			return $vars;
		}

		/**
		 * Registers the bundled library version with the global version-negotiation
		 * mechanism so multiple plugins can share the newest copy.
		 *
		 * @since x.x.x
		 *
		 * @return void
		 */
		public function version_check() {
			$file = realpath( dirname( __FILE__ ) . '/gutenberg-templates/version.json' );

			if ( ! is_file( $file ) ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_data = json_decode( file_get_contents( $file ), true );

			global $ast_block_templates_version, $ast_block_templates_init;

			$path    = realpath( dirname( __FILE__ ) . '/gutenberg-templates/ast-block-templates.php' );
			$version = isset( $file_data['ast-block-templates'] ) ? $file_data['ast-block-templates'] : 0;

			if ( null === $ast_block_templates_version ) {
				$ast_block_templates_version = '1.0.0';
			}

			if ( version_compare( $version, $ast_block_templates_version, '>' ) ) {
				$ast_block_templates_version = $version;
				$ast_block_templates_init    = $path;
			}
		}

		/**
		 * Includes the winning (highest-version) gutenberg-templates entry point.
		 *
		 * @since x.x.x
		 *
		 * @return void
		 */
		public function load() {
			global $ast_block_templates_init;

			// Bail when no plugin registered an entry point (e.g. CI runners
			// without `composer install`). Without this guard, PHP 8.1+
			// emits a `realpath(null)` deprecation that is printed before
			// any `header()` call and breaks every subsequent `wp_redirect`.
			if ( empty( $ast_block_templates_init ) ) {
				return;
			}

			$resolved = realpath( $ast_block_templates_init );
			if ( $resolved && is_file( $resolved ) ) {
				include_once $resolved;
			}
		}
	}

	Spectra_Blocks_Ast_Block_Templates::get_instance();

endif;
