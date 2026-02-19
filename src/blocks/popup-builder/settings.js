/**
 * BLOCK: Popup Builder - Settings.
 */

import { memo, useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	Dashicon,
	ToggleControl,
	SelectControl,
	RangeControl,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalGrid as Grid,
	__experimentalVStack as VStack,
	AnglePickerControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies.
 */
import Background from '@spectra-components/background';
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import { getIconName, spectraClassNames } from '@spectra-helpers';

/**
 * Meta Settings: Repetition controls for popup display frequency.
 * Replicates UAG popup builder repetition settings using WordPress components.
 *
 * @since 3.0.0
 * @return {Element} The repetition settings component.
 */
const PopupRepetitionSettings = memo( () => {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Initialize state from meta or defaults
	const [ repetition, setRepetition ] = useState(
		meta[ 'spectra-popup-repetition' ] || 1
	);
	const [ infiniteRepeat, setInfiniteRepeat ] = useState( false );

	// Setup infinite repeat state from meta on component mount
	useEffect( () => {
		if ( meta[ 'spectra-popup-repetition' ] === -1 ) {
			setInfiniteRepeat( true );
			setRepetition( 1 ); // Display value when infinite is enabled
		} else if ( meta[ 'spectra-popup-repetition' ] ) {
			setRepetition( meta[ 'spectra-popup-repetition' ] );
			setInfiniteRepeat( false );
		}
	}, [ meta ] );

	// Update repetition value
	const updateRepetition = ( value ) => {
		setRepetition( value );
		updateRepetitionMeta( value );
	};

	// Update infinite repeat toggle
	const updateInfiniteLoop = ( value ) => {
		setInfiniteRepeat( value );
		updateRepetitionMeta( value ? -1 : repetition );
	};

	// Update meta data
	const updateRepetitionMeta = ( value ) => {
		setMeta( {
			...meta,
			'spectra-popup-repetition': value,
		} );
	};

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Repetition', 'spectra-blocks' ) }
				resetAll={ () => {
					setInfiniteRepeat( false );
					setRepetition( 1 );
					updateRepetitionMeta( 1 );
				} }
				panelId={ 'popup-repetition' }
			>
				<ToolsPanelItem
					hasValue={ () => infiniteRepeat || repetition !== 1 }
					label={ __(
						'Repeat Infinitely',
						'spectra-blocks'
					) }
					onDeselect={ () => {
						setInfiniteRepeat( false );
						updateRepetitionMeta( repetition );
					} }
					resetAllFilter={ () => ( { infiniteRepeat: false } ) }
					panelId={ 'popup-repetition' }
					isShownByDefault
				>
					<ToggleControl
						label={ __(
							'Repeat Infinitely',
							'spectra-blocks'
						) }
						checked={ infiniteRepeat }
						onChange={ () =>
							updateInfiniteLoop( ! infiniteRepeat )
						}
						help={ __(
							'When enabled, popup will show every time the page loads.',
							'spectra-blocks'
						) }
					/>
				</ToolsPanelItem>

				{ ! infiniteRepeat && (
					<ToolsPanelItem
						hasValue={ () => repetition !== 1 }
						label={ __(
							'Repetition per Browser',
							'spectra-blocks'
						) }
						onDeselect={ () => {
							setRepetition( 1 );
							updateRepetitionMeta( 1 );
						} }
						resetAllFilter={ () => ( { repetition: 1 } ) }
						panelId={ 'popup-repetition' }
						isShownByDefault
					>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __(
								'Repetition per Browser',
								'spectra-blocks'
							) }
							value={ repetition }
							onChange={ ( value ) => updateRepetition( value ) }
							min={ 1 }
							max={ 100 }
							step={ 1 }
							help={ __(
								'Note: Repetition decreases on close, not on refresh.',
								'spectra-blocks'
							) }
						/>
					</ToolsPanelItem>
				) }
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: General popup settings.
 *
 * @param {Object} props The element props.
 * @since 3.0.0
 * @return {Element} The rendered block settings.
 */
const PopupGeneralSettings = memo( ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const {
		variantType,
		popupWidth,
		hasFixedHeight,
		popupPositionV,
		popupContentAlignmentV,
		hasOverlay,
		haltBackgroundInteraction,
		willPushContent,
	} = attributes;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Popup', 'spectra-blocks' ) }
				onDeselect={ () =>
					setAttributes( {
						popupWidth: 700,
						popupHeight: 50,
						hasFixedHeight: false,
						popupPositionV: 'top',
						popupContentAlignmentV: 'center',
						hasOverlay: undefined,
						isDismissable: undefined,
						haltBackgroundInteraction: undefined,
						willPushContent: false,
					} )
				}
				resetAllFilter={ () => ( {
					popupWidth: undefined,
					popupHeight: undefined,
					hasFixedHeight: false,
					popupPositionV: 'top',
					popupContentAlignmentV: 'center',
					hasOverlay: false,
					isDismissable: false,
					haltBackgroundInteraction: false,
					willPushContent: true,
				} ) }
				panelId={ clientId }
			>
				{ variantType === 'popup' && (
					<>
						<ToolsPanelItem
							hasValue={ () => !! popupWidth }
							label={ __(
								'Popup Width',
								'spectra-blocks'
							) }
							panelId={ clientId }
							onDeselect={ () =>
								setAttributes( { popupWidth: undefined } )
							}
							resetAllFilter={ () => ( {
								popupWidth: undefined,
							} ) }
						>
							<UnitControl
								__next40pxDefaultSize
								label={ __(
									'Width',
									'spectra-blocks'
								) }
								value={ popupWidth }
								onChange={ ( value ) =>
									setAttributes( { popupWidth: value } )
								}
								units={ units }
							/>
						</ToolsPanelItem>
					</>
				) }
				<ToolsPanelItem
					hasValue={ () => hasFixedHeight !== undefined }
					label={ __(
						'Fixed Height',
						'spectra-blocks'
					) }
					panelId={ clientId }
					onDeselect={ () =>
						setAttributes( { hasFixedHeight: false } )
					}
					resetAllFilter={ () => ( { hasFixedHeight: false } ) }
				>
					<ToggleControl
						label={ __(
							'Use Fixed Height',
							'spectra-blocks'
						) }
						checked={ hasFixedHeight }
						onChange={ ( value ) =>
							setAttributes( { hasFixedHeight: value } )
						}
						help={ __(
							'Enable fixed height with scrollable content.',
							'spectra-blocks'
						) }
					/>
				</ToolsPanelItem>
				{ /* Content Alignment for Fixed Height */ }
				{ hasFixedHeight && (
					<ToolsPanelItem
						hasValue={() => !!popupContentAlignmentV}
						label={__(
							'Position',
							'spectra-blocks'
						)}
						panelId={clientId}
						onDeselect={() =>
							setAttributes( { popupContentAlignmentV: 'center' } )
						}
						resetAllFilter={() => ( {
							popupContentAlignmentV: 'center',
						} )}
						isShownByDefault
					>
						<SelectControl
							label={__(
								'Position',
								'spectra-blocks'
							)}
							value={popupContentAlignmentV || 'center'}
							options={[
								{
									value: 'flex-start',
									label: __(
										'Top',
										'spectra-blocks'
									),
								},
								{
									value: 'center',
									label: __(
										'Center',
										'spectra-blocks'
									),
								},
								{
									value: 'flex-end',
									label: __(
										'Bottom',
										'spectra-blocks'
									),
								},
							]}
							onChange={( value ) =>
								setAttributes( { popupContentAlignmentV: value } )
							}
						/>
					</ToolsPanelItem>
				) }
				{ /* Overlay Settings */ }
				{ variantType === 'popup' && (
					<ToolsPanelItem
						hasValue={ () => hasOverlay !== undefined }
						label={ __(
							'Overlay',
							'spectra-blocks'
						) }
						panelId={ clientId }
						onDeselect={ () =>
							setAttributes( { hasOverlay: false } )
						}
						resetAllFilter={ () => ( { hasOverlay: false } ) }
						isShownByDefault
					>
						<ToggleControl
							label={ __(
								'Show Overlay',
								'spectra-blocks'
							) }
							checked={ hasOverlay }
							onChange={ ( value ) =>
								setAttributes( { hasOverlay: value } )
							}
							help={ __(
								'Show background overlay behind popup.',
								'spectra-blocks'
							) }
						/>
					</ToolsPanelItem>
				) }

				{ variantType === 'popup' && (
					<ToolsPanelItem
						hasValue={ () =>
							haltBackgroundInteraction !== undefined
						}
						label={ __(
							'Block Background',
							'spectra-blocks'
						) }
						panelId={ clientId }
						onDeselect={ () =>
							setAttributes( { haltBackgroundInteraction: true } )
						}
						resetAllFilter={ () => ( {
							haltBackgroundInteraction: true,
						} ) }
					>
						<ToggleControl
							label={ __(
								'Block Background Interaction',
								'spectra-blocks'
							) }
							checked={ haltBackgroundInteraction }
							onChange={ ( value ) =>
								setAttributes( {
									haltBackgroundInteraction: value,
								} )
							}
							help={ __(
								'Prevent interaction with page content behind popup.',
								'spectra-blocks'
							) }
						/>
					</ToolsPanelItem>
				) }

				{ variantType === 'banner' && (
					<ToolsPanelItem
						hasValue={ () => willPushContent !== undefined }
						label={ __(
							'Push Content',
							'spectra-blocks'
						) }
						panelId={ clientId }
						onDeselect={ () =>
							setAttributes( { willPushContent: true } )
						}
						resetAllFilter={ () => ( { willPushContent: true } ) }
					>
						<ToggleControl
							label={ __(
								'Push Page Content',
								'spectra-blocks'
							) }
							checked={ willPushContent }
							onChange={ ( value ) =>
								setAttributes( { willPushContent: value } )
							}
							help={ __(
								'Push page content down when banner is shown.',
								'spectra-blocks'
							) }
						/>
					</ToolsPanelItem>
				) }
				{ variantType === 'banner' && ! willPushContent && (
					<>
						<ToolsPanelItem
							hasValue={ () => !! popupPositionV }
							label={ __(
								'Position',
								'spectra-blocks'
							) }
							panelId={ clientId }
							onDeselect={ () =>
								setAttributes( { popupPositionV: 'top' } )
							}
							resetAllFilter={ () => ( {
								popupPositionV: 'top',
							} ) }
							isShownByDefault
						>
							<SelectControl
								label={ __(
									'Position',
									'spectra-blocks'
								) }
								value={ popupPositionV || 'top' }
								options={ [
									{
										value: 'top',
										label: __(
											'Top',
											'spectra-blocks'
										),
									},
									{
										value: 'bottom',
										label: __(
											'Bottom',
											'spectra-blocks'
										),
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { popupPositionV: value } )
								}
							/>
						</ToolsPanelItem>
					</>
				) }
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Close button settings.
 *
 * @param {Object} props The element props.
 * @since 3.0.0
 * @return {Element} The rendered close settings.
 */
const PopupCloseSettings = memo( ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const {
		isDismissable,
		closeIcon,
		flipForRTL,
		rotation,
		accessibilityMode,
		accessibilityLabel,
		closeIconPosition,
		closeIconSize,
		variantType,
		haltBackgroundInteraction,
		hasOverlay,
		closeOverlayClick,
		closeEscapePress
	} = attributes;

	// React Hooks must be called before any early returns
	const [ isCopied, setIsCopied ] = useState( false );
	const canUseClipboard = navigator.clipboard ? true : false;

	// Handle copy to clipboard for the close popup class.
	const handleCopyClass = useCallback( () => {
		if ( ! canUseClipboard ) {
			return;
		}
		navigator.clipboard.writeText( `spectra-popup-close-${ spectra_blocks_info.current_post_id }` );
		setIsCopied( true );
		setTimeout( () => {
			setIsCopied( false );
		}, 750 );
	}, [ canUseClipboard ] );

	if ( ! isDismissable ) {
		return null;
	}

	// Placehloder for the accessibility label based on the mode.
	let accessibilityPlaceholder;
	switch ( accessibilityMode ) {
		case 'svg':
			accessibilityPlaceholder = sprintf(
				/* translators: %s: The name of the SVG icon. */
				__( 'An icon named %s', 'spectra-blocks' ),
				getIconName( closeIcon ),
			);
			break;
		case 'image':
			accessibilityPlaceholder = sprintf(
				/* translators: %s: The name of the SVG image. */
				__( 'An image named %s', 'spectra-blocks' ),
				getIconName( closeIcon ),
			);
			break;
		default:
			accessibilityPlaceholder = '';
	}

	// Variable to determine if the accessibility label should be disabled or not.
	const requiresAccessibilityLabel = [ 'svg', 'image' ].includes(
		accessibilityMode
	);

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Close Icon', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						closeIcon: undefined,
						flipForRTL: false,
						accessibilityMode: undefined,
						accessibilityLabel: undefined,
						closeIconPosition: 'top-right',
						closeOverlayClick: true,
						closeEscapePress: true,
						closeIconSize: 20,
						closeIconColor: '',
						closeIconColorHover: '',
					} );
				} }
				panelId={ clientId }
			>
				{ /* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The flipForRTL attribute is true. Default: false.
				*/ }
				<ToolsPanelItem
					hasValue={ () => !! closeIcon || !! flipForRTL }
					label={ __( 'Icon', 'spectra-blocks' ) }
					onDeselect={ () =>
						setAttributes( {
							closeIcon: undefined,
							flipForRTL: false,
						} )
					}
					resetAllFilter={ () => ( {
						closeIcon: undefined,
						flipForRTL: false,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<IconPicker
							value={ closeIcon }
							onChange={ ( value ) =>
								setAttributes( { closeIcon: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ flipForRTL }
							label={ __(
								'Flip Icon for Right-To-Left',
								'spectra-blocks'
							) }
							onChange={ () =>
								setAttributes( { flipForRTL: ! flipForRTL } )
							}
							help={ __(
								'Enable this for your RTL visitors if you are using a direction-specific icon. Like \'Arrow Right\', \'Chart Line\', etc.',
								'spectra-blocks'
							) }
						/>
					</VStack>
				</ToolsPanelItem>
				{ /* This tool panel item will require reset when any of these conditions are met:
				- The rotation attribute is set. Default: undefined.
				*/ }
				<ToolsPanelItem
					hasValue={ () => !! rotation }
					label={ __( 'Rotation', 'spectra-blocks' ) }
					onDeselect={ () =>
						setAttributes( { rotation: undefined } )
					}
					resetAllFilter={ () => ( {
						rotation: undefined,
					} ) }
					panelId={ clientId }
				>
					<AnglePickerControl
						label={ __(
							'Rotation',
							'spectra-blocks'
						) }
						onChange={ ( value ) => {
							setAttributes( { rotation: value } );
						} }
						value={ rotation }
					/>
				</ToolsPanelItem>
				{ /* This tool panel item will require reset when any of these conditions are met:
				- The accessibilityMode attribute is set. Default: undefined.
				- The accessibilityLabel attribute is set. Default: undefined.
				*/ }
				<ToolsPanelItem
					hasValue={ () => !! accessibilityMode }
					label={ __( 'Accessibility', 'spectra-blocks' ) }
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
							label={ __( 'Accessibiltiy Mode', 'spectra-blocks' ) }
							value={ accessibilityMode }
							onChange={ ( value ) => setAttributes( { accessibilityMode: value } ) }
							isBlock
						>
							<ToggleGroupControlOption value="svg" label="SVG" />
							<ToggleGroupControlOption value="image" label="Image" />
							<ToggleGroupControlOption value="decorative" label="Decorative" />
						</ToggleGroupControl>
						{ requiresAccessibilityLabel && (
							<InputControl
								__next40pxDefaultSize
								label={ __( 'Accessibiltiy Label', 'spectra-blocks' ) }
								value={ accessibilityLabel }
								onChange={ ( value ) => setAttributes( { accessibilityLabel: value } ) }
								placeholder={ accessibilityPlaceholder }
							/>
						) }
					</VStack>
				</ToolsPanelItem>
				{( 'popup' === variantType && haltBackgroundInteraction ) && (
					<ToolsPanelItem
						hasValue={() => closeEscapePress !== undefined}
						label={__(
							'Close with Escape',
							'spectra-blocks'
						)}
						panelId={clientId}
						onDeselect={() =>
							setAttributes( { closeEscapePress: false } )
						}
						resetAllFilter={() => ( { closeEscapePress: false } )}
						isShownByDefault
					>
						<ToggleControl
							label={__(
								'Close with Escape',
								'spectra-blocks'
							)}
							checked={closeEscapePress}
							onChange={() =>
								setAttributes( { closeEscapePress: !closeEscapePress } )
							}
						/>
					</ToolsPanelItem>
				)}
				{hasOverlay && (
					<ToolsPanelItem
						hasValue={() => closeOverlayClick !== undefined}
						label={__(
							'Close on Overlay Click',
							'spectra-blocks'
						)}
						panelId={clientId}
						onDeselect={() =>
							setAttributes( { closeOverlayClick: false } )
						}
						resetAllFilter={() => ( { closeOverlayClick: false } )}
						isShownByDefault
					>
						<ToggleControl
							label={__(
								'Close on Overlay Click',
								'spectra-blocks'
							)}
							checked={closeOverlayClick}
							onChange={() =>
								setAttributes( { closeOverlayClick: !closeOverlayClick } )
							}
						/>
					</ToolsPanelItem>
				)}
				<ToolsPanelItem
					hasValue={ () => !! closeIconPosition }
					label={ __(
						'Icon Position',
						'spectra-blocks'
					) }
					panelId={ clientId }
					onDeselect={ () =>
						setAttributes( { closeIconPosition: 'top-right' } )
					}
					resetAllFilter={ () => ( {
						closeIconPosition: 'top-right',
					} ) }
					isShownByDefault
				>
					<SelectControl
						label={ __(
							'Close Icon Position',
							'spectra-blocks'
						) }
						value={ closeIconPosition || 'top-right' }
						options={ [
							{
								value: 'top-left',
								label: __(
									'Top Left',
									'spectra-blocks'
								),
							},
							{
								value: 'top-right',
								label: __(
									'Top Right',
									'spectra-blocks'
								),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { closeIconPosition: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					hasValue={ () => !! closeIconSize }
					label={ __(
						'Close Icon Size',
						'spectra-blocks'
					) }
					panelId={ clientId }
					onDeselect={ () =>
						setAttributes( { closeIconSize: 20 } )
					}
					resetAllFilter={ () => ( { closeIconSize: 20 } ) }
				>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Size', 'spectra-blocks' ) }
						value={ closeIconSize }
						onChange={ ( value ) =>
							setAttributes( { closeIconSize: value } )
						}
						min={ 0 }
						max={ 100 }
					/>
				</ToolsPanelItem>
				<div className='spectra-popup__close-class-option-element-notice'>
				<p
					className={ spectraClassNames( [
						'spectra-popup__notice',
						canUseClipboard && 'spectra-popup__notice--clickable',
					] ) }
					onClick={ handleCopyClass }
				>
					{ canUseClipboard && (
						<Dashicon
							icon='clipboard'
							style={ {
								color: isCopied ? '#007cba' : '',
							} }
						/>
					) }
					{ `spectra-popup-close-${ spectra_blocks_info.current_post_id }` }
				</p>
				<p className='spectra-popup__notice spectra-popup__notice--secondary'>
					{ __(
						'Copy and paste the class above into the Additional Classes field of any block in this popup to close it.',
						'spectra-blocks',
					) }
				</p>
				</div>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 * Following container's ColorSettings pattern but using WordPress core components.
 *
 * @param {Object} props The element props.
 * @since 3.0.0
 * @return {Element} The rendered block color settings.
 */
const PopupColorSettings = memo( ( props ) => {
	// Destructure the required props following container pattern
	const {
		clientId,
		setAttributes,
		attributes: {
			backgroundColor,
			backgroundGradient,
			backgroundColorHover,
			backgroundGradientHover,
			closeIconColor,
			closeIconColorHover,
			popupOverlayColor,
			variantType,
			textColor
		},
	} = props;

	const colorSettings = [];

	colorSettings.push(
		{
			colorValue: textColor,
			label: __( 'Text', 'spectra-blocks' ),
			onColorChange: ( value ) =>
				setAttributes( { textColor: value } ),
			resetAllFilter: () =>
				setAttributes( {
					textColor: undefined
				} ),
		},
		{
			colorValue: backgroundColorHover,
			gradientValue: backgroundGradientHover,
			label: __( 'Background Hover', 'spectra-blocks' ),
			onColorChange: ( value ) =>
				setAttributes( { backgroundColorHover: value } ),
			onGradientChange: ( value ) =>
				setAttributes( { backgroundGradientHover: value } ),
			resetAllFilter: () =>
				setAttributes( {
					backgroundColorHover: undefined,
					backgroundGradientHover: undefined,
				} ),
		},
		{
			colorValue: backgroundColor,
			gradientValue: backgroundGradient,
			label: __( 'Background', 'spectra-blocks' ),
			onColorChange: ( value ) =>
				setAttributes( { backgroundColor: value } ),
			onGradientChange: ( value ) =>
				setAttributes( { backgroundGradient: value } ),
			resetAllFilter: () =>
				setAttributes( {
					backgroundColor: undefined,
					backgroundGradient: undefined,
				} ),
		},
		{
			colorValue: closeIconColor,
			label: __( 'Close Icon Color', 'spectra-blocks' ),
			onColorChange: ( value ) =>
				setAttributes( { closeIconColor: value } ),
			resetAllFilter: () =>
				setAttributes( { closeIconColor: undefined } ),
		},
		{
			colorValue: closeIconColorHover,
			label: __(
				'Close Icon Hover Color',
				'spectra-blocks'
			),
			onColorChange: ( value ) =>
				setAttributes( { closeIconColorHover: value } ),
			resetAllFilter: () =>
				setAttributes( { closeIconColorHover: undefined } ),
		}
	);

	if ( variantType === 'popup' ) {
		colorSettings.push( {
			colorValue: popupOverlayColor,
			label: __( 'Overlay Color', 'spectra-blocks' ),
			onColorChange: ( value ) =>
				setAttributes( { popupOverlayColor: value } ),
			resetAllFilter: () =>
				setAttributes( { popupOverlayColor: undefined } ),
		} );
	}

	return <InspectorColor settings={ colorSettings } panelId={ clientId } />;
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel for overlay opacity.
 * Following container's OpacitySettings pattern.
 *
 * @param {Object} props The element props.
 * @since 3.0.0
 * @return {Element} The rendered block opacity settings.
 */
const PopupOpacitySettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: { dimRatio },
	} = props;

	return (
		<InspectorControls group="color">
			<ToolsPanelItem
				hasValue={ () => !! dimRatio }
				label={ __(
					'Background Overlay Opacity',
					'spectra-blocks'
				) }
				onDeselect={ () => setAttributes( { dimRatio: undefined } ) }
				resetAllFilter={ () => ( {
					dimRatio: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __(
						'Background Opacity',
						'spectra-blocks'
					) }
					value={ dimRatio }
					onChange={ ( value ) =>
						setAttributes( { dimRatio: value } )
					}
					min={ 0 }
					max={ 100 }
					step={ 5 }
				/>
			</ToolsPanelItem>
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
const PopupDimensionSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: { width, height, variantType },
	} = props;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<>
				{/* Width and Height settings */}
				<ToolsPanelItem
					hasValue={ () =>
						!! width ||
						( !! height && height !== 'auto' )
					}
					label={ __( 'Sizes', 'spectra-blocks' ) }
					as={ Grid }
					panelId={ clientId }
					isShownByDefault
					onDeselect={ () =>
						setAttributes( {
							width: undefined,
							height: 'auto',
						} )
					}
					resetAllFilter={ () => ( {
						width: undefined,
						height: 'auto',
					} ) }
				>
					{ 'popup' === variantType && 
						<UnitControl
							__next40pxDefaultSize
							label="Width"
							value={ width }
							onChange={ ( value ) =>
								setAttributes( { width: value } )
							}
							units={ units }
						/>
					}
					<UnitControl
						__next40pxDefaultSize
						label="Height"
						value={ height }
						onChange={ ( value ) =>
							setAttributes( { height: value } )
						}
						units={ units }
					/>
				</ToolsPanelItem>
			</>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Style settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block styles.
 */
const PopupBlockStyles = memo( ( props ) => {
	const { clientId, setAttributes, attributes, context = {}, style } = props;

	const {
		background,
		backgroundColorHover,
		backgroundGradientHover,
		backgroundColor,
		backgroundGradient,
		dimRatio,
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
					style,
				} }
			/>
		</InspectorControls>
	);
} );

/**
 * The Editor settings for this block.
 * Following container's Settings pattern.
 *
 * @param {Object} props The element props.
 * @since 3.0.0
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => {
	let proModalControls = [];
	const proModalSetup = applyFilters( 'spectra.popup-builder.extension', props );
	proModalControls = proModalSetup.$$typeof === Symbol.for( 'react.element' ) && proModalSetup?.props?.children;
	return (
		<>
			
			<PopupGeneralSettings { ...props } />
			<PopupCloseSettings { ...props } />
			<PopupRepetitionSettings {...props} />
			{ proModalControls && proModalSetup }
			<PopupColorSettings { ...props } />
			<PopupOpacitySettings { ...props } />
			<PopupDimensionSettings { ...props } />
			<PopupBlockStyles { ...props } />
		</>
	);
};

export default memo( Settings );
