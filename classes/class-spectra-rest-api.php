<?php
/**
 * Spectra Rest API.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Rest_API' ) ) {

	/**
	 * Class Spectra_Rest_API.
	 */
	final class Spectra_Rest_API {

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
		 */
		public function __construct() {

			// Activation hook.
			add_action( 'rest_api_init', array( $this, 'blocks_register_rest_fields' ) );
			add_action( 'init', array( $this, 'register_rest_orderby_fields' ) );
			add_filter( 'register_post_type_args', array( $this, 'add_cpts_to_api' ), 10, 2 );

			// We have added this action here to support both the ways of post updations, Rest API & Normal.
			add_action( 'save_post', array( 'Spectra_Helper', 'delete_page_assets' ), 10, 1 );
			// Adding this action to delete post assets if the post is moved to trash.
			add_action( 'wp_trash_post', array( $this, 'delete_page_assets_on_trash' ) );
			global $wp_customize;
			if ( $wp_customize ) { // Check whether the $wp_customize is set.
				add_filter( 'render_block_data', array( $this, 'content_pre_render' ) ); // Add a inline style for block when it rendered in customizer.
				add_action( 'customize_save', array( $this, 'after_widget_save_action' ) ); // Update the assets on customizer save/publish.
			} else {
				add_action( 'rest_after_save_widget', array( $this, 'after_widget_save_action' ) ); // Update the assets on widget save.
			}

		}

		/**
		 * Function to delete post assets.
		 *
		 * @param int $post_id post_id of deleted post.
		 * @since 0.0.1
		 * @return void 
		 */
		public function delete_page_assets_on_trash( $post_id ) {
			
				$css_asset_info = Spectra_Scripts_Utils::get_asset_info( 'css', $post_id );
				$js_asset_info  = Spectra_Scripts_Utils::get_asset_info( 'js', $post_id );

				$css_file_path = $css_asset_info['css'];
				$js_file_path  = $js_asset_info['js'];

			if ( file_exists( $css_file_path ) ) {
				wp_delete_file( $css_file_path );
			}
			if ( file_exists( $js_file_path ) ) {
				wp_delete_file( $js_file_path );
			}
		}

		/**
		 * Function to load assets for post/page in customizer before gutenberg rendering.
		 *
		 * @param array $block Block data.
		 *
		 * @since 0.0.1
		 *
		 * @return array New block data.
		 */
		public function content_pre_render( $block ) {
			$tab_styling_css  = '';
			$mob_styling_css  = '';
			$Spectra_Post_Assets = new Spectra_Post_Assets( get_the_ID() );

			$assets = $Spectra_Post_Assets->get_block_css_and_js( $block );

			$desktop_css = isset( $assets['css']['desktop'] ) ? $assets['css']['desktop'] : '';

			if ( ! empty( $assets['css']['tablet'] ) ) {
				$tab_styling_css .= '@media only screen and (max-width: ' . SPECTRA_TABLET_BREAKPOINT . 'px) {';
				$tab_styling_css .= $assets['css']['tablet'];
				$tab_styling_css .= '}';
			}

			if ( ! empty( $assets['css']['mobile'] ) ) {
				$mob_styling_css .= '@media only screen and (max-width: ' . SPECTRA_MOBILE_BREAKPOINT . 'px) {';
				$mob_styling_css .= $assets['css']['mobile'];
				$mob_styling_css .= '}';
			}

			$block_css_style = $desktop_css . $tab_styling_css . $mob_styling_css;

			if ( empty( $block_css_style ) || empty( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
				return $block;
			}

			// This line of code creates a new array named $font_family_attrs by searching through the keys of an existing array.
			$font_family_attrs = preg_grep( '/fontfamily/i', array_keys( $block['attrs'] ) );
			$link_tag_list     = '';

			if ( ! empty( $font_family_attrs ) && is_array( $font_family_attrs ) ) {
				foreach ( $font_family_attrs as $attr ) {
					if ( ! empty( $block['attrs'][ $attr ] ) ) {
						// Get the font family value and construct the Google Fonts URL.
						$gfont_url = 'https://fonts.googleapis.com/css?family=' . urlencode( $block['attrs'][ $attr ] );
						// Create a link tag for the stylesheet with the constructed URL.
						$link_tag_list .= '<link rel="stylesheet" href="' . esc_url( $gfont_url ) . '" media="all">';
					}
				}
			}

				$style = '<style class="uagb-widgets-style-renderer">' . $block_css_style . '</style>';
				$style = $style . $link_tag_list;

				array_push( $block['innerContent'], $style );

			return $block;
		}

		/**
		 * This function updates the __uagb_asset_version when Widgets Editor is Updated.
		 *
		 * @since 0.0.1
		 */
		public function after_widget_save_action() {
			/* Update the asset version */
			update_option( '__uagb_asset_version', time() );
		}

		/**
		 * Create API fields for additional info
		 *
		 * @since 0.0.1
		 */
		public function blocks_register_rest_fields() {
			$post_type = Spectra_Helper::get_post_types();

			foreach ( $post_type as $key => $value ) {
				// Add featured image source.
				register_rest_field(
					$value['value'],
					'spectra_featured_image_src',
					array(
						'get_callback'    => array( $this, 'get_image_src' ),
						'update_callback' => null,
						'schema'          => null,
					)
				);

				// Add author info.
				register_rest_field(
					$value['value'],
					'spectra_author_info',
					array(
						'get_callback'    => array( $this, 'get_author_info' ),
						'update_callback' => null,
						'schema'          => null,
					)
				);

				// Add comment info.
				register_rest_field(
					$value['value'],
					'spectra_comment_info',
					array(
						'get_callback'    => array( $this, 'get_comment_info' ),
						'update_callback' => null,
						'schema'          => null,
					)
				);

				// Add excerpt info.
				register_rest_field(
					$value['value'],
					'spectra_excerpt',
					array(
						'get_callback'    => array( $this, 'get_excerpt' ),
						'update_callback' => null,
						'schema'          => null,
					)
				);

			}

			register_rest_route(
				'spectra/v1',
				'all_taxonomy',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_related_taxonomy' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => array(),
					),
				)
			);

			register_rest_route(
				'spectra/v1',
				'editor',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'spectra_initial_states' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => array(),
					),
				)
			);

			register_rest_route(
				'spectra/v1',
				'check-custom-fields-support',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'check_custom_fields_support' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => array(
							'post_type' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => function( $param, $request, $key ) {
									if ( ! is_string( $param ) ) {
										return false;
									}
									// Verify post type exists.
									return post_type_exists( $param );
								},
								'description'       => __( 'Post type to check for custom fields support.', 'spectra' ),
							),
						),
					),
				)
			);
		}

		/**
		 * Get Initial States.
		 *
		 * @since 0.0.1
		 * @return array
		 */
		public function spectra_initial_states() {

			$response = array_merge( 
				// For GBS initial states.
				$this->get_gbs_initial_states(),
				// For quick action sidebar.
				$this->get_quick_action_bar_initial_states()
			);

			return $response;
		}

		/**
		 * Get Quick Action Bar Initial States.
		 *
		 * @since 0.0.1
		 * @return array
		 */
		public function get_quick_action_bar_initial_states() {
			// Get value from DB for Quick Action Bar.
			$db_value                            = \Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_quick_action_sidebar' );
			$show_enable                         = ( empty( $db_value ) ) ? 'enabled' : $db_value;
			$spectra_enable_quick_action_sidebar = \Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_quick_action_sidebar', $show_enable );

			$spectra_default_allowed_quick_sidebar_blocks = \Spectra_Admin_Helper::get_admin_settings_option(
				'spectra_quick_sidebar_allowed_blocks',
				array()
			);

			if ( empty( $spectra_default_allowed_quick_sidebar_blocks ) ) {
				$spectra_default_allowed_quick_sidebar_blocks = array(
					'uagb/container',
					'uagb/advanced-heading',
					'uagb/image',
					'uagb/icon',
					'uagb/buttons',
					'uagb/info-box',
					'uagb/call-to-action',
				);
			}
			
			$initial_state = array(
				'uag_enable_quick_action_sidebar'   => $spectra_enable_quick_action_sidebar,
				'spectra_quick_sidebar_allowed_blocks' => $spectra_default_allowed_quick_sidebar_blocks,
			);

			return $initial_state;
		}

		/**
		 * Get GBS Initial States.
		 *
		 * @since 0.0.1
		 * @return array
		 */
		public function get_gbs_initial_states() {
			// check if GBS is enabled or not.
			if ( 'enabled' !== \Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_gbs_extension', 'enabled' ) ) {
				return array();
			}

			$spectra_global_block_styles = get_option(
				'spectra_global_block_styles',
				array(
					array(
						'value' => '',
						'label' => __( 'None', 'spectra' ),
					),
				) 
			);
			
			$spectra_gbs_google_fonts_editor = get_option(
				'spectra_gbs_google_fonts_editor',
				array()
			);


			if ( empty( $spectra_global_block_styles ) ) {
				$spectra_global_block_styles = array(
					array(
						'value' => '',
						'label' => __( 'None', 'spectra' ),
					),
				);
			}

			$initial_state = array(
				'spectra_global_block_styles'     => $spectra_global_block_styles,
				'spectra_gbs_google_fonts_editor' => $spectra_gbs_google_fonts_editor,
			);

			return $initial_state;
		}

		/**
		 * Get all taxonomies.
		 *
		 * @since 0.0.1
		 * @access public
		 */
		public function get_related_taxonomy() {

			$post_types = self::get_post_types();

			$return_array = array();

			foreach ( $post_types as $key => $value ) {
				$post_type = $value['value'];

				$taxonomies = get_object_taxonomies( $post_type, 'objects' );
				$data       = array();

				foreach ( $taxonomies as $tax_slug => $tax ) {
					if ( ! $tax->public || ! $tax->show_ui || ! $tax->show_in_rest ) {
						continue;
					}

					// SECURITY: Only return necessary sanitized fields instead of entire object.
					$data[ $tax_slug ] = array(
						'name'          => sanitize_text_field( $tax->name ),
						'label'         => sanitize_text_field( $tax->label ),
						'singular_name' => isset( $tax->labels->singular_name ) ? sanitize_text_field( $tax->labels->singular_name ) : sanitize_text_field( $tax->label ),
						'hierarchical'  => (bool) $tax->hierarchical,
						'public'        => (bool) $tax->public,
						'show_in_rest'  => (bool) $tax->show_in_rest,
					);

					$terms = get_terms( $tax_slug );

					$related_tax = array();

					if ( ! empty( $terms ) ) {
						foreach ( $terms as $t_index => $t_obj ) {
							$related_tax[] = array(
								'id'    => $t_obj->term_id,
								'name'  => $t_obj->name,
								'child' => get_term_children( $t_obj->term_id, $tax_slug ),
							);
						}
						$return_array[ $post_type ]['terms'][ $tax_slug ] = $related_tax;
					}
				}

				$return_array[ $post_type ]['taxonomy'] = $data;

			}

			return apply_filters( 'spectra_post_loop_taxonomies', $return_array );
		}

		/**
		 * Get Post Types.
		 *
		 * @since 0.0.1
		 * @access public
		 */
		public static function get_post_types() {

			$post_types = get_post_types(
				array(
					'public'       => true,
					'show_in_rest' => true,
				),
				'objects'
			);

			$options = array();

			foreach ( $post_types as $post_type ) {

				if ( 'attachment' === $post_type->name ) {
					continue;
				}

				$options[] = array(
					'value' => $post_type->name,
					'label' => $post_type->label,
				);
			}

			return apply_filters( 'spectra_loop_post_types', $options );
		}
		/**
		 * Check whether a given request has permission to read notes.
		 *
		 * SECURITY: Enhanced with context-aware permission checks.
		 *
		 * @param  WP_REST_Request $request Full details about the request.
		 * @return WP_Error|boolean
		 * @since 0.0.1 Added context-aware permissions.
		 */
		public function get_items_permissions_check( $request ) {

			// Determine required capability based on endpoint context.
			$route = $request->get_route();
			$required_cap = 'edit_posts'; // Default for most endpoints.

			// Endpoints that expose site-wide data require higher permissions.
			$elevated_endpoints = array(
				'/spectra/v1/all_taxonomy',
				'/spectra/v1/editor',
				'/spectra/v1/get_recaptcha_keys',
			);

			foreach ( $elevated_endpoints as $endpoint ) {
				if ( false !== strpos( $route, $endpoint ) ) {
					$required_cap = 'edit_others_posts'; // Higher permission for site-wide data.
					break;
				}
			}

			/**
			 * Filter the required capability for REST API endpoints.
			 *
			 * @since 0.0.1
			 *
			 * @param string           $required_cap Required capability.
			 * @param WP_REST_Request  $request      REST request object.
			 */
			$required_cap = apply_filters( 'spectra_rest_required_capability', $required_cap, $request );

			if ( ! current_user_can( $required_cap ) ) {
				Spectra_Security_Helper::log_security_event(
					'rest_permission_denied',
					array(
						'route'        => $route,
						'required_cap' => $required_cap,
						'user_id'      => get_current_user_id(),
					)
				);
				return new \WP_Error( 'uag_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'spectra' ), array( 'status' => rest_authorization_required_code() ) );
			}

			return true;
		}

		/**
		 * Get featured image source for the rest field as per size
		 *
		 * @param object $object Post Object.
		 * @param string $field_name Field name.
		 * @param object $request Request Object.
		 * @since 0.0.1
		 */
		public function get_image_src( $object, $field_name, $request ) {
			$image_sizes = Spectra_Helper::get_image_sizes();

			$featured_images = array();

			if ( ! isset( $object['featured_media'] ) ) {
				return $featured_images;
			}

			foreach ( $image_sizes as $key => $value ) {
				$size = $value['value'];

				$featured_images[ $size ] = wp_get_attachment_image_src(
					$object['featured_media'],
					$size,
					false
				);
			}

			return $featured_images;
		}

		/**
		 * Get author info for the rest field
		 *
		 * @param object $object Post Object.
		 * @param string $field_name Field name.
		 * @param object $request Request Object.
		 * @since 0.0.1
		 */
		public function get_author_info( $object, $field_name, $request ) {

			$author = ( isset( $object['author'] ) ) ? $object['author'] : '';

			// Get the author name.
			$author_data['display_name'] = get_the_author_meta( 'display_name', $author );

			// Get the author link.
			$author_data['author_link'] = get_author_posts_url( $author );

			// Return the author data.
			return $author_data;
		}

		/**
		 * Get comment info for the rest field
		 *
		 * @param object $object Post Object.
		 * @param string $field_name Field name.
		 * @param object $request Request Object.
		 * @since 0.0.1
		 */
		public function get_comment_info( $object, $field_name, $request ) {
			// Get the comments link.
			$comments_count = wp_count_comments( $object['id'] );
			return $comments_count->total_comments;
		}

		/**
		 * Get excerpt for the rest field
		 *
		 * @param object $object Post Object.
		 * @param string $field_name Field name.
		 * @param object $request Request Object.
		 * @since 0.0.1
		 */
		public function get_excerpt( $object, $field_name, $request ) {
			$excerpt = wp_trim_words( get_the_excerpt( $object['id'] ) );
			if ( ! $excerpt ) {
				$excerpt = null;
			}
			return $excerpt;
		}

		/**
		 * Create API Order By Fields
		 *
		 * @since 0.0.1
		 */
		public function register_rest_orderby_fields() {
			$post_type = Spectra_Helper::get_post_types();

			foreach ( $post_type as $key => $type ) {
				add_filter( "rest_{$type['value']}_collection_params", array( $this, 'add_orderby' ), 10, 1 );
			}
		}

		/**
		 * Adds Order By values to Rest API
		 *
		 * @param object $params Parameters.
		 * @since 0.0.1
		 */
		public function add_orderby( $params ) {

			$params['orderby']['enum'][] = 'rand';
			$params['orderby']['enum'][] = 'menu_order';

			return $params;
		}

		/**
		 * Adds the Contect Form 7 Custom Post Type to REST.
		 *
		 * @param array  $args Array of arguments.
		 * @param string $post_type Post Type.
		 * @since 0.0.1
		 */
		public function add_cpts_to_api( $args, $post_type ) {
			if ( 'wpcf7_contact_form' !== $post_type || ! is_admin() ) {
				return $args; // Don't change anything for other post types.
			}

			// Modify args only for wpcf7_contact_form.
			$args['show_in_rest'] = true;
			return $args;
		}

		/**
		 * Supported arguments to check if the given post type supports custom fields.
		 *
		 * @since 0.0.1
		 * @return array The array of supported arguments.
		 */
		public function check_custom_fields_support_args() {
			$args = array();

			$args['post_type'] = array(
				'type'     => 'string',
				'required' => false,
			);

			return $args;
		}

		/**
		 * Checks if the given post type supports custom fields.
		 *
		 * @param WP_REST_Request $request All the details about the request.
		 * @since 0.0.1
		 * @return WP_REST_Response The response.
		 */
		public function check_custom_fields_support( $request ) {
			$post_type = $request->get_param( 'post_type' );

			// If the post type was not passed, abandon ship.
			if ( empty( $post_type ) || ! is_string( $post_type ) ) {
				$response = new \WP_REST_Response(
					array(
						'success' => false,
					)
				);
				$response->set_status( 400 );
				return $response;
			}

			// Sanitize the post type, and check if the post type supports custom fields.
			$post_type              = sanitize_text_field( $post_type );
			$supports_custom_fields = post_type_supports( $post_type, 'custom-fields' );

			// Return the successful response, with whether or not custom fields is supported.
			$response = new \WP_REST_Response(
				array(
					'success'                => true,
					'supports_custom_fields' => $supports_custom_fields,
				)
			);
			$response->set_status( 200 );

			return $response;
		}
	}

	/**
	 *  Prepare if class 'Spectra_Rest_API' exist.
	 *  Kicking this off by calling 'get_instance()' method
	 */
	Spectra_Rest_API::get_instance();
}
