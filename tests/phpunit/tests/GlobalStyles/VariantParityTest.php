<?php
/**
 * Tailwind v4 variant-parity tests for the JIT class compiler.
 *
 * Exercises the variant families added in the parity pass:
 *   - `dark:` (class-based)
 *   - `group-*` / `peer-*` (unnamed + named)
 *   - Structural pseudo-classes (`first:`, `odd:`, `empty:`, `only-of-type:`…)
 *   - Form-state pseudo-classes (`required:`, `valid:`, `read-only:`,
 *     `placeholder-shown:`, `autofill:`…)
 *   - `target:`, `open:`, `details-content:`
 *   - `motion-safe:`, `motion-reduce:`, `print:`, `forced-colors:`
 *   - `rtl:`, `ltr:`
 *   - Descendant variants `*:`, `**:`
 *   - Arbitrary-selector variants `[&…]:`
 *   - `has-[…]:`, `not-[…]:`, `supports-[…]:` (bracket + bare forms)
 *   - `data-[…]:` + bare `data-open:` / `data-selected:` / `data-checked:`
 *   - Extended aria-* bare forms + `aria-[…]:` bracket form
 *   - `nth-[…]:`, `nth-last-[…]:`, `nth-of-type-[…]:`, `nth-last-of-type-[…]:`
 *
 * Each test asserts a substring that pins the emitted shape (selector
 * fragment or at-rule wrapper) without over-specifying the whole rule.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * VariantParityTest test case.
 *
 * @since x.x.x
 */
class VariantParityTest extends WP_UnitTestCase {

	/**
	 * Reset memoization between tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		JitCompiler::reset_memo();
	}

	// ---------------------------------------------------------------------
	// dark:
	// ---------------------------------------------------------------------

	/**
	 * `dark:` prepends the `.dark` ancestor to the class selector.
	 *
	 * @return void
	 */
	public function test_dark_variant_wraps_with_dark_ancestor(): void {
		$css = JitCompiler::compile( array( 'dark:bg-[#0b1220]' ) );

		$this->assertStringContainsString( '.dark ', $css );
		$this->assertStringContainsString( 'background-color: #0b1220;', $css );
	}

	// ---------------------------------------------------------------------
	// group-* / peer-*
	// ---------------------------------------------------------------------

	/**
	 * `group-hover:` emits a `.group:hover` ancestor prefix.
	 *
	 * @return void
	 */
	public function test_group_hover_prepends_group_ancestor(): void {
		$css = JitCompiler::compile( array( 'group-hover:text-[#ff0000]' ) );

		$this->assertStringContainsString( '.group:hover', $css );
		$this->assertStringContainsString( 'color: #ff0000;', $css );
	}

	/**
	 * Named groups escape the `/` in the class selector.
	 *
	 * @return void
	 */
	public function test_named_group_hover_escapes_slash(): void {
		$css = JitCompiler::compile( array( 'group-hover/card:text-[#00ff00]' ) );

		$this->assertStringContainsString( '.group\\/card:hover', $css );
	}

	/**
	 * `peer-checked:` emits the `~` sibling combinator.
	 *
	 * @return void
	 */
	public function test_peer_checked_uses_sibling_combinator(): void {
		$css = JitCompiler::compile( array( 'peer-checked:bg-[#112233]' ) );

		$this->assertStringContainsString( '.peer:checked', $css );
		$this->assertStringContainsString( '~', $css );
	}

	// ---------------------------------------------------------------------
	// Structural pseudos
	// ---------------------------------------------------------------------

	/**
	 * Structural pseudos chain onto the class selector.
	 *
	 * @return void
	 */
	public function test_first_variant_chains_first_child(): void {
		$css = JitCompiler::compile( array( 'first:mt-[0px]' ) );
		$this->assertStringContainsString( ':first-child', $css );
	}

	/**
	 * `odd:` maps to `:nth-child(odd)`.
	 *
	 * @return void
	 */
	public function test_odd_variant_maps_to_nth_child_odd(): void {
		$css = JitCompiler::compile( array( 'odd:bg-[#eeeeee]' ) );
		$this->assertStringContainsString( ':nth-child(odd)', $css );
	}

