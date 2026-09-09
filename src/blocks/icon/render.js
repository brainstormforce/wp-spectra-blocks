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
import { getResponsivePreviewCss } from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
/**
 * The icon's own dimensions, which land on its `svg` rather than on the block.
 *
 * Returned with a selector because the emitter scopes to the block element by
 * default, and `size` is painted on the child — see
 * `helpers/responsive-preview.js`. The selector is ` svg` rather than a class:
 * measured in the editor, `RenderSVG` leaves the element with no class here, and
 * the block contains exactly one svg. (The front end's own map uses
 * ` svg.spectra-icon`, which would match nothing in the editor.)
 *
 * The fallback mirrors the inline paint exactly, so a band and the base cannot
 * derive different values from the same attributes.
 *
 * @since 1.0.7
 * @param {Object} attrs The block's attributes, or a band's merge of them.
 * @return {Array} Selector-scoped style entries.
 */
export const getIconSizeStyles = ( attrs = {} ) => {
	const dimension = attrs.size || 'var(--spectra-icon-default-size, 48px)';

	return [
		{
			selector: ' svg',
			styles: { width: dimension, height: dimension },
		},
	];
};

const Render = ( props ) => {
	const { attributes, clientId } = props;

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/icon',
		producers: [ getIconSizeStyles ],
	} );

	const {
		icon,
		size,
		rotation,
		flipForRTL,
		linkURL,
	} = attributes;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	let WrapperTag = 'div';

	const blockPropsToUse = {
		style,
		className: spectraClassNames( classNames ),
	};

	if ( linkURL ) {
		WrapperTag = 'a';
		blockPropsToUse.href = 'JavaScript:void(0);';
	}

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( blockPropsToUse );


	return (
		<WrapperTag { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<RenderSVG
				svg={ icon || 'star' }
				needsRTL={ flipForRTL }
				extraProps={ {
					width: size || 'var(--spectra-icon-default-size, 48px)',
					height: size || 'var(--spectra-icon-default-size, 48px)',
					style: {
						width: size || 'var(--spectra-icon-default-size, 48px)',
						height: size || 'var(--spectra-icon-default-size, 48px)',
						transform: rotation ? `rotate(${ rotation }deg)` : '',
					},
				} }
			/>
		</WrapperTag>
	);
};

export default memo( Render );
