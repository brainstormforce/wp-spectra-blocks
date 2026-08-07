<?php
/**
 * Regression guard for StateResolver::suffix()'s verbatim-tail shape validation.
 *
 * A stored class STATE key rides verbatim into the rendered stylesheet as a
 * selector suffix when it's already pseudo/attribute/class-tail shaped
 * (`:hover`, `[open]`, `.is-active`). The validator must accept every
 * legitimate shape while rejecting anything that could break out of the
 * selector position — including a `/*` / `*\/` CSS comment delimiter, which
 * the stylesheet tokenizer honors anywhere in the output regardless of
 * selector context (GIT — StateResolver CSS-comment-injection finding,
 * 2026-07-14: `:is(/*)` / `[a=/*]` both rode through the pre-fix regex).
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\StateResolver;
use WP_UnitTestCase;

/**
 * StateResolverTest test case.
 *
 * @since x.x.x
 */
class StateResolverTest extends WP_UnitTestCase {

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public function provide_legitimate_verbatim_tails(): array {
		return array(
			'pseudo'                 => array( ':hover', ':hover' ),
			'double-colon pseudo'    => array( '::before', '::before' ),
			'attribute'              => array( '[open]', '[open]' ),
			'attribute with value'   => array( '[data-state="open"]', '[data-state="open"]' ),
			'class tail'             => array( '.is-active', '.is-active' ),
			'class tail + pseudo'    => array( '.is-active:hover', '.is-active:hover' ),
			'functional pseudo'      => array( ':is(.foo)', ':is(.foo)' ),
			'functional pseudo attr' => array( ':not([disabled])', ':not([disabled])' ),
		);
	}

	/**
	 * @dataProvider provide_legitimate_verbatim_tails
	 * @param string $state    Input state key.
	 * @param string $expected Expected verbatim suffix.
	 * @return void
	 */
	public function test_accepts_legitimate_verbatim_tails( string $state, string $expected ): void {
		$this->assertSame( $expected, StateResolver::suffix( $state ) );
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function provide_comment_injection_payloads(): array {
		return array(
			'pseudo-arg comment open'  => array( ':is(/*)' ),
			'attribute comment open'   => array( '[a=/*]' ),
			'pseudo-arg comment close' => array( ':is(*/)' ),
			'bare comment open'        => array( ':hover/*' ),
		);
	}

	/**
	 * A state key that could smuggle a `/*` or `*\/` CSS comment delimiter
	 * must be dropped ('') rather than emitted — a stylesheet-wide comment
	 * breakout, not a selector-scoped one, since the tokenizer honors the
	 * delimiter anywhere in the output.
	 *
	 * @dataProvider provide_comment_injection_payloads
	 * @param string $state Malicious state key.
	 * @return void
	 */
	public function test_rejects_comment_injection_payloads( string $state ): void {
		$this->assertSame( '', StateResolver::suffix( $state ) );
	}

	/**
	 * @return void
	 */
	public function test_rejects_brace_and_angle_bracket_breakout(): void {
		$this->assertSame( '', StateResolver::suffix( ':is(){color:red}' ) );
		$this->assertSame( '', StateResolver::suffix( '[a<b]' ) );
	}

	/**
	 * @return void
	 */
	public function test_drops_a_bare_unformatted_word(): void {
		$this->assertSame( '', StateResolver::suffix( 'garbage' ) );
	}

	/**
	 * Media conditions emit in three bands: the mobile-first `min-width` ladder
	 * ascending, then `max-width` DESCENDING, then everything else. The middle
	 * band is the one that regressed — both `max-width` entries collapsed into
	 * `PHP_INT_MAX`, so they tied and `usort` fell back to stored key order.
	 * They collide at equal specificity, so a narrower breakpoint stored first
	 * lost to a wider one.
	 *
	 * Sort the conditions rather than assert on the raw ints: the offsets are an
	 * implementation detail, the resulting ORDER is the contract.
	 *
	 * @return void
	 */
	public function test_media_conditions_sort_into_min_then_descending_max_then_rest(): void {
		$conditions = array(
			'(prefers-color-scheme: dark)',
			'(max-width: 600px)',
			'(min-width: 782px)',
			'(max-width: 960px)',
			'(min-width: 600px)',
		);

		usort(
			$conditions,
			static function ( $a, $b ) {
				return StateResolver::media_order( $a ) <=> StateResolver::media_order( $b );
			}
		);

		$this->assertSame(
			array(
				'(min-width: 600px)',
				'(min-width: 782px)',
				'(max-width: 960px)',
				'(max-width: 600px)',
				'(prefers-color-scheme: dark)',
			),
			$conditions
		);
	}
}
