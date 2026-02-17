/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { useSpectraStyles } from '@spectra-hooks';
import { spectraClassNames } from '@spectra-helpers';
import RenderSVG from '@spectra-helpers/render-svg';

/**
 * The render function for the List Icon block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const {
		attributes,
		context: {
			'spectra/list/listType': listType,
			'spectra/list/textColor': inheritedListColor,
			'spectra/list/textColorHover': inheritedListColorHover,
			'spectra/list/iconSize': inheritedIconSize,
			'spectra/list/iconName': inheritedIcon,
			'spectra/list/flipForRTL': inheritedFlipForRTL,
			'spectra/list/rotation': inheritedRotation,
			'spectra/list-child-item/index': contextItemIndex,
			'spectra/list-child-item/textColor': inheritedItemColor,
			'spectra/list-child-item/textColorHover': inheritedItemColorHover,
			'spectra/list/listStyle': listStyle,
			'spectra/list/start': startValue,
			'spectra/list/reversed': isReversed,
			'spectra/list/totalItems': contextTotalItems
		}
	} = props;
	
	const {
		itemIndex,
		icon,
		iconSize,
		textColor,
		textColorHover,
		backgroundColor,
		backgroundColorHover,
		backgroundGradient,
		backgroundGradientHover,
		flipForRTL,
		rotation,
	} = attributes;

	// Configuration for the useSpectraStyles hook.
	// Three-level inheritance: Icon > List-child-item > List
	let finalTextColor;
	if ( textColor !== undefined ) {
		finalTextColor = textColor;
	} else if ( inheritedItemColor !== undefined ) {
		finalTextColor = inheritedItemColor;
	} else {
		finalTextColor = inheritedListColor;
	}
	
	let finalTextColorHover;
	if ( textColorHover !== undefined ) {
		finalTextColorHover = textColorHover;
	} else if ( inheritedItemColorHover !== undefined ) {
		finalTextColorHover = inheritedItemColorHover;
	} else {
		finalTextColorHover = inheritedListColorHover;
	}
	
	const config = [
		{ key: 'textColor', value: finalTextColor },
		{ key: 'textColorHover', value: finalTextColorHover },
		{ key: 'backgroundColor', value: backgroundColor},
		{ key: 'backgroundColorHover', value: backgroundColorHover },
		{ key: 'backgroundGradient', value: backgroundGradient },
		{ key: 'backgroundGradientHover', value: backgroundGradientHover },
	];
	
	// Use block attributes if set, otherwise fall back to inherited context
	const finalIcon = icon || inheritedIcon || 'circle';
	const finalIconSize = iconSize || inheritedIconSize || '10px';
	const finalFlipForRTL = ( flipForRTL !== undefined && flipForRTL !== false ) ? flipForRTL : inheritedFlipForRTL;
	const finalRotation = rotation !== undefined ? rotation : inheritedRotation;

	// Optimize totalItems calculation to avoid expensive re-computations
	const totalItems = useMemo( () => {
		// Prioritize context values to avoid expensive calculations
		if ( contextTotalItems ) {
			return parseInt( contextTotalItems ) || 0;
		}
		// Fallback to 0 if context not available - the parent list should provide this
		return 0;
	}, [ contextTotalItems ] );

	// Use item index from context if available, otherwise use attribute
	const index = contextItemIndex || itemIndex || 1;
	
	// Determine if this is an ordered list
	const isOrdered = listType === 'ordered';
	
	// Custom class names.
	const customClassNames = [
		'spectra-list-icon',
		`spectra-list-icon-${ listType || 'unordered' }`,
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );
	
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style,
	} );
	
	// Helper function to convert numbers to Roman numerals
	const toRoman = ( num ) => {
		const romanNumerals = {
			M: 1000, CM: 900, D: 500, CD: 400, C: 100, XC: 90,
			L: 50, XL: 40, X: 10, IX: 9, V: 5, IV: 4, I: 1
		};
		let result = '';
		
		for ( const key in romanNumerals ) {
			while ( num >= romanNumerals[key] ) {
				result += key;
				num -= romanNumerals[key];
			}
		}
		
		return result;
	};
	
	// Function to format the number based on list style
	const formatNumber = ( num ) => {
		if ( !num ) return '';
		
		// Get total items - use context first, then calculated, ensure at least 1
		const totalItemsFromContext = parseInt( contextTotalItems ) || 0;
		const calculatedTotalItems = parseInt( totalItems ) || 0;
		const finalTotalItems = Math.max( totalItemsFromContext || calculatedTotalItems, 1 );
		
		// Determine start value
		let start;
		if ( startValue !== undefined && startValue !== null && startValue !== '' && parseInt( startValue ) ) {
			// If start value is explicitly set, use it
			start = parseInt( startValue );
		} else if ( isReversed ) {
			// For reversed lists without start value (undefined), use total items
			start = finalTotalItems;
		} else {
			// For normal lists without start value (undefined), use 1
			start = 1;
		}
		
		// Calculate the actual number based on position and reversed state
		// For normal lists: start + (position - 1)
		// For reversed lists: start - (position - 1)
		let actualNum;
		if ( isReversed ) {
			// Reversed: start from start value and count backwards
			// If start=3 and 3 items: positions should be 3, 2, 1
			actualNum = start - ( num - 1 );
		} else {
			// Normal: start from start value and count forwards  
			// If start=1 and 3 items: positions should be 1, 2, 3
			actualNum = start + ( num - 1 );
		}

		// Format based on list style using switch case
		let formattedNumber = '';
		switch ( listStyle ) {
			case 'upper-alpha':
				if ( actualNum <= 0 || actualNum > 26 ) {
					formattedNumber = `${actualNum}`; // Show negative numbers for letters
				} else {
					formattedNumber = String.fromCharCode( 64 + actualNum );
				}
				break;
			case 'lower-alpha':
				if ( actualNum <= 0 || actualNum > 26 ) {
					formattedNumber = `${actualNum}`; // Show negative numbers for letters
				} else {
					formattedNumber = String.fromCharCode( 96 + actualNum );
				}
				break;
			case 'upper-roman':
				if ( actualNum <= 0 ) {
					formattedNumber = `${actualNum}`; // Show negative numbers for Roman numerals
				} else {
					formattedNumber = toRoman( actualNum ).toUpperCase();
				}
				break;
			case 'lower-roman':
				if ( actualNum <= 0 ) {
					formattedNumber = `${actualNum}`; // Show negative numbers for Roman numerals
				} else {
					formattedNumber = toRoman( actualNum ).toLowerCase();
				}
				break;
			case 'decimal-leading-zero':
				if ( actualNum <= 0 ) {
					formattedNumber = `${actualNum}`; // Show negative numbers
				} else {
					formattedNumber = actualNum < 10 ? `0${actualNum}` : `${actualNum}`;
				}
				break;
			case 'decimal':
			default:
				formattedNumber = `${actualNum}`; // Always show the number (including negative)
				break;
		}
		
		return formattedNumber;
	};


	// Memoize IconContent to prevent unnecessary re-renders
	const IconContent = useMemo( () => {
		if ( isOrdered ) {
			return <>{formatNumber( index )}.</>;
		} 
		return (
			<RenderSVG
				svg={finalIcon}
				needsRTL={finalFlipForRTL}
				extraProps={{
					width: finalIconSize,
					height: finalIconSize,
					style: {
						width: finalIconSize,
						height: finalIconSize,
						transform: finalRotation ? `rotate(${finalRotation}deg)` : undefined,
					},
				}}
			/>
		);
	}, [ isOrdered, index, finalIcon, finalFlipForRTL, finalIconSize, finalRotation ] );
	
	// Render the icon
	return (
		<span {...blockProps}>
			{IconContent}
		</span>
	);
};

export default memo( Render );
