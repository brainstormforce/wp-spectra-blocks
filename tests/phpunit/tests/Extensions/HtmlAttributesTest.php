<?php
/**
 * Tests for HtmlAttributes — custom HTML attribute rendering and capability gating.
 *
 * @package Spectra\Tests\Extensions
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use SpectraBlocks\Extensions\HtmlAttributes;
use WP_UnitTestCase;

/**
 * HtmlAttributesTest test case.
 *
 * @since 1.0.15
 */
class HtmlAttributesTest extends WP_UnitTestCase {

	/**
	 * A parsed-block array shaped like parse_blocks() output.
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 * @return array<string, mixed>
	 */
	private function block( array $attrs ): array {
		return array(
			'blockName'    => 'spectra/container',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	/**
	 * One { attribute, value } entry, the shape the editor stores.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $value     Attribute value.
	 * @return array<string, string>
	 */
	private function attr( string $attribute, string $value ): array {
		return array(
			'attribute' => $attribute,
			'value'     => $value,
		);
	}

	/**
	 * Render a container block through the extension's render_block filter.
	 *
	 * @param array<int, array<string, string>> $html_attributes Stored htmlAttributes.
	 * @param string                            $content         Block HTML.
	 * @return string Filtered HTML.
	 */
	private function render( array $html_attributes, string $content = '<div class="wp-block-spectra-container"></div>' ): string {
		return HtmlAttributes::instance()->apply_html_attributes(
			$content,
			$this->block( array( 'htmlAttributes' => $html_attributes ) )
		);
	}

	/**
	 * A safe, author-supplied attribute lands on the wrapper.
	 */
	public function test_applies_valid_attribute() {
		$html = $this->render( array( $this->attr( 'role', 'region' ) ) );

		$this->assertStringContainsString( 'role="region"', $html );
	}

	/**
	 * Event-handler attributes are stripped at render.
	 */
	public function test_strips_event_handler_attributes() {
		$html = $this->render( array( $this->attr( 'onclick', 'alert(1)' ) ) );

		$this->assertStringNotContainsString( 'onclick', $html );
	}

	/**
	 * class, id, style and srcdoc are never applied here.
	 */
	public function test_blocks_reserved_and_srcdoc_attributes() {
		$html = $this->render(
			array(
				$this->attr( 'style', 'color:red' ),
				$this->attr( 'srcdoc', '<script>alert(1)</script>' ),
			)
		);

		$this->assertStringNotContainsString( 'srcdoc', $html );
		$this->assertStringNotContainsString( 'color:red', $html );
	}

	/**
	 * javascript: URLs are stripped from URL-type attributes.
	 */
	public function test_strips_javascript_url() {
		$html = $this->render(
			array( $this->attr( 'cite', 'javascript:alert(1)' ) ),
			'<blockquote class="wp-block-spectra-container"></blockquote>'
		);

		$this->assertStringNotContainsString( 'javascript:', $html );
	}

	/**
	 * target="_blank" auto-appends rel="noopener noreferrer".
	 */
	public function test_auto_appends_rel_on_blank_target() {
		$html = $this->render(
			array( $this->attr( 'target', '_blank' ) ),
			'<a class="wp-block-spectra-container"></a>'
		);

		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringContainsString( 'noopener', $html );
		$this->assertStringContainsString( 'noreferrer', $html );
	}

	/**
	 * An incomplete custom row — a name typed but no value yet — is not rendered.
	 */
	public function test_skips_attribute_with_empty_value() {
		$html = $this->render( array( $this->attr( 'data-foo', '' ) ) );

		$this->assertStringNotContainsString( 'data-foo', $html );
	}

	/**
	 * A value of only whitespace counts as empty and is not rendered.
	 */
	public function test_skips_attribute_with_whitespace_value() {
		$html = $this->render( array( $this->attr( 'data-foo', '   ' ) ) );

		$this->assertStringNotContainsString( 'data-foo', $html );
	}

	/**
	 * A literal "0" is a real value and is kept (e.g. tabindex="0").
	 */
	public function test_keeps_zero_value() {
		$html = $this->render( array( $this->attr( 'tabindex', '0' ) ) );

		$this->assertStringContainsString( 'tabindex="0"', $html );
	}

	/**
	 * A completed row alongside an incomplete one still renders; only the empty
	 * one is dropped.
	 */
	public function test_keeps_completed_row_beside_empty_one() {
		$html = $this->render(
			array(
				$this->attr( 'data-empty', '' ),
				$this->attr( 'data-set', 'yes' ),
			)
		);

		$this->assertStringContainsString( 'data-set="yes"', $html );
		$this->assertStringNotContainsString( 'data-empty', $html );
	}

	/**
	 * The legacy object storage format is still honoured.
	 */
	public function test_supports_legacy_object_format() {
		$html = HtmlAttributes::instance()->apply_html_attributes(
			'<div class="wp-block-spectra-container"></div>',
			$this->block( array( 'htmlAttributes' => array( 'aria-label' => 'nav' ) ) )
		);

		$this->assertStringContainsString( 'aria-label="nav"', $html );
	}

	/**
	 * A user without unfiltered_html cannot INTRODUCE htmlAttributes on save.
	 */
	public function test_strips_html_attributes_from_untrusted_user() {
		$user = self::factory()->user->create( array( 'role' => 'contributor' ) );
		wp_set_current_user( $user );

		$content = serialize_blocks( array( $this->block( array( 'htmlAttributes' => array( $this->attr( 'role', 'region' ) ) ) ) ) );
		$data    = HtmlAttributes::instance()->strip_untrusted_html_attributes(
			array( 'post_content' => wp_slash( $content ) ),
			array()
		);

		$this->assertStringNotContainsString( 'htmlAttributes', wp_unslash( $data['post_content'] ) );
	}

	/**
	 * A user with unfiltered_html keeps authored htmlAttributes on save.
	 */
	public function test_keeps_html_attributes_for_capable_user() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user );
		}
		wp_set_current_user( $user );

