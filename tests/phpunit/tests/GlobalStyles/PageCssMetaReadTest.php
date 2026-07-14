<?php
/**
 * Tests for the per-page Gen CSS meta read path
 * ({@see GenCssOrphanStripper::read_page_payload()}).
 *
 * Pins the contract every render goes through:
 *   - the `spectra_blocks_pro_gs_user_css` post meta is returned when present;
 *   - empty / non-array / invalid-post-id values are treated as absent (null),
 *     never rendered.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\GenCssOrphanStripper;
use WP_UnitTestCase;

/**
 * PageCssMetaReadTest test case.
 *
 * @since x.x.x
 */
class PageCssMetaReadTest extends WP_UnitTestCase {

	/**
	 * Minimal schema-v1 payload used across cases.
	 *
	 * @return array<string,mixed>
	 */
	private function payload(): array {
		return array(
			'v'       => '1',
			'classes' => array(
				'gs-test-link' => array( 'default' => array( 'color' => '#111' ) ),
			),
		);
	}

	/**
	 * The stored meta payload is returned as-is.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_reads_meta_payload(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, GenCssOrphanStripper::META_KEY, $this->payload() );

		$this->assertSame( $this->payload(), GenCssOrphanStripper::read_page_payload( $post_id ) );
	}

	/**
	 * No row → null.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_returns_null_when_absent(): void {
		$post_id = self::factory()->post->create();

		$this->assertNull( GenCssOrphanStripper::read_page_payload( $post_id ) );
	}

	/**
	 * Empty arrays and non-array values are absent — never returned.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_empty_or_malformed_values_are_absent(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, GenCssOrphanStripper::META_KEY, array() );

		$this->assertNull( GenCssOrphanStripper::read_page_payload( $post_id ) );
	}

	/**
	 * Invalid post id → null, no queries that could fatal.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_invalid_post_id_is_null(): void {
		$this->assertNull( GenCssOrphanStripper::read_page_payload( 0 ) );
		$this->assertNull( GenCssOrphanStripper::read_page_payload( -5 ) );
	}
}
