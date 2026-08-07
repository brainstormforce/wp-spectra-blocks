<?php
/**
 * Tests for the MappingResolver (curated profiles + auto-derive).
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Sync\ColorRoles;
use SpectraBlocks\StyleGuide\Sync\MappingResolver;
use WP_UnitTestCase;
use ReflectionMethod;

/**
 * MappingResolverTest test case.
 *
 * @since x.x.x
 */
class MappingResolverTest extends WP_UnitTestCase {

	/**
	 * Invoke a private static method on MappingResolver.
	 *
	 * @param string            $method Method name.
	 * @param array<int, mixed> $args   Positional args.
	 * @return mixed
	 */
	private function invoke( string $method, array $args = array() ) {
		$ref = new ReflectionMethod( MappingResolver::class, $method );
		$ref->setAccessible( true );
		return $ref->invoke( null, ...$args );
	}

	/**
	 * Curated Spectra One profile.
	 *
	 * @return void
	 */
	public function test_curated_spectra_one(): void {
		$m = MappingResolver::for_theme( 'spectra-one' );
		$this->assertSame( 'primary', $m->slug_for( ColorRoles::PRIMARY ) );
		// `secondary` slug now carries the SG Secondary brand (chromatic2).
		$this->assertSame( 'secondary', $m->slug_for( ColorRoles::SECONDARY ) );
		// `accent` (Spectra-injected, already chromatic3-7) is now two-way too.
		$this->assertSame( 'accent', $m->slug_for( ColorRoles::ACCENT ) );
		$this->assertSame( 'body', $m->slug_for( ColorRoles::BODY_TEXT ) );
		$this->assertTrue( MappingResolver::has_curated( 'spectra-one' ) );
	}

	/**
	 * Curated Twenty Twenty-Five profile maps brand to accents (push-safe).
	 *
	 * @return void
	 */
	public function test_curated_tt5(): void {
		$m = MappingResolver::for_theme( 'twentytwentyfive' );
		$this->assertSame( 'accent-3', $m->slug_for( ColorRoles::PRIMARY ) );
		$this->assertSame( 'contrast', $m->slug_for( ColorRoles::BODY_TEXT ) );
		$this->assertNull( $m->slug_for( ColorRoles::HEADING_TEXT ) );
		// Brand and body-text never share a slug.
		$this->assertFalse( $m->is_ambiguous( 'contrast' ) );
	}

	/**
	 * Curated Astra profile uses index-based slugs.
	 *
	 * @return void
	 */
	public function test_curated_astra(): void {
		$m = MappingResolver::for_theme( 'astra' );
		$this->assertSame( 'ast-global-color-0', $m->slug_for( ColorRoles::PRIMARY ) );
		// Slot 1 is Astra's native Secondary colour, so SECONDARY maps to it.
		// Astra has no dedicated Accent slot -> ACCENT unmapped.
		$this->assertSame( 'ast-global-color-1', $m->slug_for( ColorRoles::SECONDARY ) );
		$this->assertNull( $m->slug_for( ColorRoles::ACCENT ) );
		// The curated profile is brand-only by design: Astra's non-brand slots are
		// pushed AND pulled by AstraPaletteAdapter (resolve_patch/reverse_map via
		// the update_option_astra-settings hook), not by the resolver mapping.
		$this->assertNull( $m->slug_for( ColorRoles::PAGE_BACKGROUND ) );
	}

	/**
	 * An unlisted theme with no derivable data yields an empty mapping.
	 *
	 * @return void
	 */
	public function test_unlisted_theme_empty(): void {
		$m = MappingResolver::for_theme( 'some-unknown-theme-xyz' );
		$this->assertSame( array(), $m->mapped_roles() );
		$this->assertFalse( MappingResolver::has_curated( 'some-unknown-theme-xyz' ) );
	}

