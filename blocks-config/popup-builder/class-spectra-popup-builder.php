<?php
/**
 * Spectra - Popup Builder
 *
 * @package Spectra
 *
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Popup_Builder.
 *
 * @since 0.0.1
 */
class Spectra_Popup_Builder {

	/**
	 * Post ID Member Variable.
	 *
	 * @var int $post_id
	 *
	 * @since 0.0.1
	 */
	protected $post_id;

	/**
	 * Member Variable for all Popup IDs needed to be rendered on the given page.
	 *
	 * @var array $popup_ids
	 *
	 * @since 0.0.1
	 */
	protected $popup_ids;

	/**
	 * Constructor to Default the Current Instance's Post ID and add the Shortcode if needed.
	 * 
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->post_id   = 0;
		$this->popup_ids = array();

		if ( ! shortcode_exists( 'spectra_popup' ) ) {
			add_shortcode( 'spectra_popup', array( $this, 'spectra_popup_shortcode' ) );
		}
	}

	/**
	 * Create Instance for the Admin Dashboard.
	 *
	 * @return object  Initialized object of this class.
	 *
	 * @since 0.0.1
	 */
	public static function create_for_admin() {
		$instance = new self();
		add_action( 'spectra_after_menu_register', array( $instance, 'add_popup_builder_submenu' ) );
		return $instance;
	}

	/**
	 * Create Instance with Script Generation.
	 *
	 * @return object  Initialized object of this class.
	 *
	 * @since 0.0.1
	 */
	public static function generate_scripts() {
		$instance = new self();
		add_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_popup_scripts_for_post' ), 1 );
		return $instance;
	}

	/**
	 * Add the Popup Builder Submenu to the Spectra Menu.
	 *
	 * @param string $menu_slug The menu slug.
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function add_popup_builder_submenu( $menu_slug ) {
		add_submenu_page(
			$menu_slug,
			__( 'Popup Builder', 'spectra' ),
			__( 'Popup Builder', 'spectra' ),
			'manage_options',
			'edit.php?post_type=spectra-popup'
		);
	}

	/**
	 * Enqueue all popup scripts for the current post.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function enqueue_popup_scripts_for_post() {
		if ( ! is_front_page() ) {
			$this->post_id = get_the_ID();
		}
		$elementor_preview_active = false;
		if ( defined( 'ELEMENTOR_VERSION' ) ) { // Check if elementor is active.
			$elementor_preview_active = \Elementor\Plugin::$instance->preview->is_preview_mode(); 
		}
		if ( 'spectra-popup' === get_post_type( $this->post_id ) || $elementor_preview_active ) {
			return;
		}
		$this->enqueue_popup_scripts();
	}

	/**
	 * Generate Shortcode Content.
	 *
	 * @param array $attr   The shortcode attributes.
	 * @return string|void  The output buffer or void for early return.
	 *
	 * @since 0.0.1
	 */
	public function spectra_popup_shortcode( $attr ) {
		$attr = shortcode_atts(
			array(
				'id' => 0,
			),
			$attr,
			'spectra_popup'
		);

		if ( empty( $attr['id'] ) ) {
			return;
		}

		$popup = get_post( $attr['id'] );
		if ( empty( $popup ) ) {
			return;
		}
		
		$popup_type = get_post_meta( $attr['id'], 'spectra-popup-type', true );
		if ( 'unset' === $popup_type ) {
			return;
		}

		$popup_enabled = get_post_meta( $attr['id'], 'spectra-popup-enabled', true );
		if ( ! $popup_enabled ) {
			return;
		}

		ob_start();
		echo do_shortcode( do_blocks( $popup->post_content ) );
		$output = ob_get_clean();

		return is_string( $output ) ? $output : '';
	}

