<?php
/**
 * Tests for the Global Styles bulk REST endpoint.
 *
 * Covers the happy-path round-trip for classes + keyframes in a single
 * idempotent write, structured rejection of reserved keyframe names and
 * invalid class-name patterns, and capability enforcement.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * BulkEndpointTest test case.
 *
 * @since x.x.x
 */
class BulkEndpointTest extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected $admin_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	protected $subscriber_id;

	/**
	 * Boot the REST server and route registration before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$this->server   = new WP_REST_Server();
		$wp_rest_server = $this->server;
		do_action( 'rest_api_init' );

		$this->admin_id      = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		delete_option( Engine::OPTION_KEY_USER_CSS );
	}

	/**
	 * Reset REST server after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		delete_option( Engine::OPTION_KEY_USER_CSS );
		parent::tearDown();
	}

	/**
	 * Happy path: valid classes + keyframes persist in one call.
	 *
	 * @return void
	 */
	public function test_bulk_writes_classes_and_keyframes(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param(
			'classes',
			array(
				'gs-cat-card' => array(
					'default' => array(
						'color' => 'blue',
					),
				),
			)
		);
		$request->set_param(
			'keyframes',
			array(
				'softFadeUp' => array(
					'css' => 'from { opacity: 0; } to { opacity: 1; }',
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertContains( 'gs-cat-card', $data['written_classes'] );
		$this->assertContains( 'softFadeUp', $data['written_keyframes'] );
		$this->assertEmpty( $data['skipped_classes'] );
		$this->assertEmpty( $data['skipped_keyframes'] );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS );
		$this->assertArrayHasKey( 'gs-cat-card', $stored['classes'] );
		$this->assertArrayHasKey( 'softFadeUp', $stored['keyframes'] );
	}

	/**
	 * Class names that don't match `gs-*` are rejected with `invalid_name`.
	 *
	 * @return void
	 */
	public function test_bulk_rejects_invalid_class_name(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param(
			'classes',
			array(
				'ast-bad-name'   => array(
					'default' => array(
						array(
							'property' => 'color',
							'value'    => 'red',
						),
					),
				),
				'gc-spectra-xyz' => array(
					'default' => array(
						array(
							'property' => 'color',
							'value'    => 'red',
						),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEmpty( $data['written_classes'] );

		$reasons = array();
		foreach ( $data['skipped_classes'] as $entry ) {
			$reasons[ $entry['name'] ] = $entry['reason'];
		}
		$this->assertSame( 'invalid_name', $reasons['ast-bad-name'] );
		$this->assertSame( 'invalid_name', $reasons['gc-spectra-xyz'] );
	}

	/**
	 * Keyframes using reserved names (`spectra-spin`, etc.) are rejected
	 * with the structured reason `reserved_name`.
	 *
	 * @return void
	 */
	public function test_bulk_rejects_reserved_keyframe_name(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param(
			'keyframes',
			array(
				'spectra-spin' => array(
					'css' => 'from { opacity: 0; }',
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEmpty( $data['written_keyframes'] );
		$this->assertSame( 'spectra-spin', $data['skipped_keyframes'][0]['name'] );
		$this->assertSame( 'reserved_name', $data['skipped_keyframes'][0]['reason'] );
	}

	/**
	 * Non-admin requests are rejected with 403.
	 *
	 * @return void
	 */
	public function test_bulk_requires_capability(): void {
		wp_set_current_user( $this->subscriber_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param( 'classes', array() );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * A mix of valid + invalid entries partially succeeds — valid ones land,
	 * invalid ones land in the `skipped_*` arrays.
	 *
	 * @return void
	 */
	public function test_bulk_partial_success(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param(
			'classes',
			array(
				'gs-hero-glow' => array(
					'default' => array(
						'box-shadow' => '0 0 20px rgba(0, 0, 0, 0.1)',
					),
				),
				'swt-invalid'  => array(
					'default' => array(
						array(
							'property' => 'color',
							'value'    => 'red',
						),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertContains( 'gs-hero-glow', $data['written_classes'] );
		$this->assertSame( 'swt-invalid', $data['skipped_classes'][0]['name'] );
		$this->assertSame( 'invalid_name', $data['skipped_classes'][0]['reason'] );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS );
		$this->assertArrayHasKey( 'gs-hero-glow', $stored['classes'] );
		$this->assertArrayNotHasKey( 'swt-invalid', $stored['classes'] );
	}

	/**
	 * Empty classes body is skipped with `empty_styles`, not written.
	 *
	 * @return void
	 */
	public function test_bulk_skips_empty_class_body(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/bulk' );
		$request->set_param(
			'classes',
			array(
				'gs-empty' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEmpty( $data['written_classes'] );
		$this->assertSame( 'empty_styles', $data['skipped_classes'][0]['reason'] );
	}
}
