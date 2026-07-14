<?php
/**
 * Display Conditions Extension
 *
 * @package SpectraBlocks\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Traits\Singleton;

/**
 * Display Conditions class.
 *
 * Handles server-side visibility of blocks based on user login state.
 * Unlike responsive conditions (CSS-based), this extension completely removes
 * block HTML from the page output using the render_block filter.
 *
 * @since 1.0.0
 */
class DisplayConditions {

	use Singleton;

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block to evaluate display conditions and
	 * remove blocks that should not be visible to the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'evaluate_display_conditions' ), 10, 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'spectra_3_extensions_editor_assets', array( $this, 'localize_display_conditions_data' ), 10, 2 );
	}

	/**
	 * Evaluate display conditions and remove block content if conditions are met.
	 *
	 * Returns an empty string to completely remove the block from page output
	 * when the user's login state matches a hide condition.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content or empty string if hidden.
	 */
	public function evaluate_display_conditions( $block_content, $block ) {
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$conditions = $block['attrs']['displayConditions'];

		if ( ! empty( $conditions['hideWhenLoggedIn'] ) && is_user_logged_in() ) {
			return '';
		}

		if ( ! empty( $conditions['hideWhenLoggedOut'] ) && ! is_user_logged_in() ) {
			return '';
		}

		// User Role: hide if current user has the specified role.
		if ( ! empty( $conditions['hideForRole'] ) ) {
			$user = wp_get_current_user();
			if ( is_user_logged_in() && ! empty( $user->roles )
				&& in_array( $conditions['hideForRole'], $user->roles, true ) ) {
				return '';
			}
		}

		// Browser: hide if current browser matches.
		if ( ! empty( $conditions['hideForBrowser'] ) ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			$browser    = Core::get_browser_name( $user_agent );
			if ( $conditions['hideForBrowser'] === $browser ) {
				return '';
			}
		}

		// OS: hide if current OS matches.
		if ( ! empty( $conditions['hideForOS'] ) ) {
			if ( $this->matches_os( $conditions['hideForOS'] ) ) {
				return '';
			}
		}

		// Day: hide on specified days.
		if ( ! empty( $conditions['hideOnDays'] ) && is_array( $conditions['hideOnDays'] ) ) {
			$current_day = strtolower( current_datetime()->format( 'l' ) );
			if ( in_array( $current_day, $conditions['hideOnDays'], true ) ) {
				return '';
			}
		}

		return $block_content;
	}

	/**
	 * Determine whether the block should be processed for display conditions.
	 *
	 * @since 1.0.0
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& isset( $block['attrs']['displayConditions'] )
			&& is_array( $block['attrs']['displayConditions'] )
			&& $this->is_allowed_block( $block['blockName'] );
	}

	/**
	 * Check if a block is allowed for display conditions.
	 *
	 * Uses the same filtering logic as the JavaScript side to ensure consistency.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( $block_name ) {
		/**
		 * Filter to allow or exclude specific blocks from display conditions.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $excluded_blocks Array of block names to exclude.
		 * @param string $block_name      The current block name being checked.
		 * @return array Modified array of excluded block names.
		 */
		$excluded_blocks = apply_filters( 'spectra_excluded_display_conditions_blocks', array(), $block_name );

		if ( in_array( $block_name, $excluded_blocks, true ) ) {
			return false;
		}

		/**
		 * Filter to specify which blocks explicitly support display conditions.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $supported_blocks Array of block names that support display conditions.
		 * @param string $block_name       The current block name being checked.
		 * @return array Modified array of supported block names.
		 */
		$supported_blocks = apply_filters( 'spectra_supported_display_conditions_blocks', array( 'core/image' ), $block_name );

		if ( in_array( $block_name, $supported_blocks, true ) ) {
			return true;
		}

		/**
		 * Filter to modify the allowed block prefixes for display conditions.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $allowed_prefixes Array of block name prefixes to allow.
		 * @param string $block_name       The current block name being checked.
		 * @return array Modified array of allowed prefixes.
		 */
		$allowed_prefixes = apply_filters( 'spectra_allowed_display_conditions_prefixes', array( 'spectra/', 'spectra-pro/' ), $block_name );

		foreach ( $allowed_prefixes as $prefix ) {
			if ( strpos( $block_name, $prefix ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue editor assets for display conditions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_style( 'spectra-blocks-extensions-display-conditions' );
	}

	/**
	 * Localize display conditions data for the editor.
	 *
	 * Passes user roles to JavaScript for the role-based visibility control.
	 *
	 * @since 1.0.0
	 *
	 * @param string $folder_name The extension folder name.
	 * @param array  $asset_file  The asset file data.
	 * @return void
	 */
	public function localize_display_conditions_data( $folder_name, $asset_file ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'display-conditions' !== $folder_name ) {
			return;
		}

		$handle       = "spectra-3-extension-{$folder_name}-editor";
		$roles        = wp_roles()->get_names();
		$role_options = array();

		foreach ( $roles as $slug => $name ) {
			$role_options[] = array(
				'label' => translate_user_role( $name ),
				'value' => $slug,
			);
		}

		wp_localize_script(
			$handle,
			'spectraDisplayConditions',
			array(
				'userRoles' => $role_options,
			)
		);
	}

	/**
	 * Check if the current user's OS matches the given key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $os_key The OS key to match against.
	 * @return bool Whether the OS matches.
	 */
	private function matches_os( $os_key ) {
		$os_patterns = array(
			'iphone'   => '(iPhone)',
			'android'  => '(Android)',
			'windows'  => 'Win16|(Windows 95)|(Win95)|(Windows_95)|(Windows 98)|(Win98)|(Windows NT 5.0)|(Windows 2000)|(Windows NT 5.1)|(Windows XP)|(Windows NT 5.2)|(Windows NT 6.0)|(Windows Vista)|(Windows NT 6.1)|(Windows 7)|(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)|Windows ME',
			'open_bsd' => 'OpenBSD',
			'sun_os'   => 'SunOS',
			'linux'    => '(Linux)|(X11)',
			'mac_os'   => '(Mac_PowerPC)|(Macintosh)',
		);

		if ( ! isset( $os_patterns[ $os_key ] ) ) {
			return false;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return (bool) preg_match( '@' . $os_patterns[ $os_key ] . '@', $user_agent );
	}
}
