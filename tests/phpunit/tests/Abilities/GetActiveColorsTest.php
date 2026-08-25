<?php
/**
 * Tests for the GetActiveColors ability.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\GetActiveColors;
use SpectraBlocks\StyleGuide\Engine;

/**
 * GetActiveColors test case.
 */
class GetActiveColorsTest extends WP_UnitTestCase {

	/**
	 * Ability instance.
	 *
	 * @var GetActiveColors
	 */
	private $ability;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->ability = GetActiveColors::instance();

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		delete_option( Engine::OPTION_KEY );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		delete_option( Engine::OPTION_KEY );
		parent::tear_down();
	}

	/**
	 * The palette map carries the core roles, each a valid hex.
	 */
	public function test_returns_core_roles() {
		$result = $this->ability->execute( array() );

		$this->assertIsArray( $result );
		$this->assertIsArray( $result['colors'] );
		foreach ( array( 'primary', 'secondary', 'accent', 'heading', 'body', 'background' ) as $slug ) {
			$this->assertArrayHasKey( $slug, $result['colors'] );
			$this->assertMatchesRegularExpression( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $result['colors'][ $slug ] );
		}
	}

	/**
	 * An untouched site reports `saved => false` (inherited theme colours).
	 */
	public function test_unsaved_site_reports_not_saved() {
		$result = $this->ability->execute( array() );

		$this->assertFalse( $result['saved'] );
	}

	/**
	 * A site with a stored palette reports `saved => true` and reflects it.
	 */
	public function test_saved_site_reports_saved_and_reflects_colors() {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array( 'primary' => '#abcdef' ),
				'custom_colors' => array(),
			)
		);

		$result = $this->ability->execute( array() );

		$this->assertTrue( $result['saved'] );
		$this->assertSame( '#abcdef', $result['colors']['primary'] );
	}

	/**
	 * Preview CSS is a ready-to-inject string carrying the semantic preset vars.
	 */
	public function test_preview_css_contains_preset_vars() {
		$result = $this->ability->execute( array() );

		$this->assertIsString( $result['preview_css'] );
		$this->assertStringContainsString( '--wp--preset--color--primary', $result['preview_css'] );
	}

	/**
	 * Preview CSS carries the DERIVED tones (surface-2 / overlay), matching what
	 * the front end renders — the pins-only path left these out.
	 */
	public function test_preview_css_carries_derived_tones() {
		$result = $this->ability->execute( array() );

		$this->assertStringContainsString( '--wp--preset--color--surface-2', $result['preview_css'] );
		$this->assertStringContainsString( '--wp--preset--color--overlay', $result['preview_css'] );
	}

	/**
	 * Output shape includes the documented keys.
	 */
	public function test_output_shape() {
		$result = $this->ability->execute( array() );

		$this->assertArrayHasKey( 'colors', $result );
		$this->assertArrayHasKey( 'custom_colors', $result );
		$this->assertArrayHasKey( 'saved', $result );
		$this->assertArrayHasKey( 'preview_css', $result );
	}

	/**
	 * Returns the STORED custom colour layer verbatim (slug => { hex, name }) — the
	 * shape set-active-colors accepts back — not a derived view.
	 */
	public function test_returns_stored_custom_colors() {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array( 'primary' => '#abcdef' ),
				'custom_colors' => array( 'surface-2' => array( 'hex' => '#aaaaaa' ) ),
			)
		);

		$result = $this->ability->execute( array() );

		$this->assertIsArray( $result['custom_colors'] );
		$this->assertSame( '#aaaaaa', $result['custom_colors']['surface-2']['hex'] );
	}

	/**
	 * The input schema declares an empty-object default so a no-input call (the
	 * browser GET on the run-route) normalizes to `{}` and validates against
	 * `type: object` instead of failing on a null/string input.
	 */
	public function test_input_schema_defaults_to_empty_object() {
		$schema = $this->ability->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'default', $schema );
		$this->assertSame( array(), $schema['default'] );
	}

	/**
	 * Requires edit_theme_options — a lower role gets a WP_Error.
	 */
	public function test_requires_edit_theme_options() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		$result = $this->ability->check_permission();
		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_rest_forbidden', $result->get_error_code() );
	}
}
