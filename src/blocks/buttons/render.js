/**
 * External dependencies.
 */
import {
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const {
		attributes
	} = props;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	const customClassNames = [ 'wp-block-button' ];

		// Generate styles and class names.
		const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props with custom styles
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ['spectra/button'],
		template: [['spectra/button', { Placeholder: __( 'Add Text…', 'ultimate-addons-for-gutenberg' ) }]],
	} );

	return <div {...innerBlocksProps} />;
};

export default memo( Render );
