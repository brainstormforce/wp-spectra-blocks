/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InnerBlocks,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const {
		clientId,
		context: {
			// Context from the parent item child in the accordion.
			'spectra/accordion/item/isActiveInEditor': isActiveInEditor,
			// Contexts from the rool level accordion.
			'spectra/accordion/flipForRTL': templateFlipForRTL,
			'spectra/accordion/flipForRTLSecondary': templateFlipForRTLSecondary,
			'spectra/accordion/textColorSecondary': inheritedTextColorSecondary,
			'spectra/accordion/textColorHoverSecondary': inheritedTextColorHoverSecondary,
			'spectra/accordion/backgroundColorSecondary': inheritedBackgroundColor,
			'spectra/accordion/backgroundColorHoverSecondary': inheritedBackgroundColorHover,
			'spectra/accordion/backgroundGradientSecondary': inheritedBackgroundGradient,
			'spectra/accordion/backgroundGradientHoverSecondary': inheritedBackgroundGradientHover,
			// Contexts from the parent item child in the accordion.
			'spectra/accordion/item/textColorSecondary': inheritedItemTextColorSecondary,
			'spectra/accordion/item/textColorHoverSecondary': inheritedItemTextColorHoverSecondary,
			'spectra/accordion/item/backgroundColorSecondary': inheritedItemBackgroundColor,
			'spectra/accordion/item/backgroundColorHoverSecondary': inheritedItemBackgroundColorHover,
			'spectra/accordion/item/backgroundGradientSecondary': inheritedItemBackgroundGradient,
			'spectra/accordion/item/backgroundGradientHoverSecondary': inheritedItemBackgroundGradientHover,
		},
		attributes,
	} = props;

	// When importing the color attributes, if there's a color set from the header, or the root block, use that if needed.
	const {
		textColor = inheritedItemTextColorSecondary || inheritedTextColorSecondary,
		textColorHover = inheritedItemTextColorHoverSecondary || inheritedTextColorHoverSecondary,
		backgroundColor = inheritedItemBackgroundColor || inheritedBackgroundColor,
		backgroundColorHover = inheritedItemBackgroundColorHover || inheritedBackgroundColorHover,
		backgroundGradient = inheritedItemBackgroundGradient || inheritedBackgroundGradient,
		backgroundGradientHover = inheritedItemBackgroundGradientHover || inheritedBackgroundGradientHover,
	} = attributes;

	// Use useSelect hook for better performance instead of direct select() call
	const { hasChildBlocks } = useSelect( ( select ) => {
		const { getBlockOrder } = select( blockEditorStore );
		return {
			hasChildBlocks: getBlockOrder( clientId ).length > 0,
		};
	}, [ clientId ] );

	// Configuration for the useSpectraStyles hook - memoized to prevent recalculation
	const config = useMemo( () => [
		{ key: 'textColor', value: textColor },
		{ key: 'textColorHover', value: textColorHover },
		{ key: 'backgroundColor', value: backgroundColor },
		{ key: 'backgroundColorHover', value: backgroundColorHover },
		{ key: 'backgroundGradient', value: backgroundGradient },
		{ key: 'backgroundGradientHover', value: backgroundGradientHover },
	], [ textColor, textColorHover, backgroundColor, backgroundColorHover, backgroundGradient, backgroundGradientHover ] );

	// Custom class names.
	const customClassNames = [
		isActiveInEditor && 'is-active',
	].filter( Boolean );

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	// Memoize the template to prevent unnecessary re-creation
	const headerTemplate = useMemo( () => [
		[
			'spectra/accordion-child-header-icon',
			{
				flipForRTL: templateFlipForRTL,
				flipForRTLSecondary: templateFlipForRTLSecondary,
			},
		],
		[
			'spectra/accordion-child-header-content',
			{
				tagName: 'span',
				placeholder: __( 'Accordion Title', 'ultimate-addons-for-gutenberg' ),
			},
		],
	], [ templateFlipForRTL, templateFlipForRTLSecondary ] );

	// Note: On the front-end this element is a button. In the editor it needs to be a div so that the block appender can render inside it.
	return (
		<div { ...blockProps }>
			<InnerBlocks
				template={ headerTemplate }
				orientation="horizontal"
				renderAppender={ hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender }
			/>
		</div>
	);
};

export default memo( Render );