	/**
	 * `empty:` pseudo-class pass-through.
	 *
	 * @return void
	 */
	public function test_empty_variant_chains_empty_pseudo(): void {
		$css = JitCompiler::compile( array( 'empty:p-[0px]' ) );
		$this->assertStringContainsString( ':empty', $css );
	}

	// ---------------------------------------------------------------------
	// Form-state pseudos
	// ---------------------------------------------------------------------

	/**
	 * `required:` pseudo-class pass-through.
	 *
	 * @return void
	 */
	public function test_required_variant_chains_required(): void {
		$css = JitCompiler::compile( array( 'required:text-[#ff0000]' ) );
		$this->assertStringContainsString( ':required', $css );
	}

	/**
	 * `placeholder-shown:` pseudo-class.
	 *
	 * @return void
	 */
	public function test_placeholder_shown_variant(): void {
		$css = JitCompiler::compile( array( 'placeholder-shown:text-[#999]' ) );
		$this->assertStringContainsString( ':placeholder-shown', $css );
	}

	/**
	 * `autofill:` emits the comma-union selector covering both spec + webkit.
	 *
	 * @return void
	 */
	public function test_autofill_variant_includes_webkit_pseudo(): void {
		$css = JitCompiler::compile( array( 'autofill:bg-[#fffacd]' ) );
		$this->assertStringContainsString( ':autofill', $css );
		$this->assertStringContainsString( ':-webkit-autofill', $css );
	}

	// ---------------------------------------------------------------------
	// Interaction / structural
	// ---------------------------------------------------------------------

	/**
	 * `target:` pseudo-class.
	 *
	 * @return void
	 */
	public function test_target_variant_chains_target(): void {
		$css = JitCompiler::compile( array( 'target:bg-[#ffff00]' ) );
		$this->assertStringContainsString( ':target', $css );
	}

	/**
	 * `open:` matches both the `[open]` attribute and the `:open` pseudo.
	 *
	 * @return void
	 */
	public function test_open_variant_includes_attribute_and_pseudo(): void {
		$css = JitCompiler::compile( array( 'open:text-[#333333]' ) );
		$this->assertStringContainsString( '[open]', $css );
		$this->assertStringContainsString( ':open', $css );
	}

	/**
	 * `details-content:` maps to `::details-content`.
	 *
	 * @return void
	 */
	public function test_details_content_variant_is_pseudo_element(): void {
		$css = JitCompiler::compile( array( 'details-content:bg-[#f0f0f0]' ) );
		$this->assertStringContainsString( '::details-content', $css );
	}

	// ---------------------------------------------------------------------
	// motion / print / forced-colors
	// ---------------------------------------------------------------------

	/**
	 * `motion-safe:` emits the corresponding @media wrapper.
	 *
	 * @return void
	 */
	public function test_motion_safe_wraps_media_prefers_reduced_motion_no_preference(): void {
		$css = JitCompiler::compile( array( 'motion-safe:p-[10px]' ) );
		$this->assertStringContainsString( '@media (prefers-reduced-motion: no-preference)', $css );
	}

	/**
	 * `motion-reduce:` emits the reduced-motion @media wrapper.
	 *
	 * @return void
	 */
	public function test_motion_reduce_wraps_media_reduce(): void {
		$css = JitCompiler::compile( array( 'motion-reduce:p-[10px]' ) );
		$this->assertStringContainsString( '@media (prefers-reduced-motion: reduce)', $css );
	}

	/**
	 * `print:` emits the `@media print` wrapper.
	 *
	 * @return void
	 */
	public function test_print_variant_wraps_media_print(): void {
		$css = JitCompiler::compile( array( 'print:text-[#000000]' ) );
		$this->assertStringContainsString( '@media print', $css );
	}

	/**
	 * `forced-colors:` emits the `(forced-colors: active)` wrapper.
	 *
	 * @return void
	 */
	public function test_forced_colors_variant_wraps_media_forced_colors(): void {
		$css = JitCompiler::compile( array( 'forced-colors:text-[#000000]' ) );
		$this->assertStringContainsString( '@media (forced-colors: active)', $css );
	}

