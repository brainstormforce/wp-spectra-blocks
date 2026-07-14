<?php
/**
 * Tests for the JIT per-post cache + global version stamp.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use SpectraBlocks\GlobalStyles\JitCache;
use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * JitCacheTest test case.
 *
 * @since x.x.x
 */
class JitCacheTest extends WP_UnitTestCase {

	/**
	 * Reset option + memo between tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( JitCache::VERSION_OPTION );
		delete_option( Engine::OPTION_KEY_USER_CSS );
		JitCompiler::reset_memo();
	}

	/**
	 * Rebuild stores compiled CSS + version in post meta.
	 *
	 * @return void
	 */
	public function test_rebuild_stores_css_and_version(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:paragraph {"className":"text-[#ff0000]"} --><p class="text-[#ff0000]">x</p><!-- /wp:paragraph -->',
			)
		);

		JitCache::rebuild( $post_id );

		$stored = get_post_meta( $post_id, JitCache::META_KEY, true );

		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'version', $stored );
		$this->assertArrayHasKey( 'css', $stored );
		$this->assertStringContainsString( 'color: #ff0000;', $stored['css'] );
	}

	/**
	 * Version-stamp mismatch triggers a rebuild on read.
	 *
	 * @return void
	 */
	public function test_version_bump_invalidates_cache(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:paragraph {"className":"text-[#00ff00]"} --><p class="text-[#00ff00]">x</p><!-- /wp:paragraph -->',
			)
		);

		JitCache::rebuild( $post_id );
		$first = get_post_meta( $post_id, JitCache::META_KEY, true );

		JitCache::bump_version();

		JitCache::get_for_post( $post_id );
		$second = get_post_meta( $post_id, JitCache::META_KEY, true );

		$this->assertNotSame( $first['version'], $second['version'] );
	}

	/**
	 * Clear removes the post meta entry.
	 *
	 * @return void
	 */
	public function test_clear_for_post_deletes_meta(): void {
		$post_id = self::factory()->post->create();
		update_post_meta(
			$post_id,
			JitCache::META_KEY,
			array(
				'version' => '1',
				'css'     => 'x',
			)
		);

		JitCache::clear_for_post( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, JitCache::META_KEY, true ) );
	}

	/**
	 * Empty post content yields empty cached CSS.
	 *
	 * @return void
	 */
	public function test_empty_content_produces_empty_css(): void {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		$css = JitCache::rebuild( $post_id );

		$this->assertSame( '', $css );
	}
}
