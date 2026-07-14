<?php
/**
 * Spectra Blocks Analytics Event Tracker.
 *
 * Registers hooks and detects state-based milestone events for the BSF
 * Analytics event tracking system. Ported from UAGB_Analytics_Event_Tracker
 * with the following adaptations:
 *
 *  - Namespace: Spectra\Analytics
 *  - Singleton via Spectra\Traits\Singleton
 *  - Option/meta keys re-prefixed with `spectra_blocks_*` so a site running
 *    UAGB and Spectra Blocks side-by-side does not collide.
 *  - Block-stat probes look for `spectra/` blocks only (UAGB owns `uagb/`).
 *  - Feature detections are guarded with class_exists/function_exists so
 *    they degrade silently when a paired feature (e.g. Pro, Learn tab)
 *    is not present in this plugin.
 *
 * @package Spectra\Analytics
 */

namespace Spectra\Analytics;

use SpectraBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class EventTracker.
 *
 * @since 1.0.0
 */
class EventTracker {

	use Singleton;

	/**
	 * Previous plugin version captured before an update.
	 *
	 * @var string
	 */
	private $pre_update_version = '';

	/**
	 * Allow-list of setting keys worth tracking for `settings_changed` events.
	 *
	 * Only the KEY is ever sent — never the value. Keys chosen for product-usage
	 * insight; excludes migration state and anything that could leak PII.
	 *
	 * @var string[]
	 */
	private static $tracked_settings = array(
		'spectra_blocks_active_blocks',
		'spectra_blocks_file_generation',
	);

	/**
	 * Register hooks. Called from the plugin loader.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'track_plugin_activated' ) );
		add_action( 'admin_init', array( $this, 'detect_state_events' ) );
		add_action( 'update_option_spectra_blocks_usage_optin', array( $this, 'track_analytics_optin' ), 10, 2 );
		add_action( 'save_post', array( $this, 'track_first_spectra_block_used' ), 20, 2 );
		add_action( 'wp_ajax_ast_block_templates_importer', array( $this, 'track_first_template_imported' ), 5 );
		add_action( 'wp_ajax_ast_block_templates_import_template_kit', array( $this, 'track_first_template_imported' ), 5 );
		add_action( 'wp_ajax_ast_block_templates_import_block', array( $this, 'track_first_pattern_imported' ), 5 );

		add_action( 'spectra_blocks_update_before', array( $this, 'capture_pre_update_version' ) );
		add_action( 'spectra_blocks_update_after', array( $this, 'track_plugin_updated' ) );

		foreach ( self::$tracked_settings as $setting_key ) {
			add_action( 'update_option_' . $setting_key, array( $this, 'track_setting_changed' ), 10, 3 );
		}

		add_action( 'one_onboarding_state_saved_spectra', array( $this, 'track_onboarding_skipped' ), 10, 2 );
		add_action( 'one_onboarding_completion_spectra', array( $this, 'track_onboarding_completed' ), 10, 2 );
	}

	/**
	 * Track plugin activation event.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function track_plugin_activated() {
		$referrers = get_option( 'bsf_product_referers', array() );
		$source    = 'self';
		if ( is_array( $referrers ) && ! empty( $referrers['spectra-blocks'] ) && is_string( $referrers['spectra-blocks'] ) ) {
			$source = sanitize_text_field( $referrers['spectra-blocks'] );
		}

		$version = defined( 'SPECTRA_BLOCKS_VER' ) ? SPECTRA_BLOCKS_VER : '';

		$properties = array(
			'source'             => $source,
			'days_since_install' => (string) self::get_days_since_install(),
			'site_language'      => get_locale(),
			'wp_version'         => get_bloginfo( 'version' ),
			'php_version'        => PHP_VERSION,
			'is_multisite'       => is_multisite() ? 'yes' : 'no',
		);

		Events::track( 'plugin_activated', $version, $properties );
	}

	/**
	 * Days since the plugin was first installed.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	private static function get_days_since_install() {
		$install_time = get_site_option( 'spectra_blocks_usage_installed_time', 0 );
		if ( ! $install_time || ! is_numeric( $install_time ) ) {
			return 0;
		}
		return (int) floor( ( time() - (int) $install_time ) / DAY_IN_SECONDS );
	}

	/**
	 * Track analytics opt-in/opt-out event.
	 *
	 * @since 1.0.0
	 * @param string $old_value Old value.
	 * @param string $new_value New value.
	 * @return void
	 */
	public function track_analytics_optin( $old_value, $new_value ) {
		if ( 'yes' === $new_value ) {
			Events::track( 'analytics_optin', 'yes' );
		}
	}