	// ---------------------------------------------------------------------
	// Direction
	// ---------------------------------------------------------------------

	/**
	 * `rtl:` prepends `[dir="rtl"]` ancestor selector.
	 *
	 * @return void
	 */
	public function test_rtl_variant_wraps_with_rtl_ancestor(): void {
		$css = JitCompiler::compile( array( 'rtl:ml-[10px]' ) );
		$this->assertStringContainsString( '[dir="rtl"]', $css );
	}

	/**
	 * `ltr:` prepends `[dir="ltr"]` ancestor selector.
	 *
	 * @return void
	 */
	public function test_ltr_variant_wraps_with_ltr_ancestor(): void {
		$css = JitCompiler::compile( array( 'ltr:mr-[10px]' ) );
		$this->assertStringContainsString( '[dir="ltr"]', $css );
	}

	// ---------------------------------------------------------------------
	// Descendant variants
	// ---------------------------------------------------------------------

	/**
	 * `*:` emits a direct-child combinator.
	 *
	 * @return void
	 */
	public function test_direct_child_variant_emits_greater_than_star(): void {
		$css = JitCompiler::compile( array( '*:p-[4px]' ) );
		$this->assertStringContainsString( '> *', $css );
	}

	/**
	 * `**:` emits a descendant combinator.
	 *
	 * @return void
	 */
	public function test_all_descendants_variant_emits_space_combinator(): void {
		$css = JitCompiler::compile( array( '**:p-[2px]' ) );
		// Bucket class selector immediately followed by whitespace + `*` +
		// ` {`. We pin the combinator + universal selector sequence.
		$this->assertMatchesRegularExpression( '/ \*\s*\{/', $css );
	}

	// ---------------------------------------------------------------------
	// Arbitrary-selector variants [&…]:
	// ---------------------------------------------------------------------

	/**
	 * `[&>*]:` substitutes the class selector for `&`.
	 *
	 * @return void
	 */
	public function test_arbitrary_selector_substitutes_ampersand(): void {
		$css = JitCompiler::compile( array( '[&>*]:mt-[8px]' ) );
		$this->assertStringContainsString( '>*', str_replace( ' ', '', $css ) );
		$this->assertStringContainsString( 'margin-top: 8px;', $css );
	}

	/**
	 * Arbitrary-selector variants reject rule-terminator punctuation.
	 *
	 * @return void
	 */
	public function test_arbitrary_selector_rejects_braces(): void {
		$css = JitCompiler::compile( array( '[&{color:red}]:text-[#000]' ) );
		$this->assertSame( '', $css );
	}

	// ---------------------------------------------------------------------
	// has-* / not-* / supports-*
	// ---------------------------------------------------------------------

	/**
	 * `has-[ul]:` emits `:has(ul)`.
	 *
	 * @return void
	 */
	public function test_has_bracket_variant_emits_has_pseudo(): void {
		$css = JitCompiler::compile( array( 'has-[ul]:p-[8px]' ) );
		$this->assertStringContainsString( ':has(ul)', $css );
	}

	/**
	 * `not-hover:` re-uses STATE_VARIANTS to emit `:not(:hover)`.
	 *
	 * @return void
	 */
	public function test_not_hover_bare_form_wraps_pseudo(): void {
		$css = JitCompiler::compile( array( 'not-hover:opacity-[0.5]' ) );
		$this->assertStringContainsString( ':not(:hover)', $css );
	}

	/**
	 * `supports-[display:grid]:` emits `@supports (display: grid)` wrapper.
	 *
	 * @return void
	 */
	public function test_supports_bracket_wraps_supports_at_rule(): void {
		$css     = JitCompiler::compile( array( 'supports-[display:grid]:grid-cols-[3]' ) );
		$compact = str_replace( ' ', '', $css );
		$this->assertStringContainsString( '@supports(display:grid)', $compact );
	}

	// ---------------------------------------------------------------------
	// data-*
	// ---------------------------------------------------------------------