		$content = serialize_blocks( array( $this->block( array( 'htmlAttributes' => array( $this->attr( 'role', 'region' ) ) ) ) ) );
		$data    = HtmlAttributes::instance()->strip_untrusted_html_attributes(
			array( 'post_content' => wp_slash( $content ) ),
			array()
		);

		$this->assertStringContainsString( 'htmlAttributes', wp_unslash( $data['post_content'] ) );
	}

	/**
	 * Legitimate attributes land while nothing executable — an event handler, a
	 * second attribute smuggled inside a name (`data-a="" onmouseover="…" data-b`),
	 * or `srcdoc` — reaches the wrapper. Names are lowercased on the way in.
	 *
	 * @return void
	 */
	public function test_never_injects_executable_or_second_attribute(): void {
		$html = $this->render(
			array(
				'data-tab'                                => 'pricing',
				'role'                                    => 'tab',
				'aria-label'                              => 'Pricing',
				'Data-Upper'                              => 'kept-lowercased',
				'href'                                    => 'https://example.test/pricing',
				'data-a="" onmouseover="alert(1)" data-b' => '1',
				' onmouseover'                            => 'alert(1)',
				'onclick'                                 => 'alert(1)',
				'srcdoc'                                  => '<script>alert(1)</script>',
			)
		);

		// The attributes a design may legitimately author still land.
		$this->assertStringContainsString( 'data-tab="pricing"', $html );
		$this->assertStringContainsString( 'role="tab"', $html );
		$this->assertStringContainsString( 'aria-label="Pricing"', $html );
		$this->assertStringContainsString( 'data-upper="kept-lowercased"', $html );
		$this->assertStringContainsString( 'href="https://example.test/pricing"', $html );

		// Nothing that can execute may reach the wrapper.
		$this->assertStringNotContainsString( 'onmouseover', $html );
		$this->assertStringNotContainsString( 'onclick', $html );
		$this->assertStringNotContainsString( 'srcdoc', $html );
		$this->assertStringNotContainsString( 'data-a', $html );
	}

	/**
	 * A script-scheme URL in a URL-valued attribute is stripped rather than
	 * escaped — `esc_attr()` alone never blocks `javascript:` / `vbscript:`.
	 *
	 * @dataProvider provide_script_urls
	 *
	 * @param string $url The authored URL.
	 * @return void
	 */
	public function test_strips_script_schemes_from_url_attributes( string $url ): void {
		$html = $this->render( array( $this->attr( 'href', $url ) ) );

		$this->assertStringNotContainsString( 'javascript', strtolower( $html ) );
		$this->assertStringNotContainsString( 'vbscript', strtolower( $html ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public function provide_script_urls(): array {
		return array(
			'plain'          => array( 'javascript:alert(1)' ),
			'spaced'         => array( 'javascript :alert(1)' ),
			'mixed case'     => array( 'JaVaScRiPt:alert(1)' ),
			'vbscript'       => array( 'vbscript:msgbox(1)' ),
			'leading spaces' => array( '   javascript:alert(1)' ),
		);
	}
}
