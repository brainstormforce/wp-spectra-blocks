/**
 * External dependencies.
 */
import { memo, useState, useEffect, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import useLayoutInspectorGroup from '@spectra-hooks/useLayoutInspectorGroup';
import StylePanel from '@spectra-components/style-panel';
import {
	InspectorControls,
	useSettings,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalGrid as Grid,
	SelectControl,
	Notice,
	__experimentalSpacer as Spacer,
	Button,
	RangeControl,
	ToggleControl,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	FocalPointPicker,
	ColorPalette,
	BaseControl,
} from '@wordpress/components';
import ToolsPanelItem from '@spectra-components/tools-panel-item';

/**
 * Internal dependencies.
 */
import Background, { BackgroundExtensionsSlot } from '@spectra-components/background';
import BlockControlLink from '@spectra-components/block-control-link';
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';
import ShadowControl from '@spectra-components/shadow-control';
import useInspectorStyleGroup from '@spectra-hooks/useInspectorStyleGroup';
import { TAG_CONFIG } from './toolbar';
/*
 * `constants` only, deliberately: the extension's `utils/helpers.js` imports
 * its entry point, so reaching it from here would close a cycle.
 */
import {
	DESKTOP,
	coreResponsiveEditingActive,
	coreViewportStatesAreIndependent,
} from '../../extensions/responsive-controls/utils/constants';

// Derive SelectControl options from TAG_CONFIG (SSOT).
const TAG_OPTIONS = Object.entries( TAG_CONFIG ).map( ( [ value, { label } ] ) => ( { value, label } ) );

/**
 * Get description for HTML tag
 *
 * @param {string} tag The HTML tag
 * @return {string} The description for the tag
 */
const getTagDescription = ( tag ) => {
	const descriptions = {
		div: __( 'a generic container with no semantic meaning. Best for styling and layout purposes.', 'spectra-blocks' ),
		header: __( 'represents introductory content, typically containing navigation aids and headings.', 'spectra-blocks' ),
		footer: __( 'represents a footer for its nearest sectioning content, containing information about the author, copyright, or links.', 'spectra-blocks' ),
		main: __( 'represents the dominant content of the page. There should be only one main element per page.', 'spectra-blocks' ),
		article: __( 'represents a standalone piece of content that could be distributed independently, like a blog post or news article.', 'spectra-blocks' ),
		section: __( 'represents a thematic grouping of content, typically with a heading. Use when no other semantic element is appropriate.', 'spectra-blocks' ),
		aside: __( 'represents content that is tangentially related to the main content, like a sidebar or callout box.', 'spectra-blocks' ),
		figure: __( 'represents self-contained content, like images, diagrams, or code snippets, often with a caption.', 'spectra-blocks' ),
		figcaption: __( 'represents a caption or legend describing the content of its parent figure element.', 'spectra-blocks' ),
		summary: __( 'represents a summary, caption, or legend for a details element\'s disclosure box.', 'spectra-blocks' ),
		nav: __( 'represents a section of navigation links to other pages or parts within the current page.', 'spectra-blocks' ),
		a: __( 'creates a hyperlink to other pages, files, email addresses, or locations within the same page.', 'spectra-blocks' ),
	};
	
	return descriptions[ tag ] || descriptions.div;
};



/**
 * Element Sub-settings: General settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlocksSettings = memo( ( props ) => {
	const { attributes, setAttributes, clientId } = props;

	const { htmlTag, overflow, linkURL, linkTarget, linkRel, orientationReverse, layout } = attributes;
	
	// State for showing the tag description notice - always show by default.
	const [ showTagNotice] = useState( true );
	const [ previousTag, setPreviousTag ] = useState( htmlTag );
	
	// Update previous tag when tag changes (notice stays visible).
	useEffect( () => {
		if ( htmlTag !== previousTag ) {
			setPreviousTag( htmlTag );
		}
	}, [ htmlTag, previousTag ] );

	const { group: layoutGroup, isHosted: layoutHosted } = useLayoutInspectorGroup();

	return (
		<>
			{ 'a' === htmlTag && (
				<BlockControlLink
				attributes={ attributes }
				setAttributes={ setAttributes }
				url={ {
					label: 'linkURL',
					value: linkURL,
				} }
				target={ {
					label: 'linkTarget',
					value: linkTarget,
				} }
				rel={ {
					label: 'linkRel',
					value: linkRel,
				} }
			/>
			)   }
			{ layout?.type === 'flex' && (
				<InspectorControls group={ layoutGroup }>
					<StylePanel isHosted={ layoutHosted } label={ __( 'Flex Direction', 'spectra-blocks' ) } resetAll={ () => setAttributes( { orientationReverse: undefined } ) } panelId={ clientId } showHostedHeading={ false }>
					<ToolsPanelItem
						hasValue={ () => !! orientationReverse }
						label={ __( 'Orientation Reverse', 'spectra-blocks' ) }
						panelId={ clientId }
						onDeselect={ () => setAttributes( {
							orientationReverse: undefined,
						} ) }
						resetAllFilter={ () => ( {
							orientationReverse: undefined,
						} ) }
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Orientation Reverse', 'spectra-blocks' ) }
							checked={ !! orientationReverse }
							onChange={ ( value ) => setAttributes( { orientationReverse: value } ) }
							help={ __( 'When enabled, reverses the visual order of flex items. Use this to reverse the layout of containers within this block.', 'spectra-blocks' ) }
						/>
					</ToolsPanelItem>
									</StylePanel>
				</InspectorControls>
			) }
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Container', 'spectra-blocks' ) }
					resetAll={ () => {
						setAttributes( {
							htmlTag: 'div',
							overflow: 'visible',
						} )
					} }
					panelId={ clientId }
				>
					{/* 
						This tool panel item controls the HTML tag for the block.
						Reset conditions:
						- The htmlTag attribute is set. Default: 'div'.
					*/}
					<ToolsPanelItem
						hasValue={ () => !! htmlTag }
						label={ __( 'Tag', 'spectra-blocks' ) }
						panelId={ clientId }
						onDeselect={ () => setAttributes( {
							htmlTag: 'div',
						} ) }
						resetAll={ () => ( {
							htmlTag: 'div',
						} ) }
						isShownByDefault
					>
						<SelectControl
							label={ __( 'HTML Tag', 'spectra-blocks' ) }
							value={ htmlTag }
							variant="default"
							options={ TAG_OPTIONS }
							onChange={ ( newHtmlTag ) => setAttributes( { htmlTag: newHtmlTag } ) }
							help={ __( 'Select the appropriate HTML element for semantic markup and accessibility.', 'spectra-blocks' ) }
						/>
						
						{/* Notice showing tag description */}
						{ showTagNotice && (
							<>
								<Spacer marginY={ 3 } />
								<Notice 
									status="info" 
									isDismissible={ false }
								>
									{ sprintf(
										/* translators: 1: HTML tag name, 2: tag description */
										__(
											'The %1$s HTML tag %2$s',
											'spectra-blocks'
										),
										htmlTag === 'a' ? 'Link' : htmlTag,
										getTagDescription( htmlTag ).toLowerCase()
									) }
								</Notice>
							</>
						) }
						
					</ToolsPanelItem>
					{/* 
						This tool panel item controls the overflow property for the block.
						Reset conditions:
						- The overflow attribute is set. Default: 'visible'.
					*/}
					<ToolsPanelItem
						hasValue={ () => !! overflow }
						label={ __( 'Overflow', 'spectra-blocks' ) }
						panelId={ clientId }
						onDeselect={ () => setAttributes( {
							overflow: 'visible',
						} ) }
						resetAll={ () => ( {
							overflow: 'visible',
						} ) }
						isShownByDefault
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Overflow', 'spectra-blocks' ) }
							value={ overflow }
							onChange={ ( value ) => setAttributes( { overflow: value } ) }
							isBlock
						>
							<ToggleGroupControlOption value="visible" label="Visible" />
							<ToggleGroupControlOption value="hidden" label="Hidden" />
							<ToggleGroupControlOption value="auto" label="Auto" />
						</ToggleGroupControl>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
		</>
	);
} );

