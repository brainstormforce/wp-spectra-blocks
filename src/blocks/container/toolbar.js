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
const TAG_CONFIG = {
	div: {
		label: __( 'Div', 'spectra' ),
		icon: helperIcons.content.div(),
	},
	header: {
		label: __( 'Header', 'spectra' ),
		icon: helperIcons.container.header(),
	},
	footer: {
		label: __( 'Footer', 'spectra' ),
		icon: helperIcons.container.footer(),
	},
	main: {
		label: __( 'Main', 'spectra' ),
		icon: helperIcons.container.main(),
	},
	article: {
		label: __( 'Article', 'spectra' ),
		icon: helperIcons.container.article(),
	},
	section: {
		label: __( 'Section', 'spectra' ),
		icon: helperIcons.container.section(),
	},
	aside: {
		label: __( 'Aside', 'spectra' ),
		icon: helperIcons.container.aside(),
	},
	figure: {
		label: __( 'Figure', 'spectra' ),
		icon: helperIcons.container.figure(),
	},
	figcaption: {
		label: __( 'Figcaption', 'spectra' ),
		icon: helperIcons.container.figcaption(),
	},
	nav: {
		label: __( 'Nav', 'spectra' ),
		icon: helperIcons.container.nav(),
	},
	a: {
		label: __( 'Link', 'spectra' ),
		icon: helperIcons.container.link(),
	},
};

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

	// Return memoized toolbar controls.
	return useMemo( () => (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarDropdownMenu
					icon={ currentTagIcon }
					label={ __( 'Change HTML tag', 'spectra' ) }
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
 * @param {Object}   props                    The component props.
 * @param {Object}   props.attributes         The block attributes.
 * @param {Function} props.setAttributes      Function to update block attributes.
 * @param {string}   props.clientId           The block client ID.
 * @param {boolean}  props.isSelected         Whether the block is selected.
 * @param {Function} props.onShowVariationPicker Function to show variation picker.
 * @param {Function} props.onTagChange        Function to handle HTML tag changes.
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
						label={ __( 'Choose Layout', 'spectra' ) }
						onClick={ onShowVariationPicker }
						showTooltip
					/>

					{/* Layout Help Button */}
					<ToolbarButton
						icon={ helperIcons.help() }
						label={ __( 'Layout Help', 'spectra' ) }
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
