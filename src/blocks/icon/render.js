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

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes } = props;

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
