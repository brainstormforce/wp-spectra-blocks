/**
 * Shared helpers for the bucket-based CSS class editor.
 *
 * @since x.x.x
 */

/**
 * Parse a raw "property: value" line into an object or null.
 *
 * @since x.x.x
 *
 * @param {string} line Raw declaration like "color: red".
 * @return {{ property: string, value: string }|null} Parsed declaration or null.
 */
export function parseLine( line ) {
	const idx = line.indexOf( ':' );
	if ( idx < 1 ) {
		return null;
	}
	const property = line.slice( 0, idx ).trim();
	const value = line.slice( idx + 1 ).trim();
	return property && value ? { property, value } : null;
}

/**
 * Convert a stored bucket (flat dict or array of objects) to display text.
 *
 * @since x.x.x
 *
 * @param {Object|Array|null} bucket Stored bucket value.
 * @return {string} Multi-line CSS declaration text.
 */
export function bucketToText( bucket ) {
	if ( ! bucket ) {
		return '';
	}
	if ( Array.isArray( bucket ) ) {
		return bucket
			.map( ( d ) => `${ d.property }: ${ d.value }` )
			.join( '\n' );
	}
	if ( typeof bucket === 'object' ) {
		return Object.entries( bucket )
			.map( ( [ k, v ] ) => `${ k }: ${ v }` )
			.join( '\n' );
	}
	return '';
}

/**
 * Parse textarea text back to the flat dict storage format.
 *
 * @since x.x.x
 *
 * @param {string} text Multi-line declaration text.
 * @return {Object} Flat property→value dict.
 */
export function textToBucket( text ) {
	const result = {};
	text.split( '\n' ).forEach( ( line ) => {
		const parsed = parseLine( line.trim() );
		if ( parsed ) {
			result[ parsed.property ] = parsed.value;
		}
	} );
	return result;
}

/**
 * Extract declaration text from a stored class value — handles both bucket
 * format and legacy raw CSS string format.
 *
 * Legacy raw CSS strings are parsed: declarations inside `{ }` are extracted,
 * or the whole string is used verbatim if no braces are found.
 *
 * @since x.x.x
 *
 * @param {string|Object|null} stored Stored class value.
 * @param {string}             bucket Bucket id (e.g. 'default').
 * @return {string} Declaration text for the given bucket.
 */
export function getStoredBucketText( stored, bucket ) {
	if ( ! stored ) {
		return '';
	}

	if ( typeof stored === 'string' ) {
		if ( bucket !== 'default' ) {
			return '';
		}
		const inside = stored.match( /\{([^}]*)\}/s )?.[ 1 ];
		return ( inside ?? stored )
			.replace( /^\s*\/\*[^*]*\*\/\s*/gm, '' )
			.trim();
	}

	if ( typeof stored === 'object' ) {
		return bucketToText( stored[ bucket ] ?? null );
	}

	return '';
}
