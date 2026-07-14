<?php
/**
 * Global Styles Backward Compatibility Layer.
 *
 * Outputs two CSS layers that keep existing sites working after the GS
 * System Vars tabs are removed:
 *
 *   Layer 1 — CSS variable aliases: maps old --color--primary-* / --space--*
 *             / --text--* / --heading--* names to the corresponding SG tokens.
 *
 *   Layer 2 — CSS class aliases: keeps old utility class names (.background--primary,
 *             .padding--lg, etc.) functional by re-declaring them to reference
 *             the Layer 1 variables.
 *
 * Both layers are emitted as inline CSS appended to the SG token stylesheet so
 * they always load after --spectra-* vars are defined.
 *
 * Disable for sites doing a clean cutover:
 *   add_filter( 'spectra_gs_compat_enabled', '__return_false' );
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GlobalStylesCompat
 *
 * @since 1.0.0
 */
class GlobalStylesCompat {

	/**
	 * The Engine instance.
	 *
	 * @since 1.0.0
	 * @var Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Engine $engine Style Guide engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init(): void {
		// Frontend: priority 6 so we run after GlobalStylesBridge (priority 5).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 6 );

		// Editor: appended alongside the SG token stylesheet.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Enqueue compat CSS on the frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_frontend(): void {
		$css = $this->get_compat_css();

		if ( empty( $css ) ) {
			return;
		}

		wp_add_inline_style( 'spectra-style-guide-tokens', $css );
	}

	/**
	 * Enqueue compat CSS in the block editor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor(): void {
		$css = $this->get_compat_css();

		if ( empty( $css ) ) {
			return;
		}

		wp_add_inline_style( 'spectra-style-guide-tokens', $css );
	}

	/**
	 * Build the complete compat CSS (Layer 1 + Layer 2).
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS string or empty if SG is not active or compat is disabled.
	 */
	public function get_compat_css(): string {
		if ( ! apply_filters( 'spectra_gs_compat_enabled', true ) ) {
			return '';
		}

		if ( null === $this->engine->get_token_registry() ) {
			return '';
		}

		$layer1 = $this->get_variable_aliases();

		if ( empty( $layer1 ) ) {
			return '';
		}

		$css = ":root {\n";
		foreach ( $layer1 as $old_var => $new_ref ) {
			$css .= sprintf( "\t%s: %s;\n", esc_attr( $old_var ), $new_ref );
		}
		$css .= "}\n";

		// Layer 2 (class aliases) is only output when GS was previously migrated to SG —
		// i.e., the site had GS system variables configured before the refactor.
		// Fresh installs use the GS pipeline directly and do not need this layer.
		if ( get_option( 'spectra_blocks_pro_gs_migrated_to_sg' ) ) {
			$layer2 = $this->get_class_aliases();
			if ( ! empty( $layer2 ) ) {
				$css .= "\n" . $layer2;
			}
		}

		return $css;
	}

