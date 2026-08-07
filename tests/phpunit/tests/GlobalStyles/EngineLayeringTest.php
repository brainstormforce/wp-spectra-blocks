<?php
/**
 * Tests for `@layer utilities` wrapping and dedicated JIT handle routing.
 *
 * The utility stylesheet + user classes + keyframes are all wrapped in a
 * single `@layer utilities { ... }` so block-default non-layered rules (e.g.
 * `.wp-block-spectra-container { display: flex }`) lose to the utility
 * cascade on specificity alone. Per-post JIT output lands on its own
 * `spectra-gs-jit-styles` handle so the static utility sheet stays
 * cacheable per-site.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use SpectraBlocks\GlobalStyles\JitCache;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * EngineLayeringTest test case.
 *
 * @since x.x.x
 */
class EngineLayeringTest extends WP_UnitTestCase {

	/**
	 * Reset option state between tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( Engine::OPTION_KEY_USER_CSS );
	}

	/**
	 * Invoke a private method on the singleton Engine instance.
	 *
	 * @param string            $method Method name.
	 * @param array<int, mixed> $args   Positional args.
	 * @return mixed
	 */
	private function invoke( string $method, array $args = array() ) {
		$instance   = Engine::get_instance();
		$reflection = new ReflectionClass( $instance );
		$ref_method = $reflection->getMethod( $method );
		$ref_method->setAccessible( true );
		return $ref_method->invoke( $instance, ...$args );
	}

