<?php
/**
 * A state binds to every item of a selector list, not just the last.
 *
 * WHY THIS EXISTS
 *
 * A suffix appended to a comma-separated selector binds to the LAST item only.
 * `'.a, .b' . ':hover'` is `.a, .b:hover`, which leaves `.a` unconditional — so
 * a hover colour paints at rest, permanently.
 *
 * That is not hypothetical. `spectra-pro/loop-builder-child-filter-button` takes
 * its base selector from `spectra_blocks_responsive_css_selector`, and that
 * filter returns a LIST: the button plus its siblings, because filter buttons
 * are generated per term and one rule has to reach them all. Measured on
 * WordPress 7.1.2, an unhovered and inactive filter button rendered
 * `color: rgb(128, 0, 0)` and `background-color: rgb(255, 165, 0)` — its active
 * colours — because the first item of the list carried no state.
 *
 * The split has to respect nesting: `spectra/button` ships
 * `:where(div, button, a)`, whose commas separate nothing. A naive
 * `explode( ',', … )` tears it into three broken fragments.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use ReflectionMethod;
use SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use WP_UnitTestCase;

/**
 * StateOnSelectorListTest test case.
 *
 * @since 1.0.13
 */
class StateOnSelectorListTest extends WP_UnitTestCase {

	/**
	 * Call a private static on the generator.
	 *
	 * @since 1.0.13
	 * @param string            $name Method name.
	 * @param array<int, mixed> $args Arguments.
	 * @return mixed The return value.
	 */
	private function call( string $name, array $args ) {
		$method = new ReflectionMethod( ResponsiveAttributeCSS::class, $name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $args );
	}

	/**
	 * Every item of a base selector list carries the state.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_state_is_distributed_across_a_base_selector_list() {
		$got = $this->call( 'compose_selector', array( '.a[id], .a[id] ~ .a', ':hover' ) );

		$this->assertSame( '.a[id]:hover, .a[id] ~ .a:hover', $got );

		foreach ( explode( ',', $got ) as $part ) {
			$this->assertStringContainsString(
				':hover',
				$part,
				'An item of the list carries no state, so it applies unconditionally.'
			);
		}
	}

	/**
	 * The attribute-selector form used by the active state behaves the same.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_attribute_state_is_distributed() {
		$got = $this->call( 'compose_selector', array( '.a, .b', "[data-is-active='true']" ) );

		$this->assertSame( ".a[data-is-active='true'], .b[data-is-active='true']", $got );
	}

	/**
	 * A single selector is untouched, and an empty suffix changes nothing.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_single_selector_and_empty_suffix_are_unchanged() {
		$this->assertSame( '.a:hover', $this->call( 'compose_selector', array( '.a', ':hover' ) ) );
		$this->assertSame( '.a, .b', $this->call( 'compose_selector', array( '.a, .b', '' ) ) );
	}

	/**
	 * Commas inside `:where()`, `:not()` and attribute values separate nothing.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_nested_commas_are_not_split() {
		$this->assertSame(
			array( '.btn:where(div, button, a)' ),
			$this->call( 'split_selector_list', array( '.btn:where(div, button, a)' ) )
		);

		$this->assertSame(
			array( '.x:where(a, b)', '.y' ),
			$this->call( 'split_selector_list', array( '.x:where(a, b), .y' ) )
		);

		$this->assertSame(
			array( '[data-x="a,b"]', '.y' ),
			$this->call( 'split_selector_list', array( '[data-x="a,b"], .y' ) )
		);

		// The whole point: a nested list must survive composition intact.
		$this->assertSame(
			'.btn:where(div, button, a):hover',
			$this->call( 'compose_selector', array( '.btn:where(div, button, a)', ':hover' ) )
		);
	}

	/**
	 * End to end: a rendered block whose selector is a list states every item.
	 *
	 * The three tests above exercise the helpers. This one goes through the
	 * generator, so it would have caught the original defect whatever the
	 * implementation — which is the failure the helpers were written for.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_rendered_hover_rule_states_every_item_of_a_selector_list() {
		$id = 'sl' . wp_generate_password( 6, false );

		// Stand in for `modify_filter_button_selector`: a block whose selector
		// reaches its siblings as well as itself.
		$filter = static function ( $selector ) {
			return $selector . ', ' . $selector . ' ~ .sibling';
		};
		add_filter( 'spectra_blocks_responsive_css_selector', $filter, 10, 1 );

		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		do_blocks(
			'<!-- wp:spectra/button ' . wp_json_encode(
				array(
					'spectraId'   => $id,
					'shadowHover' => '0px 0px 11px 0px rgb(255, 0, 0)',
				)
			) . ' /-->'
		);

		remove_filter( 'spectra_blocks_responsive_css_selector', $filter, 10 );

		$css = (string) preg_replace(
			'/\s+/',
			' ',
			implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) )
		);

		$this->assertStringContainsString( $id, $css, 'The block emitted no CSS at all.' );

		// Every rule carrying a hover declaration must state each of its items.
		preg_match_all( '/([^{}]*' . preg_quote( $id, '/' ) . '[^{}]*):hover[^{}]*\{[^}]*\}/', $css, $matches );
		$this->assertNotEmpty( $matches[0], 'No hover rule was emitted for the block.' );

		foreach ( $matches[0] as $rule ) {
			$selector = substr( $rule, 0, strpos( $rule, '{' ) );

			foreach ( explode( ',', $selector ) as $item ) {
				$this->assertStringContainsString(
					':hover',
					$item,
					sprintf( 'An item of the selector list carries no state, so it applies at rest: %s', $selector )
				);
			}
		}
	}
}
