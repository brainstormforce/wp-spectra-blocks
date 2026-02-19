<?php
/**
 * Spectra V3 Popup Builder Block Handler
 * Initializes and coordinates all V3 popup builder functionality
 *
 * @since 3.0.0
 * @package Spectra\Blocks
 */

namespace Spectra\Blocks; // DEV: Namespace for V3 blocks - modify if restructuring block organization.

use Spectra\Traits\Singleton; // DEV: Import singleton pattern trait - ensures single instance.
use WP_Query; // DEV: WordPress query class for fetching popup posts.

defined( 'ABSPATH' ) || exit; // DEV: Security check - prevents direct file access.

/**
 * Class PopupBuilder
 *
 * Main coordinator for V3 popup builder functionality
 * Handles popup builder functionality for Spectra Blocks
 */
class PopupBuilder {
	// DEV: Main class - add new popup functionality methods here.

	use Singleton; // DEV: Singleton pattern - use self::get_instance() to access.

	/**
	 * Post ID Member Variable.
	 *
	 * @var int $post_id
	 *
	 * @since 3.0.0
	 */
	protected $post_id; // DEV: Current post ID being processed - modify for custom post tracking.

	/**
	 * Member Variable for all Popup IDs needed to be rendered on the given page.
	 *
	 * @var array $popup_ids
	 *
	 * @since 3.0.0
	 */
	protected $popup_ids; // DEV: Array of active popup IDs - extend for popup filtering/caching.

