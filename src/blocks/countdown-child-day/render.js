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
	const { days, showDays, showLabels, daysLabel, isExpired, editorInnerBlocksPreview = false } = countdown;

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
			label: daysLabel || __( 'Days', 'spectra-blocks' ),
			showLabels,
		} ),
		[ daysLabel, showLabels ]
	);

	const blockContext = useMemo(
		() => ( {
			...staticContext,
			number: isExpired ? 0 : days,
		} ),
		[ staticContext, days, isExpired ]
	);

	// If not showing days or expired mode, return null.
	if ( editorInnerBlocksPreview || ! showDays ) return null;

	return (
		<BlockContextProvider value={ blockContext }>
			<div { ...innerBlocksProps } />
		</BlockContextProvider>
	);
};

export default memo( Render );
