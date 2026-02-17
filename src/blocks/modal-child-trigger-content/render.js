/**
 * External dependencies.
 */
import { RichText, useBlockEditingMode, useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import { select } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { useOnEnter, useOnDelete } from '@spectra-helpers/richtext';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { 
		clientId,
		mergeBlocks,
		onReplace,
		setAttributes,
		attributes,
		context: {
			'spectra/modal/modalTrigger': modalTrigger,
		},
	} = props;

	const { tagName, text, dropCap } = attributes;

	// Use the onEnter hook.
	const enterRef = useOnEnter( { clientId } );
	const deleteRef = useOnDelete( { clientId } );

	// Get the alignment.
	const align = props.attributes?.style?.typography?.textAlign || '';

	// Check if the drop cap is disabled.
	const hasDropCapDisabled = align === ( isRTL() ? 'left' : 'right' ) || align === 'center' || tagName === 'span';

	// Get the block editing mode.
	const blockEditingMode = useBlockEditingMode();

	/**
	 * Dynamically retrieve all registered RichText format types from the
	 * WordPress rich-text store and exclude the 'core/link' format.
	 *
	 * This ensures that all other formatting options (like bold, italic, etc.)
	 * remain available in the RichText toolbar, while preventing users from
	 * inserting or editing links.
	 */
	const allowedFormats = useMemo( () => {
		const allFormats = select( 'core/rich-text' ).getFormatTypes() || [];
	
		return allFormats
			.map( ( format ) => format.name )
			.filter( ( name ) => name !== 'core/link' && name !== 'core/footnote' );
	}, [] );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	const customClassNames = [ dropCap && ! hasDropCapDisabled && blockEditingMode && 'has-drop-cap',
		'text' !== modalTrigger ? 'is-hidden': '',
	 ].filter(
		Boolean
	); // Filter out falsy values.

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	const blockProps = useBlockProps( {
		ref: ( element ) => {
			enterRef( element );
			deleteRef( element );
		},
		style,
		// Add the block class names.
		className: spectraClassNames( classNames ),
	} );

	return (
		<RichText
			identifier="text"
			{ ...blockProps }
			tagName={ tagName || 'p' }
			placeholder={ __( 'Get started by writing something!', 'ultimate-addons-for-gutenberg' ) }
			value={ text }
			onChange={ ( value ) => setAttributes( { text: value } ) }
			onMerge={ mergeBlocks }
			onReplace={ onReplace }
			onRemove={ () => onReplace( [] ) }
			allowedFormats={ allowedFormats }
		/>
	);
};

export default memo( Render );
