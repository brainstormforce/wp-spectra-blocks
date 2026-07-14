/**
 * External dependencies.
 */
import {
	InspectorControls,
	BlockControls,
	useSettings,
} from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalGrid as Grid,
	SelectControl,
} from '@wordpress/components';
import { dispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import { TabBlockControls } from '@spectra-blocks/tabs/helpers';
import { helperIcons } from '@spectra-helpers/block-icons';

/**
 * Element Sub-settings: Dimension settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block dimensions.
 */
const DimensionSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: { minWidth, minHeight, maxWidth, maxHeight, width, height },
	} = props;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () =>
					!! width ||
					!! height ||
					!! minWidth ||
					!! minHeight ||
					!! maxWidth ||
					!! maxHeight
				}
				label={ __( 'Sizes', 'spectra-blocks' ) }
				as={ Grid }
				panelId={ clientId }
				isShownByDefault
				onDeselect={ () =>
					setAttributes( {
						width: undefined,
						height: undefined,
						minWidth: undefined,
						minHeight: undefined,
						maxWidth: undefined,
						maxHeight: undefined,
					} )
				}
				resetAllFilter={ () => ( {
					width: undefined,
					height: undefined,
					minWidth: undefined,
					minHeight: undefined,
					maxWidth: undefined,
					maxHeight: undefined,
				} ) }
			>
				<UnitControl
					__next40pxDefaultSize
					label="Width"
					value={ width }
					onChange={ ( value ) => setAttributes( { width: value } ) }
					units={ units }
				/>
				<UnitControl
					__next40pxDefaultSize
					label="Height"
					value={ height }
					onChange={ ( value ) => setAttributes( { height: value } ) }
					units={ units }
				/>
				<UnitControl
					__next40pxDefaultSize
					label="Min W"
					value={ minWidth }
					onChange={ ( value ) =>
						setAttributes( { minWidth: value } )
					}
					units={ units }
				/>
				<UnitControl
					__next40pxDefaultSize
					label="Min H"
					value={ minHeight }
					onChange={ ( value ) =>
						setAttributes( { minHeight: value } )
					}
					units={ units }
				/>
				<UnitControl
					__next40pxDefaultSize
					label="Max W"
					value={ maxWidth }
					onChange={ ( value ) =>
						setAttributes( { maxWidth: value } )
					}
					units={ units }
				/>
				<UnitControl
					__next40pxDefaultSize
					label="Max H"
					value={ maxHeight }
					onChange={ ( value ) =>
						setAttributes( { maxHeight: value } )
					}
					units={ units }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: General settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const GeneralSettings = memo( ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const { overflow } = attributes;

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Tab Trigger', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						overflow: 'visible',
					} );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => overflow !== 'visible' }
					label={ __( 'Overflow', 'spectra-blocks' ) }
					panelId={ clientId }
					onDeselect={ () =>
						setAttributes( {
							overflow: 'visible',
						} )
					}
					resetAll={ () => ( {
						overflow: 'visible',
					} ) }
					isShownByDefault
				>
					<SelectControl
						label={ __(
							'Overflow',
							'spectra-blocks'
						) }
						value={ overflow }
						options={ [
							{
								value: 'visible',
								label: __(
									'Visible',
									'spectra-blocks'
								),
							},
							{
								value: 'hidden',
								label: __(
									'Hidden',
									'spectra-blocks'
								),
							},
							{
								value: 'scroll',
								label: __(
									'Scroll',
									'spectra-blocks'
								),
							},
							{
								value: 'auto',
								label: __(
									'Auto',
									'spectra-blocks'
								),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { overflow: value } )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ColorSettings = memo( ( props ) => {

	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			textColorHover,
			textColorActive,
			textColorActiveHover,
			backgroundColorHover,
			backgroundGradientHover,
			backgroundColorActive,
			backgroundGradientActive,
			backgroundColorActiveHover,
			backgroundGradientActiveHover
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: textColorActive,
					label: __( 'Text Active', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorActive: value } ),
					resetAllFilter: () => setAttributes( { textColorActive: undefined } ),
				},
				{
					colorValue: textColorActiveHover,
					label: __( 'Text Active Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorActiveHover: value } ),
					resetAllFilter: () => setAttributes( { textColorActiveHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
				{
					colorValue: backgroundColorActive,
					gradientValue: backgroundGradientActive,
					label: __( 'Background Active', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorActive: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientActive: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorActive: undefined,
						backgroundGradientActive: undefined,
					} ),
				},
				{
					colorValue: backgroundColorActiveHover,
					gradientValue: backgroundGradientActiveHover,
					label: __( 'Background Active Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorActiveHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientActiveHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorActiveHover: undefined,
						backgroundGradientActiveHover: undefined,
					} ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * The block settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Settings = ( props ) => {
	const { clientId } = props;

	const { replaceBlock } = dispatch( 'core/block-editor' );

	/**
	 * Transform the current tab trigger to a tab button.
	 */
	const transformToTabButton = () => {
		const { getBlock } = select( 'core/block-editor' );
		const currentBlock = getBlock( clientId );
		const { 
			text,
			style,  
			borderColor,
			textColor,
			styleColorText,
			textColorHover,
			textColorActive,
			textColorActiveHover,
			backgroundColor,
			backgroundColorHover,
			backgroundColorActive,
			backgroundColorActiveHover,
			backgroundGradient,
			backgroundGradientHover,
			backgroundGradientActive,
			backgroundGradientActiveHover,
			borderColorHover,
			borderColorActive,
			borderColorActiveHover 
		} = currentBlock.attributes;
		
		// Extract text content from inner blocks
		let extractedText = '';
		let hasIcon = false;
		const iconData = {};

		if ( currentBlock.innerBlocks && currentBlock.innerBlocks.length > 0 ) {
			// Look for content blocks to extract text
			const contentBlocks = currentBlock.innerBlocks.filter( block => block.name === 'spectra/content' );
			if ( contentBlocks.length > 0 ) {
				extractedText = contentBlocks[0].attributes.text || '';
			}

			// Look for icon blocks to extract icon
			const iconBlocks = currentBlock.innerBlocks.filter( block => block.name === 'spectra/icons' );
			if ( iconBlocks.length > 0 && iconBlocks[0].innerBlocks && iconBlocks[0].innerBlocks.length > 0 ) {
				const iconChild = iconBlocks[0].innerBlocks.find( block => block.name === 'spectra/icon' );
				if ( iconChild && iconChild.attributes.icon ) {
					hasIcon = true;
					iconData.icon = iconChild.attributes.icon;
				}
			}
		}

		// Create new tab button attributes
		const newAttributes = {
			showText: true,
			text,
			borderColor,
			textColor,
			style,
			styleColorText,
			textColorHover,
			textColorActive,
			textColorActiveHover,
			backgroundColor,
			backgroundColorHover,
			backgroundColorActive,
			backgroundColorActiveHover,
			backgroundGradient,
			backgroundGradientHover,
			backgroundGradientActive,
			backgroundGradientActiveHover,
			borderColorHover,
			borderColorActive,
			borderColorActiveHover
		};

		// Set text content (use extracted text or fallback to placeholder)
		if ( extractedText ) {
			newAttributes.text = extractedText;
		} else {
			newAttributes.placeholder = __( 'Tab', 'spectra-blocks' );
		}

		// Add icon if present
		if ( hasIcon ) {
			Object.assign( newAttributes, iconData );
		}

		// Create a new tab button block
		const newTabButton = createBlock( 'spectra/tabs-child-tab-button', newAttributes );

		// Replace the current block with the new tab button
		replaceBlock( clientId, newTabButton );
	};

	return (
		<>
			<BlockControls>
				<TabBlockControls clientId={ clientId } removalType="tab" />
				<ToolbarGroup>
					<ToolbarButton
						icon={ helperIcons.tabDivToButtonSwitch() }
						label={ __(
							'Transform to Tab Button',
							'spectra-blocks'
						) }
						onClick={ transformToTabButton }
					/>
				</ToolbarGroup>
			</BlockControls>
			<GeneralSettings { ...props } />
			<ColorSettings { ...props } />
			<DimensionSettings { ...props } />
		</>
	);
};

export default Settings;
