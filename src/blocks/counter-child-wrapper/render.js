/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';

/**
 * The render function for the Counter Child Wrapper block.
 *
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = () => {
	const blockProps = useBlockProps( {
		className: spectraClassNames( [], [
			'wp-block-spectra-counter-child-wrapper',
			'spectra-counter-child-wrapper',
		] ),
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		renderAppender: false,
	} );

	return <div { ...innerBlocksProps } />;
};

export default Render;

