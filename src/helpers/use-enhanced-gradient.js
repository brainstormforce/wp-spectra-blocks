/**
 * External dependencies.
 */
import { useMemo, useCallback, useRef, useEffect } from '@wordpress/element';

/**
 * Custom hook for parsing and manipulating CSS gradient strings.
 *
 * @param {string}   gradientValue The CSS gradient string.
 * @param {Function} onChange      Callback function to update the gradient value.
 * @since x.x.x
 * @return {Object} Object containing parsed gradient data and manipulation functions.
 */
/**
 * Parse CSS color stops from a gradient stops string.
 * Handles rgb(), rgba(), hex, named colors, and CSS custom properties.
 *
 * @param {string} colorStopsString The stops portion of a gradient string.
 * @return {Array<{color: string, position: number}>} Parsed color stops.
 */
const parseColorStops = ( colorStopsString ) => {
	const stops = [];
	const regex =
		/((?:var\(--[^)]+\)|(?:rgb|rgba)\([^)]+\)|#[0-9a-fA-F]{3,8}|[a-z]+))\s+(-?\d+(?:\.\d+)?)%/g;
	let match;
	while ( ( match = regex.exec( colorStopsString ) ) !== null ) {
		stops.push( {
			color: match[ 1 ].trim(),
			position: parseFloat( match[ 2 ] ),
		} );
	}
	return stops;
};

/**
 * Split a CSS gradient inner string on top-level commas (ignores commas inside parentheses).
 *
 * @param {string} str The inner content of a gradient function.
 * @return {string[]} Parts split at top-level commas.
 */
const splitTopLevelCommas = ( str ) => {
	const parts = [];
	let depth = 0;
	let buffer = '';
	for ( let i = 0; i < str.length; i++ ) {
		const ch = str[ i ];
		if ( ch === '(' ) {depth++;}
		else if ( ch === ')' ) {depth--;}
		if ( ch === ',' && depth === 0 ) {
			parts.push( buffer.trim() );
			buffer = '';
		} else {
			buffer += ch;
		}
	}
	if ( buffer.trim() ) {
		parts.push( buffer.trim() );
	}
	return parts;
};

export const useEnhancedGradient = ( gradientValue, onChange ) => {
	// Store the last used angle for linear gradients to preserve when switching types.
	const lastAngleRef = useRef( 0 );

	// Parse the gradient string into structured data
	const parsed = useMemo( () => {
		if ( ! gradientValue ) {
			return null;
		}

		// Match linear-gradient pattern: linear-gradient(angle, color position, color position, ...)
		const linearMatch = gradientValue.match( /^linear-gradient\(([^,]+),\s*(.+)\)$/ );

		if ( linearMatch ) {
			const angle = parseFloat( linearMatch[ 1 ] );
			const colorStops = parseColorStops( linearMatch[ 2 ] );

			return {
				type: 'linear',
				angle: isNaN( angle ) ? 0 : angle,
				colors: colorStops,
			};
		}

		// Match radial-gradient pattern: radial-gradient([shape] at position, color position, color position, ...)
		// Supports both: radial-gradient(circle at center, ...) and radial-gradient(at center center, ...)
		const radialMatch = gradientValue.match( /^radial-gradient\(([^,]+),\s*(.+)\)$/ );

		if ( radialMatch ) {
			const position = radialMatch[ 1 ].trim();
			const colorStops = parseColorStops( radialMatch[ 2 ] );

			return {
				type: 'radial',
				position: position === 'at center center' ? 'center center' : position,
				colors: colorStops,
			};
		}

		return null;
	}, [ gradientValue ] );

	// Update lastAngleRef whenever we have a linear gradient with an angle.
	useEffect( () => {
		if ( parsed?.type === 'linear' && parsed?.angle !== undefined ) {
			lastAngleRef.current = parsed.angle;
		}
	}, [ parsed ] );

	// Helper function to ensure colors array has defaults
	const ensureColors = useCallback( ( colors ) => {
		const defaults = [
			{ color: '#06558a', position: 0 },
			{ color: '#0063A1', position: 100 },
		];

		const ensured = [ ...colors ];

		// Ensure we have at least 2 colors
		while ( ensured.length < 2 ) {
			ensured.push( defaults[ ensured.length ] );
		}

		// Ensure each color has valid values
		return ensured.map( ( c, index ) => ( {
			color: c.color || defaults[ index ]?.color || '#06558a',
			position:
				c.position !== undefined && c.position !== null
					? c.position
					: defaults[ index ]?.position ?? index * 100,
		} ) );
	}, [] );

	// Function to update gradient type
	const setType = useCallback(
		( type ) => {
			// If no gradient exists yet, create a default one with the specified type
			if ( ! parsed ) {
				const defaultColors = ensureColors( [] );
				const newGradient =
					type === 'linear'
						? `linear-gradient(0deg, ${ defaultColors.map( ( c ) => `${ c.color } ${ c.position }%` ).join( ', ' ) })`
						: `radial-gradient( at center center, ${ defaultColors.map( ( c ) => `${ c.color } ${ c.position }%` ).join( ', ' ) })`;
				onChange( newGradient );
				return;
			}

			const colors = ensureColors( parsed.colors || [] );
			// Use the last known angle when switching to linear, or current angle if already linear, or default to 0.
			let angle = 0;
			if ( type === 'linear' ) {
				angle = parsed.angle ?? lastAngleRef.current;
			}
			let newGradient;
			if ( type === 'linear' ) {
				newGradient = `linear-gradient(${ angle }deg, ${ colors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			} else {
				newGradient = `radial-gradient( at center center, ${ colors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			}
			onChange( newGradient );
		},
		[ parsed, onChange, ensureColors ]
	);

	// Function to update color at specific index
	const setColorAtIndex = useCallback(
		( index, color ) => {
			// If no gradient exists yet, create a default one with the new color
			if ( ! parsed ) {
				const defaultColors = ensureColors( [] );
				if ( defaultColors[ index ] ) {
					defaultColors[ index ] = { ...defaultColors[ index ], color: color || '#06558a' };
				}
				const newGradient = `linear-gradient(0deg, ${ defaultColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
				onChange( newGradient );
				return;
			}

			const newColors = ensureColors( parsed.colors || [] );
			if ( newColors[ index ] ) {
				newColors[ index ] = { ...newColors[ index ], color: color || '#06558a' };
			}

			const angle = parsed.angle !== undefined && parsed.angle !== null ? parsed.angle : 0;
			let newGradient;
			if ( parsed.type === 'linear' ) {
				newGradient = `linear-gradient(${ angle }deg, ${ newColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			} else {
				newGradient = `radial-gradient( at center center, ${ newColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			}
			onChange( newGradient );
		},
		[ parsed, onChange, ensureColors ]
	);

	// Function to update position at specific index
	const setPositionAtIndex = useCallback(
		( index, position ) => {
			// If no gradient exists yet, create a default one with the new position
			if ( ! parsed ) {
				const defaultColors = ensureColors( [] );
				if ( defaultColors[ index ] ) {
					defaultColors[ index ] = { ...defaultColors[ index ], position };
				}
				const newGradient = `linear-gradient(0deg, ${ defaultColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
				onChange( newGradient );
				return;
			}

			const newColors = ensureColors( parsed.colors || [] );
			if ( newColors[ index ] ) {
				newColors[ index ] = {
					...newColors[ index ],
					position: position !== undefined && position !== null ? position : 0,
				};
			}

			const angle = parsed.angle !== undefined && parsed.angle !== null ? parsed.angle : 0;
			let newGradient;
			if ( parsed.type === 'linear' ) {
				newGradient = `linear-gradient(${ angle }deg, ${ newColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			} else {
				newGradient = `radial-gradient( at center center, ${ newColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
			}
			onChange( newGradient );
		},
		[ parsed, onChange, ensureColors ]
	);

	// Function to update angle (for linear gradients)
	const setAngle = useCallback(
		( angle ) => {
			// If no gradient exists yet, create a default linear one with the new angle
			if ( ! parsed ) {
				const defaultColors = ensureColors( [] );
				const newGradient = `linear-gradient(${ angle }deg, ${ defaultColors
					.map( ( c ) => `${ c.color } ${ c.position }%` )
					.join( ', ' ) })`;
				onChange( newGradient );
				return;
			}

			if ( parsed.type !== 'linear' ) {return;}

			const colors = ensureColors( parsed.colors || [] );
			const finalAngle = angle !== undefined && angle !== null ? angle : 0;
			const newGradient = `linear-gradient(${ finalAngle }deg, ${ colors
				.map( ( c ) => `${ c.color } ${ c.position }%` )
				.join( ', ' ) })`;
			onChange( newGradient );
		},
		[ parsed, onChange, ensureColors ]
	);

	return {
		parsed,
		setType,
		setColorAtIndex,
		setPositionAtIndex,
		setAngle,
	};
};

/**
 * Builds a gradient string by applying responsive overrides (angle, location1, location2)
 * to a base gradient string.
 *
 * Used in the editor render to produce device-specific gradient previews when the user
 * has set per-breakpoint values via the responsive controls.
 *
 * @param {string} baseGradient The base CSS gradient string.
 * @param {Object} overrides    Responsive overrides: { angle, location1, location2 }.
 * @return {string} The modified gradient string, or the original if no overrides apply.
 * @since x.x.x
 */
export const buildGradientString = ( baseGradient, overrides = {} ) => {
	if ( ! baseGradient ) {
		return baseGradient;
	}

	const { angle, location1, location2 } = overrides;
	const hasOverrides =
		angle !== undefined || location1 !== undefined || location2 !== undefined;

	if ( ! hasOverrides ) {
		return baseGradient;
	}

	const isLinear = /^linear-gradient\(/i.test( baseGradient );
	const isRadial = ! isLinear && /^radial-gradient\(/i.test( baseGradient );

	if ( ! isLinear && ! isRadial ) {
		return baseGradient;
	}

	// Extract inner content between the outermost parentheses.
	const start = baseGradient.indexOf( '(' );
	const end = baseGradient.lastIndexOf( ')' );
	if ( start === -1 || end === -1 ) {
		return baseGradient;
	}

	const parts = splitTopLevelCommas( baseGradient.slice( start + 1, end ) );
	if ( parts.length < 2 ) {
		return baseGradient;
	}

	let firstStopIdx = 0;

	if ( isLinear ) {
		const first = parts[ 0 ];
		const isAngle = /^-?\d+(\.\d+)?deg$/i.test( first );
		const isKeyword = /^to\s+/i.test( first );

		if ( isAngle || isKeyword ) {
			// Replace numeric angle if override supplied; preserve keyword direction otherwise.
			if ( angle !== undefined ) {
				parts[ 0 ] = `${ angle }deg`;
			}
			firstStopIdx = 1;
		} else if ( angle !== undefined ) {
			// No existing angle segment — insert one.
			parts.unshift( `${ angle }deg` );
			firstStopIdx = 1;
		}
	} else if ( isRadial ) {
		// The first part of a radial-gradient is the shape/size/position descriptor when it
		// contains no % position (e.g. "circle at center"). Skip it to reach the first color stop.
		if ( ! /-?\d+(\.\d+)?%/.test( parts[ 0 ] ) ) {
			firstStopIdx = 1;
		}
	}

	// Apply location overrides by replacing the trailing % on the target stop string.
	const replacePos = ( stopStr, pos ) =>
		stopStr.replace( /-?\d+(\.\d+)?%\s*$/, `${ pos }%` );

	if ( location1 !== undefined && parts[ firstStopIdx ] ) {
		parts[ firstStopIdx ] = replacePos( parts[ firstStopIdx ], location1 );
	}

	const lastIdx = parts.length - 1;
	if ( location2 !== undefined && lastIdx > firstStopIdx ) {
		parts[ lastIdx ] = replacePos( parts[ lastIdx ], location2 );
	}

	const type = isLinear ? 'linear-gradient' : 'radial-gradient';
	return `${ type }(${ parts.join( ', ' ) })`;
};
