/**
 * External dependencies.
 */
import {
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
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
	const {
		attributes,
		context: {
			'spectra/modal/isVisible': isVisible,
			'spectra/modal/modalPosition': modalPosition,
			'spectra/modal/openModalAs': openModalAs,
			'spectra/modal/appearEffect': appearEffect,
			'spectra/modal/closeIconPosition': closeIconPosition,
		},
	} = props;

	const isPro = uagb_blocks_info.spectra_pro_status;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'backgroundColor' },
		{ key: 'backgroundGradient' },
	];

	// Custom class names for modal popup.
	const customClassNames = [
		appearEffect ? appearEffect : '',
		'spectra-modal-popup',
		isVisible === true ? 'active' : '',
		isPro ? `spectra-modal-type-${ openModalAs }` : '',
		( isPro && 'popup' === openModalAs ) ? `spectra-modal-position-${ modalPosition }` : '',
	].filter( Boolean ); // Filter out empty values

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Check if window positioning is active.
	const isWindowPositioning = 'window-top-left' === closeIconPosition || 'window-top-right' === closeIconPosition;

	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style,
	} );

	// Always use the same JSX structure to prevent re-render glitches when
	// switching icon positions. Use useInnerBlocksProps for consistent rendering.
	// For window positioning, use 'spectra-modal-window-wrap' class which has position: static
	// so the close icon positions relative to the outer .spectra-modal-popup container.
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: isWindowPositioning ? 'spectra-modal-window-wrap' : 'spectra-modal-popup-wrap',
		},
		{
			template: [
				[ 'spectra/modal-child-popup-close-icon' ],
				[ 'spectra/modal-popup-content' ],
			],
			renderAppender: false,
		}
	);

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps } />
		</div>
	);
};

export default memo( Render );
