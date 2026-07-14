/**
 * External dependencies.
 */
import { select, dispatch } from '@wordpress/data';
import { memo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalVStack as VStack,
	AnglePickerControl,
	TabPanel,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import { AccordionRootToolbar } from './helper';

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
		attributes: {
			icon,
			iconSecondary,
			flipForRTL,
			flipForRTLSecondary,
			rotation,
		},
	} = props;

	// The tabs for the settings.
	const tabs = [
		{
			name: 'collapsed',
			title: __( 'Collapsed', 'spectra-blocks' ),
		},
		{
			name: 'expanded',
			title: __( 'Expanded', 'spectra-blocks' ),
		},
	];

	// The tab attributes for the different states.
	const tabAttributes = {
		collapsed: {
			iconLabel: 'icon',
			iconValue: icon,
			flipForRTLLabel: 'flipForRTL',
			flipForRTLValue: flipForRTL,
		},
		expanded: {
			iconLabel: 'iconSecondary',
			iconValue: iconSecondary,
			flipForRTLLabel: 'flipForRTLSecondary',
			flipForRTLValue: flipForRTLSecondary,
		},
	};

	// Callback to update the toggles of all the icons in every accordion header - optimized with useCallback
	const handleToggleChange = useCallback( ( currentToggle ) => {
		// First set the attribute of this block.
		setAttributes( { [ currentToggle.flipForRTLLabel ]: ! currentToggle.flipForRTLValue } );
		
		// Batch all block attribute updates to improve performance
		// Get the accordion block and its children
		const { getBlocksByClientId } = select( blockEditorStore );
		const accordionBlock = getBlocksByClientId( clientId )[0];
		if ( ! accordionBlock?.innerBlocks ) {
			return;
		}
		
		const { updateBlockAttributes } = dispatch( blockEditorStore );
		// Collect all icon block IDs that need updating before making any updates
		const iconBlocksToUpdate = [];
		
		accordionBlock.innerBlocks.forEach( ( child ) => {
			// Only process accordion items
			if ( child.name !== 'spectra/accordion-child-item' ) {
				return;
			}
			
			// Find accordion headers in this item
			child.innerBlocks.forEach( ( itemBlock ) => {
				if ( itemBlock.name !== 'spectra/accordion-child-header' ) {
					return;
				}
				
				// Find icon blocks in this header
				itemBlock.innerBlocks.forEach( ( headerBlock ) => {
					if ( headerBlock.name === 'spectra/accordion-child-header-icon' ) {
						iconBlocksToUpdate.push( headerBlock.clientId );
					}
				} );
			} );
		} );
		
		// Batch update all icon blocks at once to minimize re-renders
		if ( iconBlocksToUpdate.length > 0 ) {
			const newValue = ! currentToggle.flipForRTLValue;
			iconBlocksToUpdate.forEach( ( iconBlockId ) => {
				updateBlockAttributes( iconBlockId, { [ currentToggle.flipForRTLLabel ]: newValue } );
			} );
		}
	}, [ clientId, setAttributes ] ); // Memoize with dependencies

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Icon', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
						rotation: undefined,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The iconSecondary attribute is set. Default: undefined.
				- The flipForRTL attribute is not true. Default: true.
				- The flipForRTLSecondary attribute is not true. Default: true.
				--- Note that for the accordion icons, flip for RTL is intentionally true by default for good UI.
				 */}
				<ToolsPanelItem
					hasValue={ () => ( !! icon || !! iconSecondary || ! flipForRTL || ! flipForRTLSecondary ) }
					label={ __( 'Icon', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
					} ) }
					resetAllFilter={ () => ( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<TabPanel tabs={ tabs }>
						{ ( tab ) => (
							<VStack spacing={ 4 }>
								<IconPicker
									value={ tabAttributes[ tab.name ].iconValue }
									onChange={ ( value ) => setAttributes( {
										[ tabAttributes[ tab.name ].iconLabel ]: value,
									} ) }
								/>
								<ToggleControl
									__nextHasNoMarginBottom
									checked={ tabAttributes[ tab.name ].flipForRTLValue }
									label={ __( 'Flip Icon for Right-to-Left', 'spectra-blocks' ) }
									onChange={ () => handleToggleChange( tabAttributes[ tab.name ] ) }
									help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like "Arrow Right", "Chart Line", etc.', 'spectra-blocks' ) }
								/>
							</VStack>
						) }
					</TabPanel>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The rotation attribute is set. Default: undefined.
				 */}
				<ToolsPanelItem
					hasValue={ () => !! rotation }
					label={ __( 'Rotation', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { rotation: undefined } ) }
					resetAllFilter={ () => ( {
						rotation: undefined,
					} ) }
					panelId={ clientId }
				>
					<AnglePickerControl
						label={ __( 'Rotation', 'spectra-blocks' ) }
						onChange={ ( value ) => {
							setAttributes( { rotation: value } );
						} }
						value={ rotation || 0 }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Dimensions panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const DimensionSettings = memo( ( props ) => {

	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			size,
		},
	} = props;

	// Use the available spacing units, or our units if they're not available.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! size }
				label={ __( 'Icon Size', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { size: undefined } ) }
				resetAllFilter={ () => ( { size: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Icon Size', 'spectra-blocks' ) }
					labelPosition="top"
					value={ size }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { size: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
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
			textColorSecondary,
			textColorHoverSecondary,
			backgroundColorHover,
			backgroundColorSecondary,
			backgroundColorHoverSecondary,
			backgroundGradientHover,
			backgroundGradientSecondary,
			backgroundGradientHoverSecondary,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Item Text Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Item BG Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
				{
					colorValue: textColorSecondary,
					label: __( 'Header Text', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorSecondary: value } ),
					resetAllFilter: () => {
						setAttributes( { textColorSecondary: undefined } );
					},
				},
				{
					colorValue: textColorHoverSecondary,
					label: __( 'Header Text Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( { textColorHoverSecondary: undefined } ),
				},
				{
					colorValue: backgroundColorSecondary,
					gradientValue: backgroundGradientSecondary,
					label: __( 'Header Background', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorSecondary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientSecondary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorSecondary: undefined,
						backgroundGradientSecondary: undefined,
					} ),
				},
				{
					colorValue: backgroundColorHoverSecondary,
					gradientValue: backgroundGradientHoverSecondary,
					label: __( 'Header BG Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHoverSecondary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHoverSecondary: undefined,
						backgroundGradientHoverSecondary: undefined,
					} ),
				},
			] }
			panelId={ clientId }
		/>
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
		{props?.attributes?.isPreview ? null :
			props?.name === 'spectra/accordion' && <AccordionRootToolbar clientId={props.clientId} />
		}
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
