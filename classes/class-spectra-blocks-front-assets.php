<?php
/**
 * Spectra Front Assets.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Front_Assets.
 */
class Spectra_Front_Assets {

	/**
	 * Member Variable
	 *
	 * @since 0.0.1
	 * @var instance
	 */
	private static $instance;

	/**
	 * Post ID
	 *
	 * @since 0.0.1
	 * @var array
	 */
	protected $post_id;

	/**
	 * Assets Post Object
	 *
	 * @since 0.0.1
	 * @var object
	 */
	protected $post_assets;

	/**
	 *  Initiator
	 *
	 * @since 0.0.1
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
		add_action( 'wp', array( $this, 'set_initial_variables' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_asset_files' ) );
		add_action( 'spectra_regenerate_post_assets', array( $this, 'update_current_post_assets' ) );
		add_action( 'wp_insert_post', array( $this, 'trigger_regeneration_event' ), 10, 3 );
	}

	/**
	 * Set initial variables.
	 *
	 * @since 0.0.1
	 */
	public function set_initial_variables() {

		$this->post_id = false;

		if ( is_single() || is_page() || is_404() ) {
			$this->post_id = get_the_ID();
		}

		if ( ! $this->post_id ) {
			return;
		}

		$this->post_assets = spectra_blocks_get_post_assets( $this->post_id );

		if ( ! $this->post_assets->is_allowed_assets_generation ) {
			return;
		}

		if ( is_single() || is_page() || is_404() ) {

			$this_post = get_post( $this->post_id );

			/**
			 * Filters the post to build stylesheet for.
			 *
			 * @param \WP_Post $this_post The global post.
			 */
			$this_post = apply_filters_deprecated( 'spectra_post_for_stylesheet', array( $this_post ), '1.23.0' );

			if ( $this_post && $this->post_id !== $this_post->ID ) {
				$this->post_assets->prepare_assets( $this_post );
			}
		}
	}

	/**
	 * Enqueue asset files.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_asset_files() {
		// Check if assets should be excluded for the current post type.
		if ( Spectra_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}

		if ( $this->post_assets ) {
			$this->post_assets->enqueue_scripts();
		}

		/* Archive & 404 page compatibility */
		if ( is_archive() || is_home() || is_search() || is_404() ) {

			global $wp_query;
			$current_object_id = $wp_query->get_queried_object_id();
			$cached_wp_query   = $wp_query->posts;
			if ( 0 !== $current_object_id && null !== $current_object_id ) {
				$current_post_assets = new Spectra_Post_Assets( $current_object_id );
				$current_post_assets->enqueue_scripts();
			} elseif ( ! ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) && ! empty( $cached_wp_query ) && is_array( $cached_wp_query ) ) {
				foreach ( $cached_wp_query as $post ) {
					$current_post_assets = new Spectra_Post_Assets( $post->ID );
					$current_post_assets->enqueue_scripts();
				}
			} else {
				/*
				If no posts are present in the category/archive
				or 404 page (which is an obvious case for 404), then get the current page ID and enqueue script.
				*/
				$current_object_id   = is_int( $current_object_id ) ? $current_object_id : (int) $current_object_id;
				$current_post_assets = new Spectra_Post_Assets( $current_object_id );
				$current_post_assets->enqueue_scripts();
			}
		}

		/* WooCommerce compatibility */
		if ( class_exists( 'WooCommerce' ) ) {

			if ( is_cart() ) {

				$id = get_option( 'woocommerce_cart_page_id' );
			} elseif ( is_account_page() ) {

				$id = get_option( 'woocommerce_myaccount_page_id' );
			} elseif ( is_checkout() ) {

				if ( is_order_received_page() ) {

					$id = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );
				} else {

					$id = get_option( 'woocommerce_checkout_page_id' );
				}
			} elseif ( is_checkout_pay_page() ) {

				$id = get_option( 'woocommerce_pay_page_id' );
			} elseif ( is_shop() ) {

				$id = get_option( 'woocommerce_shop_page_id' );
			} elseif ( is_order_received_page() ) {

				$id = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );
			}

			if ( ! empty( $id ) ) {
				$current_post_assets = new Spectra_Post_Assets( intval( $id ) );
				$current_post_assets->enqueue_scripts();
			}
		}

	}

	/**
	 * Trigger post assets update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 * @since 0.0.1
	 * @return mixed void if not an update, otherwise null.
	 */
	public function trigger_regeneration_event( $post_id, $post, $update ) {

		if ( ! $update ) {
			return;
		}

		if ( ! wp_next_scheduled( 'spectra_regenerate_post_assets' ) && ! wp_installing() ) {
			$post_assets_regeneration_buffer_time = apply_filters( 'spectra_post_assets_regeneration_buffer_time', 30 );
			wp_schedule_single_event( time() + $post_assets_regeneration_buffer_time, 'spectra_regenerate_post_assets', array( $post_id ) ); // Schedule for 30 seconds later.
		}
	}

	/**
	 * Update post assets.
	 *
	 * By passing everything and update assets once post is updated.
	 *
	 * @param int $post_id Post ID.
	 * @since 0.0.1
	 * @return void
	 */
	public function update_current_post_assets( $post_id ) {
		/**
		 * Case: If previous asset version is same then we need to update the assets, resultant will reduce cache conflicts.
		 */
		$page_assets = (array) get_post_meta( $post_id, '_uag_page_assets', true );
		if ( isset( $page_assets['uag_version'] ) && SPECTRA_ASSET_VER === $page_assets['uag_version'] ) {
			$page_assets['uag_version'] = '';
			update_post_meta( $post_id, '_uag_page_assets', $page_assets );
		}
	}

	/**
	 * Get post_assets obj.
	 *
	 * @since 0.0.1
	 */
	public function get_post_assets_obj() {
		return $this->post_assets;
	}
}

/**
 *  Prepare if class 'Spectra_Front_Assets' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Front_Assets::get_instance();

/**
 * Get frontend post_assets obj.
 *
 * @since 0.0.1
 */
function spectra_blocks_get_front_post_assets() {
	return Spectra_Front_Assets::get_instance()->get_post_assets_obj();
}
