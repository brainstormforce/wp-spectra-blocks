/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';

/**
 * Element Sub-settings: General settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {

	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;

	const {
		separatorStyle,
		separatorAlign,
	} = attributes;

	// Render the settings.
	return (
		<>
			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'Separator', 'spectra' ) }
					resetAll={ () => {
						setAttributes( {
							separatorStyle: undefined,
							separatorAlign: undefined,

						} );
					} }
					panelId={ clientId }
				>
					<ToolsPanelItem
						hasValue={ () => !! separatorStyle }
						label={ __( 'Style', 'spectra' ) }
						onDeselect={ () => setAttributes( { separatorStyle: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Style', 'spectra' ) }
							value={ separatorStyle || 'solid' }
							onChange={ ( value ) => setAttributes( { separatorStyle: value } ) }
							options={ [
								{
									value: 'solid',
									label: __( 'Solid', 'spectra' ),
								},
								{
									value: 'dotted',
									label: __( 'Dotted', 'spectra' ),
								},
								{
									value: 'dashed',
									label: __( 'Dashed', 'spectra' ),
								},
								{
									value: 'double',
									label: __( 'Double', 'spectra' ),
								},
								{
									value: 'rectangles',
									label: __( 'Rectangles', 'spectra' ),
								},
								{
									value: 'parallelogram',
									label: __( 'Parallelogram', 'spectra' ),
								},
								{
									value: 'slash',
									label: __( 'Slash', 'spectra' ),
								},
								{
									value: 'leaves',
									label: __( 'Leaves', 'spectra' ),
								},
							] }
							help={ separatorStyle !== 'solid' && separatorStyle !== 'dotted' && separatorStyle !== 'dashed' && separatorStyle !== 'double' ? __( 'Note: Please set Separator Height for proper thickness.', 'spectra' ) : '' }
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						hasValue={ () => !! separatorAlign }
						label={ __( 'Alignment', 'spectra' ) }
						onDeselect={ () => setAttributes( { separatorAlign: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Alignment', 'spectra' ) }
							value={ separatorAlign || 'center' }
							onChange={ ( value ) => setAttributes( { separatorAlign: value } ) }
							isBlock
						>
							<ToggleGroupControlOption value="left" label={ __( 'Left', 'spectra' ) } />
							<ToggleGroupControlOption value="center" label={ __( 'Center', 'spectra' ) } />
							<ToggleGroupControlOption value="right" label={ __( 'Right', 'spectra' ) } />
						</ToggleGroupControl>
					</ToolsPanelItem>

				</ToolsPanel>
			</InspectorControls>
		</>
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
			separatorWidth,
			separatorHeight,
			separatorSize,
			separatorStyle,
		},
	} = props;

	// Get the core provided units, else add the fallback.	
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! separatorWidth }
				label={ __( 'Separator Width', 'spectra' ) }
				onDeselect={ () => setAttributes( { separatorWidth: undefined } ) }
				resetAllFilter={ () => ( {
					separatorWidth: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Separator Width', 'spectra' ) }
					labelPosition="top"
					value={ separatorWidth }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { separatorWidth: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => !!separatorHeight }
				label={ __( 'Separator Height', 'spectra' ) }
				onDeselect={ () => setAttributes( { separatorHeight: undefined } ) }
				resetAllFilter={ () => ( {
					separatorHeight: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Separator Height', 'spectra' ) }
					labelPosition="top"
					value={ separatorHeight }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { separatorHeight: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			{ ( separatorStyle === 'rectangles' || separatorStyle === 'parallelogram' || separatorStyle === 'slash' || separatorStyle === 'leaves' ) && (
				<ToolsPanelItem
					hasValue={ () => !! separatorSize }
					label={ __( 'Separator Size', 'spectra' ) }
					onDeselect={ () => setAttributes( { separatorSize: undefined } ) }
					resetAllFilter={ () => ( {
						separatorSize: undefined,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Separator Size', 'spectra' ) }
						labelPosition="top"
						value={ separatorSize }
						min={ 0 }
						onChange={ ( value ) => setAttributes( { separatorSize: value } ) }
						units={ units }
					/>
				</ToolsPanelItem>
			) }
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
			separatorColor,
		},
	} = props;

	return(
		<InspectorColor
			settings={ [
				{
					colorValue: separatorColor,
					label: __( 'Separator Color', 'spectra' ),
					onColorChange: ( value ) => setAttributes( { separatorColor: value } ),
					resetAllFilter: () => setAttributes( { separatorColor: undefined } ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * The Editor settings for this block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );