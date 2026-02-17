/**
 * External dependencies.
 */
import { memo, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalVStack as VStack,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	TabPanel,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import Background from '@spectra-components/background';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

/**
 * Navigation Icon Settings Component
 * 
 * @param {Object}   props               The element props.
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @since x.x.x
 * @return {Element} The rendered block Navigation Icon settings.
 */
const NavigationIconSettings = memo( ( { attributes, setAttributes } ) => {
	const { navigationPrevIcon, navigationNextIcon } = attributes;

	// Performance optimization: Memoize tab configuration
	const tabConfig = useMemo( () => [
		{
			name: 'prev',
			title: __( 'Previous', 'ultimate-addons-for-gutenberg' ),
			className: 'spectra-tab-prev',
		},
		{
			name: 'next',
			title: __( 'Next', 'ultimate-addons-for-gutenberg' ),
			className: 'spectra-tab-next',
		},
	], [] );

	return (
		<TabPanel
			className="spectra-tab-panel"
			activeClass="is-active"
			tabs={ tabConfig }
		>
			{ ( tab ) => {
				const isNext = tab.name === 'next';
				const currentIcon = isNext ? navigationNextIcon : navigationPrevIcon;

				return (
						<IconPicker
							value={ currentIcon }
							onChange={ ( value ) =>
								setAttributes( {
									[ isNext
										? 'navigationNextIcon'
										: 'navigationPrevIcon' ]: value,
								} )
							}
							enableSearch={ true }
							label={
								isNext
									? __(
											'Next Icon',
											'ultimate-addons-for-gutenberg'
									  )
									: __(
											'Previous Icon',
											'ultimate-addons-for-gutenberg'
									  )
							}
						/>
				);
			} }
		</TabPanel>
	);
} );

/**
 * Element Sub-settings: Underlying Style settings from the Background component.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockStyle = memo( ( props ) => {
	const { clientId, setAttributes, attributes, context = {}, style } = props;
  
	const { 
		background,
		backgroundColor,
		dimRatio,
		backgroundGradient
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
					dimRatio,
					backgroundColor,
					backgroundGradient,
					context,
					style
				} }
			/>
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
const BlockSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			slidesPerView,
			spaceBetween,
			loop,
			autoplay,
			autoplayPauseOnHover,
			autoplayPauseOnInteraction,
			autoplaySpeed,
			allowTouchMove,
			navigation,
			pagination,
			navigationPrevIcon,
			navigationNextIcon,
			overflow,
		},
	} = props;

	// Simple state for slides per view (free version always uses 1)
	const slidesPerViewControlContent = applyFilters( 
		'spectra.slider.slidesPerViewControl', 
		// Default free version control
		<RangeControl
			label={ __(
				'Slides Per View',
				'ultimate-addons-for-gutenberg'
			) }
			value={ 1 }
			onChange={ () => {
				// Free version is locked to 1, no changes allowed
			} }
			min={ 1 }
			max={ 1 }
			step={ 1 }
			disabled={ true }
		/>,
		props
	);

	const pauseOnCondition = ( () => {
		if ( !! autoplayPauseOnHover ) {
			return 'hover';
		}
		if ( !! autoplayPauseOnInteraction ) {
			return 'interaction';
		}
		return 'none';
	} )();

	// Simple ternary function to return the space between if it's numeric.
	const getSpaceBetween = () => ( 'number' === typeof( spaceBetween ) && spaceBetween !== undefined ? spaceBetween : 30 );

	return (
		<>
			{ /* General Settings Panel */ }
			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'General', 'ultimate-addons-for-gutenberg' ) }
					resetAll={ () => {
						setAttributes( {
							slidesPerView: undefined,
							spaceBetween: undefined,
							autoplay: undefined,
							loop: undefined,
							autoplaySpeed: undefined,
							overflow: undefined,
						} );
					} }
					panelId={ clientId }
				>
					{ /* Slider Settings */ }
					{/* Individual tool panel item for slides per view */}
					<ToolsPanelItem
						hasValue={ () => !! slidesPerView }
						label={ __(
							'Slides Per View',
							'ultimate-addons-for-gutenberg'
						) }
						onDeselect={ () =>
							setAttributes( {
								slidesPerView: undefined,
							} )
						}
						isShownByDefault
						panelId={ clientId }
					>
						{ slidesPerViewControlContent }
					</ToolsPanelItem>

					{/* Individual tool panel item for space between slides */}
					<ToolsPanelItem
						hasValue={ () => !! spaceBetween }
						label={ __(
							'Space Between Slides',
							'ultimate-addons-for-gutenberg'
						) }
						onDeselect={ () =>
							setAttributes( {
								spaceBetween: undefined,
							} )
						}
						isShownByDefault
						panelId={ clientId }
					>
						<DebouncedRangeControl
							label={ __(
								'Space Between Slides',
								'ultimate-addons-for-gutenberg'
							) }
							value={ getSpaceBetween() }
							onChange={ ( value ) => setAttributes( { spaceBetween: value } ) }
							min={ 0 }
							max={ 100 }
							withInputField
							marks={ [ 
								{ value: 0, label: '0' },
								{ value: 50, label: '50' },
								{ value: 100, label: '100' }
							] }
							debounceDelay={ 200 }
							__nextHasNoMarginBottom
						/>
					</ToolsPanelItem>

					{/* Individual tool panel item for autoplay and loop settings */}
					<ToolsPanelItem
						hasValue={ () =>
							!! autoplay ||
							!! loop ||
							!! autoplaySpeed
						}
						label={ __(
							'Autoplay & Loop',
							'ultimate-addons-for-gutenberg'
						) }
						onDeselect={ () =>
							setAttributes( {
								autoplay: undefined,
								loop: undefined,
								autoplaySpeed: undefined,
							} )
						}
						resetAllFilter={ () => ( {
							autoplay: undefined,
							loop: undefined,
							autoplaySpeed: undefined,
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 4 }>
							<ToggleControl
								label={ __( 'Enable Loop', 'ultimate-addons-for-gutenberg' ) }
								checked={ !! loop }
								onChange={ ( value ) => setAttributes( { loop: value } ) }
								help={ __( 'Loop functionality will be visible on the frontend only.', 'ultimate-addons-for-gutenberg' ) }
							/>

							<ToggleControl
								label={ __( 'Enable Autoplay', 'ultimate-addons-for-gutenberg' ) }
								checked={ !! autoplay }
								onChange={ ( value ) => setAttributes( { autoplay: value } ) }
								help={ __( 'Autoplay functionality will be visible on the frontend only.', 'ultimate-addons-for-gutenberg' ) }
							/>

							{ autoplay && (
								<DebouncedRangeControl
									label={ __(
										'Autoplay Speed (ms)',
										'ultimate-addons-for-gutenberg'
									) }
									value={ autoplaySpeed || 3000 }
									onChange={ ( value ) => setAttributes( { autoplaySpeed: value } ) }
									min={ 1000 }
									max={ 10000 }
									step={ 500 }
									debounceDelay={ 300 }
									__nextHasNoMarginBottom
								/>
							) }
						</VStack>
					</ToolsPanelItem>

					{/* Touch/Interaction Settings Panel */}
					<ToolsPanelItem
						hasValue={ () => 
							!! autoplayPauseOnHover || 
							!! autoplayPauseOnInteraction || 
							allowTouchMove === false 
						}
						label={ __( 'Touch & Interaction', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () =>
							setAttributes( {
								autoplayPauseOnHover: undefined,
								autoplayPauseOnInteraction: undefined,
								allowTouchMove: undefined,
							} )
						}
						resetAllFilter={ () => ( {
							autoplayPauseOnHover: undefined,
							autoplayPauseOnInteraction: undefined,
							allowTouchMove: undefined,
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 4 }>
							{ !! autoplay && (
								<ToggleGroupControl
									label={ __(
										'Autoplay Pause On',
										'ultimate-addons-for-gutenberg'
									) }
									value={ pauseOnCondition }
									onChange={ ( value ) => {
										setAttributes( {
											autoplayPauseOnHover: value === 'hover',
											autoplayPauseOnInteraction: value === 'interaction',
										} );
									}}
									help={ __(
										'Choose when autoplay should pause based on user interaction.',
										'ultimate-addons-for-gutenberg'
									) }
									isBlock
									__nextHasNoMarginBottom
								>
									<ToggleGroupControlOption
										value="hover"
										label={ __( 'Hover', 'ultimate-addons-for-gutenberg' ) }
										aria-label={ __( 'Pause on hover', 'ultimate-addons-for-gutenberg' ) }
									/>
									<ToggleGroupControlOption
										value="interaction"
										label={ __( 'Interaction', 'ultimate-addons-for-gutenberg' ) }
										aria-label={ __( 'Pause on interaction', 'ultimate-addons-for-gutenberg' ) }
									/>
									<ToggleGroupControlOption
										value="none"
										label={ __( 'None', 'ultimate-addons-for-gutenberg' ) }
										aria-label={ __( 'No pause', 'ultimate-addons-for-gutenberg' ) }
									/>
								</ToggleGroupControl>
							) }

							<ToggleControl
								help={ __(
									'This functionality will be visible on the frontend only.',
									'ultimate-addons-for-gutenberg'
								) }
								label={ __( 'Allow Touch/Mouse Drag', 'ultimate-addons-for-gutenberg' ) }
								checked={ allowTouchMove !== false }
								onChange={ ( value ) => setAttributes( { allowTouchMove: value } ) }
							/>
						</VStack>
					</ToolsPanelItem>


					{/* This tool panel item will require reset when any of these conditions are met:
					- The overflow attribute is set. Default: 'visible'.
					- When reset, overflow returns to 'visible'.
					*/}
					<ToolsPanelItem
						hasValue={ () => !! overflow }
						label={ __( 'Overflow', 'ultimate-addons-for-gutenberg' ) }
						panelId={ clientId }
						onDeselect={ () => setAttributes( {
							overflow: undefined,
						} ) }
						resetAll={ () => ( {
							overflow: undefined,
						} ) }
						isShownByDefault
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Overflow', 'ultimate-addons-for-gutenberg' ) }
							value={ overflow || '' }
							onChange={ ( value ) => setAttributes( { overflow: '' === value ? undefined : value } ) }
							help={ 'hidden' === overflow && __( 'NOTE: Make sure that your arrows and dots are inside the slider', 'ultimate-addons-for-gutenberg' ) }
							isBlock
						>
							<ToggleGroupControlOption value="" label="Default" />
							<ToggleGroupControlOption value="hidden" label="Hidden" />
						</ToggleGroupControl>
					</ToolsPanelItem>
				</ToolsPanel>

			{ /* Navigation Settings Panel */ }
				<ToolsPanel
					label={ __( 'Slider Navigation', 'ultimate-addons-for-gutenberg' ) }
					resetAll={ () => {
						setAttributes( {
							navigation: true,
							pagination: true,
							navigationPrevIcon: 'arrow-left',
							navigationNextIcon: 'arrow-right',
						} );
					} }
					panelId={ clientId }
				>
					{ /* Navigation Controls */ }
					{/* This tool panel item will require reset when any of these conditions are met:
					- Navigation arrows are hidden. Default: true.
					- Pagination dots are hidden. Default: true.
					- When reset, both navigation and pagination are shown.
					*/}
					<ToolsPanelItem
						hasValue={ () => navigation === false || pagination === false }
						label={ __(
							'Navigation Controls',
							'ultimate-addons-for-gutenberg'
						) }
						onDeselect={ () =>
							setAttributes( {
								navigation: true,
								pagination: true,
							} )
						}
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 4 }>
							<ToggleControl
								label={ __(
									'Show Navigation Arrows',
									'ultimate-addons-for-gutenberg'
								) }
								checked={ navigation !== false }
								onChange={ ( value ) => setAttributes( { navigation: value } ) }
							/>
							<ToggleControl
								label={ __(
									'Show Pagination Dots',
									'ultimate-addons-for-gutenberg'
								) }
								checked={ pagination !== false }
								onChange={ ( value ) => setAttributes( { pagination: value } ) }
							/>
						</VStack>
					</ToolsPanelItem>

					{ /* Navigation Icon Settings */ }
					{/* This tool panel item will require reset when any of these conditions are met:
					- Previous navigation icon is not 'arrow-left'. Default: 'arrow-left'.
					- Next navigation icon is not 'arrow-right'. Default: 'arrow-right'.
					- When reset, both icons return to their default values.
					*/}
					{ navigation !== false && (
						<ToolsPanelItem
							hasValue={ () => {
								const defaults = {
									navigationPrevIcon: 'arrow-left',
									navigationNextIcon: 'arrow-right',
								};
								return (
									navigationPrevIcon !== defaults.navigationPrevIcon ||
									navigationNextIcon !== defaults.navigationNextIcon
								);
							} }
							label={ __(
								'Navigation Icons',
								'ultimate-addons-for-gutenberg'
							) }
							onDeselect={ () =>
								setAttributes( {
									navigationPrevIcon: 'arrow-left',
									navigationNextIcon: 'arrow-right',
								} )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<NavigationIconSettings
								attributes={ props.attributes }
								setAttributes={ setAttributes }
							/>
						</ToolsPanelItem>
					) }
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
	// Destructure the required props
	const {
		clientId,
		setAttributes,
		attributes: {
			navigationSize,
			navigationIconSize,
			arrowDistance,
			paginationTopMargin,
			sliderHeight,
		},
	} = props;

	// Use the available spacing units, or our units if they're not available
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'em', 'rem' ],
	} );

	// Use the custom units only as vh and vw should not be available.
	const navigationUnits = useCustomUnits( {
		availableUnits: [ 'px', 'em', 'rem' ],
	} );
	
	return (
		<InspectorControls group="dimensions">
			{/* This tool panel item will require reset when any of these conditions are met:
			- Slider height is set to any value. Default: undefined.
			- When reset, height returns to default (auto).
			*/}
			<ToolsPanelItem
				hasValue={ () => !! sliderHeight }
				label={ __( 'Slider Height', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () =>
					setAttributes( { sliderHeight: undefined } )
				}
				resetAllFilter={ () => ( { sliderHeight: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __(
						'Slider Height',
						'ultimate-addons-for-gutenberg'
					) }
					labelPosition="top"
					value={ sliderHeight }
					min={ 0 }
					onChange={ ( value ) =>
						setAttributes( { sliderHeight: value } )
					}
					units={ units }
					help={ __(
						'Adjust the height of the slider in pixels.',
						'ultimate-addons-for-gutenberg'
					) }
				/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Navigation size is set to any value. Default: undefined.
			- When reset, navigation container size returns to default.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! navigationSize }
				label={ __(
					'Navigation Size',
					'ultimate-addons-for-gutenberg'
				) }
				onDeselect={ () =>
					setAttributes( { navigationSize: undefined } )
				}
				resetAllFilter={ () => ( { navigationSize: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __(
						'Arrow Size',
						'ultimate-addons-for-gutenberg'
					) }
					labelPosition="top"
					value={ navigationSize }
					onChange={ ( value ) =>
						setAttributes( { navigationSize: value } )
					}
					units={ navigationUnits }
					help={ __(
						'Adjust the size of navigation arrows container.',
						'ultimate-addons-for-gutenberg'
					) }
				/>
			</ToolsPanelItem>
			{/* This tool panel item will require reset when any of these conditions are met:
			- Arrow icon size is set to any value. Default: undefined.
			- When reset, icon size returns to default size.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! navigationIconSize }
				label={ __(
					'Arrow Icon Size',
					'ultimate-addons-for-gutenberg'
				) }
				onDeselect={ () =>
					setAttributes( { navigationIconSize: undefined } )
				}
				resetAllFilter={ () => ( { navigationIconSize: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __(
						'Arrow Icon Size',
						'ultimate-addons-for-gutenberg'
					) }
					labelPosition="top"
					value={ navigationIconSize }
					onChange={ ( value ) =>
						setAttributes( { navigationIconSize: value } )
					}
					units={ navigationUnits }
					help={ __(
						'Adjust the size of navigation arrow icons.',
						'ultimate-addons-for-gutenberg'
					) }
				/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Arrow distance is not '0px'. Default: '0px'.
			- When reset, distance returns to 0px from edges.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! arrowDistance }
				label={ __(
					'Arrow Distance',
					'ultimate-addons-for-gutenberg'
				) }
				onDeselect={ () => {
					setAttributes( {
						arrowDistance: undefined,
					} );
				} }
				resetAllFilter={ () => ( {
					arrowDistance: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					fullWidth={ true }
					label={ __(
						'Distance from Edges',
						'ultimate-addons-for-gutenberg'
					) }
					value={ arrowDistance ? parseInt( arrowDistance ) : undefined }
					onChange={ ( value ) => {
						setAttributes( { arrowDistance: value === undefined ? undefined : `${ value }px` } );
					} }
					min={ -200 }
					max={ 200 }
					step={ 1 }
					help={ __(
						'Adjust the distance of navigation arrows from the edges. Negative values move arrows outside.',
						'ultimate-addons-for-gutenberg'
					) }
					suffix="px"
					renderTooltipContent={ ( value ) => `${ value }px` }
					debounceDelay={ 150 }
				/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Pagination dots position is not '0%'. Default: '0%'.
			- When reset, dots position returns to default (0%).
			*/}
			<ToolsPanelItem
				hasValue={ () => !! paginationTopMargin }
				label={ __( 'Dots Position', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () =>
					setAttributes( { paginationTopMargin: undefined } )
				}
				resetAllFilter={ () => ( { paginationTopMargin: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					label={ __(
						'Dots Position',
						'ultimate-addons-for-gutenberg'
					) }
					value={ paginationTopMargin ? parseInt( paginationTopMargin ) : undefined }
					onChange={ ( value ) => {
						setAttributes( { paginationTopMargin: value === undefined ? undefined : `${ value }%` } );
					} }
					min={ -450 }
					max={ 450 }
					step={ 1 }
					help={ __(
						'Negative values move dots down, positive values move up',
						'ultimate-addons-for-gutenberg'
					) }
					renderTooltipContent={ ( value ) => `${ value }%` }
					debounceDelay={ 150 }
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
	// Destructure the required props
	const {
		clientId,
		setAttributes,
		attributes: {
			arrowColor,
			arrowColorHover,
			arrowBackgroundColor,
			arrowBackgroundColorHover,
			paginationColor,
			paginationColorHover,
			paginationColorActive,
			navigation,
			pagination,
			backgroundColor,
			backgroundGradient,
		},
	} = props;

	const colorSettings = [];

	// Add arrow colors if navigation is enabled
	if ( navigation ) {
		colorSettings.push(
			{
				colorValue: arrowColor,
				label: __( 'Arrow Color', 'ultimate-addons-for-gutenberg' ),
				onColorChange: ( value ) =>
					setAttributes( { arrowColor: value } ),
				resetAllFilter: () =>
					setAttributes( { arrowColor: undefined } ),
			},
			{
				colorValue: arrowColorHover,
				label: __(
					'Arrow Hover Color',
					'ultimate-addons-for-gutenberg'
				),
				onColorChange: ( value ) =>
					setAttributes( { arrowColorHover: value } ),
				resetAllFilter: () =>
					setAttributes( { arrowColorHover: undefined } ),
			},
			{
				colorValue: arrowBackgroundColor,
				label: __(
					'Arrow Background Color',
					'ultimate-addons-for-gutenberg'
				),
				onColorChange: ( value ) =>
					setAttributes( { arrowBackgroundColor: value } ),
				resetAllFilter: () =>
					setAttributes( { arrowBackgroundColor: undefined } ),
			},
			{
				colorValue: arrowBackgroundColorHover,
				label: __(
					'Arrow Background Hover Color',
					'ultimate-addons-for-gutenberg'
				),
				onColorChange: ( value ) =>
					setAttributes( { arrowBackgroundColorHover: value } ),
				resetAllFilter: () =>
					setAttributes( { arrowBackgroundColorHover: undefined } ),
			}
		);
	}

	// Add pagination colors if pagination is enabled
	if ( pagination ) {
		colorSettings.push(
			{
				colorValue: paginationColor,
				label: __( 'Dot Color', 'ultimate-addons-for-gutenberg' ),
				onColorChange: ( value ) =>
					setAttributes( { paginationColor: value } ),
				resetAllFilter: () =>
					setAttributes( { paginationColor: undefined } ),
			},
			{
				colorValue: paginationColorActive,
				label: __(
					'Dot Active Color',
					'ultimate-addons-for-gutenberg'
				),
				onColorChange: ( value ) =>
					setAttributes( { paginationColorActive: value } ),
				resetAllFilter: () =>
					setAttributes( { paginationColorActive: undefined } ),
			},
			{
				colorValue: paginationColorHover,
				label: __( 'Dot Hover Color', 'ultimate-addons-for-gutenberg' ),
				onColorChange: ( value ) =>
					setAttributes( { paginationColorHover: value } ),
				resetAllFilter: () =>
					setAttributes( { paginationColorHover: undefined } ),
			}
		);
	}

	colorSettings.push(
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
	);

	return <InspectorColor settings={ colorSettings } panelId={ clientId } />;
} );

/**
 * Advanced Gradient Settings Component
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered advanced gradient settings.
 */
const AdvancedGradientSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const gradientConfigs = [
		{
			label: __( 'Advanced BG', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradient',
			showTopBorder: true,
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
			enableLabel={ __( 'Enable Advanced Gradient', 'ultimate-addons-for-gutenberg' ) }
			helpText={ __( 'Advanced gradients will override the basic background colors/gradients when set.', 'ultimate-addons-for-gutenberg' ) }
			hideIndividualToggles={ true }
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
				hasValue={() => !!dimRatio}
				label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
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
 * The Editor settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = memo( ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
		<AdvancedGradientSettings { ...{ ...props } } />
		<OpacitySettings { ...{ ...props } } />
		<BlockStyle { ...{ ...props } } />
	</>
) );

export default memo( Settings );