	/**
	 * Enqueue all the Spectra Popup Scripts needed on the given post.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function enqueue_popup_scripts() {
		$args   = array(
			'post_type'      => 'spectra-popup',
			'posts_per_page' => -1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'spectra-popup-enabled', // The meta key.
					'value'   => true, // The meta value to compare with.
					'compare' => '=', // The comparison type.
					'type'    => 'BOOLEAN', // The meta value type.
				),
			),
		);
		$popups = new WP_Query( $args );

		while ( $popups->have_posts() ) :
			$popups->the_post();

			$popup_id = get_the_ID();

			$render_this_popup = apply_filters( 'spectra_pro_popup_display_filters', true, $this->post_id );

			if ( $render_this_popup ) {
				$current_popup_assets = new Spectra_Post_Assets( $popup_id );
				$current_popup_assets->enqueue_scripts();
				if ( is_array( $this->popup_ids ) ) {
					array_push( $this->popup_ids, $popup_id );
				}
			}
		endwhile;
		wp_reset_postdata();
		add_action( 'wp_body_open', array( $this, 'generate_popup_shortcode' ) );
	}

	/**
	 * Generate the popup shortcodes needed.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function generate_popup_shortcode() {
		if ( is_array( $this->popup_ids ) && ! empty( $this->popup_ids ) ) {
			foreach ( $this->popup_ids as $popup_id ) {
				echo do_shortcode( '[spectra_popup id=' . esc_attr( $popup_id ) . ']' );
			}
		}
	}

	/**
	 * Adds or removes list table column headings for the Popup Builder.
	 *
	 * @param array $columns  Array of columns.
	 * @return array
	 *
	 * @since 0.0.1
	 */
	public static function popup_builder_admin_headings( $columns ) {
		unset( $columns['date'] );
		unset( $columns['author'] );

		$columns['spectra_popup_type'] = __( 'Type', 'spectra' );
		$columns['author']             = __( 'Author', 'spectra' );

		$updated_columns = apply_filters( 'spectra_pro_admin_popup_list_titles', $columns );
		if ( ! is_array( $updated_columns ) || empty( $updated_columns ) ) {
			$updated_columns = $columns;
		}

		$updated_columns['spectra_popup_toggle'] = __( 'Enable/Disable', 'spectra' );

		return $updated_columns;
	}

	/**
	 * Adds the custom list table column content for the Popup Builder.
	 *
	 * @param string $column   Name of the column.
	 * @param int    $post_id  Post id.
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function popup_builder_admin_content( $column, $post_id ) {
		switch ( $column ) {
			case 'spectra_popup_type':
				$layout = get_post_meta( $post_id, 'spectra-popup-type', true );
				if ( ! is_string( $layout ) ) {
					break;
				}
				switch ( $layout ) {
					case 'banner':
						echo esc_html__( 'Info Bar', 'spectra' );
						break;
					case 'popup':
						echo esc_html__( 'Popup', 'spectra' );
						break;
					default:
						echo esc_html__( 'Unset', 'spectra' );
						break;
				}
				break;
			case 'spectra_popup_toggle':
				$layout = get_post_meta( $post_id, 'spectra-popup-type', true );
				if ( ! is_string( $layout ) ) {
					break;
				}
				$enabled      = get_post_meta( $post_id, 'spectra-popup-enabled', true );
				$toggle_class = 'spectra-popup-builder__switch';
				if ( is_rtl() ) {
					$toggle_class .= ' is-rtl-toggle';
				}

				if ( 'unset' === $layout ) {
					$toggle_class .= ' spectra-popup-builder__switch--disabled';
				} elseif ( $enabled ) {
					$toggle_class .= ' spectra-popup-builder__switch--active';
				}

				echo '<div class="' . esc_attr( $toggle_class ) . '" data-post_id="' . esc_attr( $post_id ) . '"><span></span></div>';
				break;
			default:
				do_action( 'spectra_pro_admin_popup_list_content', $column, $post_id );
				break;
		}
	}

	/**
	 * Enqueues scripts for the Toggle Button in the Popup Table.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function popup_toggle_scripts() {

		global $pagenow;

		$screen = get_current_screen();

		if ( 'spectra-popup' === $screen->post_type && 'edit.php' === $pagenow ) {
			$extension = SCRIPT_DEBUG ? '' : '.min';
			wp_register_script(
				'uagb-popup-builder-admin-js',
				SPECTRA_URL . 'assets/js/spectra-popup-builder-admin' . $extension . '.js',
				array(),
				SPECTRA_VER,
				false
			);
			wp_register_style(
				'uagb-popup-builder-admin-css',
				SPECTRA_URL . 'assets/css/spectra-popup-builder-admin' . $extension . '.css',
				array(),
				SPECTRA_VER
			);

			wp_localize_script(
				'uagb-popup-builder-admin-js',
				'spectra_popup_builder_admin',
				array(
					'ajax_url'                       => admin_url( 'admin-ajax.php' ),
					'spectra_popup_builder_admin_nonce' => wp_create_nonce( 'spectra_popup_builder_admin_nonce' ),
				)
			);
			wp_enqueue_script( 'uagb-popup-builder-admin-js' );
			wp_enqueue_style( 'uagb-popup-builder-admin-css' );
		}
	}

	/**
	 * Update the Current Popup's Meta from Admin Table.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function update_popup_status() {
		check_ajax_referer( 'spectra_popup_builder_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! isset( $_POST['enabled'] ) || ! isset( $_POST['post_id'] ) ) {
			wp_send_json_error();
		}

		$enabled  = rest_sanitize_boolean( sanitize_text_field( $_POST['enabled'] ) );
		$popup_id = sanitize_text_field( $_POST['post_id'] );

		update_post_meta( $popup_id, 'spectra-popup-enabled', $enabled );

		wp_send_json_success();
	}
}
