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
 * Canonical device key to the legacy key it replaced.
 *
 * @since 1.0.7
 * @type {Object}
 */
const LEGACY_KEYS = { 'base': 'lg', '@tablet': 'md', '@mobile': 'sm' };

/**
 * Deep-merge two plain objects; `override` wins at every leaf.
 *
 * Matches PHP's `array_replace_recursive()` for the shapes the store holds, so
 * the editor resolves the legacy cascade exactly as the front end does.
 *
 * @since 1.0.9
 * @param {Object} base     Values to inherit from.
 * @param {Object} override Values that win where both are set.
 * @return {Object} The merged object.
 */
const deepMergeObjects = ( base, override ) => {
	const merged = { ...base };

	Object.entries( override ).forEach( ( [ key, value ] ) => {
		merged[ key ] =
			isPlainObject( value ) && isPlainObject( merged[ key ] )
				? deepMergeObjects( merged[ key ], value )
				: value;
	} );

	return merged;
};

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
	const store = isPlainObject( responsiveControls ) ? responsiveControls : {};

	/*
	 * Blocks created in-session from templates that still author `lg` / `md` /
	 * `sm` — Spectra Pro's loop-builder variations do — never pass the
	 * parse-time migration, so the canonical key is absent. Serving the legacy
	 * key keeps their values visible to the controls and lets the first
	 * responsive edit fold them into `style` instead of discarding them.
	 */
	const readBucket = ( canonical ) => {
		const bucket = store[ canonical ];

		if ( isPlainObject( bucket ) ) {
			return bucket;
		}

		const legacyKey = LEGACY_KEYS[ canonical ];
		const legacyBucket = legacyKey ? store[ legacyKey ] : undefined;

		return isPlainObject( legacyBucket ) ? legacyBucket : {};
	};

	const bucket = readBucket( device );

	/*
	 * Resolve the legacy cascade for mobile. The old generator resolved it as
	 * `sm -> md -> lg`, so a tablet value applied on phones whenever mobile was
	 * unset, and both the other halves of the system preserve that: PHP bakes it
	 * at render (`LegacyStore::normalize_device_keys()`) and the parse-time
	 * migration bakes it into `style['@mobile']` (`canonicaliseStore()`).
	 *
	 * A block that is never migrated — one whose `save()` serialises its
	 * attributes, so migrating it would change its markup — has only this
	 * reader, and without the bake its Mobile control read empty while the front
	 * end rendered the tablet value. Editor and site disagreed on content
	 * neither of them had changed.
	 *
	 * Gated on the store actually carrying legacy keys, mirroring PHP's
	 * `$changed` check: content authored since is already core-shaped, and
	 * core's model is each viewport over base only.
	 */
	if ( '@mobile' !== device ) {
		return bucket;
	}

	const hadLegacyKeys = Object.values( LEGACY_KEYS ).some( ( key ) => isPlainObject( store[ key ] ) );

	if ( ! hadLegacyKeys ) {
		return bucket;
	}

	const tablet = readBucket( '@tablet' );

	return Object.keys( tablet ).length ? deepMergeObjects( tablet, bucket ) : bucket;
};
