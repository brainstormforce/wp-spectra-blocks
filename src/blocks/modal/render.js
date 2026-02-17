import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const { attributes } = props;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	const customClassNames = [ 'spectra-modal-wrapper' ];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			...style,
			position: 'relative',
		},
	} );

	return (
		<div {...blockProps}>
			<InnerBlocks
				template={[
					['spectra/modal-child-trigger', {}],
					['spectra/modal-popup', {}]
				]}
				renderAppender={ false }
			/>
		</div>
	);
};

export default memo( Render );
