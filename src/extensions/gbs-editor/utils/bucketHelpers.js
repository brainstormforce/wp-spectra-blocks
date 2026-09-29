/**
 * Shared helpers for the bucket-based CSS class editor.
 *
 * @since 1.0.9
 */

/**
 * Parse a raw "property: value" line into an object or null.
 *
 * @since 1.0.9
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

	// Drop the author's terminating semicolon. Without this the `;` was kept as
	// part of the value, so the editor's own placeholder — `color: red;` — stored
	// `red;` and the generator emitted `color: red;;`. Only a TRAILING one is
	// removed: a `;` in the middle of a value is a different problem, and
	// validation reports it rather than silently rewriting what was typed.
	const value = line.slice( idx + 1 ).replace( /;\s*$/, '' ).trim();

	return property && value ? { property, value } : null;
}

/**
 * Convert a stored bucket (flat dict or array of objects) to display text.
 *
 * @since 1.0.9
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
 * @since 1.0.9
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
 * Split a run of CSS on its top-level `;` separators.
 *
 * Quote- and paren-aware, so a semicolon inside `url( 'a;b.png' )` or
 * `content: ";"` is part of the value rather than a separator.
 *
 * @since 1.0.10
 *
 * @param {string} css Declaration run, without the surrounding braces.
 * @return {string[]} Raw declarations, still untrimmed.
 */
function splitDeclarations( css ) {
	const parts = [];
	let buffer = '';
	let quote = null;
	let depth = 0;

	for ( let i = 0; i < css.length; i++ ) {
		const char = css[ i ];

		if ( quote ) {
			buffer += char;
			if ( '\\' === char && i + 1 < css.length ) {
				buffer += css[ ++i ];
			} else if ( char === quote ) {
				quote = null;
			}
			continue;
		}

		if ( '"' === char || "'" === char ) {
			quote = char;
		} else if ( '(' === char ) {
			depth++;
		} else if ( ')' === char ) {
			depth = Math.max( 0, depth - 1 );
		} else if ( ';' === char && 0 === depth ) {
			parts.push( buffer );
			buffer = '';
			continue;
		}

		buffer += char;
	}

	parts.push( buffer );
	return parts;
}

/**
 * Extract declaration text from a stored class value — handles both bucket
 * format and legacy raw CSS string format.
 *
 * Legacy raw CSS strings are parsed: declarations inside `{ }` are extracted,
 * or the whole string is used verbatim if no braces are found.
 *
 * @since 1.0.9
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

		// One declaration per line, matching what `bucketToText` produces for the
		// object format. Legacy CSS written on a single line — `{ color: red;
		// font-size: 1rem; }` — otherwise reached the editor as one line, and
		// `textToBucket` splits on newlines, so the whole run collapsed into a
		// single property and the later declarations were lost on the first save.
		// Newlines inside one declaration are folded for the same reason.
		return splitDeclarations(
			( inside ?? stored ).replace( /^\s*\/\*[^*]*\*\/\s*/gm, '' )
		)
			.map( ( part ) => part.replace( /\s*\n\s*/g, ' ' ).trim() )
			.filter( Boolean )
			.join( '\n' );
	}

	if ( typeof stored === 'object' ) {
		return bucketToText( stored[ bucket ] ?? null );
	}

	return '';
}
