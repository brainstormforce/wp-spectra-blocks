<?php
/**
 * Tests for AbilitiesManager.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\AbilitiesManager;

/**
 * AbilitiesManager test case.
 */
class AbilitiesManagerTest extends WP_UnitTestCase {

	/**
	 * Whether the real WP Abilities API is available (WP 6.9+).
	 *
	 * @return bool
	 */
	private function has_real_abilities_api(): bool {
		return function_exists( 'wp_has_ability' );
	}

	/**
	 * Expected ability category slugs.
	 *
	 * @return string[]
	 */
	private function expected_categories(): array {
		return array(
			'spectra-blocks-discovery',
			'spectra-blocks-content',
			'spectra-blocks-layout',
			'spectra-blocks-configuration',
			'spectra-blocks-extensions',
		);
	}

	/**
	 * Expected ability names.
	 *
	 * @return string[]
	 */
	private function expected_abilities(): array {
		return array(
			// Discovery.
			'spectra-blocks/list-available-blocks',
			'spectra-blocks/get-block-config',
			'spectra-blocks/get-post-content',
			'spectra-blocks/search-posts-by-block',
			'spectra-blocks/search-post-content',
			// Layout.
			'spectra-blocks/generate-page-layout',
			'spectra-blocks/create-container',
			'spectra-blocks/create-modal',
			'spectra-blocks/create-slider',
			// Content.
			'spectra-blocks/create-accordion',
			'spectra-blocks/create-buttons',
			'spectra-blocks/create-countdown',
			'spectra-blocks/create-counter',
			'spectra-blocks/create-google-map',
			'spectra-blocks/create-icons',
			'spectra-blocks/create-list',
			'spectra-blocks/create-content',
			'spectra-blocks/create-separator',
			'spectra-blocks/create-tabs',
			'spectra-blocks/update-block-attributes',
			'spectra-blocks/remove-block',
			'spectra-blocks/move-block',
			'spectra-blocks/duplicate-block',
			// Analytics.
			'spectra-blocks/get-analytics-summary',
			// Configuration.
			'spectra-blocks/get-plugin-settings',
			'spectra-blocks/update-plugin-setting',
			'spectra-blocks/get-block-activation-status',
			'spectra-blocks/toggle-block-activation',
			'spectra-blocks/list-selected-fonts',
			'spectra-blocks/list-available-google-fonts',
			'spectra-blocks/add-google-font',
			'spectra-blocks/remove-google-font',
			// Popup Management.
			'spectra-blocks/create-popup',
			'spectra-blocks/list-popups',
			'spectra-blocks/get-popup',
			'spectra-blocks/toggle-popup-status',
			'spectra-blocks/delete-popup',
			'spectra-blocks/update-popup',
			// Extensions.
			'spectra-blocks/apply-animation',
			'spectra-blocks/remove-animation',
			'spectra-blocks/apply-sticky',
			'spectra-blocks/remove-sticky',
			'spectra-blocks/apply-responsive-conditions',
			'spectra-blocks/remove-responsive-conditions',
			'spectra-blocks/apply-zindex',
			'spectra-blocks/apply-image-mask',
			'spectra-blocks/apply-display-conditions',
			'spectra-blocks/remove-display-conditions',
			// Content abilities.
			'spectra-blocks/create-post',
		);
	}

	/**
	 * Ensure categories and abilities are registered.
	 *
	 * On WP 6.9+ they are registered during plugin boot via hooks (bootstrap.php enables the option).
	 * On older WP they use stubs; we call the manager directly.
	 */
	private function ensure_registered(): void {
		if ( $this->has_real_abilities_api() ) {
			// Plugin boot registered abilities via wp_abilities_api_init — nothing to do here.
			return;
		}

		global $_spectra_test_registered_abilities, $_spectra_test_registered_ability_categories;
		$_spectra_test_registered_abilities          = array();
		$_spectra_test_registered_ability_categories = array();

		$manager = AbilitiesManager::instance();
		$manager->register_categories();
		$manager->register_abilities();
	}

	/**
	 * Test that all five ability categories are registered.
	 */
	public function test_register_categories() {
		$this->ensure_registered();

		foreach ( $this->expected_categories() as $slug ) {
			if ( $this->has_real_abilities_api() ) {
				$this->assertTrue( wp_has_ability_category( $slug ), "Category '$slug' should be registered." );
			} else {
				global $_spectra_test_registered_ability_categories;
				$this->assertArrayHasKey( $slug, $_spectra_test_registered_ability_categories, "Category '$slug' should be registered." );
			}
		}
	}

	/**
	 * Test that all 49 abilities are registered.
	 */
	public function test_register_abilities() {
		$this->ensure_registered();

		foreach ( $this->expected_abilities() as $name ) {
			if ( $this->has_real_abilities_api() ) {
				$this->assertTrue( wp_has_ability( $name ), "Ability '$name' should be registered." );
			} else {
				global $_spectra_test_registered_abilities;
				$this->assertArrayHasKey( $name, $_spectra_test_registered_abilities, "Ability '$name' should be registered." );
			}
		}
	}

