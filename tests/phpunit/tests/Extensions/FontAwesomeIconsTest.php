<?php
/**
 * FontAwesomeIcons: an FA `<i>` in `spectra/content` text draws its registry icon.
 *
 * @package Spectra\Tests\Extensions
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use SpectraBlocks\Blocks\InlineLeaf;
use SpectraBlocks\Extensions\FontAwesomeIcons;
use SpectraBlocks\Helpers\Renderer;
use WP_UnitTestCase;

/**
 * FontAwesomeIconsTest test case.
 *
 * @since 1.0.10
 */
class FontAwesomeIconsTest extends WP_UnitTestCase {

	/**
	 * Render a real `spectra/content` block (view + KSES + filter).
	 *
	 * @param string $text      Block text.
	 * @param bool   $converter Whether it is converter output (the pass's stamp).
	 * @return string
	 */
	private function render_content( string $text, bool $converter = true ): string {
		return (string) render_block(
			array(
				'blockName'    => 'spectra/content',
				'attrs'        => array(
					'tagName' => 'p',
					'text'    => $text,
				) + ( $converter ? array( InlineLeaf::SCOPE_ATTR => true ) : array() ),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * The `d` of every drawn SVG, in order.
	 *
	 * @param string $html Filtered HTML.
	 * @return string[]
	 */
	private function paths( string $html ): array {
		preg_match_all( '#<svg\b[^>]*>\s*<path d="([^"]*)"#', $html, $m );
		return $m[1];
	}

	/**
	 * A registry variant's path.
	 *
	 * @param string $name  Icon name.
	 * @param string $style Variant.
	 * @return string
	 */
	private function path( string $name, string $style ): string {
		return Renderer::icons()[ $name ]['svg'][ $style ]['path'];
	}

	/**
	 * Through the real render: the SVG goes first inside the `<i>`, which keeps
	 * its classes; a modifier before the name, an alias, an NBSP-padded `<i>`.
	 */
	public function test_draws_registry_svg_inside_the_i(): void {
		$out = $this->render_content( 'A <i class="fa-lg fa-solid fa-gear"></i> B <i class="fa-solid fa-check-circle"></i> <i class="fa fa-phone">&nbsp;</i>Call' );

		$this->assertStringContainsString( '<i class="fa-lg fa-solid fa-gear"><svg ', $out );
		$this->assertMatchesRegularExpression( '#<svg\b[^>]*\saria-hidden="true"[^>]*\sstyle="height: 1em; vertical-align: -0.125em;"#', $out );
		$this->assertSame( array( $this->path( 'gear', 'solid' ), $this->path( 'circle-check', 'solid' ), $this->path( 'phone', 'solid' ) ), $this->paths( $out ) );
	}

	/**
	 * Hand-built content keeps its `<i>`: a site that loads Font Awesome itself
	 * keeps its own glyphs.
	 */
	public function test_hand_built_content_is_untouched(): void {
		$out = $this->render_content( 'A <i class="fa-solid fa-gear"></i>', false );

		$this->assertStringContainsString( '<i class="fa-solid fa-gear"></i>', $out );
		$this->assertStringNotContainsString( '<svg', $out );
	}

	/**
	 * A style class picks the variant, long and short form.
	 */
	public function test_style_class_picks_the_variant(): void {
		$regular = $this->path( 'star', 'regular' );
		$this->assertNotSame( $this->path( 'star', 'solid' ), $regular );
		$this->assertSame( array( $regular, $regular ), $this->paths( FontAwesomeIcons::draw_icons( '<p><i class="fa-regular fa-star"></i><i class="far fa-star"></i></p>' ) ) );
	}

	/**
	 * Only a marked `<i>` carries FA7's element rule, its size modifier, then its own style.
	 */
	public function test_only_a_marked_i_gets_the_cell(): void {
		$rule   = 'display:var(--fa-display,inline-block);line-height:1;text-align:center;width:var(--fa-width,1.25em)';
		$marked = $this->render_content( '<i data-icon-cell class="fa-solid fa-star fa-xl" style="color:red"></i>' );
		$this->assertStringContainsString( 'style="' . $rule . ';font-size:1.5em;line-height:.04167em;vertical-align:-.125em;color:red"', $marked );

		$this->assertStringNotContainsString( 'inline-block', $this->render_content( '<i class="fa-solid fa-star fa-xl"></i>' ) );
	}

	/**
	 * The cell mark is the one the shared contract publishes (`fa_cell.attribute`).
	 */
	public function test_cell_mark_is_the_published_one(): void {
		$published = wp_json_file_decode( dirname( __DIR__ ) . '/GlobalStyles/fixtures/spectra-contract.json', array( 'associative' => true ) );
		$this->assertSame( $published['fa_cell']['attribute'], FontAwesomeIcons::CELL_ATTR );
	}

	/**
	 * Left as is: an unknown name, a modifier alone, italics, an `<i>` already
	 * drawn; a second run changes nothing.
	 */
	public function test_untouched_when_nothing_to_draw(): void {
		$html = '<p><i class="fa-solid fa-no-such-icon"></i><i class="fa-lg"></i><i>it</i><i class="fa-solid fa-star"><svg viewBox="0 0 1 1"></svg></i></p>';
		$this->assertSame( $html, FontAwesomeIcons::draw_icons( $html ) );

		$once = FontAwesomeIcons::draw_icons( '<p><i class="fa-solid fa-star"></i></p>' );
		$this->assertSame( $once, FontAwesomeIcons::draw_icons( $once ) );
	}
}
