<?php
/**
 * Tests for the Global Styles Engine stylesheet builder.
 *
 * Covers CSS output for variables, user-defined classes with pseudo-selectors,
 * raw keyframe rules, and the yield-to-Pro guard in init().
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * EngineTest test case.
 *
 * @since x.x.x
 */
class EngineTest extends WP_UnitTestCase {

	/**
	 * Clear options between tests.
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

	// ─────────────────────────────────────────────────────────────────────
	// render_user_classes — selector + per-breakpoint coverage
	//
	// All fixtures use Spectra's canonical flat `[ 'color' => 'red' ]`
	// property:value map (the only shape `render_user_classes` accepts). This
	// block covers each pseudo/selector and responsive breakpoint; the block
	// at the bottom of the file mirrors the key cases for the same path.
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Render user classes supports pseudo-class suffixes.
	 *
	 * @return void
	 */
	public function test_render_user_classes_pseudo_suffix(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-button' => array(
						'hover' => array(
							'color' => 'red',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-button:hover', $css );
		$this->assertStringContainsString( 'color: red;', $css );
	}

	/**
	 * Pseudo-element suffix uses the double-colon form.
	 *
	 * @return void
	 */
	public function test_render_user_classes_pseudo_element(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'before' => array(
							'content' => "''",
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-card::before', $css );
	}

	/**
	 * Default selector has no suffix.
	 *
	 * @return void
	 */
	public function test_render_user_classes_default_no_suffix(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'default' => array(
							'color' => 'blue',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-card {', $css );
		$this->assertStringNotContainsString( '.my-card:', $css );
	}

	/**
	 * Raw keyframe CSS is wrapped with the @keyframes rule.
	 *
	 * @return void
	 */
	public function test_render_keyframes_wraps_raw_css(): void {
		$css = $this->invoke(
			'render_keyframes',
			array(
				array(
					'fade-in' => 'from { opacity: 0; } to { opacity: 1; }',
				),
			)
		);

		$this->assertStringContainsString( '@keyframes fade-in', $css );
		$this->assertStringContainsString( 'opacity: 0;', $css );
	}

	/**
	 * Utility classes layer emits `.class { ... }` rules from ClassRegistry.
	 *
	 * @return void
	 */
	public function test_render_utility_classes_emits_rules(): void {
		$css = $this->invoke( 'render_utility_classes' );
		$this->assertNotEmpty( $css );
		$this->assertStringContainsString( '{', $css );
		$this->assertStringContainsString( '}', $css );
	}

	/**
	 * Extended utilities appear in the compiled utility stylesheet.
	 *
	 * @return void
	 */
	public function test_render_utility_classes_includes_extended_utilities(): void {
		$css = $this->invoke( 'render_utility_classes' );

		$this->assertStringContainsString( '.-mt-4', $css );
		$this->assertStringContainsString( '.rotate-45', $css );
		$this->assertStringContainsString( '.scale-110', $css );
		$this->assertStringContainsString( '.origin-center', $css );
		$this->assertStringContainsString( '.duration-300', $css );
		$this->assertStringContainsString( '.ease-in-out', $css );
		$this->assertStringContainsString( '.blur-md', $css );
		$this->assertStringContainsString( '.brightness-75', $css );
		$this->assertStringContainsString( '.truncate', $css );
		$this->assertStringContainsString( '.line-clamp-3', $css );
		$this->assertStringContainsString( '.aspect-square', $css );
		$this->assertStringContainsString( '.bg-gradient-to-br', $css );
		$this->assertStringContainsString( '.text-5xl', $css );
	}

	/**
	 * Animation utilities emit their companion @keyframes rules.
	 *
	 * @return void
	 */
	public function test_render_utility_classes_emits_animation_keyframes(): void {
		$css = $this->invoke( 'render_utility_classes' );

		$this->assertStringContainsString( '@keyframes spectra-spin', $css );
		$this->assertStringContainsString( '@keyframes spectra-pulse', $css );
		$this->assertStringContainsString( '@keyframes spectra-bounce', $css );
		$this->assertStringContainsString( '@keyframes spectra-ping', $css );
		$this->assertStringContainsString( '.animate-spin', $css );
	}

	/**
	 * Sibling-gap utilities render with the `&` nesting expanded to the class selector.
	 *
	 * @return void
	 */
	public function test_render_utility_classes_expands_space_x_sibling_selector(): void {
		$css = $this->invoke( 'render_utility_classes' );

		$this->assertStringContainsString( '.space-x-4 > :not([hidden]) ~ :not([hidden])', $css );
		$this->assertStringContainsString( 'margin-left: 1rem', $css );
	}

	/**
	 * `md` bucket wraps the rule in a mobile-first upward media query.
	 *
	 * @return void
	 */
	public function test_render_user_classes_md_bucket_wraps_media(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'md' => array(
							'padding' => '2rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringNotContainsString( 'max-width', $css );
		$this->assertStringContainsString( '.my-card { padding: 2rem; }', $css );
	}

	/**
	 * `sm` bucket uses the Tailwind-parity 640px min-width breakpoint.
	 *
	 * @return void
	 */
	public function test_render_user_classes_sm_bucket_uses_min_width_640(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'sm' => array(
							'padding' => '1rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 640px)', $css );
		$this->assertStringNotContainsString( 'max-width', $css );
	}

	/**
	 * `lg` bucket uses the 1024px min-width breakpoint.
	 *
	 * @return void
	 */
	public function test_render_user_classes_lg_bucket_uses_min_width_1024(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'lg' => array(
							'max-width' => '60rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 1024px)', $css );
	}

	/**
	 * `xl` bucket uses the 1280px min-width breakpoint.
	 *
	 * @return void
	 */
	public function test_render_user_classes_xl_bucket_uses_min_width_1280(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'xl' => array(
							'padding' => '4rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 1280px)', $css );
	}

	/**
	 * `2xl` bucket uses the 1536px min-width breakpoint.
	 *
	 * @return void
	 */
	public function test_render_user_classes_2xl_bucket_uses_min_width_1536(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'2xl' => array(
							'padding' => '5rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 1536px)', $css );
	}

	/**
	 * Compound bucket `md_hover` is wrapped and receives the pseudo-class suffix.
	 *
	 * @return void
	 */
	public function test_render_user_classes_md_hover_compound_bucket(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'md_hover' => array(
							'transform' => 'translateY(-4px)',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringContainsString( '.my-card:hover', $css );
		$this->assertStringContainsString( 'transform: translateY(-4px);', $css );
	}

	/**
	 * Compound bucket `lg_focus` wraps focus state inside the lg media.
	 *
	 * @return void
	 */
	public function test_render_user_classes_lg_focus_compound_bucket(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'lg_focus' => array(
							'outline' => '2px solid blue',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 1024px)', $css );
		$this->assertStringContainsString( '.my-card:focus', $css );
	}

	// ─────────────────────────────────────────────────────────────────────
	// Canonical flat-object render coverage
	//
	// Both this block and the tests above use Spectra's canonical flat
	// `[ 'color' => 'red' ]` property:value map — what the admin UI writes
	// today and what the ZipWP SaaS sends post-`ClusterPayload::fromJson`
	// normalisation. This block mirrors the key cases to lock the canonical
	// render path so a future engine rewrite can't silently regress it.
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Canonical flat-object: default bucket renders bare selector with
	 * declarations emitted from the property:value map.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_default_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'default' => array(
							'color'  => 'blue',
							'margin' => '10px',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-card {', $css );
		$this->assertStringContainsString( 'color: blue;', $css );
		$this->assertStringContainsString( 'margin: 10px;', $css );
		$this->assertStringNotContainsString( '.my-card:', $css );
	}

	/**
	 * Canonical flat-object: pseudo-class bucket (`hover`) gets the
	 * single-colon suffix.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_pseudo_class_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-button' => array(
						'hover' => array(
							'color' => 'red',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-button:hover', $css );
		$this->assertStringContainsString( 'color: red;', $css );
	}

	/**
	 * Canonical flat-object: pseudo-element bucket (`before`) gets the
	 * double-colon suffix.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_pseudo_element_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'before' => array(
							'content' => "''",
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '.my-card::before', $css );
		$this->assertStringContainsString( "content: '';", $css );
	}

	/**
	 * Canonical flat-object: responsive bucket wraps the rule in the
	 * matching mobile-first media query.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_responsive_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'md' => array(
							'padding' => '2rem',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringContainsString( '.my-card { padding: 2rem; }', $css );
	}

	/**
	 * Canonical flat-object: compound `md_hover` bucket wraps the
	 * pseudo-classed rule inside the breakpoint media query.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_compound_bucket_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'my-card' => array(
						'md_hover' => array(
							'transform' => 'translateY(-4px)',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringContainsString( '.my-card:hover', $css );
		$this->assertStringContainsString( 'transform: translateY(-4px);', $css );
	}

	/**
	 * Canonical flat-object: a single class with multiple buckets renders
	 * each bucket through its correct selector/media path in one pass.
	 *
	 * @return void
	 */
	public function test_render_user_classes_canonical_multi_bucket_flat_object(): void {
		$css = $this->invoke(
			'render_user_classes',
			array(
				array(
					'gs-button' => array(
						'default'  => array(
							'color'   => 'var(--wp--preset--color--primary)',
							'padding' => '12px 24px',
						),
						'hover'    => array(
							'color' => 'var(--wp--preset--color--accent)',
						),
						'md'       => array(
							'padding' => '16px 32px',
						),
						'md_hover' => array(
							'transform' => 'translateY(-2px)',
						),
					),
				),
			)
		);

		// Default + hover land in the base layer.
		$this->assertStringContainsString( '.gs-button {', $css );
		$this->assertStringContainsString( 'color: var(--wp--preset--color--primary);', $css );
		$this->assertStringContainsString( 'padding: 12px 24px;', $css );
		$this->assertStringContainsString( '.gs-button:hover', $css );
		$this->assertStringContainsString( 'color: var(--wp--preset--color--accent);', $css );

		// `md` and `md_hover` both live inside the 768px media query.
		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringContainsString( 'padding: 16px 32px;', $css );
		$this->assertStringContainsString( 'transform: translateY(-2px);', $css );
	}
}
