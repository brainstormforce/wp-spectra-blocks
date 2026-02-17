/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element}     The rendered block.
 */
const Render = ( props ) => {
	const { attributes } = props;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{
			key: 'backgroundColorTertiary',
			cssVar: '--spectra-background-color',
			className: 'spectra-background-color',
		},
		{
			key: 'backgroundGradientTertiary',
			cssVar: '--spectra-background-gradient',
			className: 'spectra-background-gradient',
		},
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks renderAppender={ false } />
		</div>
	);
};

export default memo( Render );
