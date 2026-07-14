/**
 * External dependencies.
 */
import { RichText, useBlockEditingMode, useBlockProps, BlockControls } from '@wordpress/block-editor';
import { select } from '@wordpress/data';
import { memo, useEffect, useMemo, useCallback } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import { 
	ToolbarGroup, 
	ToolbarDropdownMenu 
} from '@wordpress/components';
import { helperIcons } from '@spectra-helpers/block-icons';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { useOnEnter, useOnDelete } from '@spectra-helpers/richtext';

// Static tag configuration - single source of truth.
// Mirrors $valid_tag_names in controller.php. Icons fall back to the
// closest semantic icon (p for inline, div for block-level).
const TAG_CONFIG = {
	// Headings
	h1: { label: __( 'Heading 1', 'spectra-blocks' ), icon: helperIcons.content.h1() },
	h2: { label: __( 'Heading 2', 'spectra-blocks' ), icon: helperIcons.content.h2() },
	h3: { label: __( 'Heading 3', 'spectra-blocks' ), icon: helperIcons.content.h3() },
	h4: { label: __( 'Heading 4', 'spectra-blocks' ), icon: helperIcons.content.h4() },
	h5: { label: __( 'Heading 5', 'spectra-blocks' ), icon: helperIcons.content.h5() },
	h6: { label: __( 'Heading 6', 'spectra-blocks' ), icon: helperIcons.content.h6() },
	// Text containers
	p: { label: __( 'Paragraph', 'spectra-blocks' ), icon: helperIcons.content.p() },
	div: { label: __( 'Div', 'spectra-blocks' ), icon: helperIcons.content.div() },
	span: { label: __( 'Span', 'spectra-blocks' ), icon: helperIcons.content.span() },
	// Semantic text containers
	blockquote: { label: __( 'Blockquote', 'spectra-blocks' ), icon: helperIcons.content.div() },
	address: { label: __( 'Address', 'spectra-blocks' ), icon: helperIcons.content.div() },
	cite: { label: __( 'Cite', 'spectra-blocks' ), icon: helperIcons.content.p() },
	time: { label: __( 'Time', 'spectra-blocks' ), icon: helperIcons.content.p() },
	label: { label: __( 'Label', 'spectra-blocks' ), icon: helperIcons.content.p() },
	figcaption: { label: __( 'Figcaption', 'spectra-blocks' ), icon: helperIcons.content.div() },
	caption: { label: __( 'Caption', 'spectra-blocks' ), icon: helperIcons.content.div() },
	legend: { label: __( 'Legend', 'spectra-blocks' ), icon: helperIcons.content.div() },
	dt: { label: __( 'DT', 'spectra-blocks' ), icon: helperIcons.content.div() },
	dd: { label: __( 'DD', 'spectra-blocks' ), icon: helperIcons.content.div() },
	// Inline semantic
	strong: { label: __( 'Strong', 'spectra-blocks' ), icon: helperIcons.content.p() },
	em: { label: __( 'Em', 'spectra-blocks' ), icon: helperIcons.content.p() },
	small: { label: __( 'Small', 'spectra-blocks' ), icon: helperIcons.content.p() },
	mark: { label: __( 'Mark', 'spectra-blocks' ), icon: helperIcons.content.p() },
	del: { label: __( 'Del', 'spectra-blocks' ), icon: helperIcons.content.p() },
	ins: { label: __( 'Ins', 'spectra-blocks' ), icon: helperIcons.content.p() },
	sub: { label: __( 'Sub', 'spectra-blocks' ), icon: helperIcons.content.p() },
	sup: { label: __( 'Sup', 'spectra-blocks' ), icon: helperIcons.content.p() },
	abbr: { label: __( 'Abbr', 'spectra-blocks' ), icon: helperIcons.content.p() },
	code: { label: __( 'Code', 'spectra-blocks' ), icon: helperIcons.content.p() },
	pre: { label: __( 'Pre', 'spectra-blocks' ), icon: helperIcons.content.div() },
	kbd: { label: __( 'Kbd', 'spectra-blocks' ), icon: helperIcons.content.p() },
	samp: { label: __( 'Samp', 'spectra-blocks' ), icon: helperIcons.content.p() },
	var: { label: __( 'Var', 'spectra-blocks' ), icon: helperIcons.content.p() },
	output: { label: __( 'Output', 'spectra-blocks' ), icon: helperIcons.content.p() },
	q: { label: __( 'Q', 'spectra-blocks' ), icon: helperIcons.content.p() },
	s: { label: __( 'S', 'spectra-blocks' ), icon: helperIcons.content.p() },
	dfn: { label: __( 'Dfn', 'spectra-blocks' ), icon: helperIcons.content.p() },
	bdi: { label: __( 'Bdi', 'spectra-blocks' ), icon: helperIcons.content.p() },
	bdo: { label: __( 'Bdo', 'spectra-blocks' ), icon: helperIcons.content.p() },
	summary: { label: __( 'Summary', 'spectra-blocks' ), icon: helperIcons.content.div() },
	li: { label: __( 'LI', 'spectra-blocks' ), icon: helperIcons.content.div() },
};

// Default tag name constant.
const DEFAULT_TAG_NAME = 'p';

