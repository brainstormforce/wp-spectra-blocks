<?php
/**
 * Tests for BlockJsCompiler — per-block spectraCustomJS rendering.
 *
 * @package Spectra\Tests\Extensions
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use ReflectionMethod;
use SpectraBlocks\Extensions\BlockJsCompiler;
use WP_UnitTestCase;

/**
 * BlockJsCompilerTest test case.
 *
 * @since x.x.x
 */
class BlockJsCompilerTest extends WP_UnitTestCase {

	/**
	 * Invoke the private collect_block_js() via reflection.
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks.
	 * @return string[]
	 */
	private function collect( array $blocks ): array {
		$compiler = BlockJsCompiler::instance();
		$method   = new ReflectionMethod( $compiler, 'collect_block_js' );
		$method->setAccessible( true );

		return $method->invoke( $compiler, $blocks );
	}

	/**
	 * A parsed-block array shaped like parse_blocks() output.
	 *
	 * @param array<string, mixed> $attrs        Block attributes.
	 * @param array<int, mixed>    $inner_blocks Nested blocks.
	 * @return array<string, mixed>
	 */
	private function block( array $attrs, array $inner_blocks = array() ): array {
		return array(
			'blockName'    => 'spectra/container',
			'attrs'        => $attrs,
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	/**
	 * `_current_block_` resolves to the block's `spectra-bce-{id}` scope class.
	 */
	public function test_placeholder_resolves_to_scope_class() {
		$snippets = $this->collect(
			array(
				$this->block(
					array(
						'spectraCustomJS' => "document.querySelector('._current_block_').focus();",
						'spectraBCEId'    => 'abc123',
					)
				),
			)
		);

		$this->assertCount( 1, $snippets );
		$this->assertStringContainsString( 'spectra-bce-abc123', $snippets[0] );
		$this->assertStringNotContainsString( BlockJsCompiler::BLOCK_PLACEHOLDER, $snippets[0] );
	}

	/**
	 * Without a BCE id, the placeholder is left intact (no class to resolve to).
	 */
	public function test_placeholder_untouched_without_bce_id() {
		$snippets = $this->collect(
			array(
				$this->block(
					array( 'spectraCustomJS' => "var x = '_current_block_';" )
				),
			)
		);

		$this->assertCount( 1, $snippets );
		$this->assertStringContainsString( BlockJsCompiler::BLOCK_PLACEHOLDER, $snippets[0] );
	}

	/**
	 * Entity-encoded `&gt;` (WP serialisation) is decoded back to `>`.
	 */
	public function test_entity_encoded_gt_is_decoded() {
		$snippets = $this->collect(
			array(
				$this->block(
					array( 'spectraCustomJS' => 'if (a &gt; b) { c(); }' )
				),
			)
		);

		$this->assertCount( 1, $snippets );
		$this->assertStringContainsString( 'a > b', $snippets[0] );
		$this->assertStringNotContainsString( '&gt;', $snippets[0] );
	}

	/**
	 * Blocks with no (or empty) spectraCustomJS contribute nothing.
	 */
	public function test_blocks_without_js_are_skipped() {
		$snippets = $this->collect(
			array(
				$this->block( array( 'className' => 'no-js' ) ),
				$this->block( array( 'spectraCustomJS' => '   ' ) ),
				$this->block( array( 'spectraCustomJS' => 'kept();' ) ),
			)
		);

		$this->assertSame( array( 'kept();' ), $snippets );
	}

	/**
	 * Nested innerBlocks are walked; snippets collected parent-then-child.
	 */
	public function test_nested_inner_blocks_are_collected_in_order() {
		$snippets = $this->collect(
			array(
				$this->block(
					array( 'spectraCustomJS' => 'parent();' ),
					array(
						$this->block( array( 'spectraCustomJS' => 'child();' ) ),
					)
				),
			)
		);

		$this->assertSame( array( 'parent();', 'child();' ), $snippets );
	}

	/**
	 * Footer output collects every block's JS and wraps EACH in its own IIFE,
	 * inside a single footer <script> tag.
	 */
	public function test_footer_output_wraps_each_block_in_its_own_iife() {
		$content = serialize_blocks(
			array(
				$this->block( array( 'spectraCustomJS' => 'first();' ) ),
				$this->block( array( 'spectraCustomJS' => 'second();' ) ),
			)
		);

		$post_id = self::factory()->post->create( array( 'post_content' => wp_slash( $content ) ) );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular() );

		ob_start();
		BlockJsCompiler::instance()->output_block_js();
		$out = ob_get_clean();

		// Both blocks present, each wrapped in its own IIFE, in one <script>.
		$this->assertSame( 2, substr_count( $out, '(function(){' ) );
		$this->assertStringContainsString( 'first();', $out );
		$this->assertStringContainsString( 'second();', $out );
		$this->assertSame( 1, substr_count( $out, '<script' ) );
	}

	/**
	 * The `</script>` escape neutralises a user closing tag (any case) so it
	 * can't terminate the inline <script> early. Exercises the renderer's
	 * transform directly — the render path is covered above, and the DB
	 * round-trip's handling of an escaped `<` in a block attribute is
	 * WP-version-specific, not our code.
	 */
	public function test_script_close_is_escaped() {
		$method = new ReflectionMethod( BlockJsCompiler::class, 'escape_script_close' );
		$method->setAccessible( true );
		$out = $method->invoke( null, "var s = '</script>'; // </SCRIPT>" );

		$this->assertStringNotContainsString( '</script', $out );
		$this->assertSame( 2, substr_count( $out, '<\/script' ) );
	}

	/**
	 * On a non-singular request nothing is printed (early return).
	 */
	public function test_non_singular_prints_nothing() {
		$this->go_to( home_url( '/' ) );

		ob_start();
		BlockJsCompiler::instance()->output_block_js();
		$out = ob_get_clean();

		$this->assertSame( '', $out );
	}
}
