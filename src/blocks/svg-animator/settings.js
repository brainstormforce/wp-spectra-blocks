/**
 * External dependencies.
 */
import { memo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalVStack as VStack,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalInputControl as InputControl,
	__experimentalNumberControl as NumberControl,
	Button,
	TextareaControl,
	SelectControl,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import { processSpectraSVG } from '@spectra-helpers';

/**
 * SVG Source Settings panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const SvgSourceSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;
	const {
		svgSource,
		svgUrl,
		svgId,
		svgContent,
	} = attributes;

	const [ codeError, setCodeError ] = useState( '' );

	const onSelectSvg = ( media ) => {
		if ( ! media || ! media.url ) {
			setAttributes( { svgUrl: '', svgId: undefined } );
			return;
		}

		if ( media.mime !== 'image/svg+xml' ) {
			return;
		}

		setAttributes( {
			svgUrl: media.url,
			svgId: media.id,
			svgSource: 'upload',
		} );
	};

	const onRemoveSvg = () => {
		setAttributes( { svgUrl: '', svgId: undefined } );
	};

	const onSvgCodeChange = async ( value ) => {
		setCodeError( '' );
		if ( ! value ) {
			setAttributes( { svgContent: '' } );
			return;
		}

		const sanitized = await processSpectraSVG( value );
		if ( sanitized ) {
			setAttributes( { svgContent: sanitized, svgSource: 'code' } );
		} else {
			setCodeError( __( 'Invalid SVG content. Please check your code.', 'ultimate-addons-for-gutenberg' ) );
		}
	};

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'SVG Source', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						svgSource: 'upload',
						svgUrl: '',
						svgId: undefined,
						svgContent: '',
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
			- SVG source is changed from upload or any SVG content is set.
			- When reset, SVG source returns to upload and content is cleared.
			*/}
				<ToolsPanelItem
					hasValue={ () => svgSource !== 'upload' || !! svgUrl || !! svgContent }
					label={ __( 'Source', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						svgSource: 'upload',
						svgUrl: '',
						svgId: undefined,
						svgContent: '',
					} ) }
					resetAllFilter={ () => ( {
						svgSource: 'upload',
						svgUrl: '',
						svgId: undefined,
						svgContent: '',
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'SVG Source', 'ultimate-addons-for-gutenberg' ) }
							value={ svgSource }
							onChange={ ( value ) => setAttributes( { svgSource: value } ) }
							isBlock
						>
							<ToggleGroupControlOption value="upload" label={ __( 'Upload', 'ultimate-addons-for-gutenberg' ) } />
							<ToggleGroupControlOption value="code" label={ __( 'Code', 'ultimate-addons-for-gutenberg' ) } />
						</ToggleGroupControl>

						{ svgSource === 'upload' && (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onSelectSvg }
									allowedTypes={ [ 'image/svg+xml' ] }
									value={ svgId }
									render={ ( { open } ) => (
										<VStack spacing={ 3 }>
											{ ! svgUrl && (
												<Button
													onClick={ open }
													variant="primary"
													__next40pxDefaultSize
												>
													{ __( 'Upload SVG', 'ultimate-addons-for-gutenberg' ) }
												</Button>
											) }
											{ svgUrl && (
												<>
													<img
														src={ svgUrl }
														alt=""
														className="spectra-svg-animator__preview-image"
													/>
													<Button onClick={ open } variant="secondary" __next40pxDefaultSize>
														{ __( 'Replace SVG', 'ultimate-addons-for-gutenberg' ) }
													</Button>
													<Button onClick={ onRemoveSvg } variant="tertiary" isDestructive __next40pxDefaultSize>
														{ __( 'Remove', 'ultimate-addons-for-gutenberg' ) }
													</Button>
												</>
											) }
										</VStack>
									) }
								/>
							</MediaUploadCheck>
						) }

						{ svgSource === 'code' && (
							<VStack spacing={ 2 }>
								<TextareaControl
									label={ __( 'SVG Code', 'ultimate-addons-for-gutenberg' ) }
									help={ codeError || __( 'Paste your SVG code here. It will be sanitized automatically.', 'ultimate-addons-for-gutenberg' ) }
									value={ svgContent }
									onChange={ onSvgCodeChange }
									rows={ 6 }
									className={ codeError ? 'has-error' : '' }
								/>
							</VStack>
						) }
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Animation Settings panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const AnimationSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;
	const {
		animationType,
		animationTrigger,
		animationDuration,
		animationDelay,
		animationEasing,
		animationLoop,
		animationReverse,
		animationYoyo,
		animationStagger,
		fillBehavior,
	} = attributes;

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Animation', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						animationType: 'draw',
						animationTrigger: 'viewport',
						animationDuration: 2000,
						animationDelay: 0,
						animationEasing: 'ease',
						animationLoop: false,
						animationReverse: false,
						animationYoyo: false,
						animationStagger: 0,
						fillBehavior: 'none',
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- Animation type is changed from draw.
				- When reset, animation type returns to draw.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationType !== 'draw' }
					label={ __( 'Animation Type', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationType: 'draw' } ) }
					resetAllFilter={ () => ( { animationType: 'draw' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Animation Type', 'ultimate-addons-for-gutenberg' ) }
						value={ animationType }
						onChange={ ( value ) => setAttributes( { animationType: value } ) }
						options={ [
							{ label: __( 'Draw (Stroke)', 'ultimate-addons-for-gutenberg' ), value: 'draw' },
							{ label: __( 'Fade', 'ultimate-addons-for-gutenberg' ), value: 'fade' },
							{ label: __( 'Scale', 'ultimate-addons-for-gutenberg' ), value: 'scale' },
							{ label: __( 'Rotate', 'ultimate-addons-for-gutenberg' ), value: 'rotate' },
						] }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Animation trigger is changed from viewport.
				- When reset, trigger returns to viewport.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationTrigger !== 'viewport' }
					label={ __( 'Trigger', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationTrigger: 'viewport' } ) }
					resetAllFilter={ () => ( { animationTrigger: 'viewport' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Trigger', 'ultimate-addons-for-gutenberg' ) }
						value={ animationTrigger }
						onChange={ ( value ) => setAttributes( { animationTrigger: value } ) }
						options={ [
							{ label: __( 'On Scroll (Viewport)', 'ultimate-addons-for-gutenberg' ), value: 'viewport' },
							{ label: __( 'Page Load', 'ultimate-addons-for-gutenberg' ), value: 'load' },
							{ label: __( 'Hover', 'ultimate-addons-for-gutenberg' ), value: 'hover' },
							{ label: __( 'Click', 'ultimate-addons-for-gutenberg' ), value: 'click' },
						] }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Animation duration is changed from 2000ms.
				- When reset, duration returns to 2000ms.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationDuration !== 2000 }
					label={ __( 'Duration', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationDuration: 2000 } ) }
					resetAllFilter={ () => ( { animationDuration: 2000 } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Duration (ms)', 'ultimate-addons-for-gutenberg' ) }
						value={ animationDuration }
						onChange={ ( value ) => setAttributes( { animationDuration: value } ) }
						min={ 100 }
						max={ 10000 }
						step={ 100 }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Animation delay is set to any non-zero value.
				- When reset, delay returns to 0.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationDelay !== 0 }
					label={ __( 'Delay', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationDelay: 0 } ) }
					resetAllFilter={ () => ( { animationDelay: 0 } ) }
					panelId={ clientId }
				>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Delay (ms)', 'ultimate-addons-for-gutenberg' ) }
						value={ animationDelay }
						onChange={ ( value ) => setAttributes( { animationDelay: value } ) }
						min={ 0 }
						max={ 5000 }
						step={ 100 }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Animation easing is changed from ease.
				- When reset, easing returns to ease.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationEasing !== 'ease' }
					label={ __( 'Easing', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationEasing: 'ease' } ) }
					resetAllFilter={ () => ( { animationEasing: 'ease' } ) }
					panelId={ clientId }
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Easing', 'ultimate-addons-for-gutenberg' ) }
						value={ animationEasing }
						onChange={ ( value ) => setAttributes( { animationEasing: value } ) }
						options={ [
							{ label: __( 'Ease', 'ultimate-addons-for-gutenberg' ), value: 'ease' },
							{ label: __( 'Linear', 'ultimate-addons-for-gutenberg' ), value: 'linear' },
							{ label: __( 'Ease In', 'ultimate-addons-for-gutenberg' ), value: 'ease-in' },
							{ label: __( 'Ease Out', 'ultimate-addons-for-gutenberg' ), value: 'ease-out' },
							{ label: __( 'Ease In Out', 'ultimate-addons-for-gutenberg' ), value: 'ease-in-out' },
						] }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Stagger delay is set to any non-zero value.
				- When reset, stagger returns to 0.
				*/}
				<ToolsPanelItem
					hasValue={ () => animationStagger !== 0 }
					label={ __( 'Stagger', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { animationStagger: 0 } ) }
					resetAllFilter={ () => ( { animationStagger: 0 } ) }
					panelId={ clientId }
				>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Path Stagger (ms)', 'ultimate-addons-for-gutenberg' ) }
						help={ __( 'Delay between animating each path element.', 'ultimate-addons-for-gutenberg' ) }
						value={ animationStagger }
						onChange={ ( value ) => setAttributes( { animationStagger: value } ) }
						min={ 0 }
						max={ 2000 }
						step={ 50 }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Fill behavior is changed from none. Only visible for draw animation type.
				- When reset, fill behavior returns to none.
				*/}
				{ animationType === 'draw' && (
					<ToolsPanelItem
						hasValue={ () => fillBehavior !== 'none' }
						label={ __( 'Fill Behavior', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { fillBehavior: 'none' } ) }
						resetAllFilter={ () => ( { fillBehavior: 'none' } ) }
						panelId={ clientId }
					>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Fill Behavior', 'ultimate-addons-for-gutenberg' ) }
							value={ fillBehavior }
							onChange={ ( value ) => setAttributes( { fillBehavior: value } ) }
							options={ [
								{ label: __( 'No Fill', 'ultimate-addons-for-gutenberg' ), value: 'none' },
								{ label: __( 'Fill After Stroke', 'ultimate-addons-for-gutenberg' ), value: 'after-stroke' },
								{ label: __( 'Show Fill', 'ultimate-addons-for-gutenberg' ), value: 'show' },
							] }
						/>
					</ToolsPanelItem>
				) }

				{/* This tool panel item will require reset when any of these conditions are met:
				- Any playback option (loop, reverse, yoyo) is enabled.
				- When reset, all playback options return to false.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! animationLoop || !! animationReverse || !! animationYoyo }
					label={ __( 'Playback', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						animationLoop: false,
						animationReverse: false,
						animationYoyo: false,
					} ) }
					resetAllFilter={ () => ( {
						animationLoop: false,
						animationReverse: false,
						animationYoyo: false,
					} ) }
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ animationLoop }
							label={ __( 'Loop', 'ultimate-addons-for-gutenberg' ) }
							onChange={ () => setAttributes( { animationLoop: ! animationLoop } ) }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ animationReverse }
							label={ __( 'Reverse', 'ultimate-addons-for-gutenberg' ) }
							onChange={ () => setAttributes( { animationReverse: ! animationReverse } ) }
						/>
						{ animationLoop && (
							<ToggleControl
								__nextHasNoMarginBottom
								checked={ animationYoyo }
								label={ __( 'Yoyo', 'ultimate-addons-for-gutenberg' ) }
								help={ __( 'Alternate direction on each loop.', 'ultimate-addons-for-gutenberg' ) }
								onChange={ () => setAttributes( { animationYoyo: ! animationYoyo } ) }
							/>
						) }
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Dimension Settings injected into Core's Dimensions panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const DimensionSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;
	const { svgWidth, svgHeight } = attributes;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			{/* This tool panel item will require reset when any of these conditions are met:
			- SVG width is set to any value. Default: undefined.
			- When reset, width returns to undefined (auto).
			*/}
			<ToolsPanelItem
				hasValue={ () => !! svgWidth }
				label={ __( 'Width', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { svgWidth: undefined } ) }
				resetAllFilter={ () => ( { svgWidth: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Width', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ svgWidth }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { svgWidth: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			{/* This tool panel item will require reset when any of these conditions are met:
			- SVG height is set to any value. Default: undefined.
			- When reset, height returns to undefined (auto).
			*/}
			<ToolsPanelItem
				hasValue={ () => !! svgHeight }
				label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { svgHeight: undefined } ) }
				resetAllFilter={ () => ( { svgHeight: undefined } ) }
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ svgHeight }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { svgHeight: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Color Settings injected into Core's Color panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const ColorSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;
	const { strokeColor, strokeColorHover, fillColor } = attributes;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: strokeColor,
					label: __( 'Stroke', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { strokeColor: value } ),
					resetAllFilter: () => setAttributes( { strokeColor: undefined } ),
				},
				{
					colorValue: strokeColorHover,
					label: __( 'Stroke Hover', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { strokeColorHover: value } ),
					resetAllFilter: () => setAttributes( { strokeColorHover: undefined } ),
				},
				{
					colorValue: fillColor,
					label: __( 'Fill', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { fillColor: value } ),
					resetAllFilter: () => setAttributes( { fillColor: undefined } ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * Accessibility Settings panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const AccessibilitySettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;
	const { accessibilityMode, accessibilityLabel } = attributes;

	const requiresLabel = [ 'svg', 'image' ].includes( accessibilityMode );

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Accessibility', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						accessibilityMode: undefined,
						accessibilityLabel: undefined,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- Accessibility mode is set to any value.
				- When reset, mode and label return to undefined.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! accessibilityMode }
					label={ __( 'Accessibility Mode', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						accessibilityMode: undefined,
						accessibilityLabel: undefined,
					} ) }
					resetAllFilter={ () => ( {
						accessibilityMode: undefined,
						accessibilityLabel: undefined,
					} ) }
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Accessibility Mode', 'ultimate-addons-for-gutenberg' ) }
							value={ accessibilityMode }
							onChange={ ( value ) => setAttributes( { accessibilityMode: value } ) }
							isBlock
						>
							<ToggleGroupControlOption value="svg" label="SVG" />
							<ToggleGroupControlOption value="image" label="Image" />
							<ToggleGroupControlOption value="decorative" label="Decorative" />
						</ToggleGroupControl>
						{ requiresLabel && (
							<InputControl
								__next40pxDefaultSize
								label={ __( 'Accessibility Label', 'ultimate-addons-for-gutenberg' ) }
								value={ accessibilityLabel }
								onChange={ ( value ) => setAttributes( { accessibilityLabel: value } ) }
								placeholder={ __( 'Describe this SVG animation', 'ultimate-addons-for-gutenberg' ) }
							/>
						) }
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
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
		<SvgSourceSettings { ...{ ...props } } />
		<AnimationSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
		<AccessibilitySettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