export { TAG_CONFIG, DEFAULT_TAG_NAME };

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { clientId, mergeBlocks, onReplace, setAttributes, attributes } = props;

	const {
		tagName,
		text,
		dropCap,
		enableTextShadow = false,
		textShadowColor,
		textShadowBlur = 2,
		textShadowOffsetX = 1,
		textShadowOffsetY = 1,
		style: blockStyle,
		textColor,
	} = attributes;

	// Extract WordPress core colors to use in Spectra system
	const wpTextColor = blockStyle?.color?.text;
	const wpBackgroundColor = blockStyle?.color?.background;

	// Use the onEnter hook.
	const enterRef = useOnEnter( { clientId } );
	const deleteRef = useOnDelete( { clientId } );

	// Get the alignment.
	const align = props.attributes?.style?.typography?.textAlign || '';

	// Check if the drop cap is disabled.
	const hasDropCapDisabled = align === ( isRTL() ? 'left' : 'right' ) || align === 'center' || tagName === 'span';

	// Get the block editing mode.
	const blockEditingMode = useBlockEditingMode();

	// Check if the block is at the root level (no parent block).
	const blockParents = select( 'core/block-editor' ).getBlockParents( clientId );
	const isRootBlock = blockParents.length === 0;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', value: textColor || wpTextColor },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor', value: wpBackgroundColor },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
		{ key: 'textShadow' },
	];

	// Generate text shadow CSS
	const textShadowCSS = useMemo( () => {
		if ( ! enableTextShadow || ! textShadowColor ) {
			return '';
		}

		return `${textShadowOffsetX}px ${textShadowOffsetY}px ${textShadowBlur}px ${textShadowColor}`;
	}, [ enableTextShadow, textShadowColor, textShadowOffsetX, textShadowOffsetY, textShadowBlur ] );

	// Update attributes with text shadow CSS for CSS variable generation.
	const attributesWithTextShadow = useMemo( () => ( {
		...attributes,
		textShadow: textShadowCSS,
	} ), [ attributes, textShadowCSS ] );

	const customClassNames = [ 
		dropCap && ! hasDropCapDisabled && blockEditingMode && 'has-drop-cap',
		textShadowColor && 'has-text-shadow'
	].filter(
		Boolean
	); // Filter out falsy values.

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributesWithTextShadow, config, customClassNames );

	const blockProps = useBlockProps( {
		ref: ( element ) => {
			enterRef( element );
			deleteRef( element );
		},
		style,
		// Add the block class names.
		className: spectraClassNames( classNames ),
	} );

	// Determine if we need an extra wrapper for root-level span tags.
	const needsSpanWrapper = tagName === 'span' && isRootBlock;

	// Set the isRootBlock attribute.
	useEffect( () => {
		if ( isRootBlock === attributes.isRootBlock ) {
			return;
		}

		setAttributes( { isRootBlock } );
	}, [ isRootBlock ] );

	// Memoized current tag value
	const currentTag = useMemo( () => tagName || DEFAULT_TAG_NAME, [ tagName ] );

	// Memoized tag change handler - prevents recreation on every render.
	const handleTagChange = useCallback( ( newTag ) => {
		setAttributes( { tagName: newTag } );
	}, [ setAttributes ] );

	// Memoized tag options for toolbar dropdown.
	const tagOptions = useMemo( () => {
		return Object.entries( TAG_CONFIG ).map( ( [ value, { label, icon } ] ) => ( {
			title: label,
			icon,
			onClick: () => handleTagChange( value ),
			isActive: currentTag === value,
		} ) );
	}, [ currentTag, handleTagChange ] );

	// Optimized current tag icon - O(1) lookup with fallback.
	const currentTagIcon = useMemo( () => {
		return TAG_CONFIG[ currentTag ]?.icon || TAG_CONFIG[ DEFAULT_TAG_NAME ]?.icon;
	}, [ currentTag ] );

	// Memoized RichText configuration - prevents unnecessary re-renders.
	const richTextConfig = useMemo( () => ( {
		identifier: 'text',
		tagName: currentTag,
		placeholder: __( 'Write something – paragraph, heading, or more…', 'spectra-blocks' ),
		value: text,
		onChange: ( value ) => setAttributes( { text: value } ),
		onMerge: mergeBlocks,
		onReplace,
		onRemove: () => onReplace( [] ),
	} ), [ currentTag, text, setAttributes, mergeBlocks, onReplace ] );

	// Memoized toolbar controls - only re-renders when tagOptions or currentTagIcon change.
	const toolbarControls = useMemo( () => (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarDropdownMenu
					icon={ currentTagIcon }
					label={ __( 'Change HTML tag', 'spectra-blocks' ) }
					controls={ tagOptions }
				/>
			</ToolbarGroup>
		</BlockControls>
	), [ currentTagIcon, tagOptions ] );

	// Add the span wrapper.
	if ( needsSpanWrapper ) {
		return (
			<>
				{ toolbarControls }
				<div { ...blockProps }>
					<RichText { ...richTextConfig } />
				</div>
			</>
		);
	}

	// Standard case - no wrapper needed.
	return (
		<>
			{ toolbarControls }
			<RichText { ...blockProps } { ...richTextConfig } />
		</>
	);
};

export default memo( Render );