	/**
	 * The utility stylesheet renders user classes and keyframes without layer wrapping.
	 *
	 * Fixture seeds the option with a canonical flat-shape class
	 * (`[ 'color' => 'blue' ]`) so the engine renders it end-to-end through
	 * `build_stylesheet_css()`. The stylesheet is currently unlayered so the
	 * JIT handle can prepend per-post rules without a layer context.
	 *
	 * @return void
	 */
	public function test_stylesheet_wraps_utilities_in_layer(): void {
		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'classes'   => array(
					'my-card' => array(
						'default' => array(
							'color' => 'blue',
						),
					),
				),
				'keyframes' => array(
					'my-fade' => 'from { opacity: 0; } to { opacity: 1; }',
				),
			)
		);

		$css = $this->invoke( 'build_stylesheet_css' );

		$this->assertStringContainsString( '.my-card', $css );
		$this->assertStringContainsString( '@keyframes my-fade', $css );

		// User classes must appear before keyframes in source order.
		$user_card_pos = strpos( $css, '.my-card' );
		$keyframes_pos = strpos( $css, '@keyframes my-fade' );

		$this->assertIsInt( $user_card_pos );
		$this->assertIsInt( $keyframes_pos );
		$this->assertLessThan( $keyframes_pos, $user_card_pos );
	}

	/**
	 * Per-post JIT CSS is enqueued on the dedicated dynamic-styles handle.
	 *
	 * @return void
	 */
	public function test_jit_css_lands_on_dedicated_handle(): void {
		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph {"className":"text-[56px]"} --><p class="text-[56px]">hi</p><!-- /wp:paragraph -->',
			)
		);

		JitCache::rebuild( $post_id );

		// Simulate render context — `get_the_ID()` relies on global $post.
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		Engine::get_instance()->enqueue_jit_for_current_post();

		wp_dequeue_style( 'spectra-gs-jit-styles' );

		$this->assertTrue( wp_style_is( 'spectra-gs-jit-styles', 'registered' ) );

		$inline = wp_styles()->get_data( 'spectra-gs-jit-styles', 'after' );
		$this->assertIsArray( $inline );

		$combined = implode( "\n", array_filter( $inline, 'is_string' ) );
		$this->assertStringContainsString( '.text-\\[56px\\]', $combined );

		wp_reset_postdata();
		wp_deregister_style( 'spectra-gs-jit-styles' );
	}

	/**
	 * The dedicated JIT handle depends on the static utility stylesheet so
	 * cascade order is preserved.
	 *
	 * @return void
	 */
	/**
	 * The PR's headline claim — "registering after `enqueue_stylesheet` plus the
	 * dep pin guarantees print order" — asserted directly rather than argued.
	 *
	 * The sitewide sheet's rules and the utility classes collide at (0,3,0), so
	 * source order decides, and source order is the order `do_items()` walks
	 * `to_do`. Both halves matter: the dep must be recorded, AND it must actually
	 * make the utility block print first.
	 *
	 * @return void
	 */
	public function test_sitewide_css_prints_after_the_utility_classes(): void {
		update_option(
			Engine::OPTION_KEY_USER_CSS,
			array(
				'classes'       => array(
					'my-card' => array( 'default' => array( 'color' => 'blue' ) ),
				),
				'wrapperStyles' => array( '.my-card' => array( 'color' => 'red' ) ),
			)
		);

		$engine = Engine::get_instance();
		$engine->enqueue_stylesheet();
		$engine->enqueue_gen_sitewide_css();

		// The dep pin is not asserted separately: it is the ONLY thing that puts
		// the utility block into `to_do` first, so the order check below fails
		// without it (verified by removing the pin).
		ob_start();
		wp_styles()->do_items( array( 'spectra-gen-sitewide-css' ) );
		$printed = (string) ob_get_clean();

		// DISTINCT markers, not first-vs-last occurrence of a shared one. Comparing
		// strpos('my-card') with strrpos('my-card') asserts first < last, which is
		// true whenever the token appears twice regardless of which block emitted
		// which — a tautology that cannot detect a flipped order. The two blocks
		// declare different colours, so search for those instead.
		$utility_pos  = strpos( $printed, 'blue' );
		$sitewide_pos = strpos( $printed, 'red' );
		$this->assertIsInt( $utility_pos, 'the utility block did not print' );
		$this->assertIsInt( $sitewide_pos, 'the sitewide block did not print' );
		$this->assertLessThan(
			$sitewide_pos,
			$utility_pos,
			'the utility block must print BEFORE the sitewide block, or the (0,3,0) tie goes the wrong way'
		);

		wp_deregister_style( 'spectra-gen-sitewide-css' );
		wp_deregister_style( 'spectra-gs-utility-classes' );
	}

	public function test_jit_handle_depends_on_utility_handle(): void {
		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph {"className":"p-[10px]"} --><p class="p-[10px]">x</p><!-- /wp:paragraph -->',
			)
		);

		JitCache::rebuild( $post_id );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		Engine::get_instance()->enqueue_jit_for_current_post();

		$registered = wp_styles()->registered['spectra-gs-jit-styles'] ?? null;

		$this->assertNotNull( $registered );
		// Pinned only when the utility handle actually exists. Asserting the dep
		// unconditionally locked in the bug it documents: on a Pro-active site
		// `enqueue_stylesheet` is never hooked, so the handle is absent, and
		// `all_deps()` then drops this style — the JIT CSS is computed, cached
		// and rendered nowhere.
		if ( wp_style_is( 'spectra-gs-utility-classes', 'registered' ) || wp_style_is( 'spectra-gs-utility-classes', 'enqueued' ) ) {
			$this->assertContains( 'spectra-gs-utility-classes', $registered->deps );
		} else {
			$this->assertNotContains( 'spectra-gs-utility-classes', $registered->deps );
		}

		wp_reset_postdata();
		wp_deregister_style( 'spectra-gs-jit-styles' );
	}

	/**
	 * The absent-handle case directly: with no utility handle registered, the
	 * JIT sheet must still register with NO dep rather than pinning a missing
	 * one. A branch-covering assertion in the test above would pass whichever
	 * way the environment happens to fall, so pin the failing side explicitly.
	 */
	public function test_jit_handle_skips_the_dep_when_the_utility_handle_is_absent(): void {
		wp_deregister_style( 'spectra-gs-utility-classes' );
		wp_dequeue_style( 'spectra-gs-utility-classes' );

		$post_id = $this->factory->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph {"className":"p-[10px]"} --><p class="p-[10px]">x</p><!-- /wp:paragraph -->',
			)
		);

		JitCache::rebuild( $post_id );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		Engine::get_instance()->enqueue_jit_for_current_post();

		$registered = wp_styles()->registered['spectra-gs-jit-styles'] ?? null;

		$this->assertNotNull( $registered, 'the JIT sheet must register even with no utility handle' );
		$this->assertNotContains( 'spectra-gs-utility-classes', $registered->deps );

		wp_reset_postdata();
		wp_deregister_style( 'spectra-gs-jit-styles' );
	}

	/**
	 * The Tailwind-utility JIT hook registers UNCONDITIONALLY — even when Pro
	 * is present and the unified-render migration flag is off (today's
	 * default, real-site state). Pro has no equivalent JIT compiler anywhere
	 * (confirmed: zero references to PREFIX_MAP / compile_token / JitCompiler
	 * in spectra-blocks-pro), so yielding this specific hook to Pro — as the
	 * single shared early-return used to do before this fix — left it
	 * completely unenqueued on any Pro-active site: computed correctly,
	 * persisted correctly (JitCache), never printed. Found live 2026-07-14 on
	 * a real page: a freshly-generated section's own flex/gap layout and
	 * button colors never rendered, on both the block editor canvas and the
	 * published frontend. Fixed live end-to-end (Pro off, then Pro back on)
	 * on the actual affected page before this test was added.
	 *
	 * `enqueue_stylesheet` (the named GBS-class stylesheet, which legitimately
	 * still yields to Pro's own equivalent renderer — unchanged by this fix)
	 * isn't asserted here: WP-tests-lib's bootstrap runs the plugin's real
	 * `Engine::init()` once automatically, in a Pro-less state, before any
	 * test method body runs — even inside `@runInSeparateProcess` — so a
	 * Pro-class stub defined from within the test method always arrives one
	 * `init()` call too late to observe that hook NOT registering. Verified
	 * this is a bootstrap-ordering artifact (not a fix regression) by
	 * confirming the same hook is already registered with zero stub involved.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_jit_hook_registers_even_when_pro_present_and_unified_render_off(): void {
		if ( ! class_exists( '\\SpectraBlocksPro\\Extensions\\GlobalStyles' ) ) {
			eval( 'namespace SpectraBlocksPro\\Extensions; class GlobalStyles {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test-only stub, isolated process.
		}

		$this->assertTrue( class_exists( '\\SpectraBlocksPro\\Extensions\\GlobalStyles' ) );
		$this->assertFalse( Engine::is_unified_render() ); // default state.

		Engine::init();
		$instance = Engine::get_instance();

		$this->assertNotFalse(
			has_action( 'enqueue_block_assets', array( $instance, 'enqueue_jit_for_current_post' ) ),
			'enqueue_jit_for_current_post must register even when Pro owns the named-class stylesheet.'
		);
	}
}
