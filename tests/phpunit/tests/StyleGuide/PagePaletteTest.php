<?php
/**
 * Tests for the page-level Style Guide — a page's own palette in post meta,
 * under the same key and v2 shape as the site option.
 *
 * Covers the front-end-only read, the no-leak guarantee between pages, the
 * page-scoped REST save (capability + post-type gates, reset) and the slug set
 * the page-scoped GBS `presetLock` must defer to.
 *
 * @package Spectra\Tests\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide;

use SpectraBlocks\GlobalStyles\Engine as GbsEngine;
use SpectraBlocks\GlobalStyles\GenCssOrphanStripper;
use SpectraBlocks\StyleGuide\Engine;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * PagePaletteTest test case.
 *
 * @since x.x.x
 */
class PagePaletteTest extends WP_UnitTestCase {

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
	 * A hex no default palette uses, so finding it proves the PAGE config won.
	 *
	 * @var string
	 */
	const PAGE_PRIMARY = '#b36b2c';

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

		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		delete_option( Engine::OPTION_KEY );
	}

	/**
	 * Reset the REST server after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * A page carrying its own v2 Style Guide.
	 *
	 * @param string $primary Primary hex.
	 * @return int Post ID.
	 */
	private function page_with_palette( string $primary = self::PAGE_PRIMARY ): int {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		update_post_meta(
			$post_id,
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array( 'primary' => $primary ),
				'custom_colors' => array(),
			)
		);

		return $post_id;
	}

	/**
	 * A page with no Style Guide of its own emits nothing.
	 *
	 * @return void
	 */
	public function test_page_without_palette_emits_nothing() {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$this->assertSame( '', Engine::get_instance()->page_preset_css() );
		$this->assertSame( array(), Engine::get_instance()->page_managed_color_slugs() );
	}

	/**
	 * A page carrying its own palette renders it as a `:root` preset block.
	 *
	 * @return void
	 */
	public function test_page_palette_renders_as_root_presets() {
		$this->go_to( get_permalink( $this->page_with_palette() ) );

		$css = Engine::get_instance()->page_preset_css();

		$this->assertStringStartsWith( ':root{', $css );
		$this->assertStringContainsString( self::PAGE_PRIMARY, $css );
		$this->assertStringContainsString( '--wp--preset--color--', $css );
	}

	/**
	 * The page palette must never leak to another page.
	 *
	 * `page_preset_css()` resolves from the config alone and never from the
	 * TokenRegistry, which is cached SITE-wide — feeding a page palette through
	 * that registry would repaint every other page under a persistent object cache.
	 *
	 * @return void
	 */
	public function test_page_palette_does_not_leak_to_sibling_page() {
		$sibling = self::factory()->post->create( array( 'post_type' => 'page' ) );

		// Resolve the palette page FIRST, so anything it might cache is already warm.
		$this->go_to( get_permalink( $this->page_with_palette() ) );
		$this->assertStringContainsString( self::PAGE_PRIMARY, Engine::get_instance()->page_preset_css() );

		$this->go_to( get_permalink( $sibling ) );

		$this->assertSame( '', Engine::get_instance()->page_preset_css() );
	}

	/**
	 * The palette is FRONT END only — an editing surface resolves the site guide.
	 *
	 * @return void
	 */
	public function test_page_palette_is_not_read_off_singular() {
		$this->page_with_palette();
		$this->go_to( home_url( '/' ) );

		$this->assertSame( '', Engine::get_instance()->page_preset_css() );
	}

	/**
	 * The page's own slug set is exposed even when the SITE guide was never saved —
	 * that is what lets the page-scoped GBS `presetLock` defer to it, instead of
	 * rendering on `body` and beating the `:root` block emitted for the page.
	 *
	 * @return void
	 */
	public function test_page_slugs_are_exposed_without_a_saved_site_guide() {
		$this->assertEmpty( get_option( Engine::OPTION_KEY, array() ) );

		$this->go_to( get_permalink( $this->page_with_palette() ) );

		$slugs = Engine::get_instance()->page_managed_color_slugs();

		$this->assertNotEmpty( $slugs );
		$this->assertContains( 'primary', $slugs );
	}

	/**
	 * The meta is protected, so the classic Custom Fields box neither lists nor
	 * writes it.
	 *
	 * @return void
	 */
	public function test_page_meta_is_protected() {
		$this->assertTrue( is_protected_meta( Engine::OPTION_KEY, 'post' ) );
	}

	/**
	 * END TO END for the guide-less case: a page carrying its own palette must have
	 * the matching colour locks stripped from its page-scoped GBS `presetLock`, even
	 * though the SITE guide was never saved.
	 *
	 * The lock renders on `body`; the page palette renders on `:root`. `body` wins
	 * on specificity, so leaving the lock in place makes the page palette a no-op —
	 * which is exactly the case the PR body claimed was verified.
	 *
	 * @return void
	 */
	public function test_page_preset_lock_is_stripped_without_a_saved_site_guide() {
		$this->assertEmpty( get_option( Engine::OPTION_KEY, array() ) );

		$post_id = $this->page_with_palette();

		update_post_meta(
			$post_id,
			GenCssOrphanStripper::META_KEY,
			array(
				'v'          => '1',
				'presetLock' => array(
					// Owned by the page's Style Guide — must go.
					'--wp--preset--color--primary'  => '#111111',
					// Not a colour the guide manages — must survive.
					'--wp--preset--spacing--40'     => '2rem',
				),
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		GbsEngine::get_instance()->enqueue_gen_custom_css_for_current_post();

		// On the frontend the handle is not registered until the wp_footer p5
		// callback runs — nothing exists to read before that point.
		$this->fire_footer();


		$css = (string) wp_styles()->get_data( 'spectra-gen-custom-css-' . $post_id, 'after' )[0];

		$this->assertStringNotContainsString( '--wp--preset--color--primary', $css );
		$this->assertStringContainsString( '--wp--preset--spacing--40', $css );
	}

	/**
	 * The per-page payload must not exist as a style handle during `wp_head` on
	 * the frontend: it is registered, inline-attached and enqueued only in the
	 * `wp_footer` priority-5 callback, so nothing can enqueue it or pull it in
	 * as a dependency while `wp_print_styles` (wp_head p8) is still to come.
	 *
	 * (WP 6.9+ classic themes may later HOIST the footer-printed styles into
	 * `<head>` via wp_hoist_late_printed_styles(); that is core relocating
	 * already-resolved output and is deliberately not asserted here.)
	 *
	 * @return void
	 */
	public function test_page_payload_is_not_registered_until_footer_on_frontend() {
		$post_id = $this->page_with_palette();

		update_post_meta(
			$post_id,
			GenCssOrphanStripper::META_KEY,
			array(
				'v'          => '1',
				'presetLock' => array( '--wp--preset--spacing--40' => '2rem' ),
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$handle = 'spectra-gen-custom-css-' . $post_id;

		GbsEngine::get_instance()->enqueue_gen_custom_css_for_current_post();

		$this->assertFalse( wp_style_is( $handle, 'registered' ), 'Handle must not be registered before wp_footer.' );
		$this->assertFalse( wp_style_is( $handle, 'enqueued' ), 'Handle must not be enqueued before wp_footer.' );

		$printed = $this->fire_footer();

		$this->assertTrue( wp_style_is( $handle, 'enqueued' ), 'Handle must be enqueued by the wp_footer callback.' );
		$this->assertStringContainsString( $handle . '-inline-css', $printed, 'Payload must print during wp_footer.' );
	}

	/**
	 * `enqueue_block_assets` can fire more than once per request. The inline
	 * payload must be attached exactly once — re-running the emitter must
	 * re-enqueue the existing handle, never append the CSS a second time.
	 *
	 * @return void
	 */
	public function test_repeated_emission_does_not_duplicate_the_payload() {
		$post_id = $this->page_with_palette();

		update_post_meta(
			$post_id,
			GenCssOrphanStripper::META_KEY,
			array(
				'v'          => '1',
				'presetLock' => array( '--wp--preset--spacing--40' => '2rem' ),
			)
		);

		$this->go_to( get_permalink( $post_id ) );

		$engine = GbsEngine::get_instance();
		$engine->enqueue_gen_custom_css_for_current_post();
		$engine->enqueue_gen_custom_css_for_current_post();

		$this->fire_footer();

		$after = (array) wp_styles()->get_data( 'spectra-gen-custom-css-' . $post_id, 'after' );
		$css   = implode( '', array_map( 'strval', $after ) );

		$this->assertSame(
			1,
			substr_count( $css, '--wp--preset--spacing--40' ),
			'The per-page payload must be attached exactly once.'
		);
	}

	/**
	 * Fire `wp_footer` so the deferred per-page style registration runs.
	 *
	 * Core keeps the deprecated `the_block_template_skip_link` on `wp_footer`
	 * for back-compat and unhooks it inside
	 * `wp_enqueue_block_template_skip_link()` — which never runs here because
	 * these tests do not fire `wp_enqueue_scripts`. Unhook it the same way core
	 * does, so an unrelated deprecation notice cannot fail the assertion.
	 *
	 * @return string The markup printed during `wp_footer`.
	 */
	private function fire_footer() {
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		ob_start();
		do_action( 'wp_footer' );
		return (string) ob_get_clean();
	}

	/**
	 * Dispatch a page-scoped save.
	 *
	 * @param array<string, mixed> $params Request params.
	 * @return \WP_REST_Response
	 */
	private function save( array $params ) {
		$request = new WP_REST_Request( 'POST', '/spectra-blocks/v1/style-guide/config' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $params ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * A page-scoped save needs `edit_post` on THAT post, not just
	 * `edit_theme_options`.
	 *
	 * @return void
	 */
	public function test_page_save_requires_edit_post() {
		// An author who holds edit_theme_options but does not own the post. Editing
		// someone else's published post is not something an author can do.
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		get_role( 'author' )->add_cap( 'edit_theme_options' );
		wp_set_current_user( $author );

		$other = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_author' => $this->admin_id,
				'post_status' => 'publish',
			)
		);

		$response = $this->save(
			array(
				'post_id' => $other,
				'colors'  => array( 'primary' => self::PAGE_PRIMARY ),
			)
		);

		get_role( 'author' )->remove_cap( 'edit_theme_options' );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( '', (string) get_post_meta( $other, Engine::OPTION_KEY, true ) );
	}

	/**
	 * An unknown post is a 404, not a stray meta row.
	 *
	 * @return void
	 */
	public function test_page_save_rejects_unknown_post() {
		wp_set_current_user( $this->admin_id );

		$response = $this->save(
			array(
				'post_id' => 99999999,
				'colors'  => array( 'primary' => self::PAGE_PRIMARY ),
			)
		);

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * Only real content posts may carry a page palette — not a revision, an
	 * attachment, a `wp_block` or a `wp_template_part`.
	 *
	 * @return void
	 */
	public function test_page_save_rejects_unsupported_post_type() {
		wp_set_current_user( $this->admin_id );

		$block = self::factory()->post->create( array( 'post_type' => 'wp_block' ) );

		$response = $this->save(
			array(
				'post_id' => $block,
				'colors'  => array( 'primary' => self::PAGE_PRIMARY ),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( '', (string) get_post_meta( $block, Engine::OPTION_KEY, true ) );
	}

	/**
	 * A page-scoped save writes the canonical v2 shape into that page's meta and
	 * leaves the SITE option alone.
	 *
	 * @return void
	 */
	public function test_page_save_writes_page_meta_only() {
		wp_set_current_user( $this->admin_id );
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$response = $this->save(
			array(
				'post_id' => $post_id,
				'colors'  => array( 'primary' => self::PAGE_PRIMARY ),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );

		$stored = get_post_meta( $post_id, Engine::OPTION_KEY, true );

		$this->assertSame( 2, $stored['version'] );
		$this->assertSame( self::PAGE_PRIMARY, $stored['colors']['primary'] );
		$this->assertEmpty( get_option( Engine::OPTION_KEY, array() ) );
	}

	/**
	 * An explicit reset releases the page back to the site guide — the merge alone
	 * could only ever ADD roles.
	 *
	 * @return void
	 */
	public function test_page_save_reset_deletes_the_meta() {
		wp_set_current_user( $this->admin_id );
		$post_id = $this->page_with_palette();

		$response = $this->save(
			array(
				'post_id' => $post_id,
				'reset'   => true,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '', (string) get_post_meta( $post_id, Engine::OPTION_KEY, true ) );

		$this->go_to( get_permalink( $post_id ) );
		$this->assertSame( '', Engine::get_instance()->page_preset_css() );
	}

	/**
	 * The registered sanitize callback drops a non-string hex at WRITE time, so a
	 * value that would raise an `Array to string conversion` warning — or, for an
	 * unserialized object, a fatal — inside `wp_head` can never be stored at all.
	 *
	 * @return void
	 */
	public function test_malformed_hex_is_rejected_at_write_time() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		update_post_meta(
			$post_id,
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => array(
					'primary'    => array( 'not', 'a', 'hex' ),
					'background' => self::PAGE_PRIMARY,
					'heading'    => 'javascript:alert(1)',
				),
				'custom_colors' => array(),
			)
		);

		$stored = get_post_meta( $post_id, Engine::OPTION_KEY, true );

		// Only the well-formed hex survives.
		$this->assertSame( array( 'background' => self::PAGE_PRIMARY ), $stored['colors'] );

		$this->go_to( get_permalink( $post_id ) );
		$css = Engine::get_instance()->page_preset_css();

		$this->assertStringNotContainsString( 'Array', $css );
		$this->assertStringNotContainsString( 'javascript', $css );
		$this->assertStringContainsString( self::PAGE_PRIMARY, $css );
	}
}
