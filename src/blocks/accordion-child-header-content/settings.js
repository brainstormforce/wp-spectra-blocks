/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { helperIcons } from '@spectra-helpers/block-icons';

/**
 * Element Sub-settings: General settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {

	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		context: {
			'spectra/accordion-child-header/headerElement': headerElement,
		},
		attributes: {
			tagName,
		},
	} = props;

	// Only show the tag selector when header is div mode
	// In button mode, only span is allowed for valid HTML
	const isHeaderDiv = headerElement === 'div';
	const defaultTagName = 'span';

	// Don't render settings if header is button mode
	if ( ! isHeaderDiv ) {
		return null;
	}

	return (
		<InspectorControls group='settings'>
			<ToolsPanel
				label={ __( 'Title', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						tagName: defaultTagName,
					} )
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! tagName && defaultTagName !== tagName }
					label={ __( 'HTML Tag', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { tagName: defaultTagName } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'HTML Tag', 'ultimate-addons-for-gutenberg' ) }
						value={ tagName || defaultTagName }
						onChange={ ( value ) => setAttributes( { tagName: value } ) }
						isBlock
					>
						<ToggleGroupControlOption
							value="h1"
							label={ __( 'H1', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h1( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h2"
							label={ __( 'H2', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h2( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h3"
							label={ __( 'H3', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h3( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h4"
							label={ __( 'H4', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h4( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h5"
							label={ __( 'H5', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h5( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h6"
							label={ __( 'H6', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h6( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="p"
							label={ __( 'P', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.p( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="div"
							label={ __( 'D', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.div( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="span"
							label={ __( 'S', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.span( false ) }
						</ToggleGroupControlOption>
					</ToggleGroupControl>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * The Editor settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<BlockSettings { ...props } />
	</>
);

export default memo( Settings );