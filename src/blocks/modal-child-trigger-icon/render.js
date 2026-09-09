/**
 * External dependencies.
 */
import {
	useBlockProps,
} from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import {
	spectraClassNames,
} from '@spectra-helpers';
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
	const { attributes, context, clientId } = props;
	const {
		icon,
		size,
		rotation,
	} = attributes;

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	// The trigger button and close icon got this in the same round; this block
	// was left out (the directory name differs from the block name), so its Size
	// stayed frozen on the base value while the device switched.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/modal-child-icon',
		producers: [ ( attrs ) => iconDimensionStyles( attrs.size || '30px' ) ],
	} );

	const modalTrigger = context['spectra/modal/modalTrigger'];

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
		'icon' !== modalTrigger ? 'is-hidden' : '',
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style,
	} );

	return (
		<div { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<RenderSVG svg={ icon || 'up-right-from-square' } extraProps={ {
				width: size || '30px',
				height: size || '30px',
				style: {
					width: size || '30px',
					height: size || '30px',
					transform: ( rotation ) ? `rotate(${ rotation }deg)` : '',
				}
			} } />
		</div>
	);
};

export default memo( Render );
