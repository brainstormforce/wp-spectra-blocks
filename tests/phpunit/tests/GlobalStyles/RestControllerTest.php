<?php
/**
 * Tests for the Global Styles REST controller.
 *
 * Covers capability enforcement, GET round-trips, POST create/update/delete
 * for custom classes and keyframes, and duplicate-check behavior for
 * keyframes.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use SpectraBlocks\GlobalStyles\JitCache;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * RestControllerTest test case.
 *
 * @since x.x.x
 */
class RestControllerTest extends WP_UnitTestCase {

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
		delete_option( JitCache::VERSION_OPTION );
		parent::tearDown();
	}

	// ─────────────────────────────────────────────────────────────
	// Capability
	// ─────────────────────────────────────────────────────────────

	/**
	 * Unauthenticated requests are rejected.
	 *
	 * @return void
	 */
	public function test_get_classes_requires_capability(): void {
		wp_set_current_user( $this->subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/custom-classes' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	// ─────────────────────────────────────────────────────────────
	// Custom classes
	// ─────────────────────────────────────────────────────────────

	/**
	 * GET returns empty classes when option is unset.
	 *
	 * @return void
	 */
	public function test_get_classes_empty(): void {
		wp_set_current_user( $this->admin_id );
		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/custom-classes' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'classes', $data );
		$this->assertSame( array(), $data['classes'] );
	}

	/**
	 * POST stores a class and GET returns it.
	 *
	 * @return void
	 */
	public function test_post_and_get_class_round_trip(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$request->set_param( 'class_name', 'my-card' );
		$request->set_param(
			'styles',
			array(
				'default' => array(
					'color' => 'red',
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$get      = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/custom-classes' );
		$get_resp = $this->server->dispatch( $get );
		$data     = $get_resp->get_data();
		$this->assertArrayHasKey( 'my-card', $data['classes'] );
	}

	// ─────────────────────────────────────────────────────────────
	// Vibe Editing: live-paint render + full user-css read
	// ─────────────────────────────────────────────────────────────

	/**
	 * POST /render renders a schema-v1 payload to EDITOR-scoped CSS via the SSOT
	 * GenCssRenderer (page scope) and persists NOTHING.
	 *
	 * @return void
	 */
	public function test_render_payload_returns_editor_scoped_css_and_persists_nothing(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/render' );
		$request->set_param( 'scope', 'page' );
		$request->set_param( 'post_id', 123 );
		$request->set_param(
			'payload',
			array(
				'v'       => '1',
				'classes' => array(
					'gs-test-card' => array( 'default' => array( 'color' => 'red' ) ),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		// Editor scope → `.editor-styles-wrapper` prefix (the GBS tier).
		$this->assertStringContainsString( '.editor-styles-wrapper', $data['css'] );
		$this->assertStringContainsString( 'gs-test-card', $data['css'] );
		// Render persists nothing — the option is untouched.
		$this->assertSame( array(), get_option( Engine::OPTION_KEY_USER_CSS, array() ) );
	}

	/**
	 * GET /user-css returns the FULL option (every bucket), unlike
	 * /custom-classes which returns only `classes`.
	 *
	 * @return void
	 */
	public function test_get_user_css_full_returns_all_buckets(): void {
		wp_set_current_user( $this->admin_id );
		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'v'             => '1',
				'classes'       => array( 'gs-x' => array( 'default' => array( 'gap' => '1rem' ) ) ),
				'wrapperStyles' => array( 'header' => array( 'background' => '#000' ) ),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/user-css' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'classes', $data['payload'] );
		$this->assertArrayHasKey( 'wrapperStyles', $data['payload'] );
	}

	/**
	 * GET /user-css requires edit_theme_options (same gate as the write).
	 *
	 * @return void
	 */
	public function test_get_user_css_requires_capability(): void {
		wp_set_current_user( $this->subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/user-css' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * POST with empty styles creates a placeholder class (ClassFlyout pattern).
	 *
	 * The flyout creates classes with empty styles so the user lands directly
	 * in the CSS editor. An empty class is a valid placeholder — CSS is added
	 * in a subsequent save call.
	 *
	 * @return void
	 */
	public function test_post_class_with_empty_styles_creates_placeholder(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$request->set_param( 'class_name', 'my-card' );
		$request->set_param( 'styles', array() );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayHasKey( 'my-card', $stored['classes'] );
		$this->assertSame( array(), $stored['classes']['my-card'] );
	}

	/**
	 * POST accepts responsive buckets (md/sm) and compound buckets (md_hover).
	 *
	 * @return void
	 */
	public function test_post_class_accepts_responsive_buckets(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$request->set_param( 'class_name', 'my-card' );
		$request->set_param(
			'styles',
			array(
				'default'  => array(
					'padding' => '1rem',
				),
				'md'       => array(
					'padding' => '2rem',
				),
				'sm'       => array(
					'padding' => '0.5rem',
				),
				'md_hover' => array(
					'transform' => 'translateY(-4px)',
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayHasKey( 'md', $stored['classes']['my-card'] );
		$this->assertArrayHasKey( 'sm', $stored['classes']['my-card'] );
		$this->assertArrayHasKey( 'md_hover', $stored['classes']['my-card'] );
	}

	/**
	 * POST rejects declaration values containing `var(...)` via strict sanitizer.
	 *
	 * @return void
	 */
	public function test_post_class_strips_var_references(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$request->set_param( 'class_name', 'my-card' );
		$request->set_param(
			'styles',
			array(
				'default' => array(
					'color' => 'var(--spectra-chromatic1-6)',
				),
			)
		);

		$this->server->dispatch( $request );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		// REST controller normalises every persisted class to Spectra's
		// canonical flat-object shape. Either the class should not be stored
		// at all (sanitizer dropped the value), or the persisted value must
		// be stripped.
		if ( isset( $stored['classes']['my-card']['default']['color'] ) ) {
			$this->assertNotSame(
				'var(--spectra-chromatic1-6)',
				$stored['classes']['my-card']['default']['color']
			);
		}
	}

	/**
	 * The controller persists classes in the canonical flat-object shape —
	 * the only shape it accepts. Locks the contract that every write lands in
	 * the single canonical shape the PHP engine reads and the admin UI's
	 * CodeMirror reader expects.
	 *
	 * @return void
	 */
	public function test_post_class_persists_canonical_flat_object_shape(): void {
		wp_set_current_user( $this->admin_id );

		$legacy_request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$legacy_request->set_param( 'class_name', 'gs-legacy' );
		$legacy_request->set_param(
			'styles',
			array(
				'default' => array(
					'color'  => 'red',
					'margin' => '10px',
				),
				'hover'   => array(
					'color' => 'darkred',
				),
			)
		);
		$this->server->dispatch( $legacy_request );

		// Canonical flat-object input — must pass through unchanged.
		$canonical_request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$canonical_request->set_param( 'class_name', 'gs-canonical' );
		$canonical_request->set_param(
			'styles',
			array(
				'default' => array(
					'color'  => 'blue',
					'margin' => '20px',
				),
				'md'      => array(
					'font-size' => '18px',
				),
			)
		);
		$this->server->dispatch( $canonical_request );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );

		$this->assertSame(
			array(
				'default' => array(
					'color'  => 'red',
					'margin' => '10px',
				),
				'hover'   => array(
					'color' => 'darkred',
				),
			),
			$stored['classes']['gs-legacy']
		);

		$this->assertSame(
			array(
				'default' => array(
					'color'  => 'blue',
					'margin' => '20px',
				),
				'md'      => array(
					'font-size' => '18px',
				),
			),
			$stored['classes']['gs-canonical']
		);
	}

	/**
	 * Destructive POST removes the class.
	 *
	 * Seeds the option with a class, then verifies the destructive remove
	 * path deletes it. Canonical persistence shape is flat property:value.
	 *
	 * @return void
	 */
	public function test_destructive_post_removes_class(): void {
		wp_set_current_user( $this->admin_id );

		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'classes' => array(
					'my-card' => array(
						'default' => array(
							'color' => 'red',
						),
					),
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/custom-classes' );
		$request->set_param( 'class_name', 'my-card' );
		$request->set_param( 'is_destructive', true );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayNotHasKey( 'my-card', $stored['classes'] );
	}

	// ─────────────────────────────────────────────────────────────
	// Keyframes
	// ─────────────────────────────────────────────────────────────

	/**
	 * Keyframes can be created, listed, and deleted.
	 *
	 * @return void
	 */
	public function test_keyframes_crud(): void {
		wp_set_current_user( $this->admin_id );

		$create = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/keyframes' );
		$create->set_param( 'keyframe_name', 'fade-in' );
		$create->set_param(
			'keyframe_data',
			array(
				'css'  => 'from { opacity: 0; } to { opacity: 1; }',
				'meta' => array(
					'defaultDuration'   => '0.3s',
					'defaultEasing'     => 'ease-out',
					'defaultIterations' => '1',
				),
			)
		);

		$create_resp = $this->server->dispatch( $create );
		$this->assertSame( 200, $create_resp->get_status() );

		$get      = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/keyframes' );
		$get_resp = $this->server->dispatch( $get );
		$this->assertArrayHasKey( 'fade-in', $get_resp->get_data()['keyframes'] );

		$delete = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/keyframes' );
		$delete->set_param( 'keyframe_name', 'fade-in' );
		$delete->set_param( 'is_destructive', true );

		$delete_resp = $this->server->dispatch( $delete );
		$this->assertSame( 200, $delete_resp->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayNotHasKey( 'fade-in', $stored['keyframes'] );
	}

	/**
	 * check_duplicate=true returns 409 when keyframe name already exists.
	 *
	 * @return void
	 */
	public function test_keyframes_duplicate_check(): void {
		wp_set_current_user( $this->admin_id );

		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'keyframes' => array(
					'fade-in' => array(
						'css'  => 'from { opacity: 0; } to { opacity: 1; }',
						'meta' => array(
							'defaultDuration'   => '0.3s',
							'defaultEasing'     => 'ease-out',
							'defaultIterations' => '1',
						),
					),
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/keyframes' );
		$request->set_param( 'keyframe_name', 'fade-in' );
		$request->set_param(
			'keyframe_data',
			array(
				'css' => 'from { opacity: 0; } to { opacity: 1; }',
			)
		);
		$request->set_param( 'check_duplicate', true );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 409, $response->get_status() );
	}

	// ─────────────────────────────────────────────────────────────
	// Metadata catalogue
	// ─────────────────────────────────────────────────────────────

	/**
	 * Metadata endpoint rejects unauthorized callers.
	 *
	 * @return void
	 */
	public function test_metadata_requires_capability(): void {
		wp_set_current_user( $this->subscriber_id );
		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Metadata endpoint returns utilities + bracket_prefixes for admins.
	 *
	 * @return void
	 */
	public function test_metadata_returns_catalogue(): void {
		wp_set_current_user( $this->admin_id );
		$request  = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'utilities', $data );
		$this->assertArrayHasKey( 'bracket_prefixes', $data );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertSame( 'private, max-age=60', $headers['Cache-Control'] );
	}

	/**
	 * Matching If-None-Match yields a 304 with empty body.
	 *
	 * @return void
	 */
	public function test_metadata_returns_304_on_matching_etag(): void {
		wp_set_current_user( $this->admin_id );

		update_option( JitCache::VERSION_OPTION, 'v-test-1', false );

		$first    = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' );
		$first_rs = $this->server->dispatch( $first );
		$etag     = $first_rs->get_headers()['ETag'] ?? '';
		$this->assertNotSame( '', $etag );

		$second = new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' );
		$second->set_header( 'If-None-Match', $etag );
		$second_rs = $this->server->dispatch( $second );

		$this->assertSame( 304, $second_rs->get_status() );
		$this->assertNull( $second_rs->get_data() );
	}

	/**
	 * Bumping the JIT version option changes the ETag.
	 *
	 * @return void
	 */
	public function test_metadata_etag_changes_with_version_option(): void {
		wp_set_current_user( $this->admin_id );

		update_option( JitCache::VERSION_OPTION, 'v-test-1', false );
		$first = $this->server->dispatch( new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' ) );
		$etag1 = $first->get_headers()['ETag'] ?? '';

		update_option( JitCache::VERSION_OPTION, 'v-test-2', false );
		$second = $this->server->dispatch( new WP_REST_Request( 'GET', '/spectra-blocks/v1/global-styles/metadata' ) );
		$etag2  = $second->get_headers()['ETag'] ?? '';

		$this->assertNotSame( '', $etag1 );
		$this->assertNotSame( '', $etag2 );
		$this->assertNotSame( $etag1, $etag2 );
	}

	// ─────────────────────────────────────────────────────────────
	// Region-keyed V2 site-wide store (update_sitewide)
	// ─────────────────────────────────────────────────────────────

	/**
	 * The import site-wide write MERGES into the user-editable GBS option
	 * without clobbering user-authored classes, and sets the non-class
	 * buckets (rootStyles) alongside them.
	 *
	 * @return void
	 */
	public function test_update_sitewide_merges_without_clobbering_user_classes(): void {
		wp_set_current_user( $this->admin_id );

		// A pre-existing user-authored class (as the GBS admin UI would store).
		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'classes' => array(
					'my-card' => array(
						'default' => array( 'color' => 'red' ),
					),
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$request->set_param(
			'payload',
			array(
				'v'          => '1',
				'classes'    => array(
					'gs-imp-hero' => array(
						'default' => array( 'padding' => '80px' ),
					),
				),
				'rootStyles' => array( ':root' => array( '--brand' => '#111' ) ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayHasKey( 'my-card', $stored['classes'], 'user-authored class must survive the import merge' );
		$this->assertArrayHasKey( 'gs-imp-hero', $stored['classes'], 'import class must be merged into the GBS option' );
		$this->assertSame( array( ':root' => array( '--brand' => '#111' ) ), $stored['rootStyles'], 'non-class buckets must be stored as siblings' );
	}

	/**
	 * Regression: the SaaS/editor authoring path emits CSS-in-JS lowerCamelCase
	 * property names (`backgroundColor`, `zIndex`) and JS-toggled state classes
	 * WITHOUT their leading dot (`is-open`, the bare class its JS toggles). Both
	 * are invalid in the GBS store (renderers print keys/state suffixes verbatim,
	 * so `backgroundColor:…` is dropped by the browser and a bare `is-open` state
	 * resolves to no selector). The write choke point must canonicalize BOTH:
	 * property keys → kebab-case, and `is-…`/`has-…` state keys → leading-dot
	 * tails — while leaving pseudos, `default`, and `--` custom properties intact.
	 *
	 * @return void
	 */
	public function test_class_declarations_normalize_camelcase_props_and_toggle_state(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$request->set_param(
			'payload',
			array(
				'v'       => '1',
				'classes' => array(
					'arn-overlay' => array(
						// camelCase props + a --var (must stay) in the base state.
						'default' => array(
							'backgroundColor'      => 'rgba(0,0,0,0.7)',
							'zIndex'               => '9999',
							'justifyContent'       => 'center',
							// Vendor prefix (no leading dash) — must regain it.
							'WebkitBackgroundClip' => 'text',
							'--myVar'              => '10px',
							'opacity'              => '0',
						),
						// Bare JS-toggle state → must become `.is-open`.
						'is-open' => array( 'opacity' => '1' ),
						// A real pseudo → must stay `hover`.
						'hover'   => array( 'backdropFilter' => 'blur(4px)' ),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$cls    = $stored['classes']['arn-overlay'];

		// Facet 1 — property keys kebab-cased; `--` custom prop untouched.
		$this->assertArrayHasKey( 'background-color', $cls['default'] );
		$this->assertArrayHasKey( 'z-index', $cls['default'] );
		$this->assertArrayHasKey( 'justify-content', $cls['default'] );
		$this->assertArrayHasKey( '--myVar', $cls['default'], 'CSS custom properties are case-sensitive — must NOT be hyphenated' );
		$this->assertArrayHasKey( '-webkit-background-clip', $cls['default'], 'a camelCase vendor prefix must regain its leading dash' );
		$this->assertArrayNotHasKey( 'webkit-background-clip', $cls['default'], 'a vendor prefix without the leading dash is an invalid, dropped declaration' );
		$this->assertArrayNotHasKey( 'backgroundColor', $cls['default'] );
		$this->assertArrayNotHasKey( 'zIndex', $cls['default'] );

		// Facet 2 — bare `is-open` canonicalized to a leading-dot class tail; the
		// pseudo `hover` state is left as a resolver-known key (its body kebabed).
		$this->assertArrayHasKey( '.is-open', $cls, 'a bare JS-toggle state must gain its leading dot' );
		$this->assertArrayNotHasKey( 'is-open', $cls );
		$this->assertArrayHasKey( 'hover', $cls, 'a known pseudo state key must be left intact' );
		$this->assertArrayHasKey( 'backdrop-filter', $cls['hover'] );
	}

	/**
	 * Regression: class names are validated by SYNTAX + reserved-prefix
	 * denylist, not a `gs-|animate-` prefix allowlist. The old allowlist
	 * (letter-only first char after the prefix) silently dropped (a)
	 * `gs-{6-hex-hash}-{base}` names whose hex hash starts with a digit —
	 * ~62% of SaaS-imported classes (`gs-69f07e-ap-footer`) — and (b) bare
	 * source author classes routed site-wide (`ap-btn-signal`). Both must be
	 * accepted now; reserved theme/core/legacy families and syntactically
	 * invalid names must still be rejected.
	 *
	 * @return void
	 */
	public function test_update_sitewide_validates_names_by_syntax_and_denylist(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$request->set_param(
			'payload',
			array(
				'v'       => '1',
				'classes' => array(
					// Digit-leading hex hashes (previously rejected).
					'gs-69f07e-ap-footer'          => array(
						'default' => array( 'background' => '#fafaf5' ),
					),
					'gs-3b99f5-ap-btn'             => array(
						'default' => array( 'padding' => '14px 20px' ),
					),
					// Letter-leading hash (always accepted — control).
					'gs-a1c93a-ap-footer-colophon' => array(
						'default' => array( 'color' => '#a4a39a' ),
					),
					// Bare source author class (previously rejected).
					'ap-btn-signal'                => array(
						'default' => array( 'color' => 'red' ),
					),
					// Reserved prefixes must STILL be rejected.
					'gc-spectra-123'               => array(
						'default' => array( 'color' => 'red' ),
					),
					'ast-button'                   => array(
						'default' => array( 'color' => 'red' ),
					),
					'wp-block-group'               => array(
						'default' => array( 'color' => 'red' ),
					),
					'is-style-rounded'             => array(
						'default' => array( 'color' => 'red' ),
					),
					// Syntactically invalid (digit-leading identifier).
					'1bad-class'                   => array(
						'default' => array( 'color' => 'red' ),
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayHasKey( 'gs-69f07e-ap-footer', $stored['classes'], 'digit-leading hash class must be accepted' );
		$this->assertArrayHasKey( 'gs-3b99f5-ap-btn', $stored['classes'], 'digit-leading hash class must be accepted' );
		$this->assertArrayHasKey( 'gs-a1c93a-ap-footer-colophon', $stored['classes'], 'letter-leading hash class must be accepted' );
		$this->assertArrayHasKey( 'ap-btn-signal', $stored['classes'], 'bare source author class must be accepted' );
		$this->assertArrayNotHasKey( 'gc-spectra-123', $stored['classes'], 'reserved gc-spectra- prefix must be rejected' );
		$this->assertArrayNotHasKey( 'ast-button', $stored['classes'], 'reserved ast- (theme) prefix must be rejected' );
		$this->assertArrayNotHasKey( 'wp-block-group', $stored['classes'], 'reserved wp- (core) prefix must be rejected' );
		$this->assertArrayNotHasKey( 'is-style-rounded', $stored['classes'], 'reserved is- (core state) prefix must be rejected' );
		$this->assertArrayNotHasKey( '1bad-class', $stored['classes'], 'syntactically invalid names must be rejected' );
	}

	/**
	 * `remBase` (the source's document-root font-size, rendered verbatim by
	 * GenCssRenderer into `:root { font-size: …; }`) must be shape-validated
	 * at THIS write choke point, not only at render — `{`, `}`, `;` or a CSS comment
	 * delimiter (slash-star / star-slash) would otherwise break out of (or comment
	 * past) the declaration. A standalone slash/star stays valid (`calc()`).
	 *
	 * @return void
	 */
	public function test_update_sitewide_rejects_unsafe_rem_base_accepts_safe(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$request->set_param(
			'payload',
			array(
				'v'       => '1',
				'remBase' => '62.5%} body{background:url(evil)}/*',
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayNotHasKey( 'remBase', $stored, 'a remBase value carrying { } ; must never reach the store' );

		// A `/*`-only value (the brace check passes it) must be rejected — else
		// `:root { font-size: 62.5%/*; }` opens a comment that swallows the sheet.
		$comment_request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$comment_request->set_param( 'payload', array( 'v' => '1', 'remBase' => '62.5%/*' ) );
		$this->assertSame( 200, $this->server->dispatch( $comment_request )->get_status() );
		$this->assertArrayNotHasKey( 'remBase', get_option( Engine::OPTION_KEY_USER_CSS, array() ), 'a remBase value opening a CSS comment (/*) must never reach the store' );

		$second_request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$second_request->set_param( 'payload', array( 'v' => '1', 'remBase' => '62.5%' ) );

		$second_response = $this->server->dispatch( $second_request );
		$this->assertSame( 200, $second_response->get_status() );

		$stored_again = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertSame( '62.5%', $stored_again['remBase'], 'a safe remBase value must still be stored' );

		// `calc()` uses `/` and `*` — only the comment SEQUENCES are rejected.
		$calc_request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$calc_request->set_param( 'payload', array( 'v' => '1', 'remBase' => 'calc(16px / 1.6)' ) );
		$this->assertSame( 200, $this->server->dispatch( $calc_request )->get_status() );
		$this->assertSame( 'calc(16px / 1.6)', get_option( Engine::OPTION_KEY_USER_CSS, array() )['remBase'], 'a calc() remBase using / and * must still be stored' );
	}

	/**
	 * An empty site-wide payload is a no-op: the shared, user-editable GBS
	 * option must never be deleted or emptied by the import path.
	 *
	 * @return void
	 */
	public function test_update_sitewide_empty_payload_is_noop(): void {
		wp_set_current_user( $this->admin_id );

		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'classes' => array(
					'my-card' => array(
						'default' => array( 'color' => 'red' ),
					),
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/global-styles/sitewide' );
		$request->set_param( 'payload', array() );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['noop'] ?? false, 'empty payload must report a no-op' );

		$stored = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		$this->assertArrayHasKey( 'my-card', $stored['classes'], 'empty payload must leave user classes untouched' );
	}
}
