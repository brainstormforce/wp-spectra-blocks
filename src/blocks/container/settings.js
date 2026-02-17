/**
 * External dependencies.
 */
import { memo, useState, useEffect, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
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
	__experimentalToolsPanelItem as ToolsPanelItem,
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

/**
 * Internal dependencies.
 */
import Background from '@spectra-components/background';
import BlockControlLink from '@spectra-components/block-control-link';
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';
import ShadowControl from '@spectra-components/shadow-control';


/**
 * Get description for HTML tag
 * 
 * @param {string} tag The HTML tag
 * @return {string} The description for the tag
 */
const getTagDescription = ( tag ) => {
	const descriptions = {
		div: __( 'a generic container with no semantic meaning. Best for styling and layout purposes.', 'ultimate-addons-for-gutenberg' ),
		header: __( 'represents introductory content, typically containing navigation aids and headings.', 'ultimate-addons-for-gutenberg' ),
		footer: __( 'represents a footer for its nearest sectioning content, containing information about the author, copyright, or links.', 'ultimate-addons-for-gutenberg' ),
		main: __( 'represents the dominant content of the page. There should be only one main element per page.', 'ultimate-addons-for-gutenberg' ),
		article: __( 'represents a standalone piece of content that could be distributed independently, like a blog post or news article.', 'ultimate-addons-for-gutenberg' ),
		section: __( 'represents a thematic grouping of content, typically with a heading. Use when no other semantic element is appropriate.', 'ultimate-addons-for-gutenberg' ),
		aside: __( 'represents content that is tangentially related to the main content, like a sidebar or callout box.', 'ultimate-addons-for-gutenberg' ),
		figure: __( 'represents self-contained content, like images, diagrams, or code snippets, often with a caption.', 'ultimate-addons-for-gutenberg' ),
		figcaption: __( 'represents a caption or legend describing the content of its parent figure element.', 'ultimate-addons-for-gutenberg' ),
		summary: __( 'represents a summary, caption, or legend for a details element\'s disclosure box.', 'ultimate-addons-for-gutenberg' ),
		nav: __( 'represents a section of navigation links to other pages or parts within the current page.', 'ultimate-addons-for-gutenberg' ),
		a: __( 'creates a hyperlink to other pages, files, email addresses, or locations within the same page.', 'ultimate-addons-for-gutenberg' ),
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
			<InspectorControls>
			{ layout?.type === 'flex' && (
					<ToolsPanel
						label={ __( 'Flex Direction', 'ultimate-addons-for-gutenberg' ) }
						resetAll={ () => {
							setAttributes( {
								orientationReverse: undefined,
							} )
						} }
						panelId={ clientId }
					>
						<ToolsPanelItem
							hasValue={ () => !! orientationReverse }
							label={ __( 'Orientation Reverse', 'ultimate-addons-for-gutenberg' ) }
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
								label={ __( 'Orientation Reverse', 'ultimate-addons-for-gutenberg' ) }
								checked={ !! orientationReverse }
								onChange={ ( value ) => setAttributes( { orientationReverse: value } ) }
								help={ __( 'When enabled, reverses the visual order of flex items. Use this to reverse the layout of containers within this block.', 'ultimate-addons-for-gutenberg' ) }
							/>
						</ToolsPanelItem>
					</ToolsPanel>
				) }
				<ToolsPanel
					label={ __( 'Container', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Tag', 'ultimate-addons-for-gutenberg' ) }
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
							label={ __( 'HTML Tag', 'ultimate-addons-for-gutenberg' ) }
							value={ htmlTag }
							variant="default"
							options={ [
								{ value: 'div', label: __( 'Div', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'header', label: __( 'Header', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'footer', label: __( 'Footer', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'main', label: __( 'Main', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'article', label: __( 'Article', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'section', label: __( 'Section', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'aside', label: __( 'Aside', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'figure', label: __( 'Figure', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'figcaption', label: __( 'Figcaption', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'summary', label: __( 'Summary', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'nav', label: __( 'Nav', 'ultimate-addons-for-gutenberg' ) },
								{ value: 'a', label: __( 'Link', 'ultimate-addons-for-gutenberg' ) },
							] }
							onChange={ ( newHtmlTag ) => setAttributes( { htmlTag: newHtmlTag } ) }
							help={ __( 'Select the appropriate HTML element for semantic markup and accessibility.', 'ultimate-addons-for-gutenberg' ) }
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
											'ultimate-addons-for-gutenberg'
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
						label={ __( 'Overflow', 'ultimate-addons-for-gutenberg' ) }
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
							label={ __( 'Overflow', 'ultimate-addons-for-gutenberg' ) }
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

    return (
		<InspectorControls group="styles">
			<Background
				{ ...{
					clientId,
					attributes,
					setAttributes,
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
						label={ __( 'Sizes', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Sizes', 'ultimate-addons-for-gutenberg' ) }
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
						label: __( 'Text Hover', 'ultimate-addons-for-gutenberg' ),
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
						label: __( 'Background', 'ultimate-addons-for-gutenberg' ),
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
						label: __( 'Background Hover', 'ultimate-addons-for-gutenberg' ),
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
						label: __( 'Top Divider', 'ultimate-addons-for-gutenberg' ),
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
						label: __( 'Bottom Divider', 'ultimate-addons-for-gutenberg' ),
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
			label: __( 'Advanced BG', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradient',
			useAdvancedAttr: 'enableAdvBgGradient',
			showTopBorder: true,
		},
		{
			label: __( 'Advanced BG Hover', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradientHover',
			useAdvancedAttr: 'enableAdvBgGradientHover',
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
			helpText={ __( 'Advanced gradients will override the basic background colors/gradients when set.', 'ultimate-addons-for-gutenberg' ) }
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
				label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' ) }
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
			label={ __( 'Box Shadow', 'ultimate-addons-for-gutenberg' ) }
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
				name: __( 'Theme', 'ultimate-addons-for-gutenberg' ),
				colors: themeColors,
			} );
		}

		if ( customColors?.length > 0 ) {
			groups.push( {
				name: __( 'Custom', 'ultimate-addons-for-gutenberg' ),
				colors: customColors,
			} );
		}

		if ( defaultColors?.length > 0 ) {
			groups.push( {
				name: __( 'Default', 'ultimate-addons-for-gutenberg' ),
				colors: defaultColors,
			} );
		}

		return groups;
	}, [ defaultColors, themeColors, customColors ] );

	return (
		<InspectorControls group="border">
			<ToolsPanelItem
				hasValue={ () => !! borderHover }
				label={ __( 'Border Hover', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Enable Border Hover', 'ultimate-addons-for-gutenberg' ) }
						onChange={ ( value ) => {
							if ( value ) {
								setAttributes( { borderHover: { color: undefined } } );
							} else {
								setAttributes( { borderHover: undefined } );
							}
						} }
						help={ __( 'Enable border hover to customize the border color on hover.', 'ultimate-addons-for-gutenberg' ) }
					/>
					{ borderHover && (
						<VStack spacing={ 4 }>
							<BaseControl
								label={ __( 'Border Hover Color', 'ultimate-addons-for-gutenberg' ) }
								help={ __( 'Set the border color that appears when hovering over the container. The border width and style will match the normal border.', 'ultimate-addons-for-gutenberg' ) }
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

	return (
		<>
			<ToolsPanel
				label={ __( 'Overlay Settings', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
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
						overlayCustomWidth: undefined,
						overlayBlendMode: undefined,
						overlayOpacity: undefined,
					} );
				} }
				panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => !! overlayType && overlayType !== 'none' }
				label={ __( 'Overlay Type', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { overlayType: 'none' } ) }
				resetAllFilter={ () => ( { overlayType: 'none' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleGroupControl
						label={ __( 'Overlay Type', 'ultimate-addons-for-gutenberg' ) }
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
							label={ __( 'None', 'ultimate-addons-for-gutenberg' ) }
						/>
						<ToggleGroupControlOption
							value="image"
							label={ __( 'Image', 'ultimate-addons-for-gutenberg' ) }
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
												{ __( 'Add Overlay Image', 'ultimate-addons-for-gutenberg' ) }
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
													{ __( 'Image failed to load', 'ultimate-addons-for-gutenberg' ) }
												</div>
												<HStack spacing={ 4 }>
													<Button variant="secondary" onClick={ open }>
														{ __( 'Replace', 'ultimate-addons-for-gutenberg' ) }
													</Button>
													<Button
														variant="link"
														onClick={ () => setAttributes( { overlayImage: null } ) }
														isDestructive
													>
														{ __( 'Remove', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Position', 'ultimate-addons-for-gutenberg' ) }
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
								label={ __( 'Overlay Position', 'ultimate-addons-for-gutenberg' ) }
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
									label={ __( 'Default', 'ultimate-addons-for-gutenberg' ) }
								/>
								<ToggleGroupControlOption
									value="custom"
									label={ __( 'Custom', 'ultimate-addons-for-gutenberg' ) }
								/>
							</ToggleGroupControl>

							{/* Default Focal Point Picker */}
							{ ( overlayPositionMode === 'default' || ! overlayPositionMode ) && (
								<FocalPointPicker
									label={ __( 'Position', 'ultimate-addons-for-gutenberg' ) }
									url={ overlayImage.url }
									value={ overlayPosition || { x: 0.5, y: 0.5 } }
									onChange={ ( newPoint ) => setAttributes( { overlayPosition: newPoint } ) }
								/>
							) }

							{/* Custom Position Controls */}
							{ overlayPositionMode === 'custom' && (
								<>
									<ToggleControl
										label={ __( 'Centralized Position', 'ultimate-addons-for-gutenberg' ) }
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
										label={ __( 'X Position', 'ultimate-addons-for-gutenberg' ) }
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
										label={ __( 'Y Position', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Attachment', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { overlayAttachment: 'scroll' } ) }
						resetAllFilter={ () => ( { overlayAttachment: 'scroll' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Attachment', 'ultimate-addons-for-gutenberg' ) }
							value={ overlayAttachment || 'scroll' }
							options={ [
								{ label: __( 'Scroll', 'ultimate-addons-for-gutenberg' ), value: 'scroll' },
								{ label: __( 'Fixed', 'ultimate-addons-for-gutenberg' ), value: 'fixed' },
								{ label: __( 'Inherit', 'ultimate-addons-for-gutenberg' ), value: 'inherit' },
							] }
							onChange={ ( value ) => setAttributes( { overlayAttachment: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayRepeat }
						label={ __( 'Repeat', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { overlayRepeat: 'no-repeat' } ) }
						resetAllFilter={ () => ( { overlayRepeat: 'no-repeat' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Repeat', 'ultimate-addons-for-gutenberg' ) }
							value={ overlayRepeat || 'no-repeat' }
							options={ [
								{ label: __( 'No Repeat', 'ultimate-addons-for-gutenberg' ), value: 'no-repeat' },
								{ label: __( 'Repeat', 'ultimate-addons-for-gutenberg' ), value: 'repeat' },
								{ label: __( 'Repeat X', 'ultimate-addons-for-gutenberg' ), value: 'repeat-x' },
								{ label: __( 'Repeat Y', 'ultimate-addons-for-gutenberg' ), value: 'repeat-y' },
							] }
							onChange={ ( value ) => setAttributes( { overlayRepeat: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlaySize }
						label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { overlaySize: 'cover' } ) }
						resetAllFilter={ () => ( { overlaySize: 'cover' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
							value={ overlaySize || 'cover' }
							options={ [
								{ label: __( 'Cover', 'ultimate-addons-for-gutenberg' ), value: 'cover' },
								{ label: __( 'Contain', 'ultimate-addons-for-gutenberg' ), value: 'contain' },
								{ label: __( 'Auto', 'ultimate-addons-for-gutenberg' ), value: 'auto' },
								{ label: __( 'Custom', 'ultimate-addons-for-gutenberg' ), value: 'custom' },
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
						label={ __( 'Overlay Width', 'ultimate-addons-for-gutenberg' ) }
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
							label={ __( 'Overlay Width', 'ultimate-addons-for-gutenberg' ) }
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
						label={ __( 'Blend Mode', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { overlayBlendMode: 'normal' } ) }
						resetAllFilter={ () => ( { overlayBlendMode: 'normal' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Blend Mode', 'ultimate-addons-for-gutenberg' ) }
							value={ overlayBlendMode || 'normal' }
							options={ [
								{ label: __( 'Normal', 'ultimate-addons-for-gutenberg' ), value: 'normal' },
								{ label: __( 'Multiply', 'ultimate-addons-for-gutenberg' ), value: 'multiply' },
								{ label: __( 'Screen', 'ultimate-addons-for-gutenberg' ), value: 'screen' },
								{ label: __( 'Overlay', 'ultimate-addons-for-gutenberg' ), value: 'overlay' },
								{ label: __( 'Darken', 'ultimate-addons-for-gutenberg' ), value: 'darken' },
								{ label: __( 'Lighten', 'ultimate-addons-for-gutenberg' ), value: 'lighten' },
								{ label: __( 'Color Dodge', 'ultimate-addons-for-gutenberg' ), value: 'color-dodge' },
								{ label: __( 'Saturation', 'ultimate-addons-for-gutenberg' ), value: 'saturation' },
								{ label: __( 'Color', 'ultimate-addons-for-gutenberg' ), value: 'color' },
							] }
							onChange={ ( value ) => setAttributes( { overlayBlendMode: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayOpacity }
						label={ __( 'Opacity', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( { overlayOpacity: undefined } ) }
						resetAllFilter={ () => ( { overlayOpacity: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<RangeControl
							label={ __( 'Opacity', 'ultimate-addons-for-gutenberg' ) }
							value={ overlayOpacity !== undefined ? overlayOpacity : 50 }
							onChange={ ( value ) => setAttributes( { overlayOpacity: value } ) }
							min={ 0 }
							max={ 100 }
							step={ 1 }
						/>
					</ToolsPanelItem>
				</>
			) }
			</ToolsPanel>
		</>
	);
} );

/**
 * Element Sub-settings: Shape Divider style settings.
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
			bottomType,
			bottomFlip,
			bottomInvert,
			bottomContentAboveShape,
			topWidth,
			topHeight,
			bottomWidth,
			bottomHeight,
		},
	} = props;

	// Local state to control which divider settings are visible (UI only).
	const [ visibleDivider, setVisibleDivider ] = useState( 'top' );

	// Shape divider type options.
	const shapeOptions = [
		{ value: 'none', label: __( 'None', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'tilt', label: __( 'Tilt', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'mountains', label: __( 'Mountains', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'wave_brush', label: __( 'Wave Brush', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'waves', label: __( 'Waves', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'wave_pattern', label: __( 'Waves Pattern', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'triangle', label: __( 'Triangle', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'drops', label: __( 'Drops', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'clouds', label: __( 'Clouds', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'zigzag', label: __( 'ZigZag', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'pyramids', label: __( 'Pyramids', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'triangle_asymmetrical', label: __( 'Triangle Asymmetrical', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'tilt_opacity', label: __( 'Tilt Opacity', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'fan_opacity', label: __( 'Fan Opacity', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'curve', label: __( 'Curve', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'curve_asymmetrical', label: __( 'Curve Asymmetrical', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'curve_reverse', label: __( 'Curve Reverse', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'curve_asym_reverse', label: __( 'Curve Asymmetrical Reverse', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'arrow', label: __( 'Arrow', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'arrow_split', label: __( 'Arrow Split', 'ultimate-addons-for-gutenberg' ) },
		{ value: 'book', label: __( 'Book', 'ultimate-addons-for-gutenberg' ) },
	];

	// Get the core provided units, else add the fallback.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	// Check if dividers are active.
	const hasTopDivider = topType && topType !== 'none';
	const hasBottomDivider = bottomType && bottomType !== 'none';

	return (
		<InspectorControls group="styles">
			<ToolsPanel
				label={ __( 'Shape Dividers', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
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
					} );
				} }
				panelId={ clientId }
			>
				{/* Divider Position Selector */}
				<ToolsPanelItem
					hasValue={ () => false }
					label={ __( 'Divider Position', 'ultimate-addons-for-gutenberg' ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						label={ __( 'Shape Divider Type ', 'ultimate-addons-for-gutenberg' ) }
						value={ visibleDivider }
						onChange={ setVisibleDivider }
						isBlock
					>
						<ToggleGroupControlOption
							value="top"
							label={ __( 'Top', 'ultimate-addons-for-gutenberg' ) }
						/>
						<ToggleGroupControlOption
							value="bottom"
							label={ __( 'Bottom', 'ultimate-addons-for-gutenberg' ) }
						/>
					</ToggleGroupControl>
				</ToolsPanelItem>

				{/* Top Shape Divider Settings */}
				{ visibleDivider === 'top' && (
					<>
						<ToolsPanelItem
							hasValue={ () => hasTopDivider }
							label={ __( 'Top Shape Divider', 'ultimate-addons-for-gutenberg' ) }
							onDeselect={ () => setAttributes( {
								topType: 'none',
								topWidth: undefined,
								topHeight: undefined,
								topFlip: false,
								topInvert: false,
								topContentAboveShape: false,
							} ) }
							resetAllFilter={ () => ( {
								topType: 'none',
								topWidth: undefined,
								topHeight: undefined,
								topFlip: false,
								topInvert: false,
								topContentAboveShape: false,
							} ) }
							isShownByDefault
							panelId={ clientId }
						>
							<VStack spacing={ 4 }>
								<SelectControl
									__nextHasNoMarginBottom
									label={ __( 'Top Type', 'ultimate-addons-for-gutenberg' ) }
									value={ topType || 'none' }
									options={ shapeOptions }
									onChange={ ( value ) => setAttributes( { topType: value } ) }
								/>

								{ hasTopDivider && (
									<>
										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Flip', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! topFlip }
											onChange={ ( value ) => setAttributes( { topFlip: value } ) }
										/>

										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Invert', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! topInvert }
											onChange={ ( value ) => setAttributes( { topInvert: value } ) }
										/>

										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Bring To Front', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! topContentAboveShape }
											onChange={ ( value ) => setAttributes( { topContentAboveShape: value } ) }
										/>
									</>
								) }
							</VStack>
						</ToolsPanelItem>
						{ hasTopDivider && (
							<>
								<ToolsPanelItem
									hasValue={ () => !! topWidth }
									label={ __( 'Top Shape Width', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => setAttributes( { topWidth: undefined } ) }
									resetAllFilter={ () => ( {
										topWidth: undefined,
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'Width', 'ultimate-addons-for-gutenberg' ) }
										labelPosition="top"
										value={ topWidth }
										min={ 0 }
										onChange={ ( value ) => setAttributes( { topWidth: value } ) }
										units={ units }
									/>
								</ToolsPanelItem>
								<ToolsPanelItem
									hasValue={ () => !! topHeight }
									label={ __( 'Top Shape Height', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => setAttributes( { topHeight: undefined } ) }
									resetAllFilter={ () => ( {
										topHeight: undefined,
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
										labelPosition="top"
										value={ topHeight }
										min={ 0 }
										onChange={ ( value ) => setAttributes( { topHeight: value } ) }
										units={ units }
									/>
								</ToolsPanelItem>
							</>
						) }
					</>
				) }

				{/* Bottom Shape Divider Settings */}
				{ visibleDivider === 'bottom' && (
					<>
						<ToolsPanelItem
							hasValue={ () => hasBottomDivider }
							label={ __( 'Bottom Shape Divider', 'ultimate-addons-for-gutenberg' ) }
							onDeselect={ () => setAttributes( {
								bottomType: 'none',
								bottomWidth: undefined,
								bottomHeight: undefined,
								bottomFlip: false,
								bottomInvert: false,
								bottomContentAboveShape: false,
							} ) }
							resetAllFilter={ () => ( {
								bottomType: 'none',
								bottomWidth: undefined,
								bottomHeight: undefined,
								bottomFlip: false,
								bottomInvert: false,
								bottomContentAboveShape: false,
							} ) }
							isShownByDefault
							panelId={ clientId }
						>
							<VStack spacing={ 4 }>
								<SelectControl
									__nextHasNoMarginBottom
									label={ __( 'Bottom Type', 'ultimate-addons-for-gutenberg' ) }
									value={ bottomType || 'none' }
									options={ shapeOptions }
									onChange={ ( value ) => setAttributes( { bottomType: value } ) }
								/>

								{ hasBottomDivider && (
									<>
										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Flip', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! bottomFlip }
											onChange={ ( value ) => setAttributes( { bottomFlip: value } ) }
										/>

										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Invert', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! bottomInvert }
											onChange={ ( value ) => setAttributes( { bottomInvert: value } ) }
										/>

										<ToggleControl
											__nextHasNoMarginBottom
											label={ __( 'Bring To Front', 'ultimate-addons-for-gutenberg' ) }
											checked={ !! bottomContentAboveShape }
											onChange={ ( value ) => setAttributes( { bottomContentAboveShape: value } ) }
										/>
									</>
								) }
							</VStack>
						</ToolsPanelItem>
						{ hasBottomDivider && (
							<>
								<ToolsPanelItem
									hasValue={ () => !! bottomWidth }
									label={ __( 'Bottom Shape Width', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => setAttributes( { bottomWidth: undefined } ) }
									resetAllFilter={ () => ( {
										bottomWidth: undefined,
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'Width', 'ultimate-addons-for-gutenberg' ) }
										labelPosition="top"
										value={ bottomWidth }
										min={ 0 }
										onChange={ ( value ) => setAttributes( { bottomWidth: value } ) }
										units={ units }
									/>
								</ToolsPanelItem>
								<ToolsPanelItem
									hasValue={ () => !! bottomHeight }
									label={ __( 'Bottom Shape Height', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => setAttributes( { bottomHeight: undefined } ) }
									resetAllFilter={ () => ( {
										bottomHeight: undefined,
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<UnitControl
										__next40pxDefaultSize
										label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
										labelPosition="top"
										value={ bottomHeight }
										min={ 0 }
										onChange={ ( value ) => setAttributes( { bottomHeight: value } ) }
										units={ units }
									/>
								</ToolsPanelItem>
							</>
						) }
					</>
				) }
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
const Settings = ( props ) => {
	const { attributes } = props;
	const { background } = attributes;

	// Show overlay settings when background is not video (for image, none, and undefined)
	const showOverlaySettings = background?.type !== 'video';

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
			{ showOverlaySettings && (
				<InspectorControls group="styles">
					<OverlaySettings { ...props } />
				</InspectorControls>
			) }
			<ShapeDividerSettings { ...props } />
		</>
	);
};

export default memo( Settings );