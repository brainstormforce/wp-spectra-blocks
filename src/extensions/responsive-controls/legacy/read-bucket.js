/**
 * Reading the pre-1.0.6 store.
 *
 * A block that has not been migrated yet still carries its values in
 * `responsiveControls`. This is the only place on the read path that knows that
 * attribute exists, which is what lets the whole folder be deleted at once.
 *
 * @since 1.0.7
 */

const isPlainObject = ( value ) =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

/**
 * Read one breakpoint out of the legacy `responsiveControls` store.
 *
 * Returns an empty object for a block that has no legacy store, so callers can
 * merge the result unconditionally.
 *
 * @since 1.0.7
 * @param {Object} responsiveControls The legacy store, if the block still has one.
 * @param {string} device             Store device key: `base`, `@tablet` or `@mobile`.
 * @return {Object} The bucket, or an empty object.
 */
export const readLegacyBucket = ( responsiveControls, device ) => {
	const bucket = responsiveControls?.[ device ];

	if ( isPlainObject( bucket ) ) {
		return bucket;
	}

	/*
	 * Blocks created in-session from templates that still author `lg` / `md` /
	 * `sm` — Spectra Pro's loop-builder variations do — never pass the
	 * parse-time migration, so the canonical key is absent. Serving the legacy
	 * key keeps their values visible to the controls and lets the first
	 * responsive edit fold them into `style` instead of discarding them.
	 */
	const legacyKey = { 'base': 'lg', '@tablet': 'md', '@mobile': 'sm' }[ device ];
	const legacyBucket = legacyKey ? responsiveControls?.[ legacyKey ] : undefined;

	return isPlainObject( legacyBucket ) ? legacyBucket : {};
};
