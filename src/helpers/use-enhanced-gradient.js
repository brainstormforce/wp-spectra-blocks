/**
 * External dependencies.
 */
import { useMemo, useCallback, useRef, useEffect } from '@wordpress/element';

/**
 * Custom hook for parsing and manipulating CSS gradient strings.
 *
 * @param {string} gradientValue The CSS gradient string.
 * @param {Function} onChange Callback function to update the gradient value.
 * @since x.x.x
 * @return {Object} Object containing parsed gradient data and manipulation functions.
 */
export const useEnhancedGradient = ( gradientValue, onChange ) => {
	// Store the last used angle for linear gradients to preserve when switching types.
	const lastAngleRef = useRef( 0 );

	// Parse the gradient string into structured data
	const parsed = useMemo( () => {
		if ( ! gradientValue ) {
			return null;
		}

		// Helper function to parse color stops correctly (handles rgb/rgba, hex, named colors, and CSS variables)
		const parseColorStops = ( colorStopsString ) => {
			const stops = [];
			// Match color stops: handles rgb(), rgba(), hex, named colors, and var(--wp--preset--color--)
			const regex = /((?:var\(--[^)]+\)|(?:rgb|rgba)\([^)]+\)|#[0-9a-fA-F]{3,8}|[a-z]+))\s+(-?\d+(?:\.\d+)?)%/g;
			let match;

			while ( ( match = regex.exec( colorStopsString ) ) !== null ) {
				stops.push( {
					color: match[ 1 ].trim(),
					position: parseFloat( match[ 2 ] ),
				} );
			}

			return stops;
		};

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

			if ( parsed.type !== 'linear' ) return;

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
