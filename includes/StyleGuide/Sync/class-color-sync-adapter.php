<?php
/**
 * Color Sync Adapter — the interface every theme-store strategy implements.
 *
 * An adapter is the ONLY place that knows *where* and *how* a given theme class
 * stores its colors. It is slug-based and role-agnostic: the orchestrator
 * translates canonical roles ↔ the theme's slugs (via {@see ThemeColorMapping})
 * and role ↔ Style Guide color (via the engine), then talks to the adapter
 * purely in slugs. This keeps the role model theme-agnostic and makes each
 * store (FSE `wp_global_styles`, Astra option, …) an additive implementation.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\TokenRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface ColorSyncAdapter
 *
 * @since 1.0.4
 */
interface ColorSyncAdapter {

	/**
	 * Whether this adapter's store applies to the current environment / active theme.
	 *
	 * Called before any read/write so an unsupported store is a silent no-op,
	 * never an error (e.g. the Astra adapter when Astra is not active).
	 *
	 * @since 1.0.4
	 *
	 * @return bool
	 */
	public function is_supported(): bool;

	/**
	 * Read the effective palette as slug => hex.
	 *
	 * Returns literal color values keyed by the theme's own slug. Non-hex values
	 * (`transparent`, `currentColor`, `color-mix(...)`) may be returned verbatim;
	 * the caller decides what is usable.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> slug => color.
	 */
	public function read(): array;

	/**
	 * Patch the given slugs' colors into the store, preserving every other entry,
	 * then invalidate the store's cache.
	 *
	 * Implements the whole-palette rule: unmapped/foreign slugs are never dropped.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string> $patch slug => hex to set.
	 * @return bool True when a write occurred, false on no-op / failure.
	 */
	public function write( array $patch ): bool;

	/**
	 * Build a theme-specific slug => hex patch directly from the Style Guide
	 * tokens, bypassing the generic role model.
	 *
	 * Return an EMPTY array to use the orchestrator's generic role → slug patch
	 * (the FSE default). A theme whose palette does not fit the 1-role-1-slug
	 * model — e.g. Astra, where several slots share a token and slot indices
	 * depend on a compatibility flag — returns its own full patch here.
	 *
	 * @since 1.0.4
	 *
	 * @param TokenRegistry $tokens The computed Style Guide token registry.
	 * @return array<string, string> slug => hex, or empty to fall back to generic.
	 */
	public function resolve_patch( TokenRegistry $tokens ): array;

	/**
	 * Human-readable adapter label (logging / UI).
	 *
	 * @since 1.0.4
	 *
	 * @return string
	 */
	public function label(): string;
}
