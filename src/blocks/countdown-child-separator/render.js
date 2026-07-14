/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * Mapping of time unit child block names to their visibility attribute keys.
 */
const TIME_UNIT_BLOCKS = {
	'spectra/countdown-child-day': 'showDays',
	'spectra/countdown-child-hour': 'showHours',
	'spectra/countdown-child-minute': 'showMinutes',
	'spectra/countdown-child-second': 'showSeconds',
};

/**
 * The Editor Block render function for the separator block.
 *
 * Determines whether a separator should be visible based on the visibility
 * of adjacent time unit blocks, and renders the separator if appropriate.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element|null} The rendered block or null if not visible.
 */
const Render = ( props ) => {
	const { clientId, attributes, setAttributes, context } = props;

	// Get visibility flags and settings passed from the parent countdown block context.
	const countdown = context?.countdown || {};
	const {
		showDays,
		showHours,
		showMinutes,
		showSeconds,
		showSeparator,
		separatorType,
		editorInnerBlocksPreview = false,
	} = countdown;

	// Use the manually set separator text or fall back to the separator type (e.g., ':').
	const { text = separatorType || ':' } = attributes;

	// Cache block structure queries to prevent expensive recalculations on every timer tick
	const blockStructure = useSelect(
		( select ) => {
			const { getBlock, getBlockIndex, getBlockParentsByBlockName } = select( 'core/block-editor' );
			const parentId = getBlockParentsByBlockName( clientId, 'spectra/countdown', true )[ 0 ];
			const innerBlocks = getBlock( parentId )?.innerBlocks || [];
			const currentIndex = getBlockIndex( clientId, parentId );

			return { parentId, innerBlocks, currentIndex };
		},
		[ clientId ]
	); // Only re-run when clientId changes, not on every timer tick

	// Determine whether this separator block should be shown.
	const shouldShowSeparator = useMemo( () => {
		const { innerBlocks, currentIndex } = blockStructure;

		if ( ! innerBlocks.length ) {return false;}

		// Find if this block is a separator and locate its index in the list.
		const isSeparator = ( block ) => block.name === 'spectra/countdown-child-separator';
		const separatorIndex = innerBlocks.findIndex(
			( block, index ) => index === currentIndex && isSeparator( block )
		);

		// If not found, this is not a valid separator.
		if ( separatorIndex === -1 ) {return false;}

		// Look backward to find the last visible time unit before this separator.
		let lastVisibleUnitBefore = null;
		for ( let i = separatorIndex - 1; i >= 0; i-- ) {
			const block = innerBlocks[ i ];
			if ( TIME_UNIT_BLOCKS[ block.name ] && countdown[ TIME_UNIT_BLOCKS[ block.name ] ] ) {
				lastVisibleUnitBefore = block;
				break;
			}
		}

		// Look forward to find the next visible time unit after this separator.
		let nextVisibleUnitAfter = null;
		for ( let i = separatorIndex + 1; i < innerBlocks.length; i++ ) {
			const block = innerBlocks[ i ];
			if ( TIME_UNIT_BLOCKS[ block.name ] && countdown[ TIME_UNIT_BLOCKS[ block.name ] ] ) {
				nextVisibleUnitAfter = block;
				break;
			}
		}

		// Determines whether this is the first separator between the two visible units.
		const isFirstSeparatorBetweenUnits = () => {
			if ( ! lastVisibleUnitBefore || ! nextVisibleUnitAfter ) {return false;}

			// Check if there's another separator between them
			const lastUnitIndex = innerBlocks.findIndex( ( b ) => b.clientId === lastVisibleUnitBefore.clientId );
			const nextUnitIndex = innerBlocks.findIndex( ( b ) => b.clientId === nextVisibleUnitAfter.clientId );

			for ( let i = lastUnitIndex + 1; i < nextUnitIndex; i++ ) {
				// If there's an earlier separator between the same units, this isn't the first.
				if ( isSeparator( innerBlocks[ i ] ) && i < separatorIndex ) {
					return false; // Another separator exists before this one
				}
			}
			return true;
		};

		// Show the separator only if it is between two visible units and is the first one between them.
		const shouldShow = lastVisibleUnitBefore && nextVisibleUnitAfter && isFirstSeparatorBetweenUnits();

		return shouldShow;
	}, [ blockStructure, showDays, showHours, showMinutes, showSeconds ] );

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
	const { style, classNames } = useSpectraStyles( attributes, config );

	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	const displayText = useMemo( () => text || separatorType, [ text, separatorType ] );

	const handleTextChange = useMemo( () => ( value ) => setAttributes( { text: value } ), [ setAttributes ] );

	if ( editorInnerBlocksPreview || ! showSeparator || ! shouldShowSeparator ) {
		return null;
	}

	return (
		<RichText
			identifier="text"
			{ ...blockProps }
			tagName="div"
			placeholder=":"
			value={ displayText }
			onChange={ handleTextChange }
		/>
	);
};

export default memo( Render );
