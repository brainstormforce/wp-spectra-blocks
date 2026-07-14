/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { memo, useMemo, useState } from '@wordpress/element';
import { 
	BlockControls,
	BlockAlignmentToolbar,
} from '@wordpress/block-editor';
import {
	ToolbarButton,
	ToolbarGroup,
	ToolbarDropdownMenu,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import ContainerLayoutGuide from '@spectra-components/container-layout-guide';
import { helperIcons } from '@spectra-helpers/block-icons';

// Static tag configuration for container HTML tags.
// Tags without a dedicated helperIcon fall back to the generic container icon.
const TAG_CONFIG = {
	div: {
		label: __( 'Div', 'spectra-blocks' ),
		icon: helperIcons.content.div(),
	},
	header: {
		label: __( 'Header', 'spectra-blocks' ),
		icon: helperIcons.container.header(),
	},
	footer: {
		label: __( 'Footer', 'spectra-blocks' ),
		icon: helperIcons.container.footer(),
	},
	main: {
		label: __( 'Main', 'spectra-blocks' ),
		icon: helperIcons.container.main(),
	},
	article: {
		label: __( 'Article', 'spectra-blocks' ),
		icon: helperIcons.container.article(),
	},
	section: {
		label: __( 'Section', 'spectra-blocks' ),
		icon: helperIcons.container.section(),
	},
	aside: {
		label: __( 'Aside', 'spectra-blocks' ),
		icon: helperIcons.container.aside(),
	},
	figure: {
		label: __( 'Figure', 'spectra-blocks' ),
		icon: helperIcons.container.figure(),
	},
	figcaption: {
		label: __( 'Figcaption', 'spectra-blocks' ),
		icon: helperIcons.container.figcaption(),
	},
	nav: {
		label: __( 'Nav', 'spectra-blocks' ),
		icon: helperIcons.container.nav(),
	},
	hgroup: {
		label: __( 'Hgroup', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	dialog: {
		label: __( 'Dialog', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	a: {
		label: __( 'Link', 'spectra-blocks' ),
		icon: helperIcons.container.link(),
	},
	// Lists
	ul: {
		label: __( 'UL', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	ol: {
		label: __( 'OL', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	li: {
		label: __( 'LI', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	dl: {
		label: __( 'DL', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	dt: {
		label: __( 'DT', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	dd: {
		label: __( 'DD', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	// Forms & interactive
	form: {
		label: __( 'Form', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	fieldset: {
		label: __( 'Fieldset', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	details: {
		label: __( 'Details', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	summary: {
		label: __( 'Summary', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	address: {
		label: __( 'Address', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	// Table
	table: {
		label: __( 'Table', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	colgroup: {
		label: __( 'Colgroup', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	thead: {
		label: __( 'THead', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	tbody: {
		label: __( 'TBody', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	tfoot: {
		label: __( 'TFoot', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	tr: {
		label: __( 'TR', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	td: {
		label: __( 'TD', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	th: {
		label: __( 'TH', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	// `hidden: true` keeps a tag available to the renderer/HTML import
	// but hides it from the author dropdown.
	img: {
		label: __( 'Image', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	picture: {
		label: __( 'Picture', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	video: {
		label: __( 'Video', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	audio: {
		label: __( 'Audio', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	source: {
		label: __( 'Source', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	track: {
		label: __( 'Track', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	iframe: {
		label: __( 'Iframe', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	embed: {
		label: __( 'Embed', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	object: {
		label: __( 'Object', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	canvas: {
		label: __( 'Canvas', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	svg: {
		label: __( 'SVG', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	hr: {
		label: __( 'HR', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
	},
	br: {
		label: __( 'BR', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
	wbr: {
		label: __( 'WBR', 'spectra-blocks' ),
		icon: helperIcons.container.div(),
		hidden: true,
	},
};

// HTML void elements — render self-closing and never carry children.
// https://html.spec.whatwg.org/multipage/syntax.html#void-elements
export const VOID_TAGS = new Set( [
	'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
	'input', 'link', 'meta', 'source', 'track', 'wbr',
] );

// Default tag name constant.
const DEFAULT_TAG_NAME = 'div';

/**
 * Custom hook to create HTML tag toolbar controls.
 *
 * @param {string}   currentTag      - The currently selected HTML tag.
 * @param {Function} handleTagChange - Function to handle tag changes.
 * @return {Element} The toolbar controls JSX.
 */
export const useHtmlTagToolbar = ( currentTag, handleTagChange ) => {
	// Hidden tags are filtered from the dropdown, but the active tag is
	// always shown so authors can switch away from it.
	const tagOptions = useMemo( () => {
		return Object.entries( TAG_CONFIG )
			.filter( ( [ value, { hidden } ] ) => ! hidden || value === currentTag )
			.map( ( [ value, { label, icon } ] ) => ( {
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

	// Return memoized toolbar controls.
	return useMemo( () => (
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
};

/**
 * Container Block Toolbar Component
 *
 * Consolidates all toolbar-related functionality for the Container block:
 * - Block alignment controls (for root containers)
 * - Variation/layout picker button
 * - Layout help button with NUX guide
 *
 * @param {Object}   props                       The component props.
 * @param {Object}   props.attributes            The block attributes.
 * @param {Function} props.setAttributes         Function to update block attributes.
 * @param {string}   props.clientId              The block client ID.
 * @param {boolean}  props.isSelected            Whether the block is selected.
 * @param {Function} props.onShowVariationPicker Function to show variation picker.
 * @param {Function} props.onTagChange           Function to handle HTML tag changes.
 * @since x.x.x
 * @return {Element} The rendered toolbar controls.
 */
const ContainerToolbar = memo( ( props ) => {
	const {
		attributes,
		setAttributes,
		isSelected,
		onShowVariationPicker,
		onTagChange,
	} = props;

	// Simple state to control guide visibility
	const [ showGuide, setShowGuide ] = useState( false );

	// Get HTML tag toolbar controls if onTagChange is provided
	// eslint-disable-next-line react-hooks/rules-of-hooks -- Hook is called conditionally but always returns same result when onTagChange is present
	const tagToolbarControls = onTagChange ? useHtmlTagToolbar( attributes.tagName || DEFAULT_TAG_NAME, onTagChange ) : null;

	// Don't render toolbar if block is not selected
	if ( ! isSelected ) {
		return null;
	}

	return (
		<>
			{/* HTML Tag Controls - Rendered separately if provided */}
			{ tagToolbarControls }

			<BlockControls>
				{/* Block Alignment Controls - Only for root containers */}
				{ attributes.isBlockRootParent && (
					<BlockAlignmentToolbar
						value={ attributes.align }
						onChange={ ( align ) => setAttributes( { align } ) }
						controls={ [ 'wide', 'full' ] }
					/>
				) }

				{/* Layout and Variation Controls */}
				<ToolbarGroup>
					{/* Choose Layout / Variation Picker Button */}
					<ToolbarButton
						icon={ helperIcons.variationSwitch() }
						label={ __( 'Change Layout', 'spectra-blocks' ) }
						onClick={ onShowVariationPicker }
						showTooltip
					/>

					{/* Layout Help Button */}
					<ToolbarButton
						icon={ helperIcons.help() }
						label={ __( 'Layout Help', 'spectra-blocks' ) }
						onClick={ () => setShowGuide( true ) }
						showTooltip
					/>
				</ToolbarGroup>
			</BlockControls>

			{/* Container Layout Guide Modal */}
			<ContainerLayoutGuide
				isVisible={ showGuide }
				onClose={ () => setShowGuide( false ) }
			/>
		</>
	);
} );


// Export TAG_CONFIG and DEFAULT_TAG_NAME for use in other files
export { TAG_CONFIG, DEFAULT_TAG_NAME };

export default ContainerToolbar;