/**
 * Element Sub-settings: Style settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block styles.
 */
const BlockStyles = memo( ( props ) => {
    const {
        clientId,
        setAttributes,
        attributes,
		context = {},
		style
    } = props;

	const {
		background,
		backgroundColorHover,
		backgroundGradientHover,
		backgroundColor,
		backgroundGradient,
		dimRatio
	} = attributes;

	// On Tablet/Mobile core drops its unlabelled `styles` slot, so this fills
	// the surviving `background` slot instead and lets core's panel host it.
	const { group, isHosted } = useInspectorStyleGroup();

    return (
		<InspectorControls
			group={ group }
			resetAllFilter={ () => ( { background: undefined } ) }
		>
			<Background
				{ ...{
					clientId,
					attributes,
					setAttributes,
					isHosted,
					// Dynamic Image renders after the Shape Dividers section, so
					// the slot is placed there rather than below this panel.
					deferExtensions: true,
					background: {
						label: 'background',
						value: background,
					},
					backgroundColor,
					backgroundColorHover,
					backgroundGradient,
					backgroundGradientHover,
					context,
					dimRatio,
					style
				} }
			/>
		</InspectorControls>
    );
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Dimensions panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block dimentions styles.
 */
const DimensionSettings = memo( ( props ) => {
    const {
        clientId,
        setAttributes,
        attributes: {
			maxWidth,
			maxHeight,
			minWidth,
			minHeight,
			width,
			height,
			align
        },
    } = props;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( { availableUnits: availableUnits || ['px', '%', 'vw', 'em', 'rem'] } );

    return (
		<InspectorControls group="dimensions">
			{ align === 'none' || align === undefined ? (
				<>
				    {/* This tool panel item will require reset when any of these conditions are met:
                    - The width attribute is set. Default: undefined.
                    - The height attribute is set. Default: auto.
                    - The minWidth attribute is set. Default: undefined.
                    - The minHeight attribute is set. Default: undefined.
                    - The maxWidth attribute is set. Default: undefined.
                    - The maxHeight attribute is set. Default: undefined.
                    */}
					<ToolsPanelItem
						hasValue={ () => ( !! width || ( !! height && height !== 'auto' ) || !! minWidth || !! minHeight || !! maxWidth || !! maxHeight ) }
						label={ __( 'Sizes', 'spectra-blocks' ) }
						as={ Grid }
						panelId={ clientId }
						isShownByDefault
						onDeselect={ () => setAttributes( { 
							width: undefined,
							height: 'auto',
							minWidth: undefined,
							minHeight: undefined,
							maxWidth: undefined,
							maxHeight: undefined
							} ) }
						resetAllFilter={ () => ( {
							width: undefined,
							height: 'auto',
							minWidth: undefined,
							minHeight: undefined,
							maxWidth: undefined,
							maxHeight: undefined
						} ) }
					>
						<UnitControl __next40pxDefaultSize label="Width" value={ width } onChange={ value => setAttributes( { width: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Height" value={ height } onChange={ value => setAttributes( { height: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Min W" value={ minWidth } onChange={ value => setAttributes( { minWidth: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Min H" value={ minHeight } onChange={ value => setAttributes( { minHeight: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Max W" value={ maxWidth } onChange={ value => setAttributes( { maxWidth: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Max H" value={ maxHeight } onChange={ value => setAttributes( { maxHeight: value } ) } units={ units } />
					</ToolsPanelItem>
				</>
			) : (
				<>
					{/* This tool panel item will require reset when any of these conditions are met:
					- The height attribute is set. Default: auto.
					- The minHeight attribute is set. Default: undefined.
					- The maxHeight attribute is set. Default: undefined.
					*/}
					<ToolsPanelItem
						hasValue={ () => ( ( !! height && height !== 'auto' ) || !! minHeight || !! maxHeight ) }
						label={ __( 'Sizes', 'spectra-blocks' ) }
						panelId={ clientId }
						isShownByDefault
						onDeselect={ () => setAttributes( { 
							height: 'auto',
							minHeight: undefined,
							maxHeight: undefined
						} ) }
						resetAllFilter={ () => ( {
							height: 'auto',
							minHeight: undefined,
							maxHeight: undefined
						} ) }
					>
						<UnitControl __next40pxDefaultSize label="Height" value={ height } onChange={ value => setAttributes( { height: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Min Height" value={ minHeight } onChange={ value => setAttributes( { minHeight: value } ) } units={ units } />
						<UnitControl __next40pxDefaultSize label="Max Height" value={ maxHeight } onChange={ value => setAttributes( { maxHeight: value } ) } units={ units } />
					</ToolsPanelItem>
				</>
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
			textColorHover,
			backgroundColorHover,
			backgroundGradientHover,
			backgroundColor,
			backgroundGradient,
			topColor,
			bottomColor,
		},
	} = props;

	return (
		<>
			<InspectorColor
				settings={ [
					{
						colorValue: textColorHover,
						label: __( 'Text Hover', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
						resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
					},
				] }
				panelId={ clientId }
			/>

			<InspectorColor
				settings={ [
					{
						colorValue: backgroundColor,
						gradientValue: backgroundGradient,
						label: __( 'Background', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { backgroundColor: value } ),
						onGradientChange: ( value ) => setAttributes( { backgroundGradient: value } ),
						resetAllFilter: () => setAttributes( {
							backgroundColor: undefined,
							backgroundGradient: undefined,
						} ),
					},
				] }
				panelId={ clientId }
			/>
			
			<InspectorColor
				settings={ [
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
				] }
				panelId={ clientId }
			/>
			
			<InspectorColor
				settings={ [
					{
						colorValue: topColor,
						label: __( 'Top Divider', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { topColor: value } ),
						resetAllFilter: () => setAttributes( { topColor: undefined } ),
					},
				] }
				panelId={ clientId }
			/>
			
			<InspectorColor group="color"
				settings={ [
					{
						colorValue: bottomColor,
						label: __( 'Bottom Divider', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { bottomColor: value } ),
						resetAllFilter: () => setAttributes( { bottomColor: undefined } ),
					},
				] }
				panelId={ clientId }
			/>
		</>
	);
} );

/**
 * Element Sub-settings: Gradient settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered gradient settings.
 */
const GradientSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const gradientConfigs = [
		{
			label: __( 'Advanced BG', 'spectra-blocks' ),
			valueAttr: 'advBgGradient',
			useAdvancedAttr: 'enableAdvBgGradient',
			showTopBorder: true,
			angleAttr: 'advBgGradientAngle',
			location1Attr: 'advBgGradientLocation1',
			location2Attr: 'advBgGradientLocation2',
		},
		{
			label: __( 'Advanced BG Hover', 'spectra-blocks' ),
			valueAttr: 'advBgGradientHover',
			useAdvancedAttr: 'enableAdvBgGradientHover',
			angleAttr: 'advBgGradientHoverAngle',
			location1Attr: 'advBgGradientHoverLocation1',
			location2Attr: 'advBgGradientHoverLocation2',
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
			helpText={ __( 'Advanced gradients will override the basic background colors/gradients when set.', 'spectra-blocks' ) }
		/>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block opacity styles.
 */
const OpacitySettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			dimRatio
		},
	} = props;

	return (
		<InspectorControls group="color">
			<ToolsPanelItem
				hasValue={() => !! dimRatio }
				label={__( 'Background Color Opacity', 'spectra-blocks' ) }
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Background Color Opacity', 'spectra-blocks' ) }
					help={__( 'Opacity of the background colour or gradient. The overlay image has its own opacity under Overlay Settings.', 'spectra-blocks' ) }
					value={dimRatio}
					onChange={( value ) => setAttributes( { dimRatio: value } )}
					min={0}
					max={100}
					step={5}
					debounceDelay={150}
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Shadow settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block shadow settings.
 */
const ShadowSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			boxShadow,
			boxShadowHover
		},
	} = props;

	return (
		<ShadowControl
			clientId={ clientId }
			setAttributes={ setAttributes }
			shadow={ {
				label: 'boxShadow',
				value: boxShadow,
			} }
			shadowHover={ {
				label: 'boxShadowHover',
				value: boxShadowHover,
			} }
			label={ __( 'Box Shadow', 'spectra-blocks' ) }
			group="styles"
			showHoverState={ true }
		/>
	);
} );

/**
 * Element Sub-settings: Border Hover settings.
 *
 * Provides a toggle and color picker to set the border color on hover.
 * The border width and style will match the normal border settings from WordPress core.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered border hover settings.
 */
const BorderHoverSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			borderHover,
		},
	} = props;

	// Get colors from all palette sources (default, theme, and custom).
	const [ defaultColors ] = useSettings( 'color.palette.default' );
	const [ themeColors ] = useSettings( 'color.palette.theme' );
	const [ customColors ] = useSettings( 'color.palette.custom' );

	// Memoize color palette in grouped format to show "Theme", "Custom", and "Default" sections.
	const colorPalette = useMemo( () => {
		const groups = [];

		if ( themeColors?.length > 0 ) {
			groups.push( {
				name: __( 'Theme', 'spectra-blocks' ),
				colors: themeColors,
			} );
		}

		if ( customColors?.length > 0 ) {
			groups.push( {
				name: __( 'Custom', 'spectra-blocks' ),
				colors: customColors,
			} );
		}

		if ( defaultColors?.length > 0 ) {
			groups.push( {
				name: __( 'Default', 'spectra-blocks' ),
				colors: defaultColors,
			} );
		}

		return groups;
	}, [ defaultColors, themeColors, customColors ] );

	return (
		<InspectorControls group="border">
			<ToolsPanelItem
				hasValue={ () => !! borderHover }
				label={ __( 'Border Hover', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { borderHover: undefined } ) }
				resetAllFilter={ () => ( {
					borderHover: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleControl
						checked={ !! borderHover }
						label={ __( 'Enable Border Hover', 'spectra-blocks' ) }
						onChange={ ( value ) => {
							if ( value ) {
								setAttributes( { borderHover: { color: undefined } } );
							} else {
								setAttributes( { borderHover: undefined } );
							}
						} }
						help={ __( 'Enable border hover to customize the border color on hover.', 'spectra-blocks' ) }
					/>
					{ borderHover && (
						<VStack spacing={ 4 }>
							<BaseControl
								label={ __( 'Border Hover Color', 'spectra-blocks' ) }
								help={ __( 'Set the border color that appears when hovering over the container. The border width and style will match the normal border.', 'spectra-blocks' ) }
								id="spectra-container-border-hover-color"
							>
								<ColorPalette
									colors={ colorPalette }
									value={ borderHover?.color }
									onChange={ ( color ) => {
										setAttributes( {
											borderHover: {
												...borderHover,
												color,
											}
										} );
									} }
									enableAlpha={ true }
									clearable={ true }
								/>
							</BaseControl>
						</VStack>
					) }
				</VStack>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Overlay settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered overlay settings.
 */
const OverlaySettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			overlayType,
			overlayImage,
			overlayPosition,
			overlayPositionMode,
			overlayPositionCentered,
			overlayPositionX,
			overlayPositionY,
			overlayAttachment,
			overlayRepeat,
			overlaySize,
			overlayCustomWidth,
			overlayBlendMode,
			overlayOpacity,
		},
	} = props;

	// Use the available spacing units, or our units if they're not available.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'vh', 'em', 'rem' ],
	} );

	// Overlay is part of the background family, so on Tablet/Mobile — where core
	// drops the `styles` slot — it is hosted inside core's Background panel.
	const { group, isHosted } = useInspectorStyleGroup();

	// The reset shape is shared: as a panel it runs through `resetAll`, and while
	// hosted it runs through the fill's `resetAllFilter` instead.
	const overlayResetAttributes = {
						overlayType: 'none',
						overlayImage: undefined,
						overlayPosition: undefined,
						overlayPositionMode: undefined,
						overlayPositionCentered: undefined,
						overlayPositionX: undefined,
						overlayPositionY: undefined,
						overlayAttachment: undefined,
						overlayRepeat: undefined,
						overlaySize: undefined,
						overlayCustomWidth: undefined,
						overlayBlendMode: undefined,
						overlayOpacity: undefined,
	};

	return (
		<InspectorControls
			group={ group }
			resetAllFilter={ () => ( { ...overlayResetAttributes } ) }
		>
			<StylePanel
				isHosted={ isHosted }
				label={ __( 'Overlay Settings', 'spectra-blocks' ) }
				resetAll={ () => {
					// Clear all overlay-related attributes when switching to 'none'
					setAttributes( { ...overlayResetAttributes } );
				} }
				panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => !! overlayType && overlayType !== 'none' }
				label={ __( 'Overlay Type', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { overlayType: 'none' } ) }
				resetAllFilter={ () => ( { overlayType: 'none' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleGroupControl
						label={ __( 'Overlay Type', 'spectra-blocks' ) }
						value={ overlayType || 'none' }
						onChange={ ( value ) => {
							if ( value === 'none' ) {
								// Clear all overlay-related attributes when switching to 'none'
								setAttributes( {
									overlayType: 'none',
									overlayImage: undefined,
									overlayPosition: undefined,
									overlayPositionMode: undefined,
									overlayPositionCentered: undefined,
									overlayPositionX: undefined,
									overlayPositionY: undefined,
									overlayAttachment: undefined,
									overlayRepeat: undefined,
									overlaySize: undefined,
									overlayBlendMode: undefined,
									overlayOpacity: undefined,
								} );
							} else {
								setAttributes( { overlayType: value } );
							}
						} }
						isBlock
					>
						<ToggleGroupControlOption
							value="none"
							label={ __( 'None', 'spectra-blocks' ) }
						/>
						<ToggleGroupControlOption
							value="image"
							label={ __( 'Image', 'spectra-blocks' ) }
						/>
					</ToggleGroupControl>

					{ overlayType === 'image' && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) => {
									const overlayImageData = {
										id: media.id,
										url: media.url,
										type: media.type,
									};
									setAttributes( {
										overlayImage: overlayImageData,
									} );
								} }
								allowedTypes={ [ 'image' ] }
								value={ overlayImage?.id }
								render={ ( { open } ) => (
									<>
										{ ! overlayImage?.url ? (
											<Button
												onClick={ open }
												variant="secondary"
											>
												{ __( 'Add Overlay Image', 'spectra-blocks' ) }
											</Button>
										) : (
											<>
												<img 
													src={ overlayImage.url } 
													alt="" 
													style={{ maxWidth: '100%', height: 'auto' }}
													onError={ ( e ) => {
														e.target.style.display = 'none';
														e.target.nextSibling.style.display = 'block';
													} }
												/>
												<div style={{ display: 'none', padding: '10px', background: '#f0f0f0', textAlign: 'center' }}>
													{ __( 'Image failed to load', 'spectra-blocks' ) }
												</div>
												<HStack spacing={ 4 }>
													<Button variant="secondary" onClick={ open }>
														{ __( 'Replace', 'spectra-blocks' ) }
													</Button>
													<Button
														variant="link"
														onClick={ () => setAttributes( { overlayImage: null } ) }
														isDestructive
													>
														{ __( 'Remove', 'spectra-blocks' ) }
													</Button>
												</HStack>
											</>
										) }
									</>
								) }
							/>
						</MediaUploadCheck>
					) }


				</VStack>
			</ToolsPanelItem>
			{ overlayType === 'image' && overlayImage?.url && (
				<>
					<ToolsPanelItem
						hasValue={ () => !! overlayPosition || !! overlayPositionMode || !! overlayPositionX || !! overlayPositionY }
						label={ __( 'Position', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( {
							overlayPosition: undefined,
							overlayPositionMode: undefined,
							overlayPositionCentered: undefined,
							overlayPositionX: undefined,
							overlayPositionY: undefined,
						} ) }
						resetAllFilter={ () => ( {
							overlayPosition: undefined,
							overlayPositionMode: undefined,
							overlayPositionCentered: undefined,
							overlayPositionX: undefined,
							overlayPositionY: undefined,
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 4 }>
							{/* Position Mode Toggle */}
							<ToggleGroupControl
								label={ __( 'Overlay Position', 'spectra-blocks' ) }
								value={ overlayPositionMode || 'default' }
								onChange={ ( newMode ) => {
									setAttributes( {
										overlayPositionMode: newMode,
										// Reset position values when switching modes
										overlayPosition: newMode !== 'default' ? undefined : overlayPosition,
										overlayPositionX: newMode !== 'custom' ? undefined : overlayPositionX,
										overlayPositionY: newMode !== 'custom' ? undefined : overlayPositionY,
										overlayPositionCentered: newMode !== 'custom' ? undefined : overlayPositionCentered,
									} );
								} }
								isBlock
							>
								<ToggleGroupControlOption
									value="default"
									label={ __( 'Default', 'spectra-blocks' ) }
								/>
								<ToggleGroupControlOption
									value="custom"
									label={ __( 'Custom', 'spectra-blocks' ) }
								/>
							</ToggleGroupControl>

							{/* Default Focal Point Picker */}
							{ ( overlayPositionMode === 'default' || ! overlayPositionMode ) && (
								<FocalPointPicker
									label={ __( 'Position', 'spectra-blocks' ) }
									url={ overlayImage.url }
									value={ overlayPosition || { x: 0.5, y: 0.5 } }
									onChange={ ( newPoint ) => setAttributes( { overlayPosition: newPoint } ) }
								/>
							) }

							{/* Custom Position Controls */}
							{ overlayPositionMode === 'custom' && (
								<>
									<ToggleControl
										label={ __( 'Centralized Position', 'spectra-blocks' ) }
										checked={ overlayPositionCentered || false }
										onChange={ ( newCentered ) => {
											setAttributes( {
												overlayPositionCentered: newCentered,
												// Set default values when centered
												overlayPositionX: newCentered ? '50%' : ( overlayPositionX || '0%' ),
												overlayPositionY: newCentered ? '50%' : ( overlayPositionY || '0%' ),
											} );
										} }
									/>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'X Position', 'spectra-blocks' ) }
										labelPosition="top"
										value={ overlayPositionCentered ? '50%' : ( overlayPositionX || '0%' ) }
										onChange={ ( newX ) => {
											// Validate percentage values to be between -100% to 100%
											let validatedX = newX;
											if ( newX && newX.includes( '%' ) ) {
												const numericValue = parseFloat( newX );
												if ( numericValue < -100 ) {
													validatedX = '-100%';
												} else if ( numericValue > 100 ) {
													validatedX = '100%';
												}
											}
											setAttributes( {
												overlayPositionX: validatedX,
											} );
										} }
										units={ units }
										disabled={ overlayPositionCentered }
									/>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'Y Position', 'spectra-blocks' ) }
										labelPosition="top"
										value={ overlayPositionCentered ? '50%' : ( overlayPositionY || '0%' ) }
										onChange={ ( newY ) => {
											// Validate percentage values to be between -100% to 100%
											let validatedY = newY;
											if ( newY && newY.includes( '%' ) ) {
												const numericValue = parseFloat( newY );
												if ( numericValue < -100 ) {
													validatedY = '-100%';
												} else if ( numericValue > 100 ) {
													validatedY = '100%';
												}
											}
											setAttributes( {
												overlayPositionY: validatedY,
											} );
										} }
										units={ units }
										disabled={ overlayPositionCentered }
									/>
								</>
							) }
						</VStack>
					</ToolsPanelItem>
					<ToolsPanelItem
						hasValue={ () => !! overlayAttachment }
						label={ __( 'Attachment', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { overlayAttachment: 'scroll' } ) }
						resetAllFilter={ () => ( { overlayAttachment: 'scroll' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Attachment', 'spectra-blocks' ) }
							value={ overlayAttachment || 'scroll' }
							options={ [
								{ label: __( 'Scroll', 'spectra-blocks' ), value: 'scroll' },
								{ label: __( 'Fixed', 'spectra-blocks' ), value: 'fixed' },
								{ label: __( 'Inherit', 'spectra-blocks' ), value: 'inherit' },
							] }
							onChange={ ( value ) => setAttributes( { overlayAttachment: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayRepeat }
						label={ __( 'Repeat', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { overlayRepeat: 'no-repeat' } ) }
						resetAllFilter={ () => ( { overlayRepeat: 'no-repeat' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Repeat', 'spectra-blocks' ) }
							value={ overlayRepeat || 'no-repeat' }
							options={ [
								{ label: __( 'No Repeat', 'spectra-blocks' ), value: 'no-repeat' },
								{ label: __( 'Repeat', 'spectra-blocks' ), value: 'repeat' },
								{ label: __( 'Repeat X', 'spectra-blocks' ), value: 'repeat-x' },
								{ label: __( 'Repeat Y', 'spectra-blocks' ), value: 'repeat-y' },
							] }
							onChange={ ( value ) => setAttributes( { overlayRepeat: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlaySize }
						label={ __( 'Size', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { overlaySize: 'cover' } ) }
						resetAllFilter={ () => ( { overlaySize: 'cover' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Size', 'spectra-blocks' ) }
							value={ overlaySize || 'cover' }
							options={ [
								{ label: __( 'Cover', 'spectra-blocks' ), value: 'cover' },
								{ label: __( 'Contain', 'spectra-blocks' ), value: 'contain' },
								{ label: __( 'Auto', 'spectra-blocks' ), value: 'auto' },
								{ label: __( 'Custom', 'spectra-blocks' ), value: 'custom' },
							] }
							onChange={ ( value ) => {
								setAttributes( {
									overlaySize: value,
									// Set default width when switching to custom, clear when switching away
									overlayCustomWidth: value === 'custom' ? ( overlayCustomWidth || '100%' ) : undefined,
								} );
							} }
						/>
					</ToolsPanelItem>

				{/* Show custom width control when overlay size is set to custom */}
				{ overlaySize === 'custom' && (
					<ToolsPanelItem
						hasValue={ () => !! overlayCustomWidth }
						label={ __( 'Overlay Width', 'spectra-blocks' ) }
						onDeselect={ () => {
							setAttributes( {
								overlayCustomWidth: undefined,
							} );
						} }
						resetAllFilter={ () => ( {
							overlayCustomWidth: undefined,
						} ) }
						panelId={ clientId }
					>
						<UnitControl
							__next40pxDefaultSize
							label={ __( 'Overlay Width', 'spectra-blocks' ) }
							labelPosition="top"
							value={ overlayCustomWidth || '100%' }
							onChange={ ( newWidth ) => {
								let validatedWidth = newWidth;
								if ( newWidth && newWidth.includes( '%' ) ) {
									const numericValue = parseFloat( newWidth );
									if ( numericValue < 0 ) {
										validatedWidth = '0%';
									} else if ( numericValue > 100 ) {
										validatedWidth = '100%';
									}
								}
								setAttributes( {
									overlayCustomWidth: validatedWidth,
								} );
							} }
							units={ units }
						/>
					</ToolsPanelItem>
				) }

					<ToolsPanelItem
						hasValue={ () => !! overlayBlendMode }
						label={ __( 'Blend Mode', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { overlayBlendMode: 'normal' } ) }
						resetAllFilter={ () => ( { overlayBlendMode: 'normal' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Blend Mode', 'spectra-blocks' ) }
							value={ overlayBlendMode || 'normal' }
							options={ [
								{ label: __( 'Normal', 'spectra-blocks' ), value: 'normal' },
								{ label: __( 'Multiply', 'spectra-blocks' ), value: 'multiply' },
								{ label: __( 'Screen', 'spectra-blocks' ), value: 'screen' },
								{ label: __( 'Overlay', 'spectra-blocks' ), value: 'overlay' },
								{ label: __( 'Darken', 'spectra-blocks' ), value: 'darken' },
								{ label: __( 'Lighten', 'spectra-blocks' ), value: 'lighten' },
								{ label: __( 'Color Dodge', 'spectra-blocks' ), value: 'color-dodge' },
								{ label: __( 'Saturation', 'spectra-blocks' ), value: 'saturation' },
								{ label: __( 'Color', 'spectra-blocks' ), value: 'color' },
							] }
							onChange={ ( value ) => setAttributes( { overlayBlendMode: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayOpacity }
						label={ __( 'Overlay Image Opacity', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { overlayOpacity: undefined } ) }
						resetAllFilter={ () => ( { overlayOpacity: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<RangeControl
							label={ __( 'Overlay Image Opacity', 'spectra-blocks' ) }
							value={ overlayOpacity !== undefined ? overlayOpacity : 50 }
							onChange={ ( value ) => setAttributes( { overlayOpacity: value } ) }
							min={ 0 }
							max={ 100 }
							step={ 1 }
						/>
					</ToolsPanelItem>
				</>
			) }
			</StylePanel>
		</InspectorControls>
	);
} );

/**
 * The controls for one side's shape divider.
 *
 * Both sides carry the same settings under different attribute names, so
 * they are rendered from one component rather than two near-identical branches
 * — which is how the sides came to drift in the first place.
 *
 * @param {Object}   props               The element props.
 * @param {Object}   props.values        The side's current values, keyed by role.
 * @param {Function} props.onChange      Called with the attributes to set.
 * @param {string}   props.typeLabel     Label for the shape select.
 * @param {Array}    props.options       The shape options.
 * @param {Array}    props.units         The units for width and height.
 * @param {boolean}  props.perDeviceOnly Whether to render only the per-device
 *                                       controls, i.e. width and height.
 * @since x.x.x
 * @return {Element} The rendered controls.
 */
const DividerControls = ( {
	values,
	onChange,
	typeLabel,
	options,
	units,
	perDeviceOnly = false,
} ) => {
	const { type, flip, invert, contentAboveShape, width, height } = values;
	const isActive = type && type !== 'none';

	return (
		<VStack spacing={ 4 }>
			{/*
			  * Only the divider's width and height are per-device. Everything
			  * else here holds one value for every device, so it is rendered at
			  * Desktop alone — see `perDeviceOnly` at the call site — and while
			  * it IS rendered it carries the class that opts its label out of the
			  * responsive indicator the enhanced panel adds to every label it
			  * contains.
			  */}
			{ ! perDeviceOnly && (
				<SelectControl
					__nextHasNoMarginBottom
					className="spectra-not-per-device-control"
					label={ typeLabel }
					value={ type || 'none' }
					options={ options }
					onChange={ ( value ) => onChange( { type: value } ) }
				/>
			) }

			{ isActive && (
				<>
					{ ! perDeviceOnly && (
						<>
							<ToggleControl
								__nextHasNoMarginBottom
								className="spectra-not-per-device-control"
								label={ __( 'Flip', 'spectra-blocks' ) }
								checked={ !! flip }
								onChange={ ( value ) => onChange( { flip: value } ) }
							/>

							<ToggleControl
								__nextHasNoMarginBottom
								className="spectra-not-per-device-control"
								label={ __( 'Invert', 'spectra-blocks' ) }
								checked={ !! invert }
								onChange={ ( value ) => onChange( { invert: value } ) }
							/>

							<ToggleControl
								__nextHasNoMarginBottom
								className="spectra-not-per-device-control"
								label={ __( 'Bring To Front', 'spectra-blocks' ) }
								checked={ !! contentAboveShape }
								onChange={ ( value ) =>
									onChange( { contentAboveShape: value } )
								}
							/>
						</>
					) }

					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Shape Divider Width', 'spectra-blocks' ) }
						labelPosition="top"
						value={ width }
						min={ 0 }
						onChange={ ( value ) => onChange( { width: value } ) }
						units={ units }
					/>

					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Shape Divider Height', 'spectra-blocks' ) }
						labelPosition="top"
						value={ height }
						min={ 0 }
						onChange={ ( value ) => onChange( { height: value } ) }
						units={ units }
					/>
				</>
			) }
		</VStack>
	);
};

/**
 * Element Sub-settings: Shape Divider style settings.
 *
 * ONE ToolsPanelItem, deliberately.
 *
 * The section used to render four: the Top/Bottom switch, the active side's
 * settings, then Width and Height once that side had a shape. Every one of
 * those but the first mounts only under a condition, and that is what broke
 * the panel's order.
 *
 * Each inspector group is one `bubblesVirtually` slot, and every fill portals
 * its content into that slot's single container node. React tracks each
 * portal's children separately, so a node appended to an EARLIER portal after
 * mount lands at the end of the container — behind everything the later
 * portals have already put there. Measured on 7.1: choosing a shape with the
 * panel open sent Width and Height below Pro's Dynamic Image section, and
 * switching to Bottom sent "Bottom Type" there too, while a fresh render of
 * the same state was correctly ordered because then every portal mounted in
 * turn.
 *
 * Keeping the fill's top-level children fixed removes the whole class of
 * problem: everything conditional now lives INSIDE this item, where it is
 * ordinary subtree work that React places correctly. It also retires the
 * `hasValue: () => false` item the switch used to need — a control that stores
 * nothing had no business in the panel's options menu, where hiding it left
 * the other side unreachable.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ShapeDividerSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			topType,
			topFlip,
			topInvert,
			topContentAboveShape,
			topWidth,
			topHeight,
			bottomType,
			bottomFlip,
			bottomInvert,
			bottomContentAboveShape,
			bottomWidth,
			bottomHeight,
		},
	} = props;

	// Check if dividers are active.
	const hasTopDivider = topType && topType !== 'none';
	const hasBottomDivider = bottomType && bottomType !== 'none';

	/*
	 * Which side's settings are on screen (UI only).
	 *
	 * Seeded from the side that actually has a divider. This used to open on
	 * `top` unconditionally, and the inspector remounts whenever the block is
	 * reselected — so an author who had configured a BOTTOM divider came back
	 * to a panel reading "Top Type: None", with no sign anywhere that their
	 * divider still existed.
	 */
	const [ visibleDivider, setVisibleDivider ] = useState(
		() => ( ! hasTopDivider && hasBottomDivider ? 'bottom' : 'top' )
	);

	// Shape divider type options.
	const shapeOptions = [
		{ value: 'none', label: __( 'None', 'spectra-blocks' ) },
		{ value: 'tilt', label: __( 'Tilt', 'spectra-blocks' ) },
		{ value: 'mountains', label: __( 'Mountains', 'spectra-blocks' ) },
		{ value: 'wave_brush', label: __( 'Wave Brush', 'spectra-blocks' ) },
		{ value: 'waves', label: __( 'Waves', 'spectra-blocks' ) },
		{ value: 'wave_pattern', label: __( 'Waves Pattern', 'spectra-blocks' ) },
		{ value: 'triangle', label: __( 'Triangle', 'spectra-blocks' ) },
		{ value: 'drops', label: __( 'Drops', 'spectra-blocks' ) },
		{ value: 'clouds', label: __( 'Clouds', 'spectra-blocks' ) },
		{ value: 'zigzag', label: __( 'ZigZag', 'spectra-blocks' ) },
		{ value: 'pyramids', label: __( 'Pyramids', 'spectra-blocks' ) },
		{ value: 'triangle_asymmetrical', label: __( 'Triangle Asymmetrical', 'spectra-blocks' ) },
		{ value: 'tilt_opacity', label: __( 'Tilt Opacity', 'spectra-blocks' ) },
		{ value: 'fan_opacity', label: __( 'Fan Opacity', 'spectra-blocks' ) },
		{ value: 'curve', label: __( 'Curve', 'spectra-blocks' ) },
		{ value: 'curve_asymmetrical', label: __( 'Curve Asymmetrical', 'spectra-blocks' ) },
		{ value: 'curve_reverse', label: __( 'Curve Reverse', 'spectra-blocks' ) },
		{ value: 'curve_asym_reverse', label: __( 'Curve Asymmetrical Reverse', 'spectra-blocks' ) },
		{ value: 'arrow', label: __( 'Arrow', 'spectra-blocks' ) },
		{ value: 'arrow_split', label: __( 'Arrow Split', 'spectra-blocks' ) },
		{ value: 'book', label: __( 'Book', 'spectra-blocks' ) },
	];

	// Get the core provided units, else add the fallback.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	// `topWidth`/`topHeight`/`bottomWidth`/`bottomHeight` are per-device
	// attributes, so this panel has to stay reachable on Tablet and Mobile,
	// where core renders no `styles` slot to host it.
	const { group, isHosted } = useInspectorStyleGroup();

	const shapeDividerResetAttributes = {
		topType: 'none',
		topWidth: undefined,
		topHeight: undefined,
		topFlip: false,
		topInvert: false,
		topContentAboveShape: false,
		bottomType: 'none',
		bottomWidth: undefined,
		bottomHeight: undefined,
		bottomFlip: false,
		bottomInvert: false,
		bottomContentAboveShape: false,
	};

	/*
	 * The two sides, described rather than duplicated. `DividerControls` speaks
	 * in roles — type, flip, width — and this maps them onto the attribute names
	 * each side stores under.
	 */
	const sides = {
		top: {
			typeLabel: __( 'Top Type', 'spectra-blocks' ),
			values: {
				type: topType,
				flip: topFlip,
				invert: topInvert,
				contentAboveShape: topContentAboveShape,
				width: topWidth,
				height: topHeight,
			},
		},
		bottom: {
			typeLabel: __( 'Bottom Type', 'spectra-blocks' ),
			values: {
				type: bottomType,
				flip: bottomFlip,
				invert: bottomInvert,
				contentAboveShape: bottomContentAboveShape,
				width: bottomWidth,
				height: bottomHeight,
			},
		},
	};

	/*
	 * Whether the panel is editing a viewport NARROWER than Desktop.
	 *
	 * The device alone does not settle it. On 7.1 with core's "Responsive
	 * styles" off, previewing Tablet still edits the base layer, so every
	 * control there is live and hiding one would take away a setting the author
	 * can legitimately change. Below 7.1 the legacy projection always scopes an
	 * edit to the previewed device, so the device is the whole answer.
	 *
	 * The same pairing `ToolsPanelItem` uses to decide which layer it resets.
	 */
	const deviceType = useSelect(
		( select ) => select( 'core/editor' )?.getDeviceType?.(),
		[]
	);

	const editsNarrowerViewport =
		!! deviceType &&
		DESKTOP !== deviceType &&
		( ! coreViewportStatesAreIndependent() || coreResponsiveEditingActive() );

	const side = sides[ visibleDivider ];

	// Roles back to the attribute names for the side on screen.
	const setSideAttributes = ( changed ) => {
		const prefix = visibleDivider;
		const named = {};

		Object.entries( changed ).forEach( ( [ role, value ] ) => {
			named[ prefix + role.charAt( 0 ).toUpperCase() + role.slice( 1 ) ] = value;
		} );

		setAttributes( named );
	};

	/*
	 * Nothing to offer on a narrower viewport until a divider exists.
	 *
	 * Only width and height are per-device, and those render only for a side
	 * whose shape is set — so with no divider anywhere, Tablet and Mobile would
	 * show a "Shape Dividers" heading over an empty body. The shape, flip,
	 * invert and bring-to-front are chosen once at Desktop; the narrower
	 * viewports are for tuning the size afterwards.
	 *
	 * Every hook above has already run, so this return changes no hook order.
	 */
	if ( editsNarrowerViewport && ! hasTopDivider && ! hasBottomDivider ) {
		return null;
	}

	return (
		<InspectorControls
			group={ group }
			resetAllFilter={ () => ( { ...shapeDividerResetAttributes } ) }
		>
			<StylePanel
				isHosted={ isHosted }
				label={ __( 'Shape Dividers', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( { ...shapeDividerResetAttributes } );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! ( hasTopDivider || hasBottomDivider ) }
					label={ __( 'Shape Dividers', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { ...shapeDividerResetAttributes } ) }
					resetAllFilter={ () => ( { ...shapeDividerResetAttributes } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<VStack spacing={ 2 }>
							{/*
							  * Wrapped rather than given the class directly:
							  * `ToggleGroupControl` puts `className` on its inner
							  * control and renders its label in an outer wrapper,
							  * so the label is not a descendant of the element
							  * that carries it and the opt-out never applied.
							  */}
							<div className="spectra-not-per-device-control">
								<ToggleGroupControl
									__nextHasNoMarginBottom
									label={ __( 'Shape Divider Type', 'spectra-blocks' ) }
									value={ visibleDivider }
									onChange={ setVisibleDivider }
									isBlock
								>
									<ToggleGroupControlOption
										value="top"
										label={ __( 'Top', 'spectra-blocks' ) }
									/>
									<ToggleGroupControlOption
										value="bottom"
										label={ __( 'Bottom', 'spectra-blocks' ) }
									/>
								</ToggleGroupControl>
							</div>
							{ ( hasTopDivider || hasBottomDivider ) && (
								<Notice status="info" isDismissible={ false }>
									{ sprintf(
										/* translators: %s: the sides that have a shape divider, e.g. "Top, Bottom". */
										__( 'Divider set on: %s', 'spectra-blocks' ),
										[
											hasTopDivider && __( 'Top', 'spectra-blocks' ),
											hasBottomDivider && __( 'Bottom', 'spectra-blocks' ),
										].filter( Boolean ).join( ', ' )
									) }
								</Notice>
							) }
						</VStack>

						<DividerControls
							values={ side.values }
							onChange={ setSideAttributes }
							typeLabel={ side.typeLabel }
							options={ shapeOptions }
							units={ units }
							perDeviceOnly={ editsNarrowerViewport }
						/>
					</VStack>
				</ToolsPanelItem>
			</StylePanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: the media picker's filtered extensions.
 *
 * `Background` defers these (`deferExtensions`) so Pro's Dynamic Image section
 * lands here — after Overlay Settings and Shape Dividers — instead of directly
 * below the background media controls, where it separated those two sections
 * from the panel they belong to. The section itself is unchanged; only where
 * the slot is rendered decides where it appears.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered extensions.
 */
const MediaPickerExtensions = memo( () => {
	const { group } = useInspectorStyleGroup();

	return (
		<InspectorControls group={ group }>
			<BackgroundExtensionsSlot />
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
const Settings = ( props ) => {
	const { attributes } = props;
	const { background, style } = attributes;

	/*
	 * Show Overlay Settings unless EVERY band's background is a video.
	 *
	 * This used to read the device-resolved `background`, so the whole panel
	 * vanished while previewing a breakpoint whose background is a video —
	 * even though the overlay it edits is a per-device value that applies to
	 * the other breakpoints. Each band resolves over base, like the CSS.
	 */
	const bandBackgroundType = ( state ) => {
		const band = state ? style?.[ state ]?.background : style?.background;
		const base = style?.background ?? background;
		return ( band ?? base )?.type;
	};
	const showOverlaySettings = ! [ '', '@tablet', '@mobile' ].every( ( state ) => 'video' === bandBackgroundType( state ) );

	return (
		<>
			<BlocksSettings { ...{ ...props } } />
			<ColorSettings { ...{ ...props } } />
			<GradientSettings { ...{ ...props } } />
			<OpacitySettings { ...{ ...props } } />
			<DimensionSettings { ...{ ...props } } />
			<ShadowSettings { ...{ ...props } } />
			<BorderHoverSettings { ...{ ...props } } />
			<BlockStyles {...{ ...props }} />
			{ showOverlaySettings && <OverlaySettings { ...props } /> }
			<ShapeDividerSettings { ...props } />
			<MediaPickerExtensions { ...props } />
		</>
	);
};

export default memo( Settings );