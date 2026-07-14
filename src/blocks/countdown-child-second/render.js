/**
 * External dependencies.
 */
import { useBlockProps, useInnerBlocksProps, BlockContextProvider } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useCountdownStyles } from '../countdown/use-countdown-styles';
import TEMPLATE from './template';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes, context } = props;
	const countdown = context?.countdown || {};
	const { seconds, showSeconds, secondsLabel, isExpired, showLabels, editorInnerBlocksPreview = false } = countdown;

	// Configuration for the useSpectraStyles hook.
	const config = useMemo(
		() => [
			{ key: 'textColor' },
			{ key: 'textColorHover' },
			{ key: 'backgroundColor' },
			{ key: 'backgroundColorHover' },
			{ key: 'backgroundGradient' },
			{ key: 'backgroundGradientHover' },
		],
		[]
	);

	// Generate styles and class names.
	const { style, classNames } = useCountdownStyles( attributes, config );

	// Use the block props.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	// Context for number and label inner blocks.
	const staticContext = useMemo(
		() => ( {
			label: secondsLabel || __( 'Seconds', 'spectra-blocks' ),
			showLabels,
		} ),
		[ secondsLabel, showLabels ]
	);

	const blockContext = useMemo(
		() => ( {
			...staticContext,
			number: isExpired ? 0 : seconds,
		} ),
		[ staticContext, seconds, isExpired ]
	);

	// Return null if the editorInnerBlocksPreview (Expiry Mode) is true or showSeconds is false.
	if ( editorInnerBlocksPreview || ! showSeconds ) {return null;}

	return (
		<BlockContextProvider value={ blockContext }>
			<div { ...innerBlocksProps } />
		</BlockContextProvider>
	);
};

export default memo( Render );
