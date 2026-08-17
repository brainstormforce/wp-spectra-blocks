<?php
/**
 * Spectra V3 Popup Builder Block Handler
 * Initializes and coordinates all V3 popup builder functionality
 *
 * @since 3.0.0
 * @package Spectra\Blocks
 */

namespace SpectraBlocks\Blocks; // DEV: Namespace for V3 blocks - modify if restructuring block organization.

use SpectraBlocks\Traits\Singleton; // DEV: Import singleton pattern trait - ensures single instance.
use WP_Query; // DEV: WordPress query class for fetching popup posts.

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
	 * Pre-rendered popup HTML keyed by popup ID.
	 *
	 * Populated during enqueue_popup_scripts() (which runs at wp_enqueue_scripts
	 * priority 1, before wp_head) so that every block inside a popup — including
	 * blocks that use the Interactivity API or register a viewScript — gets its
	 * assets enqueued before wp_head fires. Without pre-rendering, do_blocks()
	 * would run at wp_body_open (after wp_head) and those scripts would be missing
	 * from the page, breaking the Interactivity API and blocks like countdown.
	 *
	 * @var array<int,string> $popup_rendered_html
	 *
	 * @since 1.0.0
	 */
	protected $popup_rendered_html = array();

	/**
	 * Constructor to Default the Current Instance's Post ID and add the Shortcode if needed.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->post_id             = 0;
		$this->popup_ids           = array();
		$this->popup_rendered_html = array();
	}

	/**
	 * Get the popup IDs that will render on the current page.
	 *
	 * Populated during enqueue_popup_scripts() which runs at
	 * wp_enqueue_scripts priority 1. Used by GlobalStyles to
	 * load GS CSS for blocks inside popups.
	 *
	 * @since 1.0.0
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
		if ( 'spectra-blocks-popup' === get_post_type( $this->post_id ) || $elementor_preview_active ) { // DEV: Skip if popup post or preview - modify conditions for custom post types.
			return; // DEV: Early return to prevent recursive loading.
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
		// DEV: Core popup loading logic - modify query parameters for custom filtering.
		// Only include legacy spectra-popup posts when UAGB is not active, to avoid double-rendering.
		$post_types = array( 'spectra-blocks-popup' );
		if ( ! defined( 'UAGB_VER' ) ) {
			$post_types[] = 'spectra-popup';
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args   = array(
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'spectra-blocks-popup-enabled',
					'value'   => true,
					'compare' => '=',
					'type'    => 'BOOLEAN',
				),
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

		while ( $popups->have_posts() ) : // DEV: Loop through found popups - add additional processing here.
			$popups->the_post(); // DEV: Setup post data for current iteration.

			// Skip legacy spectra-popup posts built with uagb/ blocks — they require UAGB to render.
			if ( 'spectra-popup' === get_post_type() ) {
				$content = get_post_field( 'post_content', get_the_ID() );
				if ( false === strpos( $content, '<!-- wp:spectra/' ) ) {
					continue;
				}
			}

			$render_this_popup = apply_filters( 'spectra_pro_popup_display_filters_v3', true, $this->post_id );

			$popup_id = get_the_ID();

			if ( $render_this_popup ) {
				if ( is_array( $this->popup_ids ) ) {
					array_push( $this->popup_ids, $popup_id );
				}
			}

		endwhile;
		wp_reset_postdata();

		// Pre-render each popup now (still inside wp_enqueue_scripts priority 1,
		// before wp_head fires). do_blocks() triggers each block's render_callback,
		// which calls wp_enqueue_style() / wp_enqueue_script() for block assets —
		// including Interactivity API view scripts. Enqueueing here ensures those
		// assets land in <head> rather than being printed too late in wp_footer.
		foreach ( $this->popup_ids as $popup_id ) {
			$popup = get_post( $popup_id );
			if ( ! $popup instanceof \WP_Post ) {
				continue;
			}
			if ( 'publish' !== $popup->post_status ) {
				continue;
			}
			$this->popup_rendered_html[ $popup_id ] = do_blocks( $popup->post_content );
		}

		// The pre-rendering above may have enqueued 'spectra-responsive-styles' via
		// wp_add_inline_style() calls inside popup block render callbacks.
		// If that handle is printed in <head>, any CSS added by page blocks (which
		// render AFTER wp_head fires, during the_content()) is silently discarded.
		// Fix: dequeue it now so it misses <head>. Page blocks will re-enqueue it
		// during template rendering. A wp_footer action pins re-enqueueing in case
		// no page blocks need responsive CSS. Either way, popup + page CSS accumulate
		// in the handle's inline data and are output together in the footer.
		wp_dequeue_style( 'spectra-responsive-styles' );
		add_action(
			'wp_footer',
			static function () {
				if ( wp_style_is( 'spectra-responsive-styles', 'registered' ) ) {
					wp_enqueue_style( 'spectra-responsive-styles' );
				}
			},
			1
		);

		add_action( 'wp_body_open', array( $this, 'generate_popup_shortcode' ) );
	}

	/**
	 * Output the pre-rendered popup HTML at wp_body_open.
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function generate_popup_shortcode() {
		foreach ( $this->popup_ids as $popup_id ) {
			if ( isset( $this->popup_rendered_html[ $popup_id ] ) ) {
				echo $this->popup_rendered_html[ $popup_id ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block HTML
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
				$popup_contents[]         = do_shortcode( '[spectra_blocks_popup id=' . esc_attr( $popup_id ) . ']' );
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

		// Use the correct meta key based on which CPT the popup belongs to.
		// spectra-popup posts (created in UAGB beta) use spectra-popup-enabled.
		$meta_key = 'spectra-popup' === get_post_type( $popup_id )
			? 'spectra-popup-enabled'
			: 'spectra-blocks-popup-enabled';

		update_post_meta( $popup_id, $meta_key, $enabled );

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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_popup_cpt' ) );
		add_action( 'init', array( $this, 'register_popup_shortcode' ) );
	}

	/**
	 * Register the [spectra_blocks_popup id=N] shortcode used to render a popup
	 * on the frontend. Without this handler, do_shortcode() returns the
	 * literal shortcode string and popups never render.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_popup_shortcode() {
		add_shortcode( 'spectra_blocks_popup', array( $this, 'render_popup_shortcode' ) );
	}

	/**
	 * Render a popup post by ID via blocks API.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string Rendered popup HTML or empty string when invalid.
	 */
	public function render_popup_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			is_array( $atts ) ? $atts : array(),
			'spectra_blocks_popup'
		);

		$popup_id = absint( $atts['id'] );

		if ( $popup_id <= 0 ) {
			return '';
		}

		$popup = get_post( $popup_id );

		if ( ! $popup || ! in_array( $popup->post_type, array( 'spectra-blocks-popup', 'spectra-popup' ), true ) ) {
			return '';
		}

		// Only render published popups. Avoid leaking drafts/private to visitors.
		if ( 'publish' !== $popup->post_status ) {
			return '';
		}

		// Render the popup post content through the blocks pipeline so the
		// spectra/popup-builder block (and any inner blocks) execute their
		// render callbacks and produce the final HTML.
		return do_blocks( $popup->post_content );
	}

	/**
	 * Register the spectra-popup custom post type and its post meta.
	 *
	 * Mirrors the registration done by UAG for backward compatibility with
	 * existing popup content and meta keys.
	 *
	 * @since 1.0.0
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
				'slug'       => 'spectra-blocks-popup',
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

		// Guard against duplicate registration when UAGB is also active.
		if ( ! post_type_exists( 'spectra-blocks-popup' ) ) {
			register_post_type( 'spectra-blocks-popup', $type_args );
		}

		// When UAGB (beta) is NOT active, register spectra-popup CPT ourselves so
		// popups created in the beta remain visible and editable in spectra-blocks.
		// They use the same spectra/popup-builder block — only the CPT slug differs.
		if ( ! post_type_exists( 'spectra-popup' ) ) {
			$beta_args           = $type_args;
			$beta_args['labels'] = array_merge(
				$labels,
				array(
					'name'          => _x( 'Popup Builder', 'plural', 'spectra-blocks' ),
					'singular_name' => _x( 'Spectra Popup', 'singular', 'spectra-blocks' ),
				)
			);
			// Keep the admin template the same block.
			$beta_args['rewrite'] = array(
				'slug'       => 'spectra-popup',
				'with-front' => false,
				'pages'      => false,
			);
			register_post_type( 'spectra-popup', $beta_args );
		}

		register_post_meta(
			'spectra-blocks-popup',
			'spectra-blocks-popup-type',
			array(
				'single'        => true,
				'type'          => 'string',
				'default'       => 'unset',
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		register_post_meta(
			'spectra-blocks-popup',
			'spectra-blocks-popup-enabled',
			array(
				'single'        => true,
				'type'          => 'boolean',
				'default'       => false,
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		register_post_meta(
			'spectra-blocks-popup',
			'spectra-blocks-popup-repetition',
			array(
				'single'        => true,
				'type'          => 'number',
				'default'       => 1,
				'auth_callback' => '__return_true',
				'show_in_rest'  => true,
			)
		);

		// Register meta for spectra-popup (UAGB beta) posts so the editor and
		// REST API can read/write them when UAGB is no longer active.
		$beta_meta_types = array(
			'spectra-popup-type'       => 'string',
			'spectra-popup-enabled'    => 'boolean',
			'spectra-popup-repetition' => 'integer',
		);
		foreach ( $beta_meta_types as $beta_meta => $beta_type ) {
			register_post_meta(
				'spectra-popup',
				$beta_meta,
				array(
					'single'        => true,
					'type'          => $beta_type,
					'auth_callback' => '__return_true',
					'show_in_rest'  => true,
				)
			);
		}

		/**
		 * Fires after the spectra-popup CPT and its post meta are registered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'spectra_blocks_register_popup_meta' );
	}

	/**
	 * Enqueues scripts for the Toggle Button in the Popup Table.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function popup_toggle_scripts() {

		global $pagenow;

		$screen = get_current_screen();

		$popup_post_types = array( 'spectra-blocks-popup', 'spectra-popup' );
		if ( in_array( $screen->post_type, $popup_post_types, true ) && 'edit.php' === $pagenow ) {
			// Notice suppression only applies to our own CPT — never touch UAGB's popup page.
			if ( 'spectra-blocks-popup' === $screen->post_type ) {
				// Suppress third-party admin notices on the popup list page so they don't clutter the UI.
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );

				// Re-add our own pro upgrade notice after clearing all others.
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$installed_plugins = get_plugins();
				if ( ! isset( $installed_plugins['spectra-blocks-pro/spectra-blocks-pro.php'] ) ) {
					wp_enqueue_style(
						'spectra-blocks-popup-upgrade-pro-css',
						SPECTRA_BLOCKS_URL . 'assets/css/spectra-popup-builder-upgrade-pro.css',
						array(),
						SPECTRA_BLOCKS_VER
					);
					add_action( 'admin_notices', array( $this, 'render_pro_upgrade_notice' ) );
				}
			}

			$extension = SCRIPT_DEBUG ? '' : '.min'; // DEV: Use minified files in production - check SCRIPT_DEBUG constant.
			wp_register_script( // DEV: Register admin JavaScript file - update path/version as needed.
				'spectra-blocks-popup-builder-admin-js', // DEV: Script handle - update if handle name changes.
				SPECTRA_BLOCKS_URL . 'assets/js/spectra-popup-builder-admin' . $extension . '.js', // DEV: Script file path - update if file location changes.
				array(), // DEV: Script dependencies - add jQuery, wp-util, etc. if needed.
				SPECTRA_BLOCKS_VER, // DEV: Script version for cache busting - update with plugin version.
				false // DEV: Load in header (false) or footer (true) - change to true for footer loading.
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
	} // DEV: End of popup_toggle_scripts method.

	/**
	 * Add custom columns to the spectra-popup admin list table.
	 *
	 * Inserts the Status (toggle) column right after the Title column so
	 * the popup list does not look like a default WordPress CPT list and
	 * exposes the enable/disable toggle that the admin JS wires up.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $columns Existing list table columns.
	 * @return array<string,string> Modified columns.
	 */
	public function add_popup_columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		// Match the spectra-classic / UAGB column layout: drop date,
		// reposition author after type, expose pro-injected columns via
		// a filter, and put the enable/disable toggle last.
		unset( $columns['date'], $columns['author'] );

		$columns['spectra_blocks_popup_type'] = __( 'Type', 'spectra-blocks' );
		$columns['author']                    = __( 'Author', 'spectra-blocks' );

		/**
		 * Filters the columns shown on the spectra-popup admin list table.
		 *
		 * Pro extensions hook here to add a Display Conditions / Trigger
		 * column before the enable/disable toggle.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string,string> $columns Current columns.
		 */
		$columns = apply_filters( 'spectra_blocks_popup_admin_columns', $columns );

		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		$columns['spectra_blocks_popup_toggle'] = __( 'Enable/Disable', 'spectra-blocks' );

		return $columns;
	}

	/**
	 * Render the value for our custom popup admin columns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Current row's post ID.
	 * @return void
	 */
	public function render_popup_column( $column, $post_id ) {
		$post_id   = absint( $post_id );
		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, array( 'spectra-blocks-popup', 'spectra-popup' ), true ) ) {
			return;
		}

		// For spectra-popup (UAGB beta), use the UAGB meta keys.
		$is_beta = 'spectra-popup' === $post_type;

		// Resolve meta key prefix based on CPT origin.
		$type_key    = $is_beta ? 'spectra-popup-type' : 'spectra-blocks-popup-type';
		$enabled_key = $is_beta ? 'spectra-popup-enabled' : 'spectra-blocks-popup-enabled';

		switch ( $column ) {
			case 'spectra_blocks_popup_toggle':
				$type    = get_post_meta( $post_id, $type_key, true );
				$enabled = (bool) get_post_meta( $post_id, $enabled_key, true );

				$classes = 'spectra-popup-builder__switch';
				if ( is_rtl() ) {
					$classes .= ' is-rtl-toggle';
				}

				// A popup with no chosen layout cannot be enabled.
				if ( 'unset' === $type || empty( $type ) ) {
					$classes .= ' spectra-popup-builder__switch--disabled';
				} elseif ( $enabled ) {
					$classes .= ' spectra-popup-builder__switch--active';
				}

				printf(
					'<div class="%1$s" data-post_id="%2$d" role="switch" aria-checked="%3$s" aria-label="%4$s"><span></span></div>',
					esc_attr( $classes ),
					absint( $post_id ),
					$enabled && 'unset' !== $type && ! empty( $type ) ? 'true' : 'false',
					esc_attr__( 'Toggle popup status', 'spectra-blocks' )
				);
				break;

			case 'spectra_blocks_popup_type':
				$type = get_post_meta( $post_id, $type_key, true );

				if ( ! is_string( $type ) ) {
					break;
				}

				switch ( $type ) {
					case 'banner':
						echo esc_html__( 'Info Bar', 'spectra-blocks' );
						break;
					case 'popup':
						echo esc_html__( 'Popup', 'spectra-blocks' );
						break;
					default:
						echo esc_html__( 'Unset', 'spectra-blocks' );
						break;
				}
				break;

			default:
				/**
				 * Fires when an unknown popup admin column is rendered so pro
				 * extensions can output their own column values (e.g. trigger,
				 * display conditions).
				 *
				 * @since 1.0.0
				 *
				 * @param string $column  Column slug being rendered.
				 * @param int    $post_id Current row's post ID.
				 */
				do_action( 'spectra_blocks_render_popup_admin_column', $column, $post_id );
				break;
		}
	}
	/**
	 * Include spectra-popup (UAGB beta) posts in the spectra-blocks-popup admin list.
	 *
	 * Popups created in the UAGB beta plugin use the spectra-popup CPT. Since
	 * spectra-blocks is the standalone successor they should be fully manageable
	 * here — same block, different plugin load path.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Query $query The current query.
	 * @return void
	 */
	public function include_beta_popups_in_admin_list( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'spectra-blocks-popup' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Avoid setting post_type to an array — WordPress core passes it to esc_attr()
		// which triggers "Array to string conversion" warnings. Use a SQL WHERE filter instead.
		add_filter( 'posts_where', array( $this, 'include_beta_popups_where' ) );
	}

	/**
	 * Extend the WHERE clause to include spectra-popup (UAGB beta) posts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $where The WHERE clause.
	 * @return string Modified WHERE clause.
	 */
	public function include_beta_popups_where( $where ) {
		global $wpdb;
		remove_filter( 'posts_where', array( $this, 'include_beta_popups_where' ) );
		$where = preg_replace(
			"/{$wpdb->posts}\.post_type\s*=\s*'spectra-blocks-popup'/",
			"{$wpdb->posts}.post_type IN ('spectra-blocks-popup', 'spectra-popup')",
			$where
		);
		return $where;
	}
	/**
	 * Render the "upgrade to pro" notice on the Popup Builder CPT list page.
	 *
	 * Shown only when spectra-blocks-pro is not installed.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function render_pro_upgrade_notice() {
		$image_path   = SPECTRA_BLOCKS_URL . 'admin/assets/images/uag-logo.svg';
		$base_url     = defined( 'SPECTRA_BLOCKS_URI' ) ? SPECTRA_BLOCKS_URI : 'https://wpspectra.com/';
		$upgrade_url  = add_query_arg(
			array(
				'utm_source'   => 'free-plugin',
				'utm_medium'   => 'popup-builder',
				'utm_campaign' => 'popup-builder-banner',
			),
			trailingslashit( $base_url ) . 'pricing/'
		);
		$filtered_url = apply_filters( 'spectra_blocks_get_pro_url', $upgrade_url );
		$upgrade_url  = esc_url( is_string( $filtered_url ) ? $filtered_url : $upgrade_url );
		?>
		<div id="spectra-blocks-popup-pro-note" class="notice notice-info is-dismissible">
			<div class="astra-notice-container" style="display:flex;align-items:flex-start;gap:12px;padding:8px 0;">
				<div class="notice-image">
					<img src="<?php echo esc_url( $image_path ); ?>" style="max-width:40px;" alt="Spectra Blocks" />
				</div>
				<div class="notice-content">
					<div class="notice-heading">
						<strong><?php esc_html_e( 'Want to do more with Popup Builder?', 'spectra-blocks' ); ?></strong>
					</div>
					<p><?php esc_html_e( 'Maximize your popup potential with Spectra Blocks Pro. Unlock enhanced features, intuitive design options, and increased conversions!', 'spectra-blocks' ); ?></p>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="spectra-blocks-review-notice button-primary" target="_blank" rel="noreferrer noopener">
						<?php esc_html_e( 'Upgrade Now', 'spectra-blocks' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
} // DEV: End of PopupBuilder class - add new methods above this closing brace.
