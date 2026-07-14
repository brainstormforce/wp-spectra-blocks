<?php
/**
 * Tests for GlobalStylesBridge::heal_global_styles_entity().
 *
 * Regression coverage for the fatal that took down every block-editor E2E
 * job: WP core's WP_REST_Global_Styles_Controller returns `settings` as an
 * empty `stdClass` when the user global-styles record has none (a fresh site
 * / non-user theme.json). Array-style access on that object — even inside
 * isset() — fatals in PHP 8 ("Cannot use object of type stdClass as array"),
 * so the heal filter must type-guard `settings` before touching nested keys.
 *
 * @package Spectra\Tests\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\GlobalStylesBridge;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * GlobalStylesBridgeHealTest test case.
 *
 * @since x.x.x
 */
class GlobalStylesBridgeHealTest extends WP_UnitTestCase {

	/**
	 * Subject under test.
	 *
	 * @var GlobalStylesBridge
	 */
	protected $bridge;

	/**
	 * Build a bridge instance. heal_global_styles_entity() does not touch the
	 * injected Engine, but the constructor requires one.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->bridge = new GlobalStylesBridge( Engine::get_instance() );
	}

	/**
	 * A GET on the user global-styles route.
	 *
	 * @return WP_REST_Request
	 */
	private function gs_request(): WP_REST_Request {
		return new WP_REST_Request( 'GET', '/wp/v2/global-styles/123' );
	}

	/**
	 * The regression: an empty `stdClass` `settings` (fresh site / TT5) must
	 * NOT fatal and must pass through untouched.
	 *
	 * @return void
	 */
	public function test_empty_stdclass_settings_does_not_fatal(): void {
		$data     = array(
			'settings' => new \stdClass(),
			'styles'   => new \stdClass(),
		);
		$response = new WP_REST_Response( $data );

		$result = $this->bridge->heal_global_styles_entity( $response, array(), $this->gs_request() );

		// If the guard were missing, the call above would have fatal-errored
		// before reaching this assertion.
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertInstanceOf( \stdClass::class, $result->get_data()['settings'] );
		$this->assertInstanceOf( \stdClass::class, $result->get_data()['styles'] );
	}

	/**
	 * A populated (array) palette must still be stripped — colour is owned by
	 * the Style Guide theme layer, so a stored palette can't shadow it.
	 *
	 * @return void
	 */
	public function test_populated_palette_is_stripped(): void {
		$data     = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						array(
							'slug'  => 'primary',
							'color' => '#050806',
							'name'  => 'Primary',
						),
					),
				),
			),
			'styles'   => new \stdClass(),
		);
		$response = new WP_REST_Response( $data );

		$result = $this->bridge->heal_global_styles_entity( $response, array(), $this->gs_request() );

		$this->assertArrayNotHasKey( 'palette', $result->get_data()['settings']['color'] );
	}

	/**
	 * Non-matching routes (e.g. the theme defaults route) are left untouched.
	 *
	 * @return void
	 */
	public function test_theme_defaults_route_is_ignored(): void {
		$data     = array(
			'settings' => array( 'color' => array( 'palette' => array( 'x' ) ) ),
			'styles'   => new \stdClass(),
		);
		$response = new WP_REST_Response( $data );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/global-styles/themes/twentytwentyfive' );

		$result = $this->bridge->heal_global_styles_entity( $response, array(), $request );

		$this->assertArrayHasKey( 'palette', $result->get_data()['settings']['color'] );
	}

	/**
	 * Non-GET methods are left untouched.
	 *
	 * @return void
	 */
	public function test_non_get_method_is_ignored(): void {
		$data     = array(
			'settings' => array( 'color' => array( 'palette' => array( 'x' ) ) ),
			'styles'   => new \stdClass(),
		);
		$response = new WP_REST_Response( $data );
		$request  = new WP_REST_Request( 'POST', '/wp/v2/global-styles/123' );

		$result = $this->bridge->heal_global_styles_entity( $response, array(), $request );

		$this->assertArrayHasKey( 'palette', $result->get_data()['settings']['color'] );
	}
}