	/**
	 * Constructor to Default the Current Instance's Post ID and add the Shortcode if needed.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		// DEV: Initialize class instance - add new default values here.
		$this->post_id   = 0; // DEV: Default post ID to 0 - modify initial value if needed.
		$this->popup_ids = array(); // DEV: Initialize empty popup array - add default popups here if needed.
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
		// DEV: Main entry point for popup script loading.
		if ( ! is_front_page() ) { // DEV: Check if not front page - modify logic for custom page types.
			$this->post_id = get_the_ID(); // DEV: Get current post ID - extend for multi-post scenarios.
		}
		$elementor_preview_active = false; // DEV: Elementor compatibility flag - add other page builder checks here.
		if ( defined( 'ELEMENTOR_VERSION' ) ) { // DEV: Check if Elementor is active - add other page builder constants.
			$elementor_preview_active = \Elementor\Plugin::$instance->preview->is_preview_mode(); // DEV: Elementor preview detection.
		}
		if ( 'spectra-popup' === get_post_type( $this->post_id ) || $elementor_preview_active ) { // DEV: Skip if popup post or preview - modify conditions for custom post types.
			return; // DEV: Early return to prevent recursive loading.
		}

		$this->enqueue_popup_scripts(); // DEV: Delegate to main script enqueueing method.
	}

	/**
	 * Enqueue all the Spectra Popup Scripts needed on the given post.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function enqueue_popup_scripts() {
		// DEV: Core popup loading logic - modify query parameters for custom filtering.
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args   = array( // DEV: WP_Query arguments for fetching enabled popups.
			'post_type'      => 'spectra-popup', // DEV: Popup post type - change if using different post type.
			'posts_per_page' => -1, // DEV: Get all popups - modify for pagination/limits.
			'meta_query'     => array( // DEV: Meta query for enabled popups - extend for additional conditions.
				array( // DEV: Single meta query condition - add more array elements for complex queries.
					'key'     => 'spectra-popup-enabled', // DEV: Meta key for popup status - update if meta key changes.
					'value'   => true, // DEV: Looking for enabled popups - change to false for disabled popups.
					'compare' => '=', // DEV: Exact match comparison - use 'IN', 'NOT IN', etc. for array values.
					'type'    => 'BOOLEAN', // DEV: Meta value type - change to 'NUMERIC', 'CHAR' for different data types.
				),
			),
		);
		$popups = new WP_Query( $args ); // DEV: Execute query - consider caching for performance.
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		while ( $popups->have_posts() ) : // DEV: Loop through found popups - add additional processing here.
			$popups->the_post(); // DEV: Setup post data for current iteration.
			$render_this_popup = apply_filters( 'spectra_pro_popup_display_filters_v3', true, $this->post_id );

			$popup_id = get_the_ID(); // DEV: Get current popup ID - add validation/sanitization if needed.

			if ( $render_this_popup ) {
				if ( is_array( $this->popup_ids ) ) { // DEV: Safety check for array - consider using array_key_exists for duplicates.
					array_push( $this->popup_ids, $popup_id ); // DEV: Add popup ID to collection - use array_unique() to prevent duplicates.
				}
			}

		endwhile;
		wp_reset_postdata(); // DEV: Reset global post data - critical for preventing conflicts.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$this_post = get_post( $this->post_id );
			$this->append_my_shortcode( $this_post, $this->popup_ids );
		}
		if ( is_404() || is_search() || is_tag() ) { // DEV: Popup rendering on special pages.
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
		if ( is_array( $this->popup_ids ) && ! empty( $this->popup_ids ) ) { // DEV: Validate popup IDs exist - add additional validation as needed.
			foreach ( $this->popup_ids as $popup_id ) { // DEV: Loop through each popup ID - add filtering logic here.
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
		// DEV: AJAX handler for popup status toggle - hook with wp_ajax_.
		check_ajax_referer( 'spectra_blocks_popup_builder_admin_nonce', 'nonce' ); // DEV: Verify AJAX nonce - update nonce name if changed.

		if ( ! current_user_can( 'manage_options' ) ) { // DEV: Permission check - modify capability for different user roles.
			wp_send_json_error(); // DEV: Send error response - add error message for debugging.
		}

		if ( ! isset( $_POST['enabled'] ) || ! isset( $_POST['post_id'] ) ) { // DEV: Validate required POST parameters - add additional field validation.
			wp_send_json_error(); // DEV: Send error for missing parameters - add specific error messages.
		}

		$enabled  = rest_sanitize_boolean( sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) ); // DEV: Sanitize boolean value - validate true/false values.
		$popup_id = absint( wp_unslash( $_POST['post_id'] ) ); // DEV: Sanitize popup ID as integer - add range validation if needed.

		update_post_meta( $popup_id, 'spectra-popup-enabled', $enabled ); // DEV: Update popup status meta - add error handling for failed updates.

		wp_send_json_success(); // DEV: Send success response - add updated data in response if needed.
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
		// DEV: Admin script enqueuing method - hook with admin_enqueue_scripts.

		global $pagenow; // DEV: WordPress global for current admin page - used for page detection.

		$screen = get_current_screen(); // DEV: Get current admin screen object - used for post type detection.

		if ( 'spectra-popup' === $screen->post_type && 'edit.php' === $pagenow ) { // DEV: Only load on popup post type list page - modify conditions for other admin pages.
			$extension = SCRIPT_DEBUG ? '' : '.min'; // DEV: Use minified files in production - check SCRIPT_DEBUG constant.
			wp_register_script( // DEV: Register admin JavaScript file - update path/version as needed.
				'spectra-blocks-popup-builder-admin-js', // DEV: Script handle - update if handle name changes.
				SPECTRA_BLOCKS_URL . 'assets/js/spectra-popup-builder-admin' . $extension . '.js', // DEV: Script file path - update if file location changes.
				array(), // DEV: Script dependencies - add jQuery, wp-util, etc. if needed.
				SPECTRA_BLOCKS_VER, // DEV: Script version for cache busting - update with plugin version.
				false // DEV: Load in header (false) or footer (true) - change to true for footer loading.
			);
			wp_register_style( // DEV: Register admin CSS file - update path/version as needed.
				'spectra-blocks-popup-builder-admin-css', // DEV: Style handle - update if handle name changes.
				SPECTRA_BLOCKS_URL . 'assets/css/spectra-popup-builder-admin' . $extension . '.css', // DEV: CSS file path - update if file location changes.
				array(), // DEV: Style dependencies - add other stylesheets if needed.
				SPECTRA_BLOCKS_VER // DEV: Style version for cache busting - update with plugin version.
			);

			wp_localize_script( // DEV: Pass PHP data to JavaScript - add new variables as needed.
				'spectra-blocks-popup-builder-admin-js', // DEV: Script handle to attach data to.
				'spectra_blocks_popup_builder_admin', // DEV: JavaScript object name - update if JS variable name changes.
				array( // DEV: Data array passed to JavaScript - add new properties as needed.
					'ajax_url' => admin_url( 'admin-ajax.php' ), // DEV: WordPress AJAX URL - standard WordPress AJAX endpoint.
					'spectra_blocks_popup_builder_admin_nonce' => wp_create_nonce( 'spectra_blocks_popup_builder_admin_nonce' ), // DEV: Security nonce - update nonce name if changed.
				)
			);
			wp_enqueue_script( 'spectra-blocks-popup-builder-admin-js' ); // DEV: Enqueue registered JavaScript - execute to load script.
			wp_enqueue_style( 'spectra-blocks-popup-builder-admin-css' ); // DEV: Enqueue registered CSS - execute to load stylesheet.
		}
	} // DEV: End of popup_toggle_scripts method.
} // DEV: End of PopupBuilder class - add new methods above this closing brace.
