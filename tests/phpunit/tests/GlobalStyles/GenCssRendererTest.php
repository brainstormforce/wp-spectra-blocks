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
	public function test_rem_base_renders_on_real_root_frontend_only() {
		// `remBase` = the SOURCE's document-root font-size (html/:root authored,
		// e.g. the 62.5% trick) — it must land on the real `:root` so the
		// source's rem lengths keep their meaning.
		$payload            = $this->payload();
		$payload['remBase'] = '62.5%';

		$frontend = GenCssRenderer::render( $payload, 234, false );
		$this->assertStringContainsString( ':root { font-size: 62.5%; }', $frontend );

		$editor = GenCssRenderer::render( $payload, 234, true );
		$this->assertStringNotContainsString( ':root { font-size:', $editor );
	}

	/** @return void */
	public function test_body_font_size_in_root_styles_never_reaches_the_document_root() {
		// A body-authored font-size is INHERITANCE intent. Hoisting it to
		// `:root` moved the rem base to the site's preset value and rescaled
		// every rem token ×1.25 (measured live 2026-07-13, ERA build on Astra).
		$payload                            = $this->payload();
		$payload['rootStyles']['font-size'] = 'var(--wp--preset--font-size--medium, 18px)';

		$css = GenCssRenderer::render( $payload, 234, false );
		$this->assertStringContainsString( 'font-size: var(--wp--preset--font-size--medium, 18px)', $css );
		$this->assertStringNotContainsString( ':root { font-size:', $css );
	}

	/** @return void */
	public function test_media_query_wraps_its_rules() {
		$css = GenCssRenderer::render( $this->payload(), 234, false );
		$this->assertMatchesRegularExpression(
			'/@media \(max-width: 960px\) \{\s*\[class\]\.gs-x\.gs-x \{ gap: 1rem; \}\s*body \.x a \{ display: none; \}\s*\}/',
			$css,
		);
	}

	/**
	 * Payload carrying the two document-level buckets: `rootRules` (a selector
	 * headed by `html`/`body`/`:root`, printed with no prefix) and `atRules`
	 * (`@view-transition` / `@property --name`), at the base and inside a
	 * media query.
	 *
	 * @return array<string,mixed>
	 */
	private function root_payload(): array {
		$payload              = $this->payload();
		$payload['rootRules'] = array(
			'html::before'                                    => array( 'content' => '""' ),
			'html.js:not([data-motion="none"]) [data-reveal]' => array( 'opacity' => '0' ),
			':root'                                           => array(),
		);
		$payload['atRules']   = array(
			'@view-transition'       => array( 'navigation' => 'auto' ),
			'@property --beam-angle' => array(
				'syntax'        => '"<angle>"',
				'inherits'      => 'false',
				'initial-value' => '0deg',
			),
			'@property --empty'      => array(),
		);

		$payload['mediaQuery']['(max-width: 960px)']['rootRules'] = array( 'html::before' => array( 'display' => 'none' ) );

		return $payload;
	}

	/** @return void */
	public function test_root_rules_render_verbatim_after_wrappers_on_the_frontend() {
		$css = GenCssRenderer::render( $this->root_payload(), 234, false );

		// No prefix, no selector surgery — the head is printed as stored.
		$this->assertStringContainsString( "\nhtml::before { content: \"\"; }\n", $css );
		$this->assertStringContainsString( "\nhtml.js:not([data-motion=\"none\"]) [data-reveal] { opacity: 0; }\n", $css );
		$this->assertStringNotContainsString( 'body html', $css );
		// After the wrappers, so a root rule wins by source order. strpos() is
		// guarded: a miss returns false, and `false < <int>` is true in PHP 8, so
		// an unguarded comparison passes when the needle is absent.
		$wrapper_pos = strpos( $css, 'body .wp-block-spectra-icon svg { width: 1em; }' );
		$root_pos    = strpos( $css, 'html::before { content: ""; }' );
		$this->assertIsInt( $wrapper_pos, 'the wrapper rule did not print' );
		$this->assertIsInt( $root_pos, 'the root rule did not print' );
		$this->assertGreaterThan( $wrapper_pos, $root_pos );
		// An empty body is skipped.
		$this->assertStringNotContainsString( "\n:root {", $css );
		// `mediaQuery[q].rootRules` prints inside its block, after that block's wrappers.
		$this->assertMatchesRegularExpression(
			'/@media \(max-width: 960px\) \{\s*\[class\]\.gs-x\.gs-x \{ gap: 1rem; \}\s*body \.x a \{ display: none; \}\s*html::before \{ display: none; \}\s*\}/',
			$css,
		);
	}

	/**
	 * A root rule reads the page's tokens from the ROOT — `html::before` is a
	 * pseudo-element of `html`, not a descendant of the page scope — so the custom
	 * properties (and only those) are printed once more on `html`, right before
	 * the root rules, and only when the payload carries any.
	 *
	 * @return void
	 */
	public function test_root_rules_get_the_page_tokens_on_html_before_them() {
		$payload                              = $this->root_payload();
		$payload['rootStyles']['--pattern-grid'] = 'linear-gradient(#000 1px, transparent 1px)';
		$css                                  = GenCssRenderer::render( $payload, 234, false );

		// tokens only — scope vars, custom properties, preset locks; the base declarations stay on the page scope
		$this->assertStringContainsString( "\nhtml:root { --wp--style--global--content-size: 1164px; --wp--style--global--wide-size: 1280px; --pattern-grid: linear-gradient(#000 1px, transparent 1px); --wp--preset--color--primary: #b36b2c; }\n", $css );
		$this->assertDoesNotMatchRegularExpression( '/\nhtml:root \{[^}]*font-family/', $css );
		// before the rules that read them. The needle must be a substring that
		// actually exists: the token block OPENS with the scope vars, so searching
		// for "html:root { --pattern-grid" missed, and the unguarded
		// `false < <int>` comparison passed however the parts were ordered.
		$tokens_pos = strpos( $css, "\nhtml:root { --wp--style--global--content-size" );
		$rule_pos   = strpos( $css, "\nhtml::before { content" );
		$this->assertIsInt( $tokens_pos, 'the html:root token block did not print' );
		$this->assertIsInt( $rule_pos, 'the root rule did not print' );
		$this->assertLessThan( $rule_pos, $tokens_pos );

		// no root rule → no html token block (an old payload renders byte-identically)
		$plain = $this->payload();
		$plain['rootStyles']['--pattern-grid'] = 'none';
		$this->assertDoesNotMatchRegularExpression( '/^html:root \{/m', GenCssRenderer::render( $plain, 234, false ) );
		// …and the two buckets are ADDITIVE: empty ones must not perturb one byte
		// of the sheet. The absence of `html:root` alone does not prove that — the
		// at-rule part and the root-rule part are pushed unconditionally too.
		$this->assertSame(
			GenCssRenderer::render( $plain, 234, false ),
			GenCssRenderer::render( $plain + array( 'rootRules' => array(), 'atRules' => array() ), 234, false ),
			'an empty rootRules/atRules bucket must render byte-identically'
		);
		// a root rule only under @media still gets the block
		$plain['mediaQuery'] = array( '(max-width: 960px)' => array( 'rootRules' => array( 'html::before' => array( 'inset' => '0' ) ) ) );
		$this->assertMatchesRegularExpression( '/^html:root \{ [^}]*--pattern-grid: none;/m', GenCssRenderer::render( $plain, 234, false ) );
		// never in the editor
		$this->assertDoesNotMatchRegularExpression( '/^html:root \{/m', GenCssRenderer::render( $payload, 234, true ) );
	}

	/** @return void */
	public function test_at_rules_render_from_declarations_after_imports_on_the_frontend() {
		$css = GenCssRenderer::render( $this->root_payload(), 234, false );

		$this->assertStringStartsWith(
			"@import url(\"https://fonts.googleapis.com/css2?family=Lato\");\n@view-transition { navigation: auto; }\n@property --beam-angle { syntax: \"<angle>\"; inherits: false; initial-value: 0deg; }\n",
			$css,
		);
		// An empty body is skipped.
		$this->assertStringNotContainsString( '@property --empty', $css );
	}

	/** @return void */
	public function test_editor_prints_neither_root_rules_nor_at_rules() {
		$css = GenCssRenderer::render( $this->root_payload(), 234, true );

		$this->assertStringNotContainsString( 'html::before', $css );
		$this->assertStringNotContainsString( '[data-reveal]', $css );
		$this->assertStringNotContainsString( '@view-transition', $css );
		$this->assertStringNotContainsString( '@property', $css );
		// Nothing unscoped reaches wp-admin: no rule headed by a bare `html`,
		// `body` or `:root` (the editor root is `body.editor-styles-wrapper`).
		$this->assertDoesNotMatchRegularExpression( '/^(?:html|:root)\b/m', $css );
		$this->assertDoesNotMatchRegularExpression( '/^body(?:\s|\{)/m', $css );
		// The rest of the sheet is untouched by the two buckets.
		$this->assertStringContainsString( '.editor-styles-wrapper .wp-block-spectra-icon svg { width: 1em; }', $css );
		$this->assertStringContainsString( '.editor-styles-wrapper .x a { display: none; }', $css );
	}
}
