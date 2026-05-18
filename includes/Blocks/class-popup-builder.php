<?php
/**
 * Spectra V3 Popup Builder Block Handler
 * Initializes and coordinates all V3 popup builder functionality
 *
 * @since 3.0.0
 * @package Spectra\Blocks
 */

namespace Spectra\Blocks;

use Spectra\Traits\Singleton;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class PopupBuilder
 *
 * Main coordinator for V3 popup builder functionality
 * Handles popup builder functionality for Spectra Blocks
 */
class PopupBuilder {


	use Singleton;

	/**
	 * Post ID Member Variable.
	 *
	 * @var int $post_id
	 *
	 * @since 3.0.0
	 */
	protected $post_id;

	/**
	 * Member Variable for all Popup IDs needed to be rendered on the given page.
	 *
	 * @var array $popup_ids
	 *
	 * @since 3.0.0
	 */
	protected $popup_ids;

	/**
	 * Constructor to Default the Current Instance's Post ID and add the Shortcode if needed.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->post_id   = 0;
		$this->popup_ids = array();
	}

	/**
	 * Get the popup IDs that will render on the current page.
	 *
	 * Populated during enqueue_popup_scripts() which runs at
	 * wp_enqueue_scripts priority 1. Used by GlobalStyles to
	 * load GS CSS for blocks inside popups.
	 *
	 * @since x.x.x
	 *
	 * @return array<int> Array of popup post IDs.
	 */
	public function get_popup_ids() {
		return \is_array( $this->popup_ids ) ? $this->popup_ids : array();
	}

