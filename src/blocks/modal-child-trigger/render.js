/**
 * External dependencies.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

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
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const { attributes, context } = props;
	
	// Get modalTrigger from context
	const { 'spectra/modal/modalTrigger': modalTrigger } = context;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Hide trigger if modalTrigger is not button, icon, or text
	const shouldHide = modalTrigger && !['button', 'icon', 'text'].includes( modalTrigger );
	
	const customClassNames = [ 
		'spectra-modal-trigger', 
		'wp-block-button',
		shouldHide ? 'is-hidden' : '',
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			...style,
			cursor: 'pointer',
		},
	} );

	// Configure props for inner blocks with allowed types and a locked template.
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ['spectra/modal-child-button', 'spectra/modal-child-icon', 'spectra/modal-child-content'],
		template: [
			['spectra/modal-child-button'],
			['spectra/modal-child-icon'],
			['spectra/modal-child-content'],
		],
		templateLock: 'all',
	} );

	return (
		<div {...innerBlocksProps}>
			{innerBlocksProps.children}
		</div>
	);
};

export default memo( Render );
