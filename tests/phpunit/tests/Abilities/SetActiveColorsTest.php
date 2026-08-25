<?php
/**
 * Tests for the SetActiveColors ability.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\SetActiveColors;
use SpectraBlocks\StyleGuide\Engine;

/**
 * SetActiveColors test case.
 */
class SetActiveColorsTest extends WP_UnitTestCase {

	/**
	 * Ability instance.
	 *
	 * @var SetActiveColors
	 */
	private $ability;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->ability = SetActiveColors::instance();

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
	 * Applies the palette and persists it to the site option.
	 */
	public function test_applies_and_persists() {
		$result = $this->ability->execute( array( 'colors' => array( 'primary' => '#ff0000' ) ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( '#ff0000', $result['colors']['primary'] );

		$stored = get_option( Engine::OPTION_KEY );
		$this->assertSame( '#ff0000', $stored['colors']['primary'] );
	}

	/**
	 * A partial map merges over the current palette — untouched roles are kept.
	 */
	public function test_partial_merge_preserves() {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array(
					'primary'   => '#111111',
					'secondary' => '#222222',
				),
				'custom_colors' => array(),
			)
		);

		$this->ability->execute( array( 'colors' => array( 'primary' => '#333333' ) ) );

		$stored = get_option( Engine::OPTION_KEY );
		$this->assertSame( '#333333', $stored['colors']['primary'] );
		$this->assertSame( '#222222', $stored['colors']['secondary'] );
	}

	/**
	 * Unknown slugs are ignored — they never reach the stored palette.
	 */
	public function test_unknown_slugs_ignored() {
		$this->ability->execute(
			array(
				'colors' => array(
					'primary' => '#ff0000',
					'bogus'   => '#abcdef',
				),
			)
		);

		$stored = get_option( Engine::OPTION_KEY );
		$this->assertSame( '#ff0000', $stored['colors']['primary'] );
		$this->assertArrayNotHasKey( 'bogus', $stored['colors'] );
	}

	/**
	 * An empty colour map is rejected before any write.
	 */
	public function test_empty_colors_errors() {
		$result = $this->ability->execute( array( 'colors' => array() ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_colors', $result->get_error_code() );
		$this->assertFalse( get_option( Engine::OPTION_KEY ) );
	}

	/**
	 * A map with only unparseable hexes is rejected (nothing valid to store).
	 */
	public function test_all_invalid_hexes_error() {
		$result = $this->ability->execute( array( 'colors' => array( 'primary' => 'not-a-hex' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_colors', $result->get_error_code() );
	}

	/**
	 * A custom_colors map is full-replaced onto the custom layer.
	 */
	public function test_custom_colors_full_replace() {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array( 'primary' => '#111111' ),
				'custom_colors' => array( 'surface-2' => array( 'hex' => '#aaaaaa' ) ),
			)
		);

		$this->ability->execute(
			array(
				'colors'        => array( 'primary' => '#222222' ),
				'custom_colors' => array( 'overlay' => array( 'hex' => '#bbbbbb' ) ),
			)
		);

		$stored = get_option( Engine::OPTION_KEY );
		$this->assertSame( '#bbbbbb', $stored['custom_colors']['overlay']['hex'] );
		$this->assertArrayNotHasKey( 'surface-2', $stored['custom_colors'] );
	}

	/**
	 * A bare 3/6-digit hex (no leading #) is normalised and applied, not dropped.
	 */
	public function test_normalizes_bare_hex() {
		$result = $this->ability->execute( array( 'colors' => array( 'primary' => 'ff0000' ) ) );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '#ff0000', $result['colors']['primary'] );
		$this->assertSame( array(), $result['ignored'] );
	}

	/**
	 * A provided role with an unparseable value is reported in `ignored`, not
	 * silently dropped while the rest still apply.
	 */
	public function test_reports_ignored_invalid_roles() {
		$result = $this->ability->execute(
			array(
				'colors' => array(
					'primary' => '#ff0000',
					'heading' => 'not-a-color',
				),
			)
		);

		$this->assertTrue( $result['success'] );
		$this->assertSame( '#ff0000', $result['colors']['primary'] );
		$this->assertContains( 'heading', $result['ignored'] );
	}

	/**
	 * Non-core slugs (typos / invented names) are reported in `unknown`, separate
	 * from `ignored`, while the valid core roles still apply.
	 */
	public function test_reports_unknown_slugs() {
		$result = $this->ability->execute(
			array(
				'colors' => array(
					'primary' => '#ff0000',
					'primry'  => '#00ff00',
				),
			)
		);

		$this->assertTrue( $result['success'] );
		$this->assertSame( '#ff0000', $result['colors']['primary'] );
		$this->assertContains( 'primry', $result['unknown'] );
		$this->assertNotContains( 'primry', $result['ignored'] );
	}

	/**
	 * Omitting custom_colors leaves the existing custom layer untouched (only an
	 * explicit custom_colors map full-replaces it).
	 */
	public function test_omitting_custom_colors_preserves_layer() {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array( 'primary' => '#111111' ),
				'custom_colors' => array( 'surface-2' => array( 'hex' => '#aaaaaa' ) ),
			)
		);

		$this->ability->execute( array( 'colors' => array( 'primary' => '#222222' ) ) );

		$stored = get_option( Engine::OPTION_KEY );
		$this->assertSame( '#aaaaaa', $stored['custom_colors']['surface-2']['hex'] );
	}

	/**
	 * Returns ready-to-inject preview CSS for the applied palette.
	 */
	public function test_preview_css_returned() {
		$result = $this->ability->execute( array( 'colors' => array( 'primary' => '#ff0000' ) ) );

		$this->assertIsString( $result['preview_css'] );
		$this->assertStringContainsString( '--wp--preset--color--primary', $result['preview_css'] );
	}

	/**
	 * After a successful apply, the site counts as having a saved Style Guide.
	 */
	public function test_saved_flag_true_after_apply() {
		$this->assertFalse( Engine::get_instance()->has_saved_style_guide() );

		$this->ability->execute( array( 'colors' => array( 'primary' => '#ff0000' ) ) );

		$this->assertTrue( Engine::get_instance()->has_saved_style_guide() );
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
