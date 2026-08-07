/**
 * External dependencies.
 */
import { RichText, useBlockEditingMode, useBlockProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { removeAnchorTag, spectraClassNames } from '@spectra-helpers';
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
			placeholder={ __( 'Get started by writing something!', 'spectra-blocks' ) }
			value={ text }
			onChange={ ( value ) => setAttributes( { text: removeAnchorTag( value ) } ) }
			onMerge={ mergeBlocks }
			onReplace={ onReplace }
			onRemove={ () => onReplace( [] ) }
			withoutInteractiveFormatting
		/>
	);
};

export default memo( Render );
