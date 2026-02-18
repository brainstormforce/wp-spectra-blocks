<?php
/**
 * Spectra Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Spectra_Init_Blocks.
 *
 * @package Spectra
 */
class Spectra_Init_Blocks {


	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * Member Variable
	 *
	 * @var block activation
	 */
	private $active_blocks;

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

		// Hook: Editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );

		// Legacy block category registration removed — handled by v3 BlockManager.
		// Legacy uagb AJAX handlers removed — handled by legacy plugin.

		if ( ! is_admin() ) {
			add_action( 'render_block', array( $this, 'render_block' ), 5, 2 );
		}

		if ( current_user_can( 'edit_posts' ) ) {
			add_action( 'wp_ajax_uagb_svg_confirmation', array( $this, 'confirm_svg_upload' ) );
		}

		add_action( 'init', array( $this, 'register_popup_builder' ) );
		add_filter( 'srfm_enable_redirect_activation', '__return_false' );

		add_action( 'wp_ajax_uagb_sureforms', array( $this, 'sureforms_plugin_activator' ) );
		add_action( 'wp_ajax_uagb_surecart', array( $this, 'surecart_plugin_activator' ) );

	}


	/**
	 * Register the Popup Builder CPT.
	 *
	 * @return void
	 *
	 * @since 0.0.1
	 */
	public function register_popup_builder() {
		$supports = array(
			'title',
			'editor',
			'custom-fields',
			'author',
		);

		$labels = array(
			'name'               => _x( 'Popup Builder', 'plural', 'spectra' ),
			'singular_name'      => _x( 'Spectra Popup', 'singular', 'spectra' ),
			'view_item'          => __( 'View Popup', 'spectra' ),
			'add_new'            => __( 'Create Popup', 'spectra' ),
			'add_new_item'       => __( 'Create New Popup', 'spectra' ),
			'edit_item'          => __( 'Edit Popup', 'spectra' ),
			'new_item'           => __( 'New Popup', 'spectra' ),
			'search_items'       => __( 'Search Popups', 'spectra' ),
			'not_found'          => __( 'No Popups Found', 'spectra' ),
			'not_found_in_trash' => __( 'No Popups in Trash', 'spectra' ),
			'all_items'          => __( 'All Popups', 'spectra' ),
			'item_published'     => __( 'Popup Published', 'spectra' ),
			'item_updated'       => __( 'Popup Updated', 'spectra' ),
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

		$meta_args_popup_type = array(
			'single'        => true,
			'type'          => 'string',
			'default'       => 'unset',
			'auth_callback' => '__return_true',
			'show_in_rest'  => true,
		);

		$meta_args_popup_enabled = array(
			'single'        => true,
			'type'          => 'boolean',
			'default'       => false,
			'auth_callback' => '__return_true',
			'show_in_rest'  => true,
		);

		$meta_args_popup_repetition = array(
			'single'        => true,
			'type'          => 'number',
			'default'       => 1,
			'auth_callback' => '__return_true',
			'show_in_rest'  => true,
		);

		register_post_type( 'spectra-popup', $type_args );

		register_post_meta( 'spectra-popup', 'spectra-popup-type', $meta_args_popup_type );
		register_post_meta( 'spectra-popup', 'spectra-popup-enabled', $meta_args_popup_enabled );
		register_post_meta( 'spectra-popup', 'spectra-popup-repetition', $meta_args_popup_repetition );
		do_action( 'register_spectra_pro_popup_meta' );

		$spectra_popup_dashboard = Spectra_Popup_Builder::create_for_admin();

		add_action( 'admin_enqueue_scripts', array( $spectra_popup_dashboard, 'popup_toggle_scripts' ) );
		add_action( 'wp_ajax_uag_update_popup_status', array( $spectra_popup_dashboard, 'update_popup_status' ) );

		do_action( 'spectra_pro_popup_dashboard' );

		add_filter( 'manage_spectra-popup_posts_columns', array( $spectra_popup_dashboard, 'popup_builder_admin_headings' ) );
		add_action( 'manage_spectra-popup_posts_custom_column', array( $spectra_popup_dashboard, 'popup_builder_admin_content' ), 10, 2 );
	}

	/**
	 * Render block.
	 *
	 * @param mixed $block_content The block content.
	 * @param array $block The block data.
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function render_block( $block_content, $block ) {
		// Legacy uagb/ runtime block registration removed — handled by legacy plugin.

		if ( ! empty( $block['attrs']['UAGDisplayConditions'] ) ) {
			switch ( $block['attrs']['UAGDisplayConditions'] ) {
				case 'userstate':
					$block_content = $this->user_state_visibility( $block['attrs'], $block_content );
					break;

				case 'userRole':
					$block_content = $this->user_role_visibility( $block['attrs'], $block_content );
					break;

				case 'browser':
					$block_content = $this->browser_visibility( $block['attrs'], $block_content );
					break;

				case 'os':
					$block_content = $this->os_visibility( $block['attrs'], $block_content );
					break;
				case 'day':
					$block_content = $this->day_visibility( $block['attrs'], $block_content );
					break;
				default:
					// code...
					break;
			}
		}

		// Check if animations extension is enabled and an animation type is selected.
		if (
			'enabled' === \Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_animations_extension', 'enabled' ) &&
			! empty( $block['attrs']['UAGAnimationType'] )
		) {

			$attrs                                      = $block['attrs'];
			$attrs['UAGAnimationDoNotApplyToContainer'] = isset( $attrs['UAGAnimationDoNotApplyToContainer'] ) ? $attrs['UAGAnimationDoNotApplyToContainer'] : false;
			$block_positioning                          = ! empty( $attrs['UAGPosition'] ) && is_string( $attrs['UAGPosition'] ) ? $attrs['UAGPosition'] : false;

			// Container-specific animation attributes.
			if ( ! $attrs['UAGAnimationDoNotApplyToContainer'] ) {
				// Defaults aren't received here, hence we set them.
				// Without these defaults, empty data is sent to markup (which doesn't affect the functionality at all but still it's a good practice to follow).
				$attrs['UAGAnimationTime']   = isset( $attrs['UAGAnimationTime'] ) ? $attrs['UAGAnimationTime'] : 400;
				$attrs['UAGAnimationDelay']  = isset( $attrs['UAGAnimationDelay'] ) ? $attrs['UAGAnimationDelay'] : 0;
				$attrs['UAGAnimationEasing'] = isset( $attrs['UAGAnimationEasing'] ) ? $attrs['UAGAnimationEasing'] : 'ease';
				$attrs['UAGAnimationRepeat'] = isset( $attrs['UAGAnimationRepeat'] ) ? 'false' : 'true';

				// Container-specific animation attributes.
				$attrs['UAGAnimationDelayInterval'] = isset( $attrs['UAGAnimationDelayInterval'] ) ? $attrs['UAGAnimationDelayInterval'] : 200;

				// If this is a sticky element, don't update the attributes of this element just yet.
				if ( 'sticky' !== $block_positioning ) {
					$aos_attributes = '<div data-aos= "' . esc_attr( $attrs['UAGAnimationType'] ) . '" data-aos-duration="' . esc_attr( $attrs['UAGAnimationTime'] ) . '" data-aos-delay="' . esc_attr( $attrs['UAGAnimationDelay'] ) . '" data-aos-easing="' . esc_attr( $attrs['UAGAnimationEasing'] ) . '" data-aos-once="' . esc_attr( $attrs['UAGAnimationRepeat'] ) . '" ';
					$block_content  = preg_replace( '/<div /', $aos_attributes, $block_content, 1 );
				}
			}
		}

		// Render Block Manipulation for the required Spectra Blocks.
		$block_content = apply_filters( 'spectra_render_block', $block_content, $block );

		// Render Block Manipulation for the required Spectra Pro Blocks.
		$block_content = apply_filters( 'spectra_pro_render_block', $block_content, $block );

		return $block_content;
	}

	/**
	 * User State Visibility.
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 *
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function user_role_visibility( $block_attributes, $block_content ) {
		if ( empty( $block_attributes['UAGUserRole'] ) ) {
			return $block_content;
		}

		$user = wp_get_current_user();
		return is_user_logged_in() && ! empty( $user->roles ) && in_array( $block_attributes['UAGUserRole'], $user->roles, true ) ? '' : $block_content;
	}

	/**
	 * User State Visibility.
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function os_visibility( $block_attributes, $block_content ) {

		if ( empty( $block_attributes['UAGSystem'] ) ) {
			return $block_content;
		}

		$os = array(
			'iphone'   => '(iPhone)',
			'android'  => '(Android)',
			'windows'  => 'Win16|(Windows 95)|(Win95)|(Windows_95)|(Windows 98)|(Win98)|(Windows NT 5.0)|(Windows 2000)|(Windows NT 5.1)|(Windows XP)|(Windows NT 5.2)|(Windows NT 6.0)|(Windows Vista)|(Windows NT 6.1)|(Windows 7)|(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)|Windows ME',
			'open_bsd' => 'OpenBSD',
			'sun_os'   => 'SunOS',
			'linux'    => '(Linux)|(X11)',
			'mac_os'   => '(Mac_PowerPC)|(Macintosh)',
		);

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';

		return isset( $os[ $block_attributes['UAGSystem'] ] ) && preg_match( '@' . $os[ $block_attributes['UAGSystem'] ] . '@', $user_agent ) ? '' : $block_content;
	}

	/**
	 * User State Visibility.
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 *
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function browser_visibility( $block_attributes, $block_content ) {

		if ( empty( $block_attributes['UAGBrowser'] ) ) {
			return $block_content;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? Spectra_Helper::get_browser_name( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return $block_attributes['UAGBrowser'] === $user_agent ? '' : $block_content;
	}

	/**
	 * User State Visibility.
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 *
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function user_state_visibility( $block_attributes, $block_content ) {

		if ( ! empty( $block_attributes['UAGLoggedIn'] ) && is_user_logged_in() ) {
			return '';
		}

		if ( ! empty( $block_attributes['UAGLoggedOut'] ) && ! is_user_logged_in() ) {
			return '';
		}

		return $block_content;

	}

	/**
	 * Day Visibility.
	 *
	 * @param array $block_attributes The block data.
	 * @param mixed $block_content The block content.
	 *
	 * @since 0.0.1
	 * @return mixed Returns the new block content.
	 */
	public function day_visibility( $block_attributes, $block_content ) {

		// If not set restriction.
		if ( empty( $block_attributes['UAGDay'] ) ) {
			return $block_content;
		}

		$current_day = strtolower( current_datetime()->format( 'l' ) );
		// Check in restricted day.
		return ! in_array( $current_day, $block_attributes['UAGDay'] ) ? $block_content : '';

	}

	/**
	 * Ajax call to get Taxonomy List.
	 *
	 * @since 0.0.1
	 */
	public function get_taxonomy() {

		$response_data = array(
			'messsage' => __( 'User is not authenticated!', 'spectra' ),
		);

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( $response_data );
		}

		check_ajax_referer( 'spectra_ajax_nonce', 'nonce' );

		$post_types = Spectra_Helper::get_post_types();

		$return_array = array();

		foreach ( $post_types as $key => $value ) {
			$post_type = $value['value'];

			$taxonomies = get_object_taxonomies( $post_type, 'objects' );
			$data       = array();

			$get_taxonomy_names = get_post_type_object( $post_type ); // Renaming this variable to follow proper naming convention.
			foreach ( $taxonomies as $tax_slug => $tax ) {
				if ( ! $tax->public || ! $tax->show_ui || ! $tax->show_in_rest ) {
					continue;
				}

				$data[ $tax_slug ] = $tax;

				$terms = get_terms( $tax_slug );

				$related_tax_terms = array();

				if ( ! empty( $terms ) ) {
					foreach ( $terms as $t_index => $t_obj ) {
						$related_tax_terms[] = array(
							'id'            => $t_obj->term_id,
							'name'          => $t_obj->name,
							'count'         => $t_obj->count,
							'link'          => get_term_link( $t_obj->term_id ),
							'singular_name' => $get_taxonomy_names ? $get_taxonomy_names->labels->singular_name : 'Post',
							'plural_name'   => $get_taxonomy_names ? $get_taxonomy_names->labels->name : 'Posts', // Adding this field to use it on the editor.
						);
					}

					$return_array[ $post_type ]['terms'][ $tax_slug ] = $related_tax_terms;
				}

				$newcategoriesList = get_terms(
					$tax_slug,
					array(
						'hide_empty' => true,
						'parent'     => 0,
					)
				);

				$related_tax = array();

				if ( ! empty( $newcategoriesList ) ) {
					foreach ( $newcategoriesList as $t_index => $t_obj ) {
						$child_arg     = array(
							'hide_empty' => true,
							'parent'     => $t_obj->term_id,
						);
						$child_cat     = get_terms( $tax_slug, $child_arg );
						$child_cat_arr = $child_cat ? $child_cat : null;
						$related_tax[] = array(
							'id'            => $t_obj->term_id,
							'name'          => $t_obj->name,
							'count'         => $t_obj->count,
							'link'          => get_term_link( $t_obj->term_id ),
							'singular_name' => $get_taxonomy_names ? $get_taxonomy_names->labels->singular_name : 'Post',
							'plural_name'   => $get_taxonomy_names ? $get_taxonomy_names->labels->name : 'Posts', // Adding this field to use it on the editor.
							'children'      => $child_cat_arr,
						);

					}

					$return_array[ $post_type ]['without_empty_taxonomy'][ $tax_slug ] = $related_tax;

				}

				$newcategoriesList_empty_tax = get_terms(
					$tax_slug,
					array(
						'hide_empty' => false,
						'parent'     => 0,
					)
				);

				$related_tax_empty_tax = array();

				if ( ! empty( $newcategoriesList_empty_tax ) ) {
					foreach ( $newcategoriesList_empty_tax as $t_index => $t_obj ) {
						$child_arg_empty_tax     = array(
							'hide_empty' => false,
							'parent'     => $t_obj->term_id,
						);
						$child_cat_empty_tax     = get_terms( $tax_slug, $child_arg_empty_tax );
						$child_cat_empty_tax_arr = $child_cat_empty_tax ? $child_cat_empty_tax : null;
						$related_tax_empty_tax[] = array(
							'id'            => $t_obj->term_id,
							'name'          => $t_obj->name,
							'count'         => $t_obj->count,
							'link'          => get_term_link( $t_obj->term_id ),
							'singular_name' => $get_taxonomy_names ? $get_taxonomy_names->labels->singular_name : 'Post',
							'plural_name'   => $get_taxonomy_names ? $get_taxonomy_names->labels->name : 'Posts', // Adding this field to use it on the editor.
							'children'      => $child_cat_empty_tax_arr,
						);
					}

					$return_array[ $post_type ]['with_empty_taxonomy'][ $tax_slug ] = $related_tax_empty_tax;

				}
			}
			$return_array[ $post_type ]['taxonomy'] = $data;

		}

		wp_send_json_success( apply_filters( 'spectra_taxonomies_list', $return_array ) );
	}

	/**
	 * Renders the Gravity Form shortcode.
	 *
	 * @since 0.0.1
	 */
	public function gf_shortcode() {

		check_ajax_referer( 'spectra_ajax_nonce', 'nonce' );

		$id = isset( $_POST['formId'] ) ? intval( $_POST['formId'] ) : 0;

		if ( $id && 0 !== $id && -1 !== $id ) {
			$data['html'] = do_shortcode( '[gravityforms id="' . $id . '" ajax="true"]' );
		} else {
			$data['html'] = '<p>' . __( 'Please select a valid Gravity Form.', 'spectra' ) . '</p>';
		}
		wp_send_json_success( $data );
	}

	/**
	 * Renders the forms recaptcha keys.
	 *
	 * @since 0.0.1
	 */
	public function forms_recaptcha() {

		$response_data = array(
			'messsage' => __( 'User is not authenticated!', 'spectra' ),
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( $response_data );
		}

		check_ajax_referer( 'spectra_ajax_nonce', 'nonce' );
		// security validation done in later stage.
		$value = isset( $_POST['value'] ) ? json_decode( wp_unslash( $_POST['value'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		\Spectra_Admin_Helper::update_admin_settings_option( 'uag_recaptcha_secret_key_v2', sanitize_text_field( $value['reCaptchaSecretKeyV2'] ) );
		\Spectra_Admin_Helper::update_admin_settings_option( 'uag_recaptcha_secret_key_v3', sanitize_text_field( $value['reCaptchaSecretKeyV3'] ) );
		\Spectra_Admin_Helper::update_admin_settings_option( 'uag_recaptcha_site_key_v2', sanitize_text_field( $value['reCaptchaSiteKeyV2'] ) );
		\Spectra_Admin_Helper::update_admin_settings_option( 'uag_recaptcha_site_key_v3', sanitize_text_field( $value['reCaptchaSiteKeyV3'] ) );

		$response_data = array(
			'messsage' => __( 'Successfully saved data!', 'spectra' ),
		);
		wp_send_json_success( $response_data );

	}

	/**
	 * Renders the Sure Form.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function sureforms_plugin_activator() {
		// Check user capability.
		if ( ! ( current_user_can( 'activate_plugins' ) && current_user_can( 'install_plugins' ) ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => 'User is not authenticated!',
				) 
			);
		}

		// Verify nonce.
		if ( ! check_ajax_referer( 'spectra_ajax_nonce', 'security', false ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => 'Invalid nonce.',
				) 
			);
		}

		$installed_plugins   = get_plugins();
		$status_of_sureforms = isset( $installed_plugins['sureforms/sureforms.php'] ) 
			? ( is_plugin_active( 'sureforms/sureforms.php' ) ? 'active' : 'inactive' ) 
			: 'not-installed';

		if ( class_exists( '\BSF_UTM_Analytics\Inc\Utils' ) && is_callable( '\BSF_UTM_Analytics\Inc\Utils::update_referer' ) ) {
			// If the plugin is found and the update_referer function is callable, update the referer with the corresponding product slug.
			\BSF_UTM_Analytics\Inc\Utils::update_referer( 'spectra', 'sureforms' );
		}

		// If plugin is not installed, install it first.
		if ( 'not-installed' === $status_of_sureforms ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$plugin_slug = 'sureforms';
			$plugin_data = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );

			// Check if $plugin_data is valid and contains the download_link property.
			if ( is_wp_error( $plugin_data ) || ! is_object( $plugin_data ) || empty( $plugin_data->download_link ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => 'Error fetching plugin data.',
					) 
				);
			}

			if ( is_object( $plugin_data ) || is_array( $plugin_data ) ) {
				$download_link = ( is_object( $plugin_data ) && isset( $plugin_data->download_link ) ) ? $plugin_data->download_link : '';
				$skin          = new WP_Ajax_Upgrader_Skin();
				$upgrader      = new Plugin_Upgrader( $skin );
				$installed     = $upgrader->install( $download_link );

				if ( is_wp_error( $installed ) ) {
					wp_send_json_error(
						array(
							'success' => false,
							'message' => 'Failed to install the plugin.',
						) 
					);
				}
			}

			$installed_plugins   = get_plugins();
			$status_of_sureforms = isset( $installed_plugins['sureforms/sureforms.php'] ) ? 'inactive' : 'not-installed';
		}

		// If the plugin is installed but inactive, activate it.
		if ( 'inactive' === $status_of_sureforms ) {
			$activate = activate_plugin( 'sureforms/sureforms.php', '', false, false );

			if ( is_wp_error( $activate ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => $activate->get_error_message(),
					) 
				);
			}

			wp_send_json_success(
				array(
					'success' => true,
					'message' => 'Plugin successfully activated.',
				) 
			);
		}

		// If already active, send a success message.
		if ( 'active' === $status_of_sureforms ) {
			wp_send_json_success(
				array(
					'success' => true,
					'message' => 'Plugin is already active.',
				) 
			);
		}

		// If no condition matches, send an error response.
		wp_send_json_error(
			array(
				'success' => false,
				'message' => 'Unexpected error occurred.',
			) 
		);
	}

	/**
	 * Renders the Sure Form.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function surecart_plugin_activator() {
		// Check user capability.
		if ( ! ( current_user_can( 'activate_plugins' ) && current_user_can( 'install_plugins' ) ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => 'User is not authenticated!',
				) 
			);
		}

		// Verify nonce.
		if ( ! check_ajax_referer( 'spectra_ajax_nonce', 'security', false ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => 'Invalid nonce.',
				) 
			);
		}

		$installed_plugins  = get_plugins();
		$status_of_surecart = isset( $installed_plugins['surecart/surecart.php'] ) 
			? ( is_plugin_active( 'surecart/surecart.php' ) ? 'active' : 'inactive' ) 
			: 'not-installed';

		if ( class_exists( '\BSF_UTM_Analytics\Inc\Utils' ) && is_callable( '\BSF_UTM_Analytics\Inc\Utils::update_referer' ) ) {
			// If the plugin is found and the update_referer function is callable, update the referer with the corresponding product slug.
			\BSF_UTM_Analytics\Inc\Utils::update_referer( 'spectra', 'surecart' );
		}

		// If plugin is not installed, install it first.
		if ( 'not-installed' === $status_of_surecart ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$plugin_slug = 'surecart';
			$plugin_data = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );

			if ( is_wp_error( $plugin_data ) || ! is_object( $plugin_data ) || empty( $plugin_data->download_link ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => 'Error fetching plugin data.',
					) 
				);
			}

			if ( is_object( $plugin_data ) || is_array( $plugin_data ) ) {
				$download_link = ( is_object( $plugin_data ) && isset( $plugin_data->download_link ) ) ? $plugin_data->download_link : '';
				$skin          = new WP_Ajax_Upgrader_Skin();
				$upgrader      = new Plugin_Upgrader( $skin );
				$installed     = $upgrader->install( $download_link );

				if ( is_wp_error( $installed ) ) {
					wp_send_json_error(
						array(
							'success' => false,
							'message' => 'Failed to install the plugin.',
						) 
					);
				}
			}

			$installed_plugins  = get_plugins();
			$status_of_surecart = isset( $installed_plugins['surecart/surecart.php'] ) ? 'inactive' : 'not-installed';
		}

		// If the plugin is installed but inactive, activate it.
		if ( 'inactive' === $status_of_surecart ) {
			$activate = activate_plugin( 'surecart/surecart.php' );
			if ( is_wp_error( $activate ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'message' => $activate->get_error_message(),
					) 
				);
			}

			wp_send_json_success(
				array(
					'success' => true,
					'message' => 'Plugin successfully activated.',
				) 
			);
		}

		// If already active, send a success message.
		if ( 'active' === $status_of_surecart ) {
			wp_send_json_success(
				array(
					'success' => true,
					'message' => 'Plugin is already active.',
				) 
			);
		}

		// If no condition matches, send an error response.
		wp_send_json_error(
			array(
				'success' => false,
				'message' => 'Unexpected error occurred.',
			) 
		);
	}

	/**
	 * Renders the Contect Form 7 shortcode.
	 *
	 * @since 0.0.1
	 */
	public function cf7_shortcode() {

		check_ajax_referer( 'spectra_ajax_nonce', 'nonce' );

		$id = isset( $_POST['formId'] ) ? intval( $_POST['formId'] ) : 0;

		if ( $id && 0 !== $id && -1 !== $id ) {
			$data['html'] = do_shortcode( '[contact-form-7 id="' . $id . '" ajax="true"]' );
		} else {
			$data['html'] = '<p>' . __( 'Please select a valid Contact Form 7.', 'spectra' ) . '</p>';
		}
		wp_send_json_success( $data );
	}

	// Legacy register_block_category method removed — block category registration handled by v3 BlockManager.

	/**
	 * Localize SVG icon scripts in chunks.
	 * Ex - if 1800 icons available so we will localize 4 variables for it.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_svg_icon_assets() {
		$localize_icon_chunks = Spectra_Helper::backend_load_font_awesome_icons();
		if ( ! $localize_icon_chunks ) {
			return;
		}

		foreach ( $localize_icon_chunks as $chunk_index => $value ) {
			wp_localize_script( 'uagb-essential-vars', "spectra_svg_icons_{$chunk_index}", $value );
		}
	}

	/**
	 * Get the status of a plugin.
	 * This function is used internally in the editor upsell scripts to check if Spectra Pro is installed or not.
	 *
	 * @since 0.0.1
	 *
	 * @param  string $plugin_init_file Plugin init file.
	 * @return string
	 */
	public static function get_plugin_status( $plugin_init_file ) {

		$installed_plugins = get_plugins();

		if ( ! isset( $installed_plugins[ $plugin_init_file ] ) ) {
			return 'Install';
		} elseif ( is_plugin_active( $plugin_init_file ) ) {
			return 'Activated';
		} else {
			return 'Installed';
		}
	}

	/**
	 * Enqueue essential variables for v3 blocks when full v2 assets are not loaded.
	 *
	 * This method provides only the essential 5 variables that v3 blocks need:
	 * - is_rtl: RTL support detection
	 * - spectra_pro_status: Spectra Pro plugin status
	 * - spectra_url: Ultimate Addons plugin URL
	 * - font_awesome_5_polyfill: Font Awesome polyfill data
	 * - spectra_svg_icons: Font Awesome SVG icon data
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function enqueue_minimal_v3_block_assets() {
		// Get all installed plugins to check Spectra Pro status.
		$installed_plugins = get_plugins();

		// Determine Spectra Pro plugin status (not-installed, inactive, or active).
		$spectra_pro_status = isset( $installed_plugins['spectra-pro/spectra-pro.php'] )
			? ( is_plugin_active( 'spectra-pro/spectra-pro.php' )
				? 'active'
				: 'inactive' )
			: 'not-installed';

		// Build array of variables for spectra_blocks_info.
		$minimal_block_variables = array(
			'is_rtl'                  => is_rtl(),
			'spectra_pro_status'      => $spectra_pro_status,
			'spectra_url'             => SPECTRA_URL,
			'font_awesome_5_polyfill' => spectra_blocks_get_font_awesome_polyfiller(),
			'current_post_id'         => get_the_ID(),
		);

		// Enqueue minimal script for localization.
		wp_enqueue_script(
			'uagb-essential-vars',
			'data:text/javascript;base64,' . base64_encode( '/* Essential Variables */' ),
			array( 'wp-blocks' ),
			SPECTRA_VER,
			true
		);

		// Get merged SVG icons for v3 compatibility.
		$localize_icon_chunks = Spectra_Helper::backend_load_font_awesome_icons();
		$merged_icons         = array();
		if ( $localize_icon_chunks ) {
			foreach ( $localize_icon_chunks as $chunk_index => $value ) {
				if ( is_array( $value ) ) {
					$merged_icons = array_merge( $merged_icons, $value );
				}
			}
			// Add merged icons to minimal variables.
			$minimal_block_variables['spectra_svg_icons'] = $merged_icons;
		}

		// Localize spectra_blocks_info for v3 blocks.
		wp_localize_script( 'uagb-essential-vars', 'spectra_blocks_info', $minimal_block_variables );

		// Provide uagb_blocks_info for v3 block JS that references it.
		// This ensures blocks work correctly whether or not the legacy plugin is active.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		wp_localize_script(
			'uagb-essential-vars',
			'uagb_blocks_info',
			array(
				'uagb_url'                 => SPECTRA_URL,
				'uagb_svg_icons'           => $merged_icons,
				'font_awesome_5_polyfill'  => spectra_blocks_get_font_awesome_polyfiller(),
				'tablet_breakpoint'        => SPECTRA_TABLET_BREAKPOINT,
				'mobile_breakpoint'        => SPECTRA_MOBILE_BREAKPOINT,
				'spectra_pro_status'       => $spectra_pro_status,
				'is_rtl'                   => is_rtl(),
				'current_post_id'          => get_the_ID(),
				'uagb_old_user_less_than_2' => get_option( 'uagb-old-user-less-than-2' ),
				'is_customize_preview'     => is_customize_preview(),
				'is_site_editor'           => $screen ? $screen->id : '',
				'exclude_crops_iframes'    => apply_filters( 'spectra_exclude_crops_iframes', array( '__privateStripeMetricsController8690' ) ),
			)
		);

		// Add inline JavaScript to process SVG icons exactly like v2.
		$inline_js = '
		if ( spectra_blocks_info.spectra_svg_icons?.spectra_category_list ) {
			wp.spectra_icon_category_list = [ ...spectra_blocks_info.spectra_svg_icons.spectra_category_list ];
			delete spectra_blocks_info.spectra_svg_icons.spectra_category_list;
		}

		wp.UAGBSvgIcons = Object.keys( spectra_blocks_info.spectra_svg_icons );
		';

		wp_add_inline_script( 'uagb-essential-vars', $inline_js, 'after' );
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * @since 0.0.1
	 */
	public function editor_assets() {
		// Check if assets should be excluded for the current post type.
		if ( Spectra_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}

		// Legacy v2 blocks have been removed from spectra-blocks — they load from
		// the legacy plugin instead.  V3 blocks register
		// their own editor/view scripts via block.json metadata, so there is no
		// need to enqueue a combined dist/blocks.js bundle.
		$this->enqueue_minimal_v3_block_assets();

		// To match the editor with frontend.
		// Scripts Dependency.
		Spectra_Scripts_Utils::enqueue_blocks_dependency_both();
		// Style.
		Spectra_Scripts_Utils::enqueue_blocks_styles();
		// RTL Styles.
		Spectra_Scripts_Utils::enqueue_blocks_rtl_styles();

		// Add svg icons in chunks.
		$this->add_svg_icon_assets();
	}

	/**
	 * Ajax call to confirm add users confirmation option in database
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function confirm_svg_upload() {
		check_ajax_referer( 'spectra_confirm_svg_nonce', 'svg_nonce' );
		if ( empty( $_POST['confirmation'] ) || 'yes' !== sanitize_text_field( $_POST['confirmation'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request', 'spectra' ) ) );
		}

		update_option( 'spectra_svg_confirmation', 'yes' );
		wp_send_json_success();
	}
}

/**
 *  Prepare if class 'Spectra_Init_Blocks' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Init_Blocks::get_instance();
