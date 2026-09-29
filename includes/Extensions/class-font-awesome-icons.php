<?php
/**
 * Font Awesome icons in block text.
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Blocks\InlineLeaf;
use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Helpers\Renderer;
use WP_HTML_Tag_Processor;

defined( 'ABSPATH' ) || exit;

/**
 * Draws a Font Awesome `<i>` in `spectra/content` text with the registry SVG
 * `spectra/icon` draws from. WordPress never loads Font Awesome, so the `<i>`
 * would render as an empty box.
 *
 * @since 1.0.10
 */
class FontAwesomeIcons {

	/**
	 * An `<i>` open tag whose first child is not already an SVG (ours, or Font
	 * Awesome's script's). Runs after the block's KSES, which ends a tag at `>`.
	 *
	 * @since 1.0.10
	 * @var string
	 */
	const UNDRAWN_I_OPEN_TAG = '/<i\b[^>]*>(?!\s*<svg\b)/i';

	/**
	 * Font Awesome style classes, by the registry variant each names.
	 *
	 * @since 1.0.10
	 * @var array<string, string>
	 */
	const STYLE_CLASSES = array(
		'fa-solid'   => 'solid',
		'fas'        => 'solid',
		'fa-regular' => 'regular',
		'far'        => 'regular',
		'fa-brands'  => 'brands',
		'fab'        => 'brands',
	);

	/**
	 * The converter's mark on a page whose preview drew icons with Font
	 * Awesome 7's sheet (`fa_cell.attribute` in zipwp-credits-saas
	 * `config/spectra-contract.json`).
	 *
	 * @since 1.0.10
	 * @var string
	 */
	const CELL_ATTR = 'data-icon-cell';

	/**
	 * Font Awesome 7.3.1's element rule, less its webfont. Inline on a marked
	 * `<i>`, it beats page rules and loses to the `<i>`'s own style, as the
	 * unlayered FA sheet stands over the preview's layered page rules.
	 *
	 * @since 1.0.10
	 * @var string
	 */
	const ELEMENT_RULE = 'display:var(--fa-display,inline-block);line-height:1;text-align:center;width:var(--fa-width,1.25em)';

	/**
	 * Font Awesome 7's size modifiers the design library uses, in FA's source order.
	 *
	 * @since 1.0.10
	 * @var array<string, string>
	 */
	const MODIFIER_RULES = array(
		'fa-lg' => 'font-size:1.25em;line-height:.05em;vertical-align:-.075em',
		'fa-xl' => 'font-size:1.5em;line-height:.04167em;vertical-align:-.125em',
	);

	/**
	 * Hook the content block's rendered output.
	 *
	 * @since 1.0.10
	 * @return void
	 */
	public static function init() {
		add_filter( 'render_block_spectra/content', array( self::class, 'draw_converter_icons' ), 10, 2 );
	}

	/**
	 * Draw icons in converter output only ({@see InlineLeaf::in_scope()}). A
	 * hand-built page whose site loads Font Awesome itself keeps its own glyphs.
	 *
	 * @since 1.0.10
	 * @param string       $block_content Rendered block HTML.
	 * @param array<mixed> $block         The parsed block.
	 * @return string
	 */
	public static function draw_converter_icons( $block_content, $block = array() ) {
		if ( ! is_string( $block_content ) || false === strpos( $block_content, 'fa-' ) ) {
			return $block_content;
		}
		$attrs = is_array( $block ) && is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
		return InlineLeaf::in_scope( $attrs ) ? self::draw_icons( $block_content ) : $block_content;
	}

	/**
	 * Draw every undrawn FA `<i>` of a rendered block.
	 *
	 * @since 1.0.10
	 * @param string $block_content Rendered block HTML.
	 * @return string
	 */
	public static function draw_icons( $block_content ) {
		if ( ! is_string( $block_content ) || false === strpos( $block_content, 'fa-' ) ) {
			return $block_content;
		}

		return preg_replace_callback( self::UNDRAWN_I_OPEN_TAG, array( self::class, 'draw_icon' ), $block_content ) ?? $block_content;
	}

	/**
	 * The `<i>` open tag followed by its icon's SVG, or the tag untouched when
	 * no `fa-` class names a registry icon.
	 *
	 * @since 1.0.10
	 * @param array<int, string> $matches The matched open tag.
	 * @return string
	 */
	private static function draw_icon( array $matches ): string {
		$tag = new WP_HTML_Tag_Processor( $matches[0] );
		if ( ! $tag->next_tag( array( 'tag_name' => 'i' ) ) ) {
			return $matches[0];
		}

		$icons   = Renderer::icons();
		$aliases = Core::backend_load_font_awesome_icon_aliases();
		$classes = iterator_to_array( $tag->class_list(), false );
		$name    = '';
		$variant = '';
		foreach ( $classes as $class_name ) {
			$token = 0 === strpos( $class_name, 'fa-' ) ? substr( $class_name, 3 ) : '';
			if ( '' === $name && ( isset( $icons[ $token ] ) || isset( $icons[ $aliases[ $token ] ?? '' ] ) ) ) {
				$name = $token;
			}
			if ( '' === $variant ) {
				$variant = self::STYLE_CLASSES[ $class_name ] ?? '';
			}
		}

		if ( '' === $name ) {
			return $matches[0];
		}

		if ( null !== $tag->get_attribute( self::CELL_ATTR ) ) {
			$rules = self::ELEMENT_RULE;
			foreach ( array_intersect_key( self::MODIFIER_RULES, array_flip( $classes ) ) as $rule ) {
				$rules .= ';' . $rule;
			}
			$own_style = trim( (string) $tag->get_attribute( 'style' ) );
			$tag->set_attribute( 'style', '' === $own_style ? $rules : $rules . ';' . $own_style );
		}

		ob_start();
		Renderer::svg_html(
			$name,
			false,
			array(
				'aria-hidden' => 'true',
				'style'       => array(
					'height'         => '1em',
					'vertical-align' => '-0.125em',
				),
			),
			$variant
		);

		return $tag->get_updated_html() . trim( (string) ob_get_clean() );
	}
}
