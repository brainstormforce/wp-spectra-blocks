<?php
/**
 * The imported-page layout-class strip must remove ONLY the flow channel.
 *
 * `spectra/container` takes its display/gap/direction from core layout support
 * (`supports.layout.default.type = flex` in block.json; the block ships no
 * `display:flex` of its own), so `is-layout-*` / `wp-container-*` on a FLEX or
 * GRID container ARE that container's layout. Stripping them there collapses a
 * row into a stack and drops the authored gap — while the injection this strip
 * exists to kill is core's flow rule
 * (`:where(.is-layout-flow) > * { margin-block-start: … }`), which is keyed on
 * the class and cannot be prevented through block attributes.
 *
 * Both halves are asserted. Only checking that a flow container gets stripped
 * would pass just as well with the old strip-everything behaviour — the
 * flex/grid/constrained survival is the point.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests;

use SpectraBlocks\AssetLoader;
use WP_UnitTestCase;

/**
 * AssetLoaderLayoutStripTest test case.
 *
 * @since x.x.x
 */
class AssetLoaderLayoutStripTest extends WP_UnitTestCase {

	/**
	 * A rendered container carrying the classes core's layout support adds.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const CONTAINER_HTML = '<div class="wp-block-spectra-container gs-hero is-layout-flow wp-block-spectra-container-is-layout-flow wp-container-core-group-is-layout-1"><p>x</p></div>';

	/**
	 * Put a page on the singular frontend query the filter gates on.
	 *
	 * @since x.x.x
	 *
	 * @param bool $imported Whether to set the importer marker.
	 * @return int Post ID.
	 */
	private function go_to_page( bool $imported ): int {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		if ( $imported ) {
			update_post_meta( $post_id, AssetLoader::IMPORTED_MARKER_META_KEY, true );
		}
		$this->go_to( get_permalink( $post_id ) );

		return $post_id;
	}

	/**
	 * Run the filter the way `render_block_spectra/container` would.
	 *
	 * @since x.x.x
	 *
	 * @param string $layout_type Value for `attrs.layout.type` ('' = attribute absent).
	 * @return string Filtered markup.
	 */
	private function render( string $layout_type, string $class_name = '' ): string {
		$attrs = '' === $layout_type ? array() : array( 'layout' => array( 'type' => $layout_type ) );
		if ( '' !== $class_name ) {
			$attrs['className'] = $class_name;
		}
		$block = array( 'blockName' => 'spectra/container', 'attrs' => $attrs );

		// Through the REAL hook, not the method: the filter is registered with
		// `, 2` so it receives $block, and calling the method directly would
		// keep passing if that arity were ever dropped — $block would default to
		// array(), the gate would read every container as flow, and the pre-fix
		// strip-everything behaviour would come back green.
		return apply_filters( 'render_block_spectra/container', self::CONTAINER_HTML, $block );
	}