	/**
	 * Bare `data-open:` uses the default map.
	 *
	 * @return void
	 */
	public function test_data_open_bare_form_emits_attribute_selector(): void {
		$css = JitCompiler::compile( array( 'data-open:bg-[#cccccc]' ) );
		$this->assertStringContainsString( '[data-open="true"]', $css );
	}

	/**
	 * `data-[size=lg]:` emits a concrete attribute selector.
	 *
	 * @return void
	 */
	public function test_data_bracket_keyvalue_emits_attribute_selector(): void {
		$css = JitCompiler::compile( array( 'data-[size=lg]:text-[#222]' ) );
		$this->assertStringContainsString( '[data-size="lg"]', $css );
	}

	/**
	 * `spectra_gs_jit_data_variants` filter can extend the bare-form map.
	 *
	 * @return void
	 */
	public function test_data_variants_filter_extensibility(): void {
		$filter = static function ( $map ) {
			$map['custom'] = '[data-custom="yes"]';
			return $map;
		};
		add_filter( 'spectra_gs_jit_data_variants', $filter );
		JitCompiler::reset_memo();

		$css = JitCompiler::compile( array( 'data-custom:text-[#abcdef]' ) );

		remove_filter( 'spectra_gs_jit_data_variants', $filter );

		$this->assertStringContainsString( '[data-custom="yes"]', $css );
	}

	// ---------------------------------------------------------------------
	// aria-*
	// ---------------------------------------------------------------------

	/**
	 * Extended aria-* bare form (beyond the original six).
	 *
	 * @return void
	 */
	public function test_aria_hidden_bare_form_emits_attribute_selector(): void {
		$css = JitCompiler::compile( array( 'aria-hidden:opacity-[0]' ) );
		$this->assertStringContainsString( '[aria-hidden="true"]', $css );
	}

	/**
	 * `aria-[key=value]:` bracket form.
	 *
	 * @return void
	 */
	public function test_aria_bracket_keyvalue_emits_attribute_selector(): void {
		$css = JitCompiler::compile( array( 'aria-[sort=ascending]:text-[#000]' ) );
		$this->assertStringContainsString( '[aria-sort="ascending"]', $css );
	}

	// ---------------------------------------------------------------------
	// nth-*
	// ---------------------------------------------------------------------

	/**
	 * `nth-[3]:` emits `:nth-child(3)`.
	 *
	 * @return void
	 */
	public function test_nth_bracket_integer(): void {
		$css = JitCompiler::compile( array( 'nth-[3]:bg-[#eee]' ) );
		$this->assertStringContainsString( ':nth-child(3)', $css );
	}

	/**
	 * `nth-[2n+1]:` emits `:nth-child(2n+1)`.
	 *
	 * @return void
	 */
	public function test_nth_bracket_an_plus_b(): void {
		$css = JitCompiler::compile( array( 'nth-[2n+1]:bg-[#fff]' ) );
		$this->assertStringContainsString( ':nth-child(2n+1)', $css );
	}

	/**
	 * `nth-of-type-[2]:` emits `:nth-of-type(2)`.
	 *
	 * @return void
	 */
	public function test_nth_of_type_bracket(): void {
		$css = JitCompiler::compile( array( 'nth-of-type-[2]:text-[#111]' ) );
		$this->assertStringContainsString( ':nth-of-type(2)', $css );
	}

	/**
	 * `nth-last-of-type-[1]:` emits `:nth-last-of-type(1)`.
	 *
	 * @return void
	 */
	public function test_nth_last_of_type_bracket(): void {
		$css = JitCompiler::compile( array( 'nth-last-of-type-[1]:text-[#222]' ) );
		$this->assertStringContainsString( ':nth-last-of-type(1)', $css );
	}

	/**
	 * Adversarial `nth-[…]:` payloads are rejected.
	 *
	 * @return void
	 */
	public function test_nth_bracket_rejects_adversarial_payload(): void {
		$css = JitCompiler::compile( array( 'nth-[3);color:red;]:text-[#000]' ) );
		$this->assertSame( '', $css );
	}
}
