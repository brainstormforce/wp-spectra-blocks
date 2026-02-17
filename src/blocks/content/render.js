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

// Static tag configuration - single source of truth for better performance.
const TAG_CONFIG = {
	p: {
		label: __( 'Paragraph', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.p(),
	},
	h1: {
		label: __( 'Heading 1', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h1(),
	},
	h2: {
		label: __( 'Heading 2', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h2(),
	},
	h3: {
		label: __( 'Heading 3', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h3(),
	},
	h4: {
		label: __( 'Heading 4', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h4(),
	},
	h5: {
		label: __( 'Heading 5', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h5(),
	},
	h6: {
		label: __( 'Heading 6', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.h6(),
	},
	div: {
		label: __( 'Div', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.div(),
	},
	span: {
		label: __( 'Span', 'ultimate-addons-for-gutenberg' ),
		icon: helperIcons.content.span(),
	},
};

// Default tag name constant.
const DEFAULT_TAG_NAME = 'p';

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
		textColor
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
		placeholder: __( 'Write something – paragraph, heading, or more…', 'ultimate-addons-for-gutenberg' ),
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
					label={ __( 'Change HTML tag', 'ultimate-addons-for-gutenberg' ) }
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
