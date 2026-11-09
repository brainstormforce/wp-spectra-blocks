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
	 * Definition version for the site_classification snapshot.
	 *
	 * Bump when the active/super thresholds or states change so the series
	 * segments instead of silently mutating. Mirrors the classifier in
	 * BlockUsageTracker::get_site_activity_level().
	 *
	 * @var int
	 */
	const SITE_CLASSIFICATION_DEFINITION_VERSION = 1;

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
		'spectra_blocks_enable_gbs_extension',
		'spectra_blocks_enable_dynamic_content',
		'spectra_blocks_enable_animations_extension',
		'spectra_blocks_enable_block_responsive',
		'spectra_blocks_enable_abilities',
		'spectra_blocks_enable_mcp_server',
		'spectra_blocks_visibility_mode',
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
		add_action( 'update_site_option_spectra_blocks_usage_optin', array( $this, 'track_analytics_optin' ), 10, 2 );
		add_action( 'save_post', array( $this, 'track_first_spectra_block_used' ), 20, 2 );
		add_action( 'wp_ajax_ast_block_templates_importer', array( $this, 'track_first_template_imported' ), 5 );
		add_action( 'wp_ajax_ast_block_templates_import_template_kit', array( $this, 'track_first_template_imported' ), 5 );
		add_action( 'wp_ajax_ast_block_templates_import_block', array( $this, 'track_first_pattern_imported' ), 5 );

		add_action( 'spectra_blocks_update_before', array( $this, 'capture_pre_update_version' ) );
		add_action( 'spectra_blocks_update_after', array( $this, 'track_plugin_updated' ) );

		foreach ( self::$tracked_settings as $setting_key ) {
			add_action( 'update_option_' . $setting_key, array( $this, 'track_setting_changed' ), 10, 3 );
		}

		add_action( 'one_onboarding_state_saved_spectra-blocks', array( $this, 'track_onboarding_skipped' ), 10, 2 );
		add_action( 'one_onboarding_completion_spectra-blocks', array( $this, 'track_onboarding_completed' ), 10, 2 );

		// Flush queued milestone events into the analytics payload so they are transmitted.
		add_filter( 'bsf_core_stats', array( $this, 'add_events_stats' ), 40 );

		// Append the recurring site classification snapshot after the event flush (40).
		add_filter( 'bsf_core_stats', array( $this, 'append_site_classification_snapshot' ), 45 );

		// Append the recurring per-block usage snapshot after the event flush (40).
		add_filter( 'bsf_core_stats', array( $this, 'append_block_usage_snapshot' ), 46 );
	}

	/**
	 * Flush queued milestone events into the bsf_core_stats analytics payload.
	 *
	 * Events are transmitted under the plugin's own `spectra_blocks` product
	 * bucket, independent of Spectra/UAGB. Gated on the usage opt-in.
	 *
	 * @since 1.0.4
	 * @param array<string, mixed> $stats Aggregated stats passed through the bsf_core_stats filter.
	 * @return array<string, mixed>
	 */
	public function add_events_stats( $stats ) {
		if ( 'yes' !== get_site_option( 'spectra_blocks_usage_optin', 'no' ) ) {
			return $stats;
		}

		$events = Events::flush_pending();
		if ( empty( $events ) ) {
			return $stats;
		}

		$plugin_data = ( isset( $stats['plugin_data'] ) && is_array( $stats['plugin_data'] ) ) ? $stats['plugin_data'] : array();
		$bucket      = ( isset( $plugin_data['spectra_blocks'] ) && is_array( $plugin_data['spectra_blocks'] ) ) ? $plugin_data['spectra_blocks'] : array();

		$bucket['events_record']       = $events;
		$plugin_data['spectra_blocks'] = $bucket;
		$stats['plugin_data']          = $plugin_data;

		return $stats;
	}

	/**
	 * Append a point-in-time site classification snapshot to the analytics payload.
	 *
	 * The classification (active / super / dormant, etc.) is a site-level state,
	 * so it is emitted as a recurring `site_classification` event that rides the
	 * existing events channel — ingestion already persists event_name/event_value/
	 * properties, so no server-side schema change is required. The dashboard reads
	 * the latest value per domain via argMax( event_value, date ).
	 *
	 * Runs at priority 45 so it appends AFTER add_events_stats() (40) has populated
	 * events_record from the pending queue. Not sent via Events::track() because
	 * that dedupes one-shot events by name, whereas a snapshot must re-emit each
	 * send cycle. A 24h transient prevents duplicate appends within a single send.
	 *
	 * @since 1.0.14
	 * @param array<string, mixed> $stats Aggregated stats passed through the bsf_core_stats filter.
	 * @return array<string, mixed>
	 */
	public function append_site_classification_snapshot( $stats ) {
		if ( 'yes' !== get_site_option( 'spectra_blocks_usage_optin', 'no' ) ) {
			return $stats;
		}

		if ( false !== get_transient( 'spectra_blocks_site_classification_sent' ) ) {
			return $stats;
		}
		set_transient( 'spectra_blocks_site_classification_sent', 1, DAY_IN_SECONDS );

		$activity = BlockUsageTracker::instance()->get_site_activity_level();

		$plugin_data = ( isset( $stats['plugin_data'] ) && is_array( $stats['plugin_data'] ) ) ? $stats['plugin_data'] : array();
		$bucket      = ( isset( $plugin_data['spectra_blocks'] ) && is_array( $plugin_data['spectra_blocks'] ) ) ? $plugin_data['spectra_blocks'] : array();
		$events      = ( isset( $bucket['events_record'] ) && is_array( $bucket['events_record'] ) ) ? $bucket['events_record'] : array();

		$events[] = array(
			'event_name'  => 'site_classification',
			'event_value' => (string) $activity['site_type'],
			'properties'  => array(
				'is_active_site'     => (int) $activity['is_active_site'],
				'is_super_site'      => (int) $activity['is_super_site'],
				'is_dormant_site'    => (int) $activity['is_dormant_site'],
				'active_pages_180d'  => (int) $activity['active_pages_180d'],
				'active_pages_total' => (int) $activity['active_pages_total'],
				'definition_version' => self::SITE_CLASSIFICATION_DEFINITION_VERSION,
			),
			'date'        => wp_date( 'Y-m-d H:i:s' ),
		);

		$bucket['events_record']       = $events;
		$plugin_data['spectra_blocks'] = $bucket;
		$stats['plugin_data']          = $plugin_data;

		return $stats;
	}

	/**
	 * Append a per-block usage snapshot to the analytics payload.
	 *
	 * Emits one `block_usage_snapshot` event whose properties map each in-use
	 * Spectra block to its instance count (e.g. `spectra/container` => 12),
	 * riding the existing events channel — ingestion already persists
	 * event_name/event_value/properties to `spectra_blocks_events`, so no
	 * server-side schema change is required. The dashboard ranks most/least used
	 * blocks from the latest snapshot per domain (argMax by date, then
	 * arrayJoin the properties map).
	 *
	 * Runs at priority 46 (after add_events_stats() at 40). Not sent via
	 * Events::track() (which dedupes one-shot events by name and sanitises
	 * property keys — that would mangle the `namespace/block` slug). Opt-in
	 * gated; a 24h transient prevents duplicate appends within a single send.
	 *
	 * @since 1.0.14
	 * @param array<string, mixed> $stats Aggregated stats passed through the bsf_core_stats filter.
	 * @return array<string, mixed>
	 */
	public function append_block_usage_snapshot( $stats ) {
		if ( 'yes' !== get_site_option( 'spectra_blocks_usage_optin', 'no' ) ) {
			return $stats;
		}

		if ( false !== get_transient( 'spectra_blocks_block_usage_sent' ) ) {
			return $stats;
		}
		set_transient( 'spectra_blocks_block_usage_sent', 1, DAY_IN_SECONDS );

		$counts = IncrementalBlockTracker::instance()->get_block_usage_counts();
		if ( empty( $counts ) ) {
			return $stats; // No Spectra blocks in use — nothing to report.
		}

		$properties = array();
		foreach ( $counts as $block => $count ) {
			$properties[ $block ] = (int) $count;
		}

		$plugin_data = ( isset( $stats['plugin_data'] ) && is_array( $stats['plugin_data'] ) ) ? $stats['plugin_data'] : array();
		$bucket      = ( isset( $plugin_data['spectra_blocks'] ) && is_array( $plugin_data['spectra_blocks'] ) ) ? $plugin_data['spectra_blocks'] : array();
		$events      = ( isset( $bucket['events_record'] ) && is_array( $bucket['events_record'] ) ) ? $bucket['events_record'] : array();

		$events[] = array(
			'event_name'  => 'block_usage_snapshot',
			'event_value' => (string) count( $properties ), // Distinct blocks in use.
			'properties'  => $properties,
			'date'        => wp_date( 'Y-m-d H:i:s' ),
		);

		$bucket['events_record']       = $events;
		$plugin_data['spectra_blocks'] = $bucket;
		$stats['plugin_data']          = $plugin_data;

		return $stats;
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
