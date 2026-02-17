<?php
/**
 * Astra compatibility
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Astra_Compatibility.
 */
class Spectra_Astra_Compatibility {

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
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		// Update Astra's admin top level menu position.
		add_filter( 'astra_menu_priority', array( $this, 'update_admin_menu_position' ) );

		$uag_load_fonts_locally = Spectra_Admin_Helper::get_admin_settings_option( 'uag_load_gfonts_locally', 'disabled' );

		if ( 'disabled' === $uag_load_fonts_locally ) {

			$astra_settings = ( defined( 'ASTRA_THEME_SETTINGS' ) ) ? get_option( ASTRA_THEME_SETTINGS ) : '';

			if ( is_array( $astra_settings ) && empty( $astra_settings['load-google-fonts-locally'] ) || ( isset( $astra_settings['load-google-fonts-locally'] ) && false === $astra_settings['load-google-fonts-locally'] ) ) {

				// Disabled uag fonts.
				add_filter( 'spectra_enqueue_google_fonts', '__return_false' );

				// Add uag fonts in astra.
				add_filter( 'astra_google_fonts_selected', array( $this, 'add_google_fonts_in_astra' ) );

			}
		}
	}

	/**
	 * This functions adds UAG Google Fonts in Astra filter to load a common Google Font File for both UAG & Astra.
	 *
	 * @param array $astra_fonts Astra Fonts Object.
	 *
	 * @since 0.0.1
	 * @return array
	 */
	public function add_google_fonts_in_astra( $astra_fonts ) {

		global $post;

		if ( $post ) {
			$post_id = $post->ID;
		}

		if ( is_404() ) {
			$post_id = get_queried_object_id();
		}

		if ( isset( $post_id ) ) {

			$google_fonts = spectra_blocks_get_post_assets( $post_id )->get_fonts();

			if ( is_array( $google_fonts ) && ! empty( $google_fonts ) ) {

				foreach ( $google_fonts as $key => $gfont_values ) {
					if ( ! empty( $gfont_values['fontfamily'] ) && is_string( $gfont_values['fontfamily'] ) && isset( $gfont_values['fontvariants'] ) ) {

						$astra_fonts[ $gfont_values['fontfamily'] ] = $gfont_values['fontvariants'];

						foreach ( $gfont_values['fontvariants'] as $key => $font_variants ) {

							$astra_fonts[ $gfont_values['fontfamily'] ][ $key ] .= ',' . $font_variants . 'italic';
						}
					}
				}
			}
		}

		return $astra_fonts;
	}

	/**
	 * Update Astra's menu priority to show after Dashboard menu.
	 *
	 * @param int $menu_priority top level menu priority.
	 * @since 0.0.1
	 */
	public function update_admin_menu_position( $menu_priority ) {
		return 2.1;
	}
}

/**
 *  Prepare if class 'Spectra_Astra_Compatibility' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Astra_Compatibility::get_instance();
