/**
 * External dependencies.
 */
import {
	InnerBlocks,
	useBlockProps,
} from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import {
	colourVarStyles,
	getResponsivePreviewCss,
} from '@spectra-helpers/responsive-preview';

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
		attributes,
	} = props;


	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/icons',
		producers: [
			( attrs ) =>
				colourVarStyles( attrs, {
					backgroundColorHover: '--spectra-background-color-hover',
					backgroundGradientHover: '--spectra-background-gradient-hover',
				} ),
		],
	} );

	// Parent block properties.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style
	} );

	// Render the icons
	return (
		<div
			{ ...blockProps }
		>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<InnerBlocks
				allowedBlocks={['spectra/icon']}
				template={[['spectra/icon']]}
			/>
		</div>
	);
};

export default memo( Render );