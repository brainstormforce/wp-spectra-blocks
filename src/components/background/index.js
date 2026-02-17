/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import {
	MediaUpload,
	MediaUploadCheck,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	Button,
	SelectControl,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	FocalPointPicker,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	ToggleControl
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

const Background = ( props ) => {
	// Show/hide the media preview.
	const [ showPreview, setShowPreview ] = useState( true );

	// Destructure the props.
	const {
		clientId,
		setAttributes,
		background: {
			label: attributeLabel,
			value: attributeValue = {},
		},
		backgroundColor,
		backgroundColorHover,
		backgroundGradient,
		backgroundGradientHover,
	} = props;

	// Destructure the background attribute.
	const {
		type,
		media,
		useOverlay,
		backgroundSize,
		backgroundRepeat,
		backgroundPosition,
		backgroundWidth,
		backgroundAttachment,
		positionMode,
		positionCentered,
		positionX,
		positionY,
	} = attributeValue;

	// Add the background size options.
	const backgroundSizeOptions = [
		{ label: __( 'Cover', 'ultimate-addons-for-gutenberg' ), value: 'cover' },
		{ label: __( 'Contain', 'ultimate-addons-for-gutenberg' ), value: 'contain' },
		{ label: __( 'Auto', 'ultimate-addons-for-gutenberg' ), value: 'auto' },
		{ label: __( 'Custom', 'ultimate-addons-for-gutenberg' ), value: 'custom' },
	];

	// Add the background repeat options.
	const backgroundRepeatOptions = [
		{ label: __( 'No Repeat', 'ultimate-addons-for-gutenberg' ), value: 'no-repeat' },
		{ label: __( 'Repeat', 'ultimate-addons-for-gutenberg' ), value: 'repeat' },
		{ label: __( 'Repeat X', 'ultimate-addons-for-gutenberg' ), value: 'repeat-x' },
		{ label: __( 'Repeat Y', 'ultimate-addons-for-gutenberg' ), value: 'repeat-y' },
	];

	const validBackgroundTypes = [ 'image', 'video' ];

	// Use the available spacing units, or our units if they're not available.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'vh', 'em', 'rem' ],
	} );

	// Condition to check if the background color is set.
	const hasColorOrGradient = (
		undefined !== backgroundColor
		|| undefined !== backgroundColorHover
		|| undefined !== backgroundGradient
		|| undefined !== backgroundGradientHover
	);

	// UseEffect to run whenever the background color or gradient changes.
	useEffect( () => {

		// If this doesn't have a background color or gradient, and the overlay toggle was on.
		if ( ! hasColorOrGradient && useOverlay ) {

			// Update the background attribute function, but set the attribute of useOverlay to false.
			setAttributes( {
				[ attributeLabel ]: {
					...attributeValue,
					useOverlay: false,
				}
			} );
		}
	}, [
		backgroundColor,
		backgroundGradient,
		useOverlay,
	] );

	// Get the button text based on the media type
	const getButtonText = () => ( type === 'image'
		? __( 'Add background image', 'ultimate-addons-for-gutenberg' )
		: __( 'Add background video', 'ultimate-addons-for-gutenberg' )
	);

	// Allow extensions to add custom UI below the media selector.
	const Extensions = applyFilters( 'spectra.mediaPicker.extensions', null, {
		type,
		setShowPreview,
		attributeLabel,
		attributeValue,
		...props,
	} );

	// Render the background component Tools Panel.
	return (
		<>
			<ToolsPanel
				label={ __( 'Background', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( { [ attributeLabel ]: undefined } );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The background type is set. Default should be undefined.
				- The background media is set. Default should be undefined.
				--- Note that resetting this panel does not reset the entire background attribute, but just these keys inside it.
				*/}
				<ToolsPanelItem
					hasValue={ () => ( !! type || !! media ) }
					label={ __( 'Background Type', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => {
						setAttributes( {
							[ attributeLabel ]: {
								...attributeValue,
								type: undefined,
								media: undefined,
							}
						} );
					} }
					resetAllFilter={ () => ( {
						[ attributeLabel ]: {
							...attributeValue,
							type: undefined,
							media: undefined,
						}
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						{/* Rendering the Toggle Group to select between Image, Video, or none. */}
						<ToggleGroupControl
							label={ __( 'Background Type', 'ultimate-addons-for-gutenberg' ) }
							value={ type || 'none' }
							onChange={ ( newType ) => {
								// Only reset media if it doesn't match the new type.
								const newMedia = ( media?.type === newType ) ? media : null;
								setAttributes( {
									[ attributeLabel ]: {
										...attributeValue,
										type: newType,
										media: newMedia,
									},
								} );
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
							<ToggleGroupControlOption
								value="video"
								label={ __( 'Video', 'ultimate-addons-for-gutenberg' ) }
							/>
						</ToggleGroupControl>
						{ /* If a type is selected, show the media uploader. */ }
						{ validBackgroundTypes.includes( type ) && (
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( newMedia ) => {
											
											const defaultAttributes = {
												[ attributeLabel ]: {
													...attributeValue,
													media: {
														id: newMedia.id,
														url: newMedia.url,
														type: newMedia.type,
													},
												},
											};

											// Allow pro extension to modify onSelect attributes.
											const filteredAttributes = applyFilters(
												'spectra.mediaPicker.onSelect',
												defaultAttributes,
												newMedia,
												props
											);

											setAttributes( filteredAttributes );
										} }
										allowedTypes={ [ type ] }
										value={ media?.id }
										render={ ( { open } ) => (
											<>
												{/* If there's a media URL, show the button. */}
												{ ! media?.url ? (
													<Button
														__next40pxDefaultSize
														onClick={ open }
														variant="secondary"
													>
														{ getButtonText() }
													</Button>
												) : (
													<>
														{ showPreview && (
															<>
																{/* Else first show the media accordingly. */}
																{ type === 'image' ? (
																	<img
																		src={ media.url }
																		alt=""
																		style={ {
																			objectPosition: `${
																				( 'number' === typeof backgroundPosition?.x ? backgroundPosition.x : 1/2 ) * 100
																			}% ${
																				( 'number' === typeof backgroundPosition?.y ? backgroundPosition.y : 1/2 ) * 100
																			}%`,
																		} }
																	/>
																) : (
																	<video src={ media.url } />
																) }
																{/* Then show the replace and remove buttons. */}
																<HStack spacing={ 4 }>
																	<Button
																		variant="secondary"
																		onClick={ open }
																	>
																		{ __( 'Replace', 'ultimate-addons-for-gutenberg' ) }
																	</Button>
																	<Button
																		variant="link"
																		onClick={ () => {
																			setAttributes( {
																				[ attributeLabel ]: {
																					...attributeValue,
																					media: null,
																				},
																			} );
																		} }
																		isDestructive
																	>
																		{ __( 'Remove', 'ultimate-addons-for-gutenberg' ) }
																	</Button>
																</HStack>
															</>
														) }
													</>
												) }
											</>
										) }
									/>
						</MediaUploadCheck>
						) }
					</VStack>
				</ToolsPanelItem>
				{ /* Show controls when media is selected (either image or video). */ }
				{ validBackgroundTypes.includes( type ) && media?.url && (
					<>
						{/* For the image type, render the additional required controls. */}
						{ type === 'image' && (
							<>
								{/* This tool panel item will require reset when any of these conditions are met:
								- The background size is set. Default should be undefined.
								--- Note that resetting this panel does not reset the entire background attribute, but just this key inside it.
								*/}
								<ToolsPanelItem
									hasValue={ () => !! backgroundSize }
									label={ __( 'Background Size', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => {
										setAttributes( {
											[ attributeLabel ]: {
												...attributeValue,
												backgroundSize: undefined,
											}
										} );
									} }
									resetAllFilter={ () => ( {
										[ attributeLabel ]: {
											...attributeValue,
											backgroundSize: undefined,
										}
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<SelectControl
										label={ __( 'Background Size', 'ultimate-addons-for-gutenberg' ) }
										value={ backgroundSize || 'cover' }
										options={ backgroundSizeOptions }
										onChange={ ( newSize ) => {
											setAttributes( {
												[ attributeLabel ]: {
													...attributeValue,
													backgroundSize: newSize,
													// Set default width when switching to custom, clear when switching away
													backgroundWidth: newSize === 'custom' ? ( backgroundWidth || '100%' ) : undefined,
												},
											} );
										} }
									/>
								</ToolsPanelItem>
								{/* Show custom width control when background size is set to custom */}
								{ backgroundSize === 'custom' && (
									<ToolsPanelItem
										hasValue={ () => !! backgroundWidth }
										label={ __( 'Background Width', 'ultimate-addons-for-gutenberg' ) }
										onDeselect={ () => {
											setAttributes( {
												[ attributeLabel ]: {
													...attributeValue,
													backgroundWidth: undefined,
												},
											} );
										} }
										resetAllFilter={ () => ( {
											[ attributeLabel ]: {
												...attributeValue,
												backgroundWidth: undefined,
											}
										} ) }
										panelId={ clientId }
									>
										<UnitControl
											__next40pxDefaultSize
											label={ __( 'Background Width', 'ultimate-addons-for-gutenberg' ) }
											labelPosition="top"
											value={ backgroundWidth || '100%' }
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
													[ attributeLabel ]: {
														...attributeValue,
														backgroundWidth: validatedWidth,
													},
												} );
											} }
											units={ units }
										/>
									</ToolsPanelItem>
								) }
								{/* This tool panel item will require reset when any of these conditions are met:
								- The background repeat is set. Default should be undefined.
								--- Note that resetting this panel does not reset the entire background attribute, but just this key inside it.
								*/}
								<ToolsPanelItem
									hasValue={ () => !! backgroundRepeat }
									label={ __( 'Background Repeat', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => {
										setAttributes( {
											[ attributeLabel ]: {
												...attributeValue,
												backgroundRepeat: undefined,
											}
										} );
									} }
									resetAllFilter={ () => ( {
										[ attributeLabel ]: {
											...attributeValue,
											backgroundRepeat: undefined,
										}
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<SelectControl
										label={ __( 'Background Repeat', 'ultimate-addons-for-gutenberg' ) }
										value={ backgroundRepeat || 'no-repeat' }
										options={ backgroundRepeatOptions }
										onChange={ ( newRepeat ) => {
											setAttributes( {
												[ attributeLabel ]: {
													...attributeValue,
													backgroundRepeat: newRepeat,
												},
											} );
										} }
									/>
								</ToolsPanelItem>
								{/* This tool panel item will require reset when any of these conditions are met:
								- The background attachment is set. Default should be undefined.
								--- Note that resetting this panel does not reset the entire background attribute, but just this key inside it.
								*/}
								<ToolsPanelItem
									hasValue={ () => !! backgroundAttachment }
									label={ __( 'Background Attachment', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => {
										setAttributes( {
											[ attributeLabel ]: {
												...attributeValue,
												backgroundAttachment: undefined,
											}
										} );
									} }
									resetAllFilter={ () => ( {
										[ attributeLabel ]: {
											...attributeValue,
											backgroundAttachment: undefined,
										}
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<SelectControl
										label={ __( 'Background Attachment', 'ultimate-addons-for-gutenberg' ) }
										value={ backgroundAttachment || 'scroll' }
										options={ [
											{ label: __( 'Scroll', 'ultimate-addons-for-gutenberg' ), value: 'scroll' },
											{ label: __( 'Fixed', 'ultimate-addons-for-gutenberg' ), value: 'fixed' },
											{ label: __( 'Inherit', 'ultimate-addons-for-gutenberg' ), value: 'inherit' },
										] }
										onChange={ ( value ) => {
											setAttributes( {
												[ attributeLabel ]: {
													...attributeValue,
													backgroundAttachment: value,
												},
											} );
										} }
									/>
								</ToolsPanelItem>
								{/* This tool panel item will require reset when any of these conditions are met:
								- The background position is set. Default should be undefined.
								--- Note that resetting this panel does not reset the entire background attribute, but just this key inside it.
								*/}
								<ToolsPanelItem
									hasValue={ () => !! backgroundPosition || !! positionMode || !! positionX || !! positionY }
									label={ __( 'Background Position', 'ultimate-addons-for-gutenberg' ) }
									onDeselect={ () => {
										setAttributes( {
											[ attributeLabel ]: {
												...attributeValue,
												backgroundPosition: undefined,
												positionMode: undefined,
												positionCentered: undefined,
												positionX: undefined,
												positionY: undefined,
											}
										} );
									} }
									resetAllFilter={ () => ( {
										[ attributeLabel ]: {
											...attributeValue,
											backgroundPosition: undefined,
											positionMode: undefined,
											positionCentered: undefined,
											positionX: undefined,
											positionY: undefined,
										}
									} ) }
									isShownByDefault
									panelId={ clientId }
								>
									<VStack spacing={ 4 }>
										{/* Position Mode Toggle */}
										<ToggleGroupControl
											label={ __( 'Image Position', 'ultimate-addons-for-gutenberg' ) }
											value={ positionMode || 'default' }
											onChange={ ( newMode ) => {
												setAttributes( {
													[ attributeLabel ]: {
														...attributeValue,
														positionMode: newMode,
														// Reset position values when switching modes
														backgroundPosition: newMode !== 'default' ? undefined : backgroundPosition,
														positionX: newMode !== 'custom' ? undefined : positionX,
														positionY: newMode !== 'custom' ? undefined : positionY,
														positionCentered: newMode !== 'custom' ? undefined : positionCentered,
													},
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
										{ ( positionMode === 'default' || ! positionMode ) && (
											<FocalPointPicker
												label={ __( 'Position', 'ultimate-addons-for-gutenberg' ) }
												url={ media.url }
												value={ backgroundPosition }
												onChange={ ( newPoint ) => {
													setAttributes( {
														[ attributeLabel ]: {
															...attributeValue,
															backgroundPosition: newPoint,
														},
													} );
												} }
											/>
										) }

										{/* Custom Position Controls */}
										{ positionMode === 'custom' && (
											<>
												<ToggleControl
													label={ __( 'Centralized Position', 'ultimate-addons-for-gutenberg' ) }
													checked={ positionCentered || false }
													onChange={ ( newCentered ) => {
														setAttributes( {
															[ attributeLabel ]: {
																...attributeValue,
																positionCentered: newCentered,
																// Set default values when centered
																positionX: newCentered ? '50%' : ( positionX || '0%' ),
																positionY: newCentered ? '50%' : ( positionY || '0%' ),
															},
														} );
													} }
												/>
												<UnitControl
													__next40pxDefaultSize
													label={ __( 'X Position', 'ultimate-addons-for-gutenberg' ) }
													labelPosition="top"
													value={ positionCentered ? '50%' : ( positionX || '0%' ) }
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
															[ attributeLabel ]: {
																...attributeValue,
																positionX: validatedX,
															},
														} );
													} }
													units={ units }
													disabled={ positionCentered }
												/>
												<UnitControl
													__next40pxDefaultSize
													label={ __( 'Y Position', 'ultimate-addons-for-gutenberg' ) }
													labelPosition="top"
													value={ positionCentered ? '50%' : ( positionY || '0%' ) }
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
															[ attributeLabel ]: {
																...attributeValue,
																positionY: validatedY,
															},
														} );
													} }
													units={ units }
													disabled={ positionCentered }
												/>
											</>
										) }
									</VStack>
								</ToolsPanelItem>
							</>
						) }
					</>
				) }
			</ToolsPanel>

			{ /* Allow extensions to add custom UI below the media selector. */ }
			{ Extensions }
		</>
	);
};

export default Background;
