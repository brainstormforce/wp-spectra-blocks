<?php
/**
 * Tests for BlockJsCompiler — per-block spectraCustomJS rendering.
 *
 * @package Spectra\Tests\Extensions
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use ReflectionMethod;
use ReflectionProperty;
use SpectraBlocks\Extensions\BlockJsCompiler;
use WP_UnitTestCase;

/**
 * BlockJsCompilerTest test case.
 *
 * @since x.x.x
 */
class BlockJsCompilerTest extends WP_UnitTestCase {

	/**
	 * The compiler is a singleton, so snippets harvested by one test would leak
	 * into the next. Reset the buffer before each.
	 */
	public function set_up() {
		parent::set_up();

		$this->buffer()->setValue( BlockJsCompiler::instance(), array() );
	}

	/**
	 * Accessor for the private harvest buffer.
	 *
	 * @return ReflectionProperty
	 */
	private function buffer(): ReflectionProperty {
		$property = new ReflectionProperty( BlockJsCompiler::class, 'rendered_js' );
		$property->setAccessible( true );

		return $property;
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
			// One null per inner block — that is how WP_Block::render() knows where
			// to render each child. Without them the children never render, so their
			// JS is never harvested.
			'innerContent' => array_fill( 0, count( $inner_blocks ), null ),
		);
	}

	/**
	 * A post carrying block JS, stored by a user who is allowed to author it.
	 *
	 * The gate only exempts WP-CLI / cron, so a fixture written with no current
	 * user is stripped like any other untrusted write — seed as a capable user or
	 * the arrange step silently produces an empty post.
	 *
	 * @param string $js Snippet to store.
	 * @return int Post ID.
	 */
	private function post_with_js( string $js ): int {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user );
		}
		wp_set_current_user( $user );

		$post_id = self::factory()->post->create(
			array(
				'post_content' => wp_slash(
					serialize_blocks( array( $this->block( array( 'spectraCustomJS' => $js ) ) ) )
				),
			)
		);
		$this->assertStringContainsString( $js, get_post_field( 'post_content', $post_id ) );

		return $post_id;
	}

	/**
	 * Push blocks through the REAL render pass and return what was harvested.
	 *
	 * @param array<int, mixed> $blocks Parsed blocks.
	 * @return string[]
	 */
	private function harvest( array $blocks ): array {
		do_blocks( serialize_blocks( $blocks ) );

		return $this->buffer()->getValue( BlockJsCompiler::instance() );
	}

	/**
	 * The harvest is wired to `render_block`, not to parsing the queried post —
	 * that is what lets a template part's or pattern's JS travel with the block.
	 */
	public function test_harvest_is_hooked_to_render_block() {
		$this->assertNotFalse(
			has_filter( 'render_block', array( BlockJsCompiler::instance(), 'harvest_block_js' ) )
		);
	}

	/**
	 * `_current_block_` resolves to the block's `spectra-bce-{id}` scope class.
	 */
	public function test_placeholder_resolves_to_scope_class() {
		$snippets = $this->harvest(
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
		$snippets = $this->harvest(
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
	 * Blocks with no (or empty) spectraCustomJS contribute nothing.
	 */
	public function test_blocks_without_js_are_skipped() {
		$snippets = $this->harvest(
			array(
				$this->block( array( 'className' => 'no-js' ) ),
				$this->block( array( 'spectraCustomJS' => '   ' ) ),
				$this->block( array( 'spectraCustomJS' => 'kept();' ) ),
			)
		);

		$this->assertSame( array( 'kept();' ), $snippets );
	}

	/**
	 * Nested innerBlocks render too, so their JS is harvested.
	 *
	 * Order is CHILD before parent: `render_block` fires on a block only once its
	 * inner blocks have rendered into it. The old post_content walk emitted
	 * parent-then-child, so nesting order is inverted relative to 1.0.0.
	 */
	public function test_nested_inner_blocks_are_harvested_child_first() {
		$snippets = $this->harvest(
			array(
				$this->block(
					array( 'spectraCustomJS' => 'parent();' ),
					array(
						$this->block( array( 'spectraCustomJS' => 'child();' ) ),
					)
				),
			)
		);

		$this->assertSame( array( 'child();', 'parent();' ), $snippets );
	}

	/**
	 * A snippet rendered twice — e.g. a template part used in two places — runs once.
	 */
	public function test_duplicate_snippets_collapse() {
		$snippets = $this->harvest(
			array(
				$this->block( array( 'spectraCustomJS' => 'once();' ) ),
				$this->block( array( 'spectraCustomJS' => 'once();' ) ),
				$this->block( array( 'spectraCustomJS' => 'twice();' ) ),
			)
		);

		$this->assertSame( array( 'once();', 'twice();' ), $snippets );
	}

	/**
	 * The attribute is NOT entity-decoded: `serialize_block_attributes()` escapes
	 * `<`/`>`/`&` as JSON unicode escapes, which `parse_blocks()` has already
	 * decoded — so a decode pass here would only undo kses's `&` => `&amp;`
	 * normalisation and hand back live syntax.
	 */
	public function test_entities_are_not_decoded() {
		$snippets = $this->harvest(
			array(
				$this->block( array( 'spectraCustomJS' => 'if (a &gt; b) { c(); }' ) ),
			)
		);

		$this->assertSame( array( 'if (a &gt; b) { c(); }' ), $snippets );
	}

	/**
	 * Footer output wraps EACH harvested snippet in its own IIFE, inside a single
	 * footer <script> tag.
	 */
	public function test_footer_output_wraps_each_block_in_its_own_iife() {
		$this->harvest(
			array(
				$this->block( array( 'spectraCustomJS' => 'first();' ) ),
				$this->block( array( 'spectraCustomJS' => 'second();' ) ),
			)
		);

		ob_start();
		BlockJsCompiler::instance()->output_block_js();
		$out = ob_get_clean();

		$this->assertSame( 2, substr_count( $out, '(function(){' ) );
		$this->assertStringContainsString( 'first();', $out );
		$this->assertStringContainsString( 'second();', $out );
		$this->assertSame( 1, substr_count( $out, '<script' ) );
	}

	/**
	 * Nothing rendered this request means nothing printed.
	 */
	public function test_nothing_harvested_prints_nothing() {
		ob_start();
		BlockJsCompiler::instance()->output_block_js();
		$out = ob_get_clean();

		$this->assertSame( '', $out );
	}

	/**
	 * The `</script>` escape neutralises a user closing tag (any case) so it
	 * can't terminate the inline <script> early.
	 */
	public function test_script_close_is_escaped() {
		$method = new ReflectionMethod( BlockJsCompiler::class, 'escape_script_close' );
		$method->setAccessible( true );
		$out = $method->invoke( null, "var s = '</script>'; // </SCRIPT>" );

		$this->assertStringNotContainsString( '</script', $out );
		$this->assertSame( 2, substr_count( $out, '<\/script' ) );
	}

	/**
	 * A user without `unfiltered_html` cannot PERSIST block JS.
	 *
	 * This is what makes harvesting at render time safe: kses preserves block
	 * delimiters (`wp_kses_split2()` re-wraps the comment), and
	 * `excerpt_remove_blocks()` calls `render_block()` while building archive and
	 * search excerpts — so a snippet an Author saved would otherwise execute for
	 * an administrator on the blog index.
	 */
	public function test_author_cannot_persist_block_js() {
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $author );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$content = serialize_blocks(
			array(
				$this->block(
					array(
						'spectraCustomJS' => 'stealCookies();',
						'className'       => 'keep-me',
					),
					array(
						$this->block( array( 'spectraCustomJS' => 'nestedSteal();' ) ),
					)
				),
			)
		);

		$post_id = self::factory()->post->create(
			array(
				'post_author'  => $author,
				'post_content' => wp_slash( $content ),
			)
		);
		$stored  = get_post_field( 'post_content', $post_id );

		$this->assertStringNotContainsString( 'spectraCustomJS', $stored );
		$this->assertStringNotContainsString( 'stealCookies', $stored );
		$this->assertStringNotContainsString( 'nestedSteal', $stored );

		// Only the JS attribute is dropped — the rest of the block survives.
		$this->assertStringContainsString( 'keep-me', $stored );
		$this->assertStringContainsString( 'spectra/container', $stored );
	}

	/**
	 * A user who holds `unfiltered_html` still can.
	 *
	 * Administrator holds it on single-site; on multisite core grants it ONLY to a
	 * super admin (`map_meta_cap()`), and never to anyone under
	 * `DISALLOW_UNFILTERED_HTML` — so assert against the capability, not the role,
	 * or this passes for the wrong reason (or fails) depending on the install.
	 */
	public function test_a_user_with_the_capability_can_persist_block_js() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user );
		}
		wp_set_current_user( $user );
		$this->assertTrue( current_user_can( 'unfiltered_html' ) );

		$content = serialize_blocks(
			array(
				$this->block( array( 'spectraCustomJS' => 'trusted();' ) ),
			)
		);

		$post_id = self::factory()->post->create( array( 'post_content' => wp_slash( $content ) ) );

		$this->assertStringContainsString( 'trusted();', get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * The attribute name is matched on the DECODED block, never as a substring.
	 *
	 * JSON allows `\uXXXX` inside an object key, so an escaped spelling carries no
	 * literal `spectraCustomJS` for a `strpos()` fast-path to find, while
	 * `parse_blocks()` — which `json_decode`s attributes — yields the real key.
	 *
	 * The filter is invoked DIRECTLY here on purpose. Going through
	 * `wp_insert_post()` would prove nothing: `pre_kses` runs
	 * `wp_pre_kses_block_attributes()` → `filter_block_content()`, which
	 * re-serializes every block through `wp_json_encode()` and normalises the
	 * escape away before this filter ever sees it. That happens only for users who
	 * get kses, so it must not be what this gate relies on.
	 */
	public function test_gate_matches_the_decoded_block_not_a_substring() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );

		// Assembled from parts so nothing in transit can normalise it.
		$escaped = chr( 92 ) . 'u0073' . 'pectraCustomJS';
		$content = '<!-- wp:paragraph {"' . $escaped . '":"smuggled();"} --><p>hi</p><!-- /wp:paragraph -->';

		// The premise: no literal to match, yet the parser yields the real key.
		$this->assertFalse( strpos( $content, 'spectraCustomJS' ) );
		$this->assertArrayHasKey( 'spectraCustomJS', parse_blocks( $content )[0]['attrs'] );

		$out = BlockJsCompiler::instance()->strip_untrusted_block_js(
			array( 'post_content' => wp_slash( $content ) ),
			array()
		);

		$this->assertStringNotContainsString( 'smuggled();', wp_unslash( $out['post_content'] ) );
	}

	/**
	 * An edit that does not touch the content must not delete JS already stored.
	 *
	 * `wp_update_post()` re-reads the whole row before this filter runs, so Quick
	 * Edit, Bulk Edit, trash/untrash, autosave and revision restore all push the
	 * existing content back through it. Stripping unconditionally would delete
	 * snippets the editor never touched — unrecoverably, since restoring the
	 * revision re-runs the strip.
	 */
	public function test_untrusted_edit_keeps_block_js_it_did_not_introduce() {
		// Stored by someone who was allowed to.
		$post_id = $this->post_with_js( 'legitimate();' );

		// …then edited by someone who is not (a site admin on multisite, anyone
		// under DISALLOW_UNFILTERED_HTML, or a plain Author here).
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => 'Renamed, content untouched',
			)
		);

		$this->assertStringContainsString( 'legitimate();', get_post_field( 'post_content', $post_id ) );
	}

	/**
	 * …but the same user still cannot ADD a new snippet alongside the old one.
	 */
	public function test_untrusted_edit_still_cannot_introduce_new_block_js() {
		$post_id = $this->post_with_js( 'legitimate();' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash(
					serialize_blocks(
						array(
							$this->block( array( 'spectraCustomJS' => 'legitimate();' ) ),
							$this->block( array( 'spectraCustomJS' => 'stealCookies();' ) ),
						)
					)
				),
			)
		);

		$stored = get_post_field( 'post_content', $post_id );
		$this->assertStringContainsString( 'legitimate();', $stored );
		$this->assertStringNotContainsString( 'stealCookies();', $stored );
	}

	/**
	 * An attachment description is block content too, and takes its OWN insert
	 * filter — hooking only `wp_insert_post_data` leaves it wide open.
	 */
	public function test_author_cannot_persist_block_js_on_an_attachment() {
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $author );

		$content = serialize_blocks(
			array(
				$this->block( array( 'spectraCustomJS' => 'stealViaMedia();' ) ),
			)
		);

		$attachment_id = self::factory()->attachment->create(
			array(
				'post_author'  => $author,
				'post_content' => wp_slash( $content ),
			)
		);

		$this->assertStringNotContainsString(
			'stealViaMedia();',
			get_post_field( 'post_content', $attachment_id )
		);
	}

	/**
	 * The archive/excerpt path is the whole reason the gate sits at save time:
	 * `excerpt_remove_blocks()` renders allowed blocks, so a snippet on any
	 * published post would run for whoever is viewing the blog index.
	 */
	public function test_excerpt_generation_renders_blocks_through_the_harvest() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$post_id = self::factory()->post->create(
			array(
				'post_content' => wp_slash(
					'<!-- wp:paragraph {"spectraCustomJS":"onArchive();"} --><p>hi</p><!-- /wp:paragraph -->'
				),
				'post_excerpt' => '',
			)
		);

		// This is what an archive loop does for a post with no manual excerpt.
		excerpt_remove_blocks( get_post_field( 'post_content', $post_id ) );

		$buffer = $this->buffer()->getValue( BlockJsCompiler::instance() );
		$this->assertContains( 'onArchive();', $buffer );
	}
}
