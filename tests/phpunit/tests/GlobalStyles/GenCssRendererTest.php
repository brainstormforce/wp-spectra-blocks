<?php
/**
 * Tests for the per-page imported-CSS renderer (schema v1 payload → CSS).
 *
 * Pins the context-aware render contract (CHG-002 Phase 2b):
 *   - frontend: root `body`; classes `[class].{class}.{class}` (compound on `[class]`);
 *     other selectors `body <…>`;
 *   - editor:   root `body.editor-styles-wrapper, div.editor-styles-wrapper`;
 *     classes/selectors descend from `.editor-styles-wrapper`;
 *   - no page id in any selector (per-post enqueue isolates); CLEAN declarations
 *     (no `!important`).
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\GenCssRenderer;
use WP_UnitTestCase;

/**
 * GenCssRendererTest test case.
 *
 * @since x.x.x
 */
class GenCssRendererTest extends WP_UnitTestCase {

	/**
	 * @return array<string,mixed>
	 */
	private function payload(): array {
		return array(
			'v'             => '1',
			'imports'       => array( 'https://fonts.googleapis.com/css2?family=Lato' ),
			'scopeVars'     => array(
				'--wp--style--global--content-size' => '1164px',
				'--wp--style--global--wide-size'    => '1280px',
			),
			'rootStyles'    => array(
				'font-family' => 'DM Sans',
				'background'  => '#fbf6ec',
			),
			'presetLock'    => array( '--wp--preset--color--primary' => '#b36b2c' ),
			'classes'       => array(
				'gs-link'       => array(
					'default' => array( 'color' => 'var(--heading)' ),
					'hover'   => array( 'color' => '#b36b2c' ),
				),
				'tdrx-faq-item' => array( '[open]' => array( 'background' => '#fff' ) ),
			),
			'wrapperStyles' => array(
				'.wp-block-spectra-icon svg' => array( 'width' => '1em' ),
			),
			'mediaQuery'    => array(
				'(max-width: 960px)' => array(
					'classes'       => array( 'gs-x' => array( 'default' => array( 'gap' => '1rem' ) ) ),
					'wrapperStyles' => array( '.x a' => array( 'display' => 'none' ) ),
				),
			),
		);
	}

	/** @return void */
	public function test_empty_payload_renders_nothing() {
		$this->assertSame( '', GenCssRenderer::render( array( 'v' => '1' ), 7 ) );
		$this->assertSame( '', GenCssRenderer::render( $this->payload(), 0 ) );
	}

	/** @return void */
	public function test_no_important_and_no_page_id() {
		// Per-page CSS has never used !important, and isolation is by enqueue,
		// so no page id belongs in any selector.
		foreach ( array( false, true ) as $is_editor ) {
			$css = GenCssRenderer::render( $this->payload(), 234, $is_editor );
			$this->assertStringNotContainsString( '!important', $css );
			$this->assertStringNotContainsString( 'page-id', $css );
		}
	}

	/** @return void */
	public function test_imports_render_first() {
		$css = GenCssRenderer::render( $this->payload(), 234 );
		$this->assertStringStartsWith( '@import url("https://fonts.googleapis.com/css2?family=Lato");', $css );
	}

	/** @return void */
	public function test_frontend_scope() {
		$css = GenCssRenderer::render( $this->payload(), 234, false );

		// Root rules on the bare body, with the real content-size.
		$this->assertStringContainsString( 'body { --wp--style--global--content-size: 1164px;', $css );
		$this->assertStringContainsString( 'body { font-family: DM Sans; background: #fbf6ec; }', $css );
		$this->assertStringContainsString( 'body { --wp--preset--color--primary: #b36b2c; }', $css );
		// Classes are COMPOUND on the `[class]` attribute (matches any element
		// carrying the class), with the class token repeated for the (0,3,0) lift.
		$this->assertStringContainsString( '[class].gs-link.gs-link { color: var(--heading); }', $css );
		$this->assertStringContainsString( '[class].gs-link.gs-link:hover { color: #b36b2c; }', $css );
		$this->assertStringContainsString( '[class].tdrx-faq-item.tdrx-faq-item[open] { background: #fff; }', $css );
		// Wrapper selectors descend from body (NOT specificity-lifted).
		$this->assertStringContainsString( 'body .wp-block-spectra-icon svg { width: 1em; }', $css );
		// Media query.
		$this->assertStringContainsString( '[class].gs-x.gs-x { gap: 1rem; }', $css );
		$this->assertStringContainsString( 'body .x a { display: none; }', $css );
	}

	/** @return void */
	public function test_editor_scope() {
		$css = GenCssRenderer::render( $this->payload(), 234, true );

		// Root + body styling on the editor canvas, content-size = WIDE.
		$this->assertStringContainsString(
			'body.editor-styles-wrapper, div.editor-styles-wrapper { --wp--style--global--content-size: 1280px;',
			$css,
		);
		$this->assertStringContainsString(
			'body.editor-styles-wrapper, div.editor-styles-wrapper { font-family: DM Sans; background: #fbf6ec; }',
			$css,
		);
		// Classes + wrappers descend from the editor wrapper; class token repeated.
		$this->assertStringContainsString( '.editor-styles-wrapper .gs-link.gs-link { color: var(--heading); }', $css );
		$this->assertStringContainsString( '.editor-styles-wrapper .wp-block-spectra-icon svg { width: 1em; }', $css );
		// No data-spectra-id selectors in the editor.
		$this->assertStringNotContainsString( '[data-spectra-id]', $css );
	}

	/** @return void */
	public function test_media_query_wraps_its_rules() {
		$css = GenCssRenderer::render( $this->payload(), 234, false );
		$this->assertMatchesRegularExpression(
			'/@media \(max-width: 960px\) \{\s*\[class\]\.gs-x\.gs-x \{ gap: 1rem; \}\s*body \.x a \{ display: none; \}\s*\}/',
			$css,
		);
	}
}