	/**
	 * Track first time a Spectra block is used in a post.
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function track_first_spectra_block_used( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( Events::is_tracked( 'first_spectra_block_used' ) ) {
			return;
		}

		if ( empty( $post->post_content ) ) {
			return;
		}

		if ( ! preg_match( '/<!-- wp:(spectra)\/(\S+)/', $post->post_content, $matches ) ) {
			return;
		}

		$block_slug = $matches[1] . '/' . $matches[2];

		Events::track(
			'first_spectra_block_used',
			$block_slug,
			array(
				'post_type'          => get_post_type( $post_id ),
				'days_since_install' => (string) self::get_days_since_install(),
			)
		);
	}

	/**
	 * Capture the plugin version before an update overwrites it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function capture_pre_update_version() {
		$version                  = get_option( 'spectra_blocks_version', '' );
		$this->pre_update_version = is_string( $version ) ? $version : '';
	}

	/**
	 * Track plugin version update event.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function track_plugin_updated() {
		$version = defined( 'SPECTRA_BLOCKS_VER' ) ? SPECTRA_BLOCKS_VER : '';
		Events::retrack_event(
			'plugin_updated',
			$version,
			array( 'from_version' => $this->pre_update_version )
		);
	}

	/**
	 * Track changes to allow-listed settings. Sends the KEY only, never the value.
	 *
	 * @since 1.0.0
	 * @param mixed  $old_value Previous value (unused).
	 * @param mixed  $new_value New value (unused).
	 * @param string $option    Option name.
	 * @return void
	 */
	public function track_setting_changed( $old_value, $new_value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_string( $option ) || '' === $option ) {
			return;
		}
		if ( ! in_array( $option, self::$tracked_settings, true ) ) {
			return;
		}

