/**
 * Legacy responsive-store constants.
 *
 * Everything in this folder exists only to read content written before the store
 * adopted WordPress core's viewport vocabulary. Nothing on the current path imports
 * from here — the dependency runs one way, legacy -> current — so the folder can be
 * deleted outright once the oldest supported content no longer uses these keys.
 *
 * @since 1.0.7
 */

/**
 * Legacy device keys mapped to the canonical viewport keys.
 *
 * Spectra stored breakpoints as `lg` / `md` / `sm` up to and including 1.0.5.
 *
 * Keep in sync with `ResponsiveControls\Legacy\LegacyStore::DEVICE_MAP`.
 *
 * @since 1.0.7
 * @type {Object}
 */
export const LEGACY_DEVICE_MAP = Object.freeze( {
	lg: 'base',
	md: '@tablet',
	sm: '@mobile',
} );