	/**
	 * Test that each registered ability has required arguments.
	 */
	public function test_registered_abilities_have_required_args() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			foreach ( $this->expected_abilities() as $name ) {
				$ability = wp_get_ability( $name );
				$this->assertNotNull( $ability, "Ability '$name' should be registered." );
				$this->assertNotEmpty( $ability->get_label(), "Ability '$name' should have a label." );
				$this->assertNotEmpty( $ability->get_description(), "Ability '$name' should have a description." );
				$this->assertNotEmpty( $ability->get_category(), "Ability '$name' should have a category." );
			}
		} else {
			global $_spectra_test_registered_abilities;
			$required_keys = array( 'label', 'description', 'category', 'input_schema', 'output_schema', 'execute_callback', 'permission_callback', 'meta' );

			foreach ( $_spectra_test_registered_abilities as $name => $args ) {
				foreach ( $required_keys as $key ) {
					$this->assertArrayHasKey( $key, $args, "Ability '$name' should have '$key' argument." );
				}
			}
		}
	}

	/**
	 * Test that each registered ability has a callable callback.
	 */
	public function test_registered_abilities_have_callable_callbacks() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			foreach ( $this->expected_abilities() as $name ) {
				$ability = wp_get_ability( $name );
				$this->assertNotNull( $ability, "Ability '$name' should be registered." );
				// WP_Ability properties are protected; verify the ability object exists and has methods.
				$this->assertTrue( method_exists( $ability, 'execute' ), "Ability '$name' should have execute method." );
				$this->assertTrue( method_exists( $ability, 'check_permissions' ), "Ability '$name' should have check_permissions method." );
			}
		} else {
			global $_spectra_test_registered_abilities;

			foreach ( $_spectra_test_registered_abilities as $name => $args ) {
				$this->assertTrue( is_callable( $args['execute_callback'] ), "Ability '$name' execute_callback should be callable." );
				$this->assertTrue( is_callable( $args['permission_callback'] ), "Ability '$name' permission_callback should be callable." );
			}
		}
	}

	/**
	 * Test that AbilitiesManager hooks into wp_abilities_api_init.
	 */
	public function test_abilities_manager_init_hooks() {
		$manager = AbilitiesManager::instance();

		// Verify the instance has the expected methods.
		$this->assertTrue( method_exists( $manager, 'register_categories' ) );
		$this->assertTrue( method_exists( $manager, 'register_abilities' ) );
	}

	/**
	 * Test that each registered category has required arguments.
	 */
	public function test_registered_categories_have_required_args() {
		$this->ensure_registered();

		foreach ( $this->expected_categories() as $slug ) {
			if ( $this->has_real_abilities_api() ) {
				$category = wp_get_ability_category( $slug );
				$this->assertNotNull( $category, "Category '$slug' should be registered." );
				$this->assertNotEmpty( $category->get_label(), "Category '$slug' should have a label." );
			} else {
				global $_spectra_test_registered_ability_categories;
				$this->assertArrayHasKey( 'label', $_spectra_test_registered_ability_categories[ $slug ], "Category '$slug' should have a label." );
			}
		}
	}

	/**
	 * Read-only ability names.
	 *
	 * @return string[]
	 */
	private function readonly_abilities(): array {
		return array(
			'spectra-blocks/list-available-blocks',
			'spectra-blocks/get-block-config',
			'spectra-blocks/get-post-content',
			'spectra-blocks/search-posts-by-block',
			'spectra-blocks/search-post-content',
			'spectra-blocks/get-analytics-summary',
			'spectra-blocks/get-plugin-settings',
			'spectra-blocks/get-block-activation-status',
			'spectra-blocks/list-selected-fonts',
			'spectra-blocks/list-available-google-fonts',
			'spectra-blocks/list-popups',
			'spectra-blocks/get-popup',
			'spectra-blocks/get-global-styles-config',
		);
	}

	/**
	 * Destructive ability names.
	 *
	 * @return string[]
	 */
	private function destructive_abilities(): array {
		return array(
			'spectra-blocks/remove-block',
			'spectra-blocks/remove-google-font',
			'spectra-blocks/delete-popup',
		);
	}

	/**
	 * Idempotent write ability names.
	 *
	 * @return string[]
	 */
	private function idempotent_write_abilities(): array {
		return array(
			'spectra-blocks/update-block-attributes',
			'spectra-blocks/move-block',
			'spectra-blocks/update-plugin-setting',
			'spectra-blocks/toggle-block-activation',
			'spectra-blocks/add-google-font',
			'spectra-blocks/toggle-popup-status',
			'spectra-blocks/update-popup',
			'spectra-blocks/apply-animation',
			'spectra-blocks/remove-animation',
			'spectra-blocks/apply-sticky',
			'spectra-blocks/remove-sticky',
			'spectra-blocks/apply-responsive-conditions',
			'spectra-blocks/remove-responsive-conditions',
			'spectra-blocks/apply-zindex',
			'spectra-blocks/apply-image-mask',
			'spectra-blocks/apply-display-conditions',
			'spectra-blocks/remove-display-conditions',
			'spectra-blocks/update-global-styles',
		);
	}

	/**
	 * Test that all abilities have show_in_rest: true in meta.
	 */
	public function test_all_abilities_have_show_in_rest() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			$this->markTestSkipped( 'Meta inspection requires stub API.' );
		}

		global $_spectra_test_registered_abilities;

		foreach ( $_spectra_test_registered_abilities as $name => $args ) {
			$this->assertArrayHasKey( 'meta', $args, "Ability '$name' should have 'meta' argument." );
			$this->assertTrue( $args['meta']['show_in_rest'], "Ability '$name' should have show_in_rest: true." );
			$this->assertArrayHasKey( 'annotations', $args['meta'], "Ability '$name' should have annotations in meta." );
		}
	}

	/**
	 * Test that read-only abilities have readonly: true annotation.
	 */
	public function test_readonly_abilities_have_correct_annotations() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			$this->markTestSkipped( 'Meta inspection requires stub API.' );
		}

		global $_spectra_test_registered_abilities;

		foreach ( $this->readonly_abilities() as $name ) {
			$annotations = $_spectra_test_registered_abilities[ $name ]['meta']['annotations'];
			$this->assertTrue( $annotations['readonly'], "Ability '$name' should be readonly." );
			$this->assertFalse( $annotations['destructive'], "Ability '$name' should not be destructive." );
			$this->assertTrue( $annotations['idempotent'], "Ability '$name' should be idempotent." );
		}
	}

	/**
	 * Test that destructive abilities have destructive: true annotation.
	 */
	public function test_destructive_abilities_have_correct_annotations() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			$this->markTestSkipped( 'Meta inspection requires stub API.' );
		}

		global $_spectra_test_registered_abilities;

		foreach ( $this->destructive_abilities() as $name ) {
			$annotations = $_spectra_test_registered_abilities[ $name ]['meta']['annotations'];
			$this->assertFalse( $annotations['readonly'], "Ability '$name' should not be readonly." );
			$this->assertTrue( $annotations['destructive'], "Ability '$name' should be destructive." );
			$this->assertTrue( $annotations['idempotent'], "Ability '$name' should be idempotent." );
		}
	}

	/**
	 * Test that idempotent write abilities have correct annotations.
	 */
	public function test_idempotent_write_abilities_have_correct_annotations() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			$this->markTestSkipped( 'Meta inspection requires stub API.' );
		}

		global $_spectra_test_registered_abilities;

		foreach ( $this->idempotent_write_abilities() as $name ) {
			$annotations = $_spectra_test_registered_abilities[ $name ]['meta']['annotations'];
			$this->assertFalse( $annotations['readonly'], "Ability '$name' should not be readonly." );
			$this->assertFalse( $annotations['destructive'], "Ability '$name' should not be destructive." );
			$this->assertTrue( $annotations['idempotent'], "Ability '$name' should be idempotent." );
		}
	}

	/**
	 * Test that non-idempotent (create) abilities have all-false annotations.
	 */
	public function test_non_idempotent_abilities_have_default_annotations() {
		$this->ensure_registered();

		if ( $this->has_real_abilities_api() ) {
			$this->markTestSkipped( 'Meta inspection requires stub API.' );
		}

		global $_spectra_test_registered_abilities;

		$annotated = array_merge(
			$this->readonly_abilities(),
			$this->destructive_abilities(),
			$this->idempotent_write_abilities()
		);

		foreach ( $_spectra_test_registered_abilities as $name => $args ) {
			if ( in_array( $name, $annotated, true ) ) {
				continue;
			}
			$annotations = $args['meta']['annotations'];
			$this->assertFalse( $annotations['readonly'], "Ability '$name' should not be readonly." );
			$this->assertFalse( $annotations['destructive'], "Ability '$name' should not be destructive." );
			$this->assertFalse( $annotations['idempotent'], "Ability '$name' should not be idempotent." );
		}
	}

	/**
	 * Test that abilities belong to valid categories.
	 */
	public function test_registered_abilities_use_valid_categories() {
		$this->ensure_registered();

		$valid = $this->expected_categories();

		if ( $this->has_real_abilities_api() ) {
			foreach ( $this->expected_abilities() as $name ) {
				$ability = wp_get_ability( $name );
				$this->assertNotNull( $ability, "Ability '$name' should be registered." );
				$this->assertContains( $ability->get_category(), $valid, "Ability '$name' has invalid category." );
			}
		} else {
			global $_spectra_test_registered_abilities;

			foreach ( $_spectra_test_registered_abilities as $name => $args ) {
				$this->assertContains( $args['category'], $valid, "Ability '$name' has invalid category '{$args['category']}'." );
			}
		}
	}
}