		Events::retrack_event(
			'settings_changed',
			$option,
			array( 'option' => $option )
		);
	}

	/**
	 * Detect state-based events on admin load.
	 *
	 * Throttled to run once per 24 hours via transient.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function detect_state_events() {
		if ( false !== get_transient( 'spectra_blocks_state_events_checked' ) ) {
			return;
		}

		$this->detect_spectra_pro_activated();
		$this->detect_ai_assistant_first_use();
		$this->detect_onboarding_completed();
		$this->detect_first_form_created();
		$this->detect_first_popup_created();

		set_transient( 'spectra_blocks_state_events_checked', 1, DAY_IN_SECONDS );
	}

	/**
	 * Detect if Spectra Pro is active.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function detect_spectra_pro_activated() {
		if ( Events::is_tracked( 'spectra_pro_activated' ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) ) {
			$pro_version = defined( 'SPECTRA_PRO_VER' ) ? SPECTRA_PRO_VER : '';
			Events::track( 'spectra_pro_activated', $pro_version );
		}
	}

	/**
	 * Detect first use of AI assistant via the Zip AI library.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function detect_ai_assistant_first_use() {
		if ( Events::is_tracked( 'ai_assistant_first_use' ) ) {
			return;
		}

		if ( ! class_exists( '\\ZipAI\\Classes\\Helper' ) || ! method_exists( '\\ZipAI\\Classes\\Helper', 'is_authorized' ) ) {
			return;
		}

		if ( \ZipAI\Classes\Helper::is_authorized() ) {
			Events::track(
				'ai_assistant_first_use',
				'',
				array( 'module' => 'ai_assistant' )
			);
		}
	}

	/**
	 * Detect if onboarding has been completed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function detect_onboarding_completed() {
		if ( Events::is_tracked( 'onboarding_completed' ) ) {
			return;
		}

		if ( ! class_exists( '\\Spectra_Blocks_Onboarding' ) || ! is_callable( array( '\\Spectra_Blocks_Onboarding', 'is_onboarding_completed' ) ) ) {
			return;
		}

		if ( ! \Spectra_Blocks_Onboarding::is_onboarding_completed() ) {
			return;
		}

		$analytics  = get_option( 'spectra_blocks_onboarding_analytics', array() );
		$analytics  = is_array( $analytics ) ? $analytics : array();
		$properties = array();

		if ( ! empty( $analytics['skippedSteps'] ) && is_array( $analytics['skippedSteps'] ) ) {
			$properties['skipped_steps'] = implode( ',', array_map( 'sanitize_text_field', $analytics['skippedSteps'] ) );
		}

		$properties['exited_early'] = ! empty( $analytics['exitedEarly'] ) ? 'yes' : 'no';
		$properties['consent']      = ! empty( $analytics['consent'] ) ? 'yes' : 'no';

		Events::clear_event( 'onboarding_skipped' );
		Events::track( 'onboarding_completed', '', $properties );
	}

	/**
	 * Track first template import via AJAX hook from Gutenberg Templates lib.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function track_first_template_imported() {
		Events::track( 'first_template_imported' );
	}

	/**
	 * Track first pattern (block) import via AJAX hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function track_first_pattern_imported() {
		Events::track( 'first_pattern_imported' );
	}

	/**
	 * Detect if a Spectra form block has been created.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function detect_first_form_created() {
		if ( Events::is_tracked( 'first_form_created' ) ) {
			return;
		}

		if ( ! class_exists( '\\Spectra\\Analytics\\BlockUsageTracker' ) ) {
			return;
		}

		$tracker     = BlockUsageTracker::instance();
		$usage_stats = is_callable( array( $tracker, 'get_usage_statistics' ) ) ? $tracker->get_usage_statistics() : array();
		$most_used   = isset( $usage_stats['most_used_blocks'] ) && is_array( $usage_stats['most_used_blocks'] ) ? $usage_stats['most_used_blocks'] : array();

		if ( ! empty( $most_used['forms'] ) && (int) $most_used['forms'] > 0 ) {
			Events::track( 'first_form_created' );
		}
	}

	/**
	 * Detect if a Spectra popup has been created.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function detect_first_popup_created() {
		if ( Events::is_tracked( 'first_popup_created' ) ) {
			return;
		}

		if ( ! post_type_exists( 'spectra-popup' ) ) {
			return;
		}

		$popup_count = wp_count_posts( 'spectra-popup' );

		if ( is_object( $popup_count ) && ( $popup_count->publish > 0 || $popup_count->draft > 0 ) ) {
			Events::track( 'first_popup_created' );
		}
	}

	/**
	 * Track onboarding completion from the `one_onboarding_completion_spectra` hook.
	 *
	 * @since 1.0.0
	 * @param array                 $completion_data Completion payload from the REST endpoint.
	 * @param \WP_REST_Request|null $request         The REST request (unused).
	 * @return void
	 */
	public function track_onboarding_completed( $completion_data, $request = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_array( $completion_data ) ) {
			return;
		}

		Events::clear_event( 'onboarding_skipped' );

		$properties = self::build_onboarding_completion_properties( $completion_data );
		$version    = defined( 'SPECTRA_BLOCKS_VER' ) ? SPECTRA_BLOCKS_VER : '';

		Events::retrack_event( 'onboarding_completed', $version, $properties );
	}

	/**
	 * Build the property bag for onboarding_completed from completion data.
	 *
	 * @since 1.0.0
	 * @param array $completion_data Payload from one_onboarding_completion_spectra.
	 * @return array
	 */
	private static function build_onboarding_completion_properties( $completion_data ) {
		$screens           = isset( $completion_data['screens'] ) && is_array( $completion_data['screens'] ) ? $completion_data['screens'] : array();
		$skipped_steps     = array();
		$screens_completed = 0;
		foreach ( $screens as $screen ) {
			if ( ! is_array( $screen ) ) {
				continue;
			}
			$screen_id = isset( $screen['id'] ) && is_string( $screen['id'] ) ? $screen['id'] : '';
			if ( ! empty( $screen['skipped'] ) ) {
				if ( '' !== $screen_id ) {
					$skipped_steps[] = sanitize_text_field( $screen_id );
				}
			} else {
				++$screens_completed;
			}
		}

		$completion_screen = isset( $completion_data['completion_screen'] ) && is_string( $completion_data['completion_screen'] )
			? sanitize_text_field( $completion_data['completion_screen'] )
			: '';

		$properties = array(
			'completion_screen' => $completion_screen,
			'screens_completed' => $screens_completed,
			'screens_total'     => count( $screens ),
		);

		if ( ! empty( $skipped_steps ) ) {
			$properties['skipped_steps'] = implode( ',', $skipped_steps );
		}

		$st_builder = isset( $completion_data['starter_templates_builder'] ) && is_string( $completion_data['starter_templates_builder'] )
			? sanitize_text_field( $completion_data['starter_templates_builder'] )
			: '';
		if ( '' !== $st_builder ) {
			$properties['st_builder'] = $st_builder;
		}

		if ( ! empty( $completion_data['pro_features'] ) && is_array( $completion_data['pro_features'] ) ) {
			$properties['pro_features'] = implode( ',', array_map( 'sanitize_text_field', $completion_data['pro_features'] ) );
		}

		if ( ! empty( $completion_data['selected_addons'] ) && is_array( $completion_data['selected_addons'] ) ) {
			$properties['selected_addons'] = implode( ',', array_map( 'sanitize_text_field', $completion_data['selected_addons'] ) );
		}

		return $properties;
	}

	/**
	 * Track onboarding exits via the `one_onboarding_state_saved_spectra` hook.
	 *
	 * @since 1.0.0
	 * @param array                 $state_data Onboarding state from the REST endpoint.
	 * @param \WP_REST_Request|null $request    The REST request (unused).
	 * @return void
	 */
	public function track_onboarding_skipped( $state_data, $request = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_array( $state_data ) ) {
			return;
		}

		if ( empty( $state_data['exited_early'] ) ) {
			return;
		}

		if ( Events::is_tracked( 'onboarding_completed' ) ) {
			return;
		}

		$screens           = isset( $state_data['screens'] ) && is_array( $state_data['screens'] ) ? $state_data['screens'] : array();
		$screens_completed = 0;
		foreach ( $screens as $screen ) {
			if ( is_array( $screen ) && empty( $screen['skipped'] ) ) {
				++$screens_completed;
			}
		}

		$exit_screen = '';
		if ( isset( $state_data['exit_screen'] ) && is_string( $state_data['exit_screen'] ) ) {
			$exit_screen = sanitize_text_field( $state_data['exit_screen'] );
		} elseif ( isset( $state_data['current_screen'] ) && is_string( $state_data['current_screen'] ) ) {
			$exit_screen = sanitize_text_field( $state_data['current_screen'] );
		}

		$properties = array(
			'exit_screen'       => $exit_screen,
			'screens_completed' => $screens_completed,
			'screens_total'     => count( $screens ),
		);

		$version = defined( 'SPECTRA_BLOCKS_VER' ) ? SPECTRA_BLOCKS_VER : '';

		Events::retrack_event( 'onboarding_skipped', $version, $properties );
	}
}
