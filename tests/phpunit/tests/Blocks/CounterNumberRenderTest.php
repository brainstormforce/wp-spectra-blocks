<?php
/**
 * The counter number renders its prefix, value and suffix as one word.
 *
 * The number block prints three inline spans. White space between them in the
 * template is a text node the browser draws as a space, so "98%" read "98 %"
 * on the front end while the editor showed "98%".
 *
 * @package Spectra\Tests\Blocks
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * CounterNumberRenderTest test case.
 *
 * @since 1.0.13
 */
class CounterNumberRenderTest extends WP_UnitTestCase {

	/**
	 * A counter as the editor saves it, its number reading prefix and suffix from the root's context.
	 *
	 * @since 1.0.13
	 *
	 * @param array<string, mixed> $attrs Root attributes.
	 * @return string Rendered HTML.
	 */
	private function render_counter( array $attrs ): string {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'spectra/counter-child-number' ),
			'spectra/counter-child-number is not registered — run `npm run build`.'
		);

		return do_blocks(
			'<!-- wp:spectra/counter ' . wp_json_encode( $attrs ) . ' -->'
			. '<!-- wp:spectra/counter-child-wrapper -->'
			. '<!-- wp:spectra/counter-child-number /-->'
			. '<!-- /wp:spectra/counter-child-wrapper -->'
			. '<!-- /wp:spectra/counter -->'
		);
	}

	/**
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_the_suffix_follows_the_value_with_no_space(): void {
		$html = $this->render_counter( array( 'endNumber' => 98, 'suffix' => '%' ) );

		$this->assertMatchesRegularExpression(
			'#<span class="spectra-counter-value">[^<]*</span><span class="spectra-counter-suffix">%</span>#',
			$html,
			'White space between the value and the suffix prints as a space on the front end.'
		);
	}

	/**
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_the_prefix_and_suffix_hug_the_value(): void {
		$html = $this->render_counter( array( 'endNumber' => 1.4, 'decimalPlaces' => 1, 'prefix' => '$', 'suffix' => 'M' ) );

		$this->assertMatchesRegularExpression(
			'#<span class="spectra-counter-prefix">\$</span><span class="spectra-counter-value">[^<]*</span><span class="spectra-counter-suffix">M</span>#',
			$html
		);
	}

	/**
	 * The figure is in the page before the count runs, formatted as the count ends.
	 *
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_the_number_prints_its_end_value(): void {
		$cases = array(
			'98%'                         => array( 'endNumber' => 98, 'suffix' => '%' ),
			'1,428,950'                   => array( 'endNumber' => 1428950, 'thousandSeparator' => ',' ),
			'$4.9M'                       => array( 'endNumber' => 4.9, 'decimalPlaces' => 1, 'prefix' => '$', 'suffix' => 'M' ),
			'0.50'                        => array( 'endNumber' => 0.5, 'decimalPlaces' => 2 ),
		);
		foreach ( $cases as $expected => $attrs ) {
			$this->assertSame( $expected, trim( wp_strip_all_tags( $this->render_counter( $attrs ) ) ), wp_json_encode( $attrs ) );
		}
	}

	/**
	 * A simple counter paints its end; a bar or circular counter paints its start, as its bar and
	 * ring do until they fill. A counter that counts down paints the same way.
	 *
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_the_first_paint_follows_the_counter_style(): void {
		$cases = array(
			'simple, 0 to 98'             => array( '98%', array( 'counterStyle' => 'simple', 'endNumber' => 98, 'suffix' => '%' ) ),
			'bar, 0 to 75'                => array( '0%', array( 'counterStyle' => 'bar', 'endNumber' => 75, 'suffix' => '%' ) ),
			'circular, 0 to 75'           => array( '0%', array( 'counterStyle' => 'circular', 'endNumber' => 75, 'suffix' => '%' ) ),
			'simple, 5,000 down to 3,000' => array( '3,000', array( 'startNumber' => 5000, 'endNumber' => 3000, 'thousandSeparator' => ',' ) ),
			'bar, 80 down to 20'          => array( '80', array( 'counterStyle' => 'bar', 'startNumber' => 80, 'endNumber' => 20 ) ),
			'an unknown style, 0 to 500'  => array( '500+', array( 'counterStyle' => 'number', 'endNumber' => 500, 'suffix' => '+' ) ),
		);
		foreach ( $cases as $name => $case ) {
			$this->assertSame( $case[0], trim( wp_strip_all_tags( $this->render_counter( $case[1] ) ) ), $name );
		}
	}

	/**
	 * A circular counter's ring paints its start, as its number does, over the total the view
	 * script resolves: the total, else the end, else 100, never 0.
	 *
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_the_circular_ring_paints_its_start(): void {
		$cases = array(
			'25 of 100: a quarter drawn'           => array( '25', 0.75, array( 'startNumber' => 25, 'endNumber' => 75 ) ),
			'0 of 100: empty'                      => array( '0', 1.0, array( 'startNumber' => 0, 'endNumber' => 75 ) ),
			'total 0: the end is the total'        => array( '25', 2 / 3, array( 'startNumber' => 25, 'endNumber' => 75, 'totalNumber' => 0 ) ),
			'a negative total and 0 to 0: renders' => array( '0', 1.0, array( 'startNumber' => 0, 'endNumber' => 0, 'totalNumber' => -1 ) ),
		);
		foreach ( $cases as $name => $case ) {
			$html = $this->render_counter( array_merge( array( 'counterStyle' => 'circular' ), $case[2] ) );
			$this->assertMatchesRegularExpression( '#class="spectra-counter-progress-circle".*?stroke-dasharray="([0-9.]+)"\s+stroke-dashoffset="([0-9.]+)"#s', $html, $name );
			preg_match( '#class="spectra-counter-progress-circle".*?stroke-dasharray="([0-9.]+)"\s+stroke-dashoffset="([0-9.]+)"#s', $html, $ring );
			$this->assertEqualsWithDelta( $case[1], (float) $ring[2] / (float) $ring[1], 0.001, $name );
			$this->assertSame( $case[0], trim( wp_strip_all_tags( $html ) ), $name );
		}
	}

	/**
	 * @since 1.0.13
	 *
	 * @return void
	 */
	public function test_a_counter_without_a_suffix_prints_no_suffix_span(): void {
		$html = $this->render_counter( array( 'endNumber' => 70 ) );

		$this->assertStringNotContainsString( 'spectra-counter-suffix', $html );
		$this->assertStringNotContainsString( 'spectra-counter-prefix', $html );
	}
}
