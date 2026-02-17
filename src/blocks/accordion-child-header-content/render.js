/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { useOnEnter } from '@spectra-helpers/richtext';

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
			'spectra/accordion-child-header/headerElement': headerElement,
		} = {}
	} = props;

	const { text, tagName } = attributes;
	
	// When header is button, only span is allowed for valid HTML
	// When header is div, allow all supported tags
	const isHeaderDiv = headerElement === 'div';
	const effectiveTagName = isHeaderDiv ? ( tagName || 'span' ) : 'span';

	// Use the onEnter hook.
	const ref = useOnEnter( { clientId } );

	// Configuration for the useSpectraStyles hook - memoized to prevent recalculation
	const config = useMemo( () => [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	], [] ); // Empty dependency array since config is static

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	const blockProps = useBlockProps( {
		ref,
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<RichText
			identifier="text"
			{ ...blockProps }
			tagName={ effectiveTagName }
			placeholder={ __( 'Accordion Title', 'ultimate-addons-for-gutenberg' ) }
			value={ text }
			onChange={ ( value ) => setAttributes( { text: value } ) }
			onMerge={ mergeBlocks }
			onReplace={ onReplace }
			onRemove={ () => onReplace( [] ) }
		/>
	);
};

export default memo( Render );
