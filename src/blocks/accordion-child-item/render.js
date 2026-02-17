/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks, store as blockEditorStore } from '@wordpress/block-editor';
import { memo, useEffect, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * Allowed blocks.
 */
const allowedBlocks = [ 'spectra/accordion-child-header', 'spectra/accordion-child-details' ];

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
		setAttributes,
		attributes,
		context: {
			'spectra/accordion/activeAccordion': activeAccordion,
			'spectra/accordion/textColor': inheritedTextColor,
			'spectra/accordion/textColorHover': inheritedTextColorHover,
			'spectra/accordion/backgroundColor': inheritedBackgroundColor,
			'spectra/accordion/backgroundColorHover': inheritedBackgroundColorHover,
			'spectra/accordion/backgroundGradient': inheritedBackgroundGradient,
			'spectra/accordion/backgroundGradientHover': inheritedBackgroundGradientHover,
		},
	} = props;

	const {
		textColor = inheritedTextColor,
		textColorHover = inheritedTextColorHover,
		backgroundColor = inheritedBackgroundColor,
		backgroundColorHover = inheritedBackgroundColorHover,
		backgroundGradient = inheritedBackgroundGradient,
		backgroundGradientHover = inheritedBackgroundGradientHover,
	} = attributes;

	// Effect to set the current accordion item as active whenever required - optimized
	useEffect( () => {
		const isCurrentlyActive = clientId === activeAccordion || !! attributes.openByDefault;
		// Only update if the state actually changed to prevent unnecessary re-renders
		if ( attributes.isActiveInEditor !== isCurrentlyActive ) {
			setAttributes( { isActiveInEditor: isCurrentlyActive } );
		}
	}, [ activeAccordion, clientId, attributes.isActiveInEditor, attributes.openByDefault, setAttributes ] );

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
		( textColor || textColorHover || inheritedTextColor || inheritedTextColorHover ) && 'has-text-color',
		attributes.isActiveInEditor && 'is-active',
	].filter( Boolean ); // Filter out falsy values.

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Check if the current block has children using useSelect for better performance.
	const { hasChildBlocks } = useSelect( ( select ) => {
		const { getBlockOrder } = select( blockEditorStore );
		return {
			hasChildBlocks: getBlockOrder( clientId ).length > 0,
		};
	}, [ clientId ] );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				template={ [
					[
						'spectra/accordion-child-header',
						{
							style: {
								spacing: {
									padding: {
										top: 'var:preset|spacing|20',
										right: 'var:preset|spacing|20',
										bottom: 'var:preset|spacing|20',
										left: 'var:preset|spacing|20',
									},
									blockGap: 'var:preset|spacing|20',
								},
							},
						},
					],
					[
						'spectra/accordion-child-details',
						{
							style: {
								spacing: {
									padding: {
										top: 'var:preset|spacing|20',
										right: 'var:preset|spacing|20',
										bottom: 'var:preset|spacing|20',
										left: 'var:preset|spacing|20',
									},
									blockGap: 'var:preset|spacing|20',
								},
							},
						},
					],
				] }
				renderAppender={ hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender }
			/>
		</div>
	);
};

export default memo( Render );
