<?php
/**
 * Inline leaf: a nested `spectra/content` block that prints a phrasing tag.
 *
 * @package Spectra\Blocks
 */

namespace SpectraBlocks\Blocks;

use SpectraBlocks\AssetLoader;

/**
 * An inline leaf sits in its parent's line, where any whitespace is a visible
 * space: it prints tight, and a container drops the serializer's whitespace-only
 * `innerContent` beside it. A source space rides `spaceBefore` / `spaceAfter`.
 *
 * Converter output only — an imported page, or a container carrying the
 * converter's {@see AssetLoader::NO_BLOCK_GAP_MARKER} (sections the vibe editor
 * inserts into drafts the importer never wrote). Hand-built nested leaves keep
 * the whitespace their pages already render with.
 *
 * @since 1.0.10
 */
class InlineLeaf {

	/**
	 * The `spectra/content` tags that are phrasing content.
	 *
	 * @since 1.0.10
	 * @var string[]
	 */
	const PHRASING_TAGS = array( 'span', 'a', 'strong', 'em', 'small', 'mark', 'del', 'ins', 'sub', 'sup', 'abbr', 'code', 'kbd', 'samp', 'var', 'output', 'q', 's', 'dfn', 'bdi', 'bdo', 'cite', 'time', 'label' );

	/**
	 * Render-time attribute the pass stamps on a `spectra/content` inside a
	 * converter-marked container. Never serialized.
	 *
	 * @since 1.0.10
	 * @var string
	 */
	const SCOPE_ATTR = 'spectraConverterLeaf';

	/**
	 * Hook the whitespace pass.
	 *
	 * @since 1.0.10
	 */
	public static function init(): void {
		add_filter( 'render_block_data', array( self::class, 'drop_whitespace_beside_inline_leaves' ), 10, 3 );
	}

	/**
	 * Whether a `spectra/content` block is converter output.
	 *
	 * @since 1.0.10
	 *
	 * @param array<mixed> $attrs The block's attributes.
	 * @return bool
	 */
	public static function in_scope( array $attrs ): bool {
		return ! empty( $attrs[ self::SCOPE_ATTR ] ) || AssetLoader::is_imported_singular();
	}

	/**
	 * An inline leaf that is converter output: it prints tight.
	 *
	 * @since 1.0.10
	 *
	 * @param array<mixed> $attrs The block's attributes.
	 * @return bool
	 */
	public static function prints_tight( array $attrs ): bool {
		return self::is_inline( $attrs ) && self::in_scope( $attrs );
	}

	/**
	 * Nested (a root `<span>` gets a `<div>` wrapper) and a phrasing tag.
	 *
	 * @since 1.0.10
	 *
	 * @param array<mixed> $attrs The block's attributes.
	 * @return bool
	 */
	public static function is_inline( array $attrs ): bool {
		return false === ( $attrs['isRootBlock'] ?? true )
			&& in_array( $attrs['tagName'] ?? 'p', self::PHRASING_TAGS, true );
	}

	/**
	 * Run the whitespace pass once, from the top-level block. Core applies
	 * `render_block_data` to every inner block too; answering those would walk
	 * each subtree again at every level. One pass from the top also covers
	 * WP < 6.8, which does not carry a nested block's result to its `innerContent`.
	 *
	 * @since 1.0.10
	 *
	 * @param array<mixed> $parsed_block The parsed block.
	 * @param array<mixed> $source_block Unused: the block before filtering.
	 * @param mixed        $parent_block The parent WP_Block; null for a top-level block.
	 * @return array<mixed>
	 */
	public static function drop_whitespace_beside_inline_leaves( array $parsed_block, $source_block = array(), $parent_block = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- filter signature.
		if ( null !== $parent_block || empty( $parsed_block['innerBlocks'] ) ) {
			return $parsed_block;
		}
		return self::walk( $parsed_block, AssetLoader::is_imported_singular() );
	}

	/**
	 * Stamp converter-scoped leaves and blank a converter `spectra/container`'s
	 * whitespace-only chunk between two inner blocks when either is an inline leaf.
	 *
	 * @since 1.0.10
	 *
	 * @param array<mixed> $parsed_block The parsed block.
	 * @param bool         $imported     Whether the page is imported.
	 * @return array<mixed>
	 */
	private static function walk( array $parsed_block, bool $imported ): array {
		$blocks = $parsed_block['innerBlocks'] ?? null;
		if ( ! is_array( $blocks ) || array() === $blocks ) {
			return $parsed_block;
		}
		$is_container = 'spectra/container' === ( $parsed_block['blockName'] ?? '' );
		$scoped       = $is_container && ( $imported || AssetLoader::has_no_block_gap_marker( $parsed_block ) );
		foreach ( $blocks as $key => $inner ) {
			if ( ! is_array( $inner ) ) {
				continue;
			}
			if ( $scoped && 'spectra/content' === ( $inner['blockName'] ?? '' ) && is_array( $inner['attrs'] ?? null ) ) {
				$inner['attrs'][ self::SCOPE_ATTR ] = true;
			}
			$blocks[ $key ] = self::walk( $inner, $imported );
		}
		$parsed_block['innerBlocks'] = $blocks;
		$content                     = $parsed_block['innerContent'] ?? null;
		if ( ! $scoped || ! is_array( $content ) ) {
			return $parsed_block;
		}
		$inline = array_values( array_map( fn( $inner ) => is_array( $inner ) && 'spectra/content' === ( $inner['blockName'] ?? '' ) && is_array( $inner['attrs'] ?? null ) && self::is_inline( $inner['attrs'] ), $blocks ) );
		$next   = 0; // Index of the inner block after the current chunk.
		foreach ( $content as $i => $chunk ) {
			if ( null === $chunk ) {
				++$next;
			} elseif ( is_string( $chunk ) && $next > 0 && $next < count( $inline ) && '' === trim( $chunk ) && ( $inline[ $next - 1 ] || $inline[ $next ] ) ) {
				$content[ $i ] = '';
			}
		}
		$parsed_block['innerContent'] = $content;
		return $parsed_block;
	}
}
