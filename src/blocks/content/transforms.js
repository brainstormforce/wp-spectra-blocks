/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Transforms for the Content block.
 * 
 * Note: The following properties are NOT transferred to core blocks as they don't support them natively:
 * - Text Shadow properties (textShadowColor, textShadowBlur, etc.)
 * - Box Shadow properties (border.shadow)
 * - Hover properties (textColorHover, backgroundColorHover, etc.)
 * 
 * Animation properties ARE transferred as they are supported via the Spectra Animation Extension.
 * Typography and border properties are also transferred as they are supported by core blocks.
 * These properties are preserved when transforming FROM core blocks TO Spectra Content blocks.
 */
const transforms = {
	from: [
		{
			type: 'block',
			blocks: [ 'core/paragraph' ],
			transform: ( { 
				content, 
				align, 
				fontSize, 
				fontFamily,
				fontWeight,
				fontStyle,
				style, 
				textColor, 
				backgroundColor, 
				borderColor,
				backgroundGradient,
				dropCap,
				spectraAnimationType,
				spectraAnimationTime,
				spectraAnimationDelay,
				spectraAnimationEasing,
				spectraAnimationOnce,
			} ) => {
				// Build typography object conditionally to avoid overwriting existing values with undefined.
				const typographyFromAttributes = {};
				if ( align !== undefined ) typographyFromAttributes.textAlign = align;
				if ( fontSize !== undefined ) typographyFromAttributes.fontSize = fontSize;
				if ( fontFamily !== undefined ) typographyFromAttributes.fontFamily = fontFamily;
				if ( fontWeight !== undefined ) typographyFromAttributes.fontWeight = fontWeight;
				if ( fontStyle !== undefined ) typographyFromAttributes.fontStyle = fontStyle;

				// Preserve all attributes that are compatible
				const transformedAttributes = {
					text: content,
					tagName: 'p',
					style: {
						...style,
						typography: {
							...style?.typography,
							...typographyFromAttributes,
						},
					},
					textColor,
					backgroundColor,
					backgroundGradient,
					borderColor,
				};

				// Add animation attributes if they exist.
				if ( spectraAnimationType ) {
					transformedAttributes.spectraAnimationType = spectraAnimationType;
				}
				if ( spectraAnimationTime !== undefined ) {
					transformedAttributes.spectraAnimationTime = spectraAnimationTime;
				}
				if ( spectraAnimationDelay !== undefined ) {
					transformedAttributes.spectraAnimationDelay = spectraAnimationDelay;
				}
				if ( spectraAnimationEasing ) {
					transformedAttributes.spectraAnimationEasing = spectraAnimationEasing;
				}
				if ( spectraAnimationOnce !== undefined ) {
					transformedAttributes.spectraAnimationOnce = spectraAnimationOnce;
				}

				// Add dropCap if it exists.
				if ( dropCap ) {
					transformedAttributes.dropCap = dropCap;
				}

				return createBlock( 'spectra/content', transformedAttributes );
			},
		},
		{
			type: 'block',
			blocks: [ 'core/heading' ],
			transform: ( { 
				content, 
				level, 
				align, 
				fontSize, 
				fontFamily,
				fontWeight,
				fontStyle,
				style, 
				textColor, 
				backgroundColor, 
				borderColor,
				backgroundGradient,
				spectraAnimationType,
				spectraAnimationTime,
				spectraAnimationDelay,
				spectraAnimationEasing,
				spectraAnimationOnce,
			} ) => {
				// Build typography object conditionally to avoid overwriting existing values with undefined.
				const typographyFromAttributes = {};
				if ( align !== undefined ) typographyFromAttributes.textAlign = align;
				if ( fontSize !== undefined ) typographyFromAttributes.fontSize = fontSize;
				if ( fontFamily !== undefined ) typographyFromAttributes.fontFamily = fontFamily;
				if ( fontWeight !== undefined ) typographyFromAttributes.fontWeight = fontWeight;
				if ( fontStyle !== undefined ) typographyFromAttributes.fontStyle = fontStyle;

				// Preserve all attributes that are compatible.
				const transformedAttributes = {
					text: content,
					tagName: `h${ level || 2 }`,
					style: {
						...style,
						typography: {
							...style?.typography,
							...typographyFromAttributes,
						},
					},
					textColor,
					backgroundColor,
					backgroundGradient,
					borderColor,
				};

				// Add animation attributes if they exist.
				if ( spectraAnimationType ) {
					transformedAttributes.spectraAnimationType = spectraAnimationType;
				}
				if ( spectraAnimationTime !== undefined ) {
					transformedAttributes.spectraAnimationTime = spectraAnimationTime;
				}
				if ( spectraAnimationDelay !== undefined ) {
					transformedAttributes.spectraAnimationDelay = spectraAnimationDelay;
				}
				if ( spectraAnimationEasing ) {
					transformedAttributes.spectraAnimationEasing = spectraAnimationEasing;
				}
				if ( spectraAnimationOnce !== undefined ) {
					transformedAttributes.spectraAnimationOnce = spectraAnimationOnce;
				}

				return createBlock( 'spectra/content', transformedAttributes );
			},
		},
	],
	
	to: [
		{
			type: 'block',
			blocks: [ 'core/paragraph' ],
			isMatch: ( { tagName } ) => {
				return ! tagName || tagName === 'p' || tagName === 'div' || tagName === 'span';
			},
			transform: ( { 
				text, 
				style = {}, 
				textColor, 
				backgroundColor, 
				backgroundGradient,
				borderColor,
				dropCap,
				fontFamily,
				spectraAnimationType,
				spectraAnimationTime,
				spectraAnimationDelay,
				spectraAnimationEasing,
				spectraAnimationOnce,
			} ) => {
				const typography = style.typography || {};



				// Clean style object - remove properties that core blocks don't support.
				const cleanStyle = { ...style };
				
				// Remove text shadow properties.
				delete cleanStyle.textShadow;
				
				// Remove box shadow properties (core blocks don't support these).
				delete cleanStyle.shadow;
				
				// Remove border shadow properties.
				if ( cleanStyle.border ) {
					delete cleanStyle.border.shadow;
				}
				
				// Clean typography object.
				if ( cleanStyle.typography ) {
					delete cleanStyle.typography.textShadow;
					// Remove empty typography object
					if ( Object.keys( cleanStyle.typography ).length === 0 ) {
						delete cleanStyle.typography;
					}
				}

				// Prepare attributes for core/paragraph - only supported properties.
				const transformedAttributes = {
					content: text,
					style: cleanStyle,
				};

				// Add core-supported color properties.
				if ( textColor ) {
					transformedAttributes.textColor = textColor;
				}
				if ( backgroundColor ) {
					transformedAttributes.backgroundColor = backgroundColor;
				}
				if ( backgroundGradient ) {
					transformedAttributes.backgroundGradient = backgroundGradient;
				}
				if ( borderColor ) {
					transformedAttributes.borderColor = borderColor;
				}

				// Add typography properties if present.
				if ( typography.textAlign ) {
					transformedAttributes.align = typography.textAlign;
				}
				
				// Font family: Check both style.typography.fontFamily and top-level fontFamily.
				const fontFamilyValue = typography.fontFamily || fontFamily;
				if ( fontFamilyValue ) {
					transformedAttributes.fontFamily = fontFamilyValue;
				}
				
				if ( typography.fontWeight ) {
					transformedAttributes.fontWeight = typography.fontWeight;
				}
				if ( typography.fontStyle ) {
					transformedAttributes.fontStyle = typography.fontStyle;
				}

				// Add animation attributes if they exist (supported via Spectra Animation Extension).
				if ( spectraAnimationType ) {
					transformedAttributes.spectraAnimationType = spectraAnimationType;
				}
				if ( spectraAnimationTime !== undefined ) {
					transformedAttributes.spectraAnimationTime = spectraAnimationTime;
				}
				if ( spectraAnimationDelay !== undefined ) {
					transformedAttributes.spectraAnimationDelay = spectraAnimationDelay;
				}
				if ( spectraAnimationEasing ) {
					transformedAttributes.spectraAnimationEasing = spectraAnimationEasing;
				}
				if ( spectraAnimationOnce !== undefined ) {
					transformedAttributes.spectraAnimationOnce = spectraAnimationOnce;
				}

				// Add dropCap if it exists and is supported by paragraphs.
				if ( dropCap ) {
					transformedAttributes.dropCap = dropCap;
				}

				return createBlock( 'core/paragraph', transformedAttributes );
			},
		},
		{
			type: 'block',
			blocks: [ 'core/heading' ],
			isMatch: ( { tagName } ) => {
				return tagName && tagName.match( /^h[1-6]$/ );
			},
			transform: ( { 
				text, 
				tagName, 
				style = {}, 
				textColor, 
				backgroundColor, 
				backgroundGradient,
				borderColor,
				fontFamily,
				spectraAnimationType,
				spectraAnimationTime,
				spectraAnimationDelay,
				spectraAnimationEasing,
				spectraAnimationOnce,
			} ) => {
				const level = tagName ? parseInt( tagName.replace( 'h', '' ) ) : 2;
				const typography = style.typography || {};



				// Clean style object - remove properties that core blocks don't support.
				const cleanStyle = { ...style };
				
				// Remove text shadow properties
				delete cleanStyle.textShadow;
				
				// Remove box shadow properties (core blocks don't support these).
				delete cleanStyle.shadow;
				
				// Remove border shadow properties
				if ( cleanStyle.border ) {
					delete cleanStyle.border.shadow;
				}
				
				// Clean typography object
				if ( cleanStyle.typography ) {
					delete cleanStyle.typography.textShadow;
					// Remove empty typography object.
					if ( Object.keys( cleanStyle.typography ).length === 0 ) {
						delete cleanStyle.typography;
					}
				}

				// Prepare attributes for core/heading - only supported properties.
				const transformedAttributes = {
					content: text,
					level,
					style: cleanStyle,
				};

				// Add core-supported color properties.
				if ( textColor ) {
					transformedAttributes.textColor = textColor;
				}
				if ( backgroundColor ) {
					transformedAttributes.backgroundColor = backgroundColor;
				}
				if ( backgroundGradient ) {
					transformedAttributes.backgroundGradient = backgroundGradient;
				}
				if ( borderColor ) {
					transformedAttributes.borderColor = borderColor;
				}

				// Add typography properties if present.
				if ( typography.textAlign ) {
					transformedAttributes.align = typography.textAlign;
				}
				
				// Font family: Check both style.typography.fontFamily and top-level fontFamily.
				const fontFamilyValue = typography.fontFamily || fontFamily;
				if ( fontFamilyValue ) {
					transformedAttributes.fontFamily = fontFamilyValue;
				}
				
				if ( typography.fontWeight ) {
					transformedAttributes.fontWeight = typography.fontWeight;
				}
				if ( typography.fontStyle ) {
					transformedAttributes.fontStyle = typography.fontStyle;
				}

				// Add animation attributes if they exist (supported via Spectra Animation Extension).
				if ( spectraAnimationType ) {
					transformedAttributes.spectraAnimationType = spectraAnimationType;
				}
				if ( spectraAnimationTime !== undefined ) {
					transformedAttributes.spectraAnimationTime = spectraAnimationTime;
				}
				if ( spectraAnimationDelay !== undefined ) {
					transformedAttributes.spectraAnimationDelay = spectraAnimationDelay;
				}
				if ( spectraAnimationEasing ) {
					transformedAttributes.spectraAnimationEasing = spectraAnimationEasing;
				}
				if ( spectraAnimationOnce !== undefined ) {
					transformedAttributes.spectraAnimationOnce = spectraAnimationOnce;
				}

				return createBlock( 'core/heading', transformedAttributes );
			},
		},
	],
	
};

export default transforms; 