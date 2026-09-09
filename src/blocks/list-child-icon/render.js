/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { useSpectraStyles } from '@spectra-hooks';
import { spectraClassNames } from '@spectra-helpers';
import RenderSVG from '@spectra-helpers/render-svg';
import {
	getResponsivePreviewCss,
	iconDimensionStyles,
	inheritResponsiveKey,
	resolveInheritedResponsiveValue,
} from '@spectra-helpers/responsive-preview';

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
		clientId,
		context: {
			'spectra/list/listType': listType,
			'spectra/list/iconSize': inheritedIconSize,
			'spectra/list/style': listStyleAttr,
			'spectra/list/iconName': inheritedIcon,
			'spectra/list/flipForRTL': inheritedFlipForRTL,
			'spectra/list/rotation': inheritedRotation,
			'spectra/list-child-item/index': contextItemIndex,
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
		backgroundColor,
		backgroundColorHover,
		backgroundGradient,
		backgroundGradientHover,
		flipForRTL,
		rotation,
	} = attributes;

	/*
	 * Configuration for the useSpectraStyles hook.
	 *
	 * The icon paints its OWN colour only. It used to resolve a three-level
	 * inheritance — icon, then list-child-item, then list — and paint the result
	 * as `--spectra-text-color`, which carried `.spectra-text-color` with it. That
	 * class sets an explicit `color`, so an icon that had merely INHERITED a
	 * colour stopped inheriting: the value it copied was the parent's ROOT
	 * attribute, which holds one colour and no viewport states, so a list with
	 * `@tablet`/`@mobile` colours moved everything except its icons. Measured on
	 * a list coloured error/info/vivid-green-cyan: at Tablet and Mobile the list
	 * text followed and the icon stayed on the base colour.
	 *
	 * Both parents are DOM ancestors of the icon, so the cascade delivers their
	 * colour — including their per-device colour, and the hover colour through
	 * the `:not(.spectra-text-color)` rule in this block's stylesheet, which the
	 * copied class used to switch off. `controller.php` has always read the
	 * icon's own attribute here, which is why the front end was already right;
	 * this makes the editor agree with it.
	 */
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor', value: backgroundColor},
		{ key: 'backgroundColorHover', value: backgroundColorHover },
		{ key: 'backgroundGradient', value: backgroundGradient },
		{ key: 'backgroundGradientHover', value: backgroundGradientHover },
	];
	
	// Use block attributes if set, otherwise fall back to inherited context
	const finalIcon = icon || inheritedIcon || 'circle';
	/*
	 * The inherited size comes from block context, which carries the parent's
	 * ROOT attribute — the last-edited device's value — so an inheriting icon
	 * previewed that one value at every breakpoint. Resolve it from the parent's
	 * `style` for the previewed device, and let the bands the icon does not size
	 * itself inherit the parent's bands in the preview CSS.
	 */
	const previewDevice = useSelect( ( select ) => select( 'core/editor' )?.getDeviceType?.(), [] );
	const resolvedInheritedSize = resolveInheritedResponsiveValue( listStyleAttr, 'iconSize', previewDevice, inheritedIconSize );
	const finalIconSize = iconSize || resolvedInheritedSize || '10px';

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes: { ...attributes, style: inheritResponsiveKey( attributes.style, 'iconSize', listStyleAttr, 'iconSize' ) },
		blockName: 'spectra/list-child-icon',
		producers: [ ( attrs ) => iconDimensionStyles( attrs.iconSize || '10px' ) ],
	} );
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
		if ( !num ) {return '';}
		
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
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			{IconContent}
		</span>
	);
};

export default memo( Render );
