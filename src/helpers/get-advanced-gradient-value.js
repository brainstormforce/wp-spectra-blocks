/**
 * Get the final gradient value based on advanced mode
 *
 * @param {boolean} enableAdvBg Whether advanced gradient is enabled
 * @param {string} advValue The advanced gradient value
 * @param {string} basicValue The basic gradient value
 * @param {boolean} enableAdvGradients Whether advanced gradients are globally enabled
 * @return {string} The final gradient value to use
 */
export const getAdvancedGradientValue = ( enableAdvBg, advValue, basicValue, enableAdvGradients = false ) => {
	// If both toggles are enabled, use advanced value only (never fallback to basic)
	if ( enableAdvGradients && enableAdvBg ) {
		return advValue || '';
	}
	// Otherwise use basic value
	return basicValue;
};

/**
 * Get advanced gradient attributes for block.json
 *
 * @param {string} baseAttrName Base attribute name (e.g., 'backgroundGradient')
 * @return {Object} Attributes object for block.json
 */
export const getAdvancedGradientAttributes = ( baseAttrName ) => {
	const advancedAttrName = `advanced${ baseAttrName.charAt( 0 ).toUpperCase() + baseAttrName.slice( 1 ) }`;
	const useAdvancedAttrName = `useAdvanced${ baseAttrName.charAt( 0 ).toUpperCase() + baseAttrName.slice( 1 ) }`;

	return {
		[ useAdvancedAttrName ]: {
			type: 'boolean',
			default: false,
		},
		[ advancedAttrName ]: {
			type: 'string',
		},
	};
};
