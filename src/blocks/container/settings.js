/**
 * External dependencies.
 */
import { memo, useState, useEffect } from '@wordpress/element';
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
		div: __( 'a generic container with no semantic meaning. Best for styling and layout purposes.', 'spectra' ),
		header: __( 'represents introductory content, typically containing navigation aids and headings.', 'spectra' ),
		footer: __( 'represents a footer for its nearest sectioning content, containing information about the author, copyright, or links.', 'spectra' ),
		main: __( 'represents the dominant content of the page. There should be only one main element per page.', 'spectra' ),
		article: __( 'represents a standalone piece of content that could be distributed independently, like a blog post or news article.', 'spectra' ),
		section: __( 'represents a thematic grouping of content, typically with a heading. Use when no other semantic element is appropriate.', 'spectra' ),
		aside: __( 'represents content that is tangentially related to the main content, like a sidebar or callout box.', 'spectra' ),
		figure: __( 'represents self-contained content, like images, diagrams, or code snippets, often with a caption.', 'spectra' ),
		figcaption: __( 'represents a caption or legend describing the content of its parent figure element.', 'spectra' ),
		summary: __( 'represents a summary, caption, or legend for a details element\'s disclosure box.', 'spectra' ),
		nav: __( 'represents a section of navigation links to other pages or parts within the current page.', 'spectra' ),
		a: __( 'creates a hyperlink to other pages, files, email addresses, or locations within the same page.', 'spectra' ),
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
						label={ __( 'Flex Direction', 'spectra' ) }
						resetAll={ () => {
							setAttributes( {
								orientationReverse: undefined,
							} )
						} }
						panelId={ clientId }
					>
						<ToolsPanelItem
							hasValue={ () => !! orientationReverse }
							label={ __( 'Orientation Reverse', 'spectra' ) }
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
								label={ __( 'Orientation Reverse', 'spectra' ) }
								checked={ !! orientationReverse }
								onChange={ ( value ) => setAttributes( { orientationReverse: value } ) }
								help={ __( 'When enabled, reverses the visual order of flex items. Use this to reverse the layout of containers within this block.', 'spectra' ) }
							/>
						</ToolsPanelItem>
					</ToolsPanel>
				) }
				<ToolsPanel
					label={ __( 'Container', 'spectra' ) }
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
						label={ __( 'Tag', 'spectra' ) }
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
							label={ __( 'HTML Tag', 'spectra' ) }
							value={ htmlTag }
							variant="default"
							options={ [
								{ value: 'div', label: __( 'Div', 'spectra' ) },
								{ value: 'header', label: __( 'Header', 'spectra' ) },
								{ value: 'footer', label: __( 'Footer', 'spectra' ) },
								{ value: 'main', label: __( 'Main', 'spectra' ) },
								{ value: 'article', label: __( 'Article', 'spectra' ) },
								{ value: 'section', label: __( 'Section', 'spectra' ) },
								{ value: 'aside', label: __( 'Aside', 'spectra' ) },
								{ value: 'figure', label: __( 'Figure', 'spectra' ) },
								{ value: 'figcaption', label: __( 'Figcaption', 'spectra' ) },
								{ value: 'summary', label: __( 'Summary', 'spectra' ) },
								{ value: 'nav', label: __( 'Nav', 'spectra' ) },
								{ value: 'a', label: __( 'Link', 'spectra' ) },
							] }
							onChange={ ( newHtmlTag ) => setAttributes( { htmlTag: newHtmlTag } ) }
							help={ __( 'Select the appropriate HTML element for semantic markup and accessibility.', 'spectra' ) }
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
											'spectra'
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
						label={ __( 'Overflow', 'spectra' ) }
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
							label={ __( 'Overflow', 'spectra' ) }
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
						label={ __( 'Sizes', 'spectra' ) }
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
						label={ __( 'Sizes', 'spectra' ) }
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
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover', 'spectra' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
				{
					colorValue: backgroundColor,
					gradientValue: backgroundGradient,
					label: __( 'Background', 'spectra' ),
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
			label: __( 'Advanced BG', 'spectra' ),
			valueAttr: 'advBgGradient',
			useAdvancedAttr: 'enableAdvBgGradient',
			showTopBorder: true,
		},
		{
			label: __( 'Advanced BG Hover', 'spectra' ),
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
			helpText={ __( 'Advanced gradients will override the basic background colors/gradients when set.', 'spectra' ) }
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
				label={__( 'Overlay Opacity', 'spectra' ) }
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Overlay Opacity', 'spectra' ) }
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
			label={ __( 'Box Shadow', 'spectra' ) }
			group="styles"
			showHoverState={ true }
		/>
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
				label={ __( 'Overlay Settings', 'spectra' ) }
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
				label={ __( 'Overlay Type', 'spectra' ) }
				onDeselect={ () => setAttributes( { overlayType: 'none' } ) }
				resetAllFilter={ () => ( { overlayType: 'none' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleGroupControl
						label={ __( 'Overlay Type', 'spectra' ) }
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
							label={ __( 'None', 'spectra' ) }
						/>
						<ToggleGroupControlOption
							value="image"
							label={ __( 'Image', 'spectra' ) }
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
												{ __( 'Add Overlay Image', 'spectra' ) }
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
													{ __( 'Image failed to load', 'spectra' ) }
												</div>
												<HStack spacing={ 4 }>
													<Button variant="secondary" onClick={ open }>
														{ __( 'Replace', 'spectra' ) }
													</Button>
													<Button
														variant="link"
														onClick={ () => setAttributes( { overlayImage: null } ) }
														isDestructive
													>
														{ __( 'Remove', 'spectra' ) }
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
						label={ __( 'Position', 'spectra' ) }
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
								label={ __( 'Overlay Position', 'spectra' ) }
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
									label={ __( 'Default', 'spectra' ) }
								/>
								<ToggleGroupControlOption
									value="custom"
									label={ __( 'Custom', 'spectra' ) }
								/>
							</ToggleGroupControl>

							{/* Default Focal Point Picker */}
							{ ( overlayPositionMode === 'default' || ! overlayPositionMode ) && (
								<FocalPointPicker
									label={ __( 'Position', 'spectra' ) }
									url={ overlayImage.url }
									value={ overlayPosition || { x: 0.5, y: 0.5 } }
									onChange={ ( newPoint ) => setAttributes( { overlayPosition: newPoint } ) }
								/>
							) }

							{/* Custom Position Controls */}
							{ overlayPositionMode === 'custom' && (
								<>
									<ToggleControl
										label={ __( 'Centralized Position', 'spectra' ) }
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
										label={ __( 'X Position', 'spectra' ) }
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
										label={ __( 'Y Position', 'spectra' ) }
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
						label={ __( 'Attachment', 'spectra' ) }
						onDeselect={ () => setAttributes( { overlayAttachment: 'scroll' } ) }
						resetAllFilter={ () => ( { overlayAttachment: 'scroll' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Attachment', 'spectra' ) }
							value={ overlayAttachment || 'scroll' }
							options={ [
								{ label: __( 'Scroll', 'spectra' ), value: 'scroll' },
								{ label: __( 'Fixed', 'spectra' ), value: 'fixed' },
								{ label: __( 'Inherit', 'spectra' ), value: 'inherit' },
							] }
							onChange={ ( value ) => setAttributes( { overlayAttachment: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayRepeat }
						label={ __( 'Repeat', 'spectra' ) }
						onDeselect={ () => setAttributes( { overlayRepeat: 'no-repeat' } ) }
						resetAllFilter={ () => ( { overlayRepeat: 'no-repeat' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Repeat', 'spectra' ) }
							value={ overlayRepeat || 'no-repeat' }
							options={ [
								{ label: __( 'No Repeat', 'spectra' ), value: 'no-repeat' },
								{ label: __( 'Repeat', 'spectra' ), value: 'repeat' },
								{ label: __( 'Repeat X', 'spectra' ), value: 'repeat-x' },
								{ label: __( 'Repeat Y', 'spectra' ), value: 'repeat-y' },
							] }
							onChange={ ( value ) => setAttributes( { overlayRepeat: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlaySize }
						label={ __( 'Size', 'spectra' ) }
						onDeselect={ () => setAttributes( { overlaySize: 'cover' } ) }
						resetAllFilter={ () => ( { overlaySize: 'cover' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Size', 'spectra' ) }
							value={ overlaySize || 'cover' }
							options={ [
								{ label: __( 'Cover', 'spectra' ), value: 'cover' },
								{ label: __( 'Contain', 'spectra' ), value: 'contain' },
								{ label: __( 'Auto', 'spectra' ), value: 'auto' },
								{ label: __( 'Custom', 'spectra' ), value: 'custom' },
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
						label={ __( 'Overlay Width', 'spectra' ) }
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
							label={ __( 'Overlay Width', 'spectra' ) }
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
						label={ __( 'Blend Mode', 'spectra' ) }
						onDeselect={ () => setAttributes( { overlayBlendMode: 'normal' } ) }
						resetAllFilter={ () => ( { overlayBlendMode: 'normal' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'Blend Mode', 'spectra' ) }
							value={ overlayBlendMode || 'normal' }
							options={ [
								{ label: __( 'Normal', 'spectra' ), value: 'normal' },
								{ label: __( 'Multiply', 'spectra' ), value: 'multiply' },
								{ label: __( 'Screen', 'spectra' ), value: 'screen' },
								{ label: __( 'Overlay', 'spectra' ), value: 'overlay' },
								{ label: __( 'Darken', 'spectra' ), value: 'darken' },
								{ label: __( 'Lighten', 'spectra' ), value: 'lighten' },
								{ label: __( 'Color Dodge', 'spectra' ), value: 'color-dodge' },
								{ label: __( 'Saturation', 'spectra' ), value: 'saturation' },
								{ label: __( 'Color', 'spectra' ), value: 'color' },
							] }
							onChange={ ( value ) => setAttributes( { overlayBlendMode: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! overlayOpacity }
						label={ __( 'Opacity', 'spectra' ) }
						onDeselect={ () => setAttributes( { overlayOpacity: undefined } ) }
						resetAllFilter={ () => ( { overlayOpacity: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<RangeControl
							label={ __( 'Opacity', 'spectra' ) }
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
			<BlockStyles {...{ ...props }} />
			{ showOverlaySettings && (
				<InspectorControls group="styles">
					<OverlaySettings { ...props } />
				</InspectorControls>
			) }
		</>
	);
};

export default memo( Settings );