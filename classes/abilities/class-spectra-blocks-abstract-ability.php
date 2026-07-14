<?php
/**
 * Abstract Ability base class.
 *
 * Provides shared registration, permission, gating, and annotation logic
 * for all Spectra Blocks abilities.
 *
 * @package Spectra_Blocks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Spectra_Blocks_Abstract_Ability.
 *
 * @since 1.0.0
 */
abstract class Spectra_Blocks_Abstract_Ability {

	/**
	 * Option gate for this ability.
	 *
	 * Write abilities use 'spectra_blocks_enable_edit_abilities'.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $gated = '';

	/**
	 * Get the ability name (slug).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract protected function get_name();

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract protected function get_label();

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract protected function get_description();

	/**
	 * Get the ability category slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract protected function get_category();

	/**
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	abstract protected function get_input_schema();

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	abstract protected function get_output_schema();

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 * @param array $input Validated input.
	 * @return array|WP_Error
	 */
	abstract public function execute( array $input );

	/**
	 * Get the required WordPress capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_required_capability() {
		return 'edit_posts';
	}

	/**
	 * Get the MCP annotations.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'openWorldHint' => false,
		);
	}

	/**
	 * Check if the ability is enabled based on its gate.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_enabled() {
		if ( ! empty( $this->gated ) && 'disabled' === \Spectra_Blocks_Admin_Helper::get_admin_settings_option( $this->gated, 'enabled' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Permission callback: 3-layer gate (master toggle → per-category gate → WP capability).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_permission() {
		if ( 'enabled' !== \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_abilities', 'disabled' ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		return current_user_can( $this->get_required_capability() );
	}

	/**
	 * Register the ability with WordPress.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			$this->get_name(),
			array(
				'label'               => $this->get_label(),
				'description'         => $this->get_description(),
				'category'            => $this->get_category(),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'meta'                => array(
					'annotations'  => $this->get_annotations(),
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}
}