	/**
	 * The importer emits every container with `layout: {type:'default'}`, so
	 * flow is exactly the imported surface the blockGap injection rides.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_flow_container_on_an_imported_page_loses_its_layout_classes(): void {
		$this->go_to_page( true );

		foreach ( array( 'default', 'flow' ) as $layout_type ) {
			$out = $this->render( $layout_type );

			$this->assertStringNotContainsString( 'is-layout-flow', $out, "layout '{$layout_type}': flow class must go" );
			$this->assertStringNotContainsString( 'wp-container-', $out, "layout '{$layout_type}': generated container class must go" );
			$this->assertStringNotContainsString(
				'wp-block-spectra-container-is-layout-flow',
				$out,
				"layout '{$layout_type}': block-scoped layout class must go"
			);
			// Everything that is not core's layout decoration survives.
			$this->assertStringContainsString( 'wp-block-spectra-container', $out );
			$this->assertStringContainsString( 'gs-hero', $out );
			$this->assertStringContainsString( '<p>x</p>', $out );
		}
	}

	/**
	 * A flex/grid/constrained container keeps its layout classes — they carry
	 * its display, gap and direction (or its content-width and alignments).
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_non_flow_containers_keep_their_layout_classes(): void {
		$this->go_to_page( true );

		foreach ( array( 'flex', 'grid', 'constrained' ) as $layout_type ) {
			$this->assertSame(
				self::CONTAINER_HTML,
				$this->render( $layout_type ),
				"a {$layout_type} container's layout classes ARE its layout — never stripped"
			);
		}
	}

	/**
	 * An ABSENT layout attribute is not flow. Core resolves it to the block
	 * type's `supports.layout.default`, which for spectra/container is FLEX, so
	 * such a container renders `is-layout-flex` + `wp-container-*` and stripping
	 * them deletes its layout. Reading absent as flow hit every container that
	 * never wrote the attribute.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_absent_layout_attribute_resolves_to_the_block_default(): void {
		$this->go_to_page( true );

		$registered = \WP_Block_Type_Registry::get_instance()->get_registered( 'spectra/container' );
		$this->assertNotNull( $registered, 'spectra/container must be registered for this to mean anything' );
		$this->assertSame(
			'flex',
			$registered->supports['layout']['default']['type'] ?? null,
			'this test only holds while the block default is flex'
		);

		$this->assertSame(
			self::CONTAINER_HTML,
			$this->render( '' ),
			'no layout attribute = the block default (flex) = never stripped'
		);
	}

	/**
	 * The strip must be reachable through the hook with the block payload —
	 * the whole gate is inert without it.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_filter_is_registered_with_the_block_argument(): void {
		$this->assertNotFalse(
			has_filter( 'render_block_spectra/container' ),
			'the strip must be hooked, or nothing below it runs'
		);

		$this->go_to_page( true );
		// A flow container still gets stripped through the hook — proves the
		// registration passes $block AND that the gate reads it.
		$this->assertStringNotContainsString(
			'is-layout-flow',
			$this->render( 'default' ),
			'flow containers must still be stripped through the real hook'
		);
	}

	/**
	 * A page the importer never wrote is untouched whatever its layout.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_non_imported_page_is_untouched(): void {
		$this->go_to_page( false );

		$this->assertSame( self::CONTAINER_HTML, $this->render( 'default' ) );
		$this->assertSame( self::CONTAINER_HTML, $this->render( 'flex' ) );
	}

	/**
	 * The A32 case: a converter-built section the VIBE EDITOR inserted into an
	 * editor-created draft. The page carries no importer marker, so the page gate
	 * never fires — but the block carries the converter's marker, which travels
	 * WITH it. Without this, core's flow blockGap re-margined the section
	 * (measured 2026-08-21: 17.81px on a flex button row's second button).
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_marked_flow_container_is_stripped_on_a_non_imported_page(): void {
		$this->go_to_page( false );

		foreach ( array( 'default', 'flow' ) as $layout_type ) {
			$out = $this->render( $layout_type, 'spectra-no-block-gap' );
			// Every layout class core injected is gone...
			$this->assertStringNotContainsString( 'is-layout-flow', $out, $layout_type );
			$this->assertStringNotContainsString( 'wp-container-core-group-is-layout-1', $out, $layout_type );
			// ...and nothing else was touched. The marker itself rides in
			// attrs.className, not the rendered markup, so it is not asserted here.
			$this->assertStringContainsString( 'wp-block-spectra-container', $out, $layout_type );
			$this->assertStringContainsString( 'gs-hero', $out, $layout_type );
		}
	}

	/**
	 * The marker does not widen the LAYOUT gate. Stripping the classes off a flex
	 * container would delete its layout outright — the block takes display and
	 * gap from core layout support, so a row would collapse to a stack.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_marked_flex_container_keeps_its_layout_classes(): void {
		$this->go_to_page( false );

		$this->assertSame( self::CONTAINER_HTML, $this->render( 'flex', 'spectra-no-block-gap' ) );
	}

	/**
	 * Whitespace-delimited token match: a longer class that merely starts with the
	 * marker's text is NOT the marker.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_a_class_that_only_prefixes_the_marker_does_not_qualify(): void {
		$this->go_to_page( false );

		$this->assertSame( self::CONTAINER_HTML, $this->render( 'flow', 'spectra-no-block-gap-x' ) );
		// ...and the marker still qualifies beside other classes.
		$this->assertStringNotContainsString(
			'is-layout-flow',
			$this->render( 'flow', 'gs-abc123 spectra-no-block-gap wp-block' )
		);
	}
}