	/**
	 * Enqueue all popup scripts for the current post.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function enqueue_popup_scripts_for_post() {

		if ( ! is_front_page() ) {
			$this->post_id = get_the_ID();
		}
		$elementor_preview_active = false;
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$elementor_preview_active = \Elementor\Plugin::$instance->preview->is_preview_mode();
		}
		if ( 'spectra-popup' === get_post_type( $this->post_id ) || $elementor_preview_active ) {
			return;
		}

		$this->enqueue_popup_scripts();
	}

	/**
	 * Enqueue all the Spectra Popup Scripts needed on the given post.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function enqueue_popup_scripts() {

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args   = array(
			'post_type'      => 'spectra-popup',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'spectra-popup-enabled',
					'value'   => true,
					'compare' => '=',
					'type'    => 'BOOLEAN',
				),
			),
		);
		$popups = new WP_Query( $args );
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		while ( $popups->have_posts() ) :
			$popups->the_post();
			$render_this_popup = apply_filters( 'spectra_pro_popup_display_filters_v3', true, $this->post_id );

			$popup_id = get_the_ID();

			if ( $render_this_popup ) {
				if ( is_array( $this->popup_ids ) ) {
					array_push( $this->popup_ids, $popup_id );
				}
			}

		endwhile;
		wp_reset_postdata();
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$this_post = get_post( $this->post_id );
			$this->append_my_shortcode( $this_post, $this->popup_ids );
		}
		if ( is_404() || is_search() || is_tag() ) {
			add_action( 'wp_body_open', array( $this, 'generate_popup_shortcode' ) );
		}
	}

	/**
	 * Generate the popup shortcodes needed.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function generate_popup_shortcode() {
		if ( is_array( $this->popup_ids ) && ! empty( $this->popup_ids ) ) {
			foreach ( $this->popup_ids as $popup_id ) {
				echo do_shortcode( '[spectra_popup id=' . esc_attr( $popup_id ) . ']' );
			}
		}
	}

	/**
	 * Append the popup shortcode to the post content.
	 *
	 * @param object $this_post  The post object.
	 * @param array  $_popup_ids The array of popup IDs (reserved, intentionally unused).
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function append_my_shortcode( $this_post, $_popup_ids ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( is_array( $this->popup_ids ) && ! empty( $this->popup_ids ) ) {
			foreach ( $this->popup_ids as $popup_id ) {
				$popup_contents[]         = do_shortcode( '[spectra_popup id=' . esc_attr( $popup_id ) . ']' );
				$this_post->post_content .= implode( '', $popup_contents ); // Append your shortcode to the block content.
			}
		}
	}

	/**
	 * Update the Current Popup's Meta from Admin Table.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function update_popup_status() {

		check_ajax_referer( 'spectra_blocks_popup_builder_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! isset( $_POST['enabled'] ) || ! isset( $_POST['post_id'] ) ) {
			wp_send_json_error();
		}

		$enabled  = rest_sanitize_boolean( sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) );
		$popup_id = absint( wp_unslash( $_POST['post_id'] ) );

		update_post_meta( $popup_id, 'spectra-popup-enabled', $enabled );

		wp_send_json_success();
	}

	/**
	 * Enqueues scripts for the Toggle Button in the Popup Table.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	/**
	 * Initialize the Popup Builder: register the CPT and its post meta.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_popup_cpt' ) );
	}

	/**
	 * Register the spectra-popup custom post type and its post meta.
	 *
	 * Mirrors the registration done by UAG for backward compatibility with
	 * existing popup content and meta keys.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register_popup_cpt() {
		$supports = array(
			'title',
			'editor',
			'custom-fields',
			'author',
		);

		$labels = array(
			'name'               => _x( 'Popup Builder', 'plural', 'spectra-blocks' ),
			'singular_name'      => _x( 'Spectra Popup', 'singular', 'spectra-blocks' ),
			'view_item'          => __( 'View Popup', 'spectra-blocks' ),
			'add_new'            => __( 'Create Popup', 'spectra-blocks' ),
			'add_new_item'       => __( 'Create New Popup', 'spectra-blocks' ),
			'edit_item'          => __( 'Edit Popup', 'spectra-blocks' ),
			'new_item'           => __( 'New Popup', 'spectra-blocks' ),
			'search_items'       => __( 'Search Popups', 'spectra-blocks' ),
			'not_found'          => __( 'No Popups Found', 'spectra-blocks' ),
			'not_found_in_trash' => __( 'No Popups in Trash', 'spectra-blocks' ),
			'all_items'          => __( 'All Popups', 'spectra-blocks' ),
			'item_published'     => __( 'Popup Published', 'spectra-blocks' ),
			'item_updated'       => __( 'Popup Updated', 'spectra-blocks' ),
		);

		$type_args = array(
			'supports'          => $supports,
			'labels'            => $labels,
			'public'            => false,
			'show_in_menu'      => false,
			'show_in_admin_bar' => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'template_lock'     => 'all',
			'template'          => array(
				array( 'spectra/popup-builder', array() ),
			),
			'rewrite'           => array(
				'slug'       => 'spectra-popup',
				'with-front' => false,
				'pages'      => false,
			),
			'capabilities'      => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
				'delete_posts'       => 'manage_options',
				'create_posts'       => 'manage_options',
			),
		);

		register_post_type( 'spectra-popup', $type_args );

		register_post_meta(
			'spectra-popup',
			'spectra-popup-type',
			array(
				'single'        => true,
				'type'          => 'string',
				'default'       => 'unset',
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		register_post_meta(
			'spectra-popup',
			'spectra-popup-enabled',
			array(
				'single'        => true,
				'type'          => 'boolean',
				'default'       => false,
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		register_post_meta(
			'spectra-popup',
			'spectra-popup-repetition',
			array(
				'single'        => true,
				'type'          => 'number',
				'default'       => 1,
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		/**
		 * Fires after the spectra-popup CPT and its post meta are registered.
		 *
		 * @since x.x.x
		 */
		do_action( 'spectra_blocks_register_popup_meta' );
	}

	/**
	 * Enqueues scripts for the Toggle Button in the Popup Table.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function popup_toggle_scripts() {

		global $pagenow;

		$screen = get_current_screen();

		if ( 'spectra-popup' === $screen->post_type && 'edit.php' === $pagenow ) {
			$extension = SCRIPT_DEBUG ? '' : '.min';
			wp_register_script(
				'spectra-blocks-popup-builder-admin-js',
				SPECTRA_BLOCKS_URL . 'assets/js/spectra-popup-builder-admin' . $extension . '.js',
				array(),
				SPECTRA_BLOCKS_VER,
				false
			);
			wp_register_style(
				'spectra-blocks-popup-builder-admin-css',
				SPECTRA_BLOCKS_URL . 'assets/css/spectra-popup-builder-admin' . $extension . '.css',
				array(),
				SPECTRA_BLOCKS_VER
			);

			wp_localize_script(
				'spectra-blocks-popup-builder-admin-js',
				'spectra_blocks_popup_builder_admin',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'spectra_blocks_popup_builder_admin_nonce' => wp_create_nonce( 'spectra_blocks_popup_builder_admin_nonce' ),
				)
			);
			wp_enqueue_script( 'spectra-blocks-popup-builder-admin-js' );
			wp_enqueue_style( 'spectra-blocks-popup-builder-admin-css' );
		}
	}
}
