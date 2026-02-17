/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';

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
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { context, attributes } = props;

	const { number: contextNumber = undefined, role = 'timer' } = context;
	const { number } = attributes;

	// Configuration for the useSpectraStyles hook - memoized for stability.
	const config = useMemo(
		() => [
			{ key: 'numberColor' },
			{ key: 'numberColorHover' },
			{ key: 'backgroundColor' },
			{ key: 'backgroundColorHover' },
			{ key: 'backgroundGradient' },
			{ key: 'backgroundGradientHover' },
		],
		[]
	);

	// Memoize the formatted number to prevent unnecessary re-renders
	const formattedNumber = useMemo( () => {
		const finalNumber = contextNumber !== undefined ? contextNumber : number;
		return Number.isFinite( finalNumber ) && finalNumber < 10 ? `0${ finalNumber }` : finalNumber;
	}, [ contextNumber, number ] );

	// Generate styles and class names - only recalculate when attributes change.
	const { style, classNames } = useSpectraStyles( attributes, config );

	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
		role,
	} );

	return <div { ...blockProps }>{ formattedNumber }</div>;
};

export default memo( Render );
