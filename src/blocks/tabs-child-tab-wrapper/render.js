/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { useSpectraStyles } from '@spectra-hooks';
import { spectraClassNames } from '@spectra-helpers';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes } = props;

	const { backgroundColor, backgroundGradient } = attributes;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor', value: backgroundColor },
		{ key: 'backgroundGradient', value: backgroundGradient },
	];

	const customClassNames = [ 'wp-block-button' ];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles(
		attributes,
		config,
		customClassNames
	);

	// Use the block props, with the added CSS varialbes and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	// Render the tab wrapper.
	return (
		<div { ...blockProps }>
			<InnerBlocks renderAppender={ false } />
		</div>
	);
};

export default memo( Render );