	/**
	 * The mapping filter can override the resolved map.
	 *
	 * @return void
	 */
	public function test_filter_override(): void {
		$cb = static function () {
			return array( ColorRoles::PRIMARY => 'brand-x' );
		};
		add_filter( 'spectra_style_guide_theme_color_mapping', $cb );
		$m = MappingResolver::for_theme( 'some-unknown-theme-xyz' );
		remove_filter( 'spectra_style_guide_theme_color_mapping', $cb );
		$this->assertSame( 'brand-x', $m->slug_for( ColorRoles::PRIMARY ) );
	}

	/**
	 * preset_slug parses both theme.json syntaxes and rejects literals.
	 *
	 * @return void
	 */
	public function test_preset_slug(): void {
		$this->assertSame( 'base', $this->invoke( 'preset_slug', array( 'var(--wp--preset--color--base)' ) ) );
		$this->assertSame( 'contrast', $this->invoke( 'preset_slug', array( 'var:preset|color|contrast' ) ) );
		$this->assertNull( $this->invoke( 'preset_slug', array( 'currentColor' ) ) );
		$this->assertNull( $this->invoke( 'preset_slug', array( '#ffffff' ) ) );
	}

	/**
	 * Auto-derive infers roles from styles usage and stays push-safe.
	 *
	 * @return void
	 */
	public function test_derive_from_styles(): void {
		// Distinct button color -> primary derived; bg/text/heading mapped.
		$styles  = array(
			'color'    => array(
				'background' => 'var(--wp--preset--color--paper)',
				'text'       => 'var(--wp--preset--color--ink)',
			),
			'elements' => array(
				'heading' => array( 'color' => array( 'text' => 'var(--wp--preset--color--night)' ) ),
				'button'  => array( 'color' => array( 'background' => 'var(--wp--preset--color--brand)' ) ),
			),
		);
		$palette = array(
			'paper' => true,
			'ink'   => true,
			'night' => true,
			'brand' => true,
		);
		$m = $this->invoke( 'derive_from_styles', array( $styles, $palette ) );
		$this->assertSame( 'brand', $m[ ColorRoles::PRIMARY ] );
		$this->assertSame( 'paper', $m[ ColorRoles::PAGE_BACKGROUND ] );
		$this->assertSame( 'ink', $m[ ColorRoles::BODY_TEXT ] );
		$this->assertSame( 'night', $m[ ColorRoles::HEADING_TEXT ] );
	}

	/**
	 * Auto-derive skips primary when the button slug collides with body text.
	 *
	 * @return void
	 */
	public function test_derive_primary_push_safety(): void {
		// TT5-shape: button bg == body text slug (contrast) -> primary must be skipped.
		$styles  = array(
			'color'    => array(
				'background' => 'var:preset|color|base',
				'text'       => 'var:preset|color|contrast',
			),
			'elements' => array(
				'button' => array( 'color' => array( 'background' => 'var:preset|color|contrast' ) ),
			),
		);
		$palette = array(
			'base'     => true,
			'contrast' => true,
		);
		$m = $this->invoke( 'derive_from_styles', array( $styles, $palette ) );
		$this->assertArrayNotHasKey( ColorRoles::PRIMARY, $m, 'primary skipped to avoid tinting body text' );
		$this->assertSame( 'base', $m[ ColorRoles::PAGE_BACKGROUND ] );
		$this->assertSame( 'contrast', $m[ ColorRoles::BODY_TEXT ] );
	}

	/**
	 * Derived slugs are validated against the palette; unknown slugs dropped.
	 *
	 * @return void
	 */
	public function test_derive_validates_palette(): void {
		$styles = array(
			'color' => array( 'background' => 'var(--wp--preset--color--ghost)' ),
		);
		$m = $this->invoke( 'derive_from_styles', array( $styles, array( 'real' => true ) ) );
		$this->assertArrayNotHasKey( ColorRoles::PAGE_BACKGROUND, $m, 'slug not in palette is dropped' );
	}
}
