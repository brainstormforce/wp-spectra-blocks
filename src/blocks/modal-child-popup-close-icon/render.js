/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';
import { getResponsivePreviewCss, iconDimensionStyles } from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {

	const { attributes, clientId } = props;

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/modal-child-popup-close-icon',
		producers: [ ( attrs ) => iconDimensionStyles( attrs.size || '25px' ) ],
	} );

	const {
		context: {
			'spectra/modal/closeIconPosition': closeIconPosition,
		},
		attributes: {
			icon,
			size,
			rotation,
		},
	} = props

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Custom class names.
	const customClassNames = [
		'spectra-modal-popup-close',
		closeIconPosition
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps }   data-wp-interactive="spectra/modal"
		data-wp-on--click="actions.updateToggle">
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<RenderSVG svg={ icon || 'xmark' } extraProps={ {
				width: size || '25px',
				height: size || '25px',
				style: {
					width: size || '25px',
					height: size || '25px',
					transform: ( rotation ) ? `rotate(${ rotation }deg)` : '',
				}
			} } />
		</div>
	);
};

export default memo( Render );
