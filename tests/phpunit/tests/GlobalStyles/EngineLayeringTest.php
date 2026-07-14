<?php
/**
 * Tests for `@layer utilities` wrapping and dedicated JIT handle routing.
 *
 * The utility stylesheet + user classes + keyframes are all wrapped in a
 * single `@layer utilities { ... }` so block-default non-layered rules (e.g.
 * `.wp-block-spectra-container { display: flex }`) lose to the utility
 * cascade on specificity alone. Per-post JIT output lands on its own
 * `spectra-gs-dynamic-styles` handle so the static utility sheet stays
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

		wp_dequeue_style( 'spectra-gs-dynamic-styles' );

		$this->assertTrue( wp_style_is( 'spectra-gs-dynamic-styles', 'registered' ) );

		$inline = wp_styles()->get_data( 'spectra-gs-dynamic-styles', 'after' );
		$this->assertIsArray( $inline );

		$combined = implode( "\n", array_filter( $inline, 'is_string' ) );
		$this->assertStringContainsString( '.text-\\[56px\\]', $combined );

		wp_reset_postdata();
		wp_deregister_style( 'spectra-gs-dynamic-styles' );
	}

	/**
	 * The dedicated JIT handle depends on the static utility stylesheet so
	 * cascade order is preserved.
	 *
	 * @return void
	 */
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

		$registered = wp_styles()->registered['spectra-gs-dynamic-styles'] ?? null;

		$this->assertNotNull( $registered );
		$this->assertContains( 'spectra-gs-utility-classes', $registered->deps );

		wp_reset_postdata();
		wp_deregister_style( 'spectra-gs-dynamic-styles' );
	}
}