	/**
	 * Build Layer 1: CSS variable aliases mapping old GS names to SG tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Old var name => CSS value/reference.
	 */
	private function get_variable_aliases(): array {
		$aliases = array();

		// --- Chromatic color pairs (primary → chromatic1, secondary → chromatic2) ---
		$pairs = array(
			'primary'   => 1,
			'secondary' => 2,
		);

		foreach ( $pairs as $legacy_name => $c_index ) {
			$prefix = "--spectra-chromatic{$c_index}";

			// Main.
			$aliases[ "--color--{$legacy_name}" ] = "var(--wp--preset--color--{$legacy_name})";

			// Lighter shades.
			$aliases[ "--color--{$legacy_name}-near-white" ] = "var({$prefix}-1)";
			$aliases[ "--color--{$legacy_name}-lightest" ]   = "var({$prefix}-1)";
			$aliases[ "--color--{$legacy_name}-lighter" ]    = "var({$prefix}-3)";
			$aliases[ "--color--{$legacy_name}-light" ]      = "var({$prefix}-5)";

			// Darker shades — mapped to black-mix indices 8-11.
			$aliases[ "--color--{$legacy_name}-dark" ]       = "var({$prefix}-8)";
			$aliases[ "--color--{$legacy_name}-darker" ]     = "var({$prefix}-9)";
			$aliases[ "--color--{$legacy_name}-darkest" ]    = "var({$prefix}-10)";
			$aliases[ "--color--{$legacy_name}-near-black" ] = "var({$prefix}-11)";

			// No SG equivalent — graceful fallbacks.
			$aliases[ "--color--{$legacy_name}-complement" ] = "var({$prefix}-7)";
			$aliases[ "--color--{$legacy_name}-inverted" ]   = 'var(--spectra-neutral-7)';
		}

		// --- Base → neutral scale ---
		$aliases['--color--base']            = 'var(--spectra-neutral-4)';
		$aliases['--color--base-near-white'] = 'var(--spectra-neutral-7)';
		$aliases['--color--base-lightest']   = 'var(--spectra-neutral-7)';
		$aliases['--color--base-lighter']    = 'var(--spectra-neutral-6)';
		$aliases['--color--base-light']      = 'var(--spectra-neutral-5)';
		$aliases['--color--base-dark']       = 'var(--spectra-neutral-3)';
		$aliases['--color--base-darker']     = 'var(--spectra-neutral-2)';
		$aliases['--color--base-darkest']    = 'var(--spectra-neutral-1)';
		$aliases['--color--base-near-black'] = 'var(--spectra-neutral-0)';

		// --- Spacing ---
		$aliases['--space--xs']  = 'var(--spectra-space-xs)';
		$aliases['--space--sm']  = 'var(--spectra-space-sm)';
		$aliases['--space--md']  = 'var(--spectra-space-md)';
		$aliases['--space--lg']  = 'var(--spectra-space-lg)';
		$aliases['--space--xl']  = 'var(--spectra-space-xl)';
		$aliases['--space--xxl'] = 'var(--spectra-space-2xl)';

		// --- Font sizes ---
		$aliases['--text--xs']  = 'var(--spectra-text-xs)';
		$aliases['--text--sm']  = 'var(--spectra-text-sm)';
		$aliases['--text--md']  = 'var(--spectra-text-md)';
		$aliases['--text--lg']  = 'var(--spectra-text-lg)';
		$aliases['--text--xl']  = 'var(--spectra-text-xl)';
		$aliases['--text--xxl'] = 'var(--spectra-text-2xl)';

		// --- Heading sizes ---
		$aliases['--heading--1'] = 'var(--spectra-heading-1)';
		$aliases['--heading--2'] = 'var(--spectra-heading-2)';
		$aliases['--heading--3'] = 'var(--spectra-heading-3)';
		$aliases['--heading--4'] = 'var(--spectra-heading-4)';
		$aliases['--heading--5'] = 'var(--spectra-heading-5)';
		$aliases['--heading--6'] = 'var(--spectra-heading-6)';

		return $aliases;
	}

	/**
	 * Build Layer 2: CSS class aliases for all old GS utility class names.
	 *
	 * Deliberately references Layer 1 variables rather than SG tokens
	 * directly — one canonical mapping to maintain.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS rules block.
	 */
	private function get_class_aliases(): string {
		$rules = array();

		$color_names = array( 'primary', 'secondary', 'base' );

		$color_shades = array(
			'',
			'-near-white',
			'-lightest',
			'-lighter',
			'-light',
			'-dark',
			'-darker',
			'-darkest',
			'-near-black',
			'-complement',
			'-inverted',
		);

		foreach ( $color_names as $color ) {
			foreach ( $color_shades as $shade ) {
				$class   = "background--{$color}{$shade}";
				$rules[] = ".{$class} { background: var(--color--{$color}{$shade}); }";
			}
		}

		foreach ( $color_names as $color ) {
			foreach ( $color_shades as $shade ) {
				$class   = "color--{$color}{$shade}";
				$rules[] = ".{$class} { color: var(--color--{$color}{$shade}); }";
			}
		}

		// Opacity variants (10% through 90%).
		foreach ( $color_names as $color ) {
			foreach ( range( 1, 9 ) as $i ) {
				$pct     = $i * 10;
				$class   = "background--{$color}--{$pct}";
				$rules[] = ".{$class} { background: var(--color--{$color}--{$pct}); }";
			}
		}

		// Spacing.
		$sizes = array( 'xs', 'sm', 'md', 'lg', 'xl', 'xxl' );

		foreach ( $sizes as $size ) {
			$rules[] = ".padding--{$size} { padding: var(--space--{$size}); }";
			$rules[] = ".padding-top--{$size} { padding-top: var(--space--{$size}); }";
			$rules[] = ".padding-bottom--{$size} { padding-bottom: var(--space--{$size}); }";
			$rules[] = ".padding-left--{$size} { padding-left: var(--space--{$size}); }";
			$rules[] = ".padding-right--{$size} { padding-right: var(--space--{$size}); }";
			$rules[] = ".margin--{$size} { margin: var(--space--{$size}); }";
			$rules[] = ".margin-top--{$size} { margin-top: var(--space--{$size}); }";
			$rules[] = ".margin-bottom--{$size} { margin-bottom: var(--space--{$size}); }";
			$rules[] = ".gap--{$size} { gap: var(--space--{$size}); }";
		}

		// Typography.
		foreach ( $sizes as $size ) {
			$rules[] = ".text--{$size} { font-size: var(--text--{$size}); }";
		}

		foreach ( range( 1, 6 ) as $level ) {
			$rules[] = ".heading--{$level} { font-size: var(--heading--{$level}); }";
		}

		return implode( "\n", $rules ) . "\n";
	}
}
