/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';

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
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { context, attributes, clientId } = props;

	const { label = undefined, showLabels = true } = context;
	const { text } = attributes;

	// Configuration for the useSpectraStyles hook - memoized for stability.
	const config = useMemo(
		() => [
			{ key: 'textColor' },
			{ key: 'textColorHover' },
			{ key: 'backgroundColor' },
			{ key: 'backgroundColorHover' },
			{ key: 'backgroundGradient' },
			{ key: 'backgroundGradientHover' },
		],
		[]
	);

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );


	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	if ( ! showLabels || ! text ) {return null;}

	// Prioritize context label, fallback to attribute text
	const finalText = label !== undefined ? label : text || '';

	if ( ! finalText ) {return null;}

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/countdown-child-label',
		producers: [
			( attrs ) =>
				colourVarStyles( attrs, {
					backgroundColorHover: '--spectra-background-color-hover',
					backgroundGradientHover: '--spectra-background-gradient-hover',
					textColorHover: '--spectra-text-color-hover',
				} ),
		],
	} );

	return (
		<div { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			{ finalText }
		</div>
	);
};

export default memo( Render );
