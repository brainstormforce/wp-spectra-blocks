/**
 * Hook utilities.
 */

/**
 * External dependencies.
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { convertToKebabCase } from '@spectra-helpers';
import { updateCategory } from '@wordpress/blocks';
import blockIcons from '@spectra-helpers/block-icons';

updateCategory( 'spectra', {
	icon: blockIcons.logo(),
} );

/**
 * Converts WordPress preset format to proper CSS var() syntax.
 * Handles: 'var:preset|type|slug' → 'var(--wp--preset--type--slug)'
 *
 * @param {string} value - The value to convert.
 * @return {string} The converted CSS value, or original if no conversion needed.
 */
const convertWordPressPreset = ( value ) => {
	if ( ! value || typeof value !== 'string' ) {
		return value;
	}

	// Handle WordPress preset format: var:preset|type|slug
	const presetMatch = value.match( /^var:preset\|([^|]+)\|(.+)$/ );
	if ( presetMatch ) {
		const [ , type, slug ] = presetMatch;
		return `var(--wp--preset--${ type }--${ slug })`;
	}

	return value;
};

/**
 * useSpectraStyles hook for generating styles and class names based on
 * Spectra block attributes and configuration.
 *
 * @param {Object} attributes - Block attributes.
 * @param {Array}  config - Configuration array of objects with the following properties:
 *   - key: Required attribute key (e.g., 'textColor').
 *   - cssVar: Optional CSS variable (e.g., '--spectra-text-color') or null to skip style generation.
 *   - className: Optional class name (e.g., 'spectra-text-color') or null to skip class generation.
 *   - value: Optional explicit value (e.g., '#fff').
 * @param {Array}  customClassNames - Optional custom class names array to merge with generated class names. Format: string[].
 * @param {Object} customStyles - Optional custom styles object to merge with generated styles. Format: { [string]: string }.
 * @since x.x.x
 * @return {Object} - Object containing generated styles and class names.
 *   - style: { [string]: string } - Generated styles object with CSS variable names as keys.
 *   - classNames: string[] - Generated class names array.
 */
export const useSpectraStyles = ( attributes, config = [], customClassNames = [], customStyles = {} ) => {
	return useMemo( () => {
		const classNames = [ ...customClassNames ]; // Merge custom class names into the base array.
		const style = { ...customStyles }; // Merge custom styles into the base object.

		config.forEach( ( mapping ) => {
			// Extract configuration details, only 'key' is required.
			const {
				key, // Required: Attribute key (e.g., 'textColor').
				cssVar, // Optional: CSS variable (e.g., '--spectra-text-color').
				className, // Optional: Class name (e.g., 'spectra-text-color').
				value, // Optional: Explicit value (e.g., '#fff').
			} = mapping;

			// If both cssVar and className are explicitly null, skip this mapping entirely.
			if ( cssVar === null && className === null ) {
				return;
			}

			// Generate defaults if not provided (and not explicitly null).
			const defaultCssVar = `--spectra-${ convertToKebabCase( key ) }`;
			const defaultClassName = `spectra-${ convertToKebabCase( key ) }`;

			// Use provided values or fall back to defaults, unless explicitly null.
			const finalCssVar = cssVar !== undefined ? cssVar : defaultCssVar;
			const finalClassName = className !== undefined ? className : defaultClassName;

		// Determine the value: explicit value takes precedence over attribute.
		const attrValue = attributes[ key ] || ''; // Fallback to empty string if undefined.
		let finalValue = value !== undefined ? value : attrValue;

		// Convert WordPress preset format (var:preset|type|slug) to CSS var().
		finalValue = convertWordPressPreset( finalValue );

		// Set CSS variable if a value exists and cssVar is not null.
		if ( finalValue && finalCssVar !== null ) {
			style[ finalCssVar ] = finalValue;
		}

			// Add class if a value exists and className is not null.
			if ( finalValue && finalClassName !== null ) {
				classNames.push( finalClassName );
			}
		} );

		// Return the generated styles and class names.
		return { style, classNames };
	}, [ attributes, config, customClassNames, customStyles ] );
};