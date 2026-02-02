/**
 * Flexible Shadow Control Component
 * 
 * A highly reusable shadow control component that can be easily integrated into any block.
 * Follows the same pattern as the Background component for consistency and flexibility.
 * Features a color picker, position sliders, blur/spread controls, inset/outset toggle, and preset options.
 * 
 * @since x.x.x
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ColorPalette,
	__experimentalVStack as VStack,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalText as Text,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { InspectorControls, useSetting } from '@wordpress/block-editor';
import { memo, useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import DebouncedRangeControl from '../debounced-range-control';
import { parseShadowString, generateShadowString } from '@spectra-helpers/shadow';

/**
 * Flexible Shadow Control Component
 * 
 * @param {Object} props Component props.
 * @param {string} props.clientId Block client ID.
 * @param {Function} props.setAttributes Block setAttributes function.
 * @param {Object} props.shadow Shadow configuration object with label and value.
 * @param {string} props.shadow.label Attribute name for the shadow.
 * @param {string} props.shadow.value Current shadow CSS string value.
 * @param {Object} props.shadowHover Shadow hover configuration object (optional).
 * @param {string} props.shadowHover.label Attribute name for the hover shadow.
 * @param {string} props.shadowHover.value Current hover shadow CSS string value.
 * @param {string} props.label Custom label for the shadow control.
 * @param {string} props.group Inspector controls group (default: 'styles').
 * @param {boolean} props.showHoverState Whether to show hover state toggle (default: false).
 * @return {Element} The ShadowControl component.
 */
const ShadowControl = memo( ( {
	clientId,
	setAttributes,
	shadow: {
		label: attributeLabel,
		value: attributeValue = '',
	},
	shadowHover = null,
	label = __( 'Box Shadow', 'spectra' ),
	group = 'styles',
	showHoverState = false,
} ) => {
	// Get only default WordPress colors for the color palette.
	const defaultColors = useSetting( 'color.palette.default' ) || [];
	
	// Memoize color palette to prevent recalculation on every render.
	const colorPalette = useMemo( () => {
		return defaultColors;
	}, [ defaultColors ] );

	// State for current shadow tab (normal or hover).
	const [currentState, setCurrentState] = useState( 'normal' );
	
	// Parse current shadow values or use defaults.
	const [shadowState, setShadowState] = useState( () => parseShadowString( attributeValue ) );
	const [shadowHoverState, setShadowHoverState] = useState( () => 
		shadowHover ? parseShadowString( shadowHover.value ) : parseShadowString( '' )
	);

	// Sync shadow states when attribute values change externally.
	useEffect( () => {
		setShadowState( parseShadowString( attributeValue ) );
	}, [ attributeValue ] );

	useEffect( () => {
		if ( shadowHover ) {
			setShadowHoverState( parseShadowString( shadowHover.value ) );
		}
	}, [ shadowHover?.value ] );

	// Update shadow value based on current state.
	const updateShadow = ( property, newValue ) => {

		if ( currentState === 'hover' && shadowHover ) {
			// Update hover shadow
			const newShadowHoverState = {
				...shadowHoverState,
				[property]: newValue,
			};
			setShadowHoverState( newShadowHoverState );

			// Generate CSS shadow string and update hover attributes.
			const shadowString = generateShadowString( newShadowHoverState );
			
			if ( setAttributes && shadowHover.label ) {
				setAttributes( { [shadowHover.label]: shadowString } );
			}
		} else {
			// Update normal shadow.
			const newShadowState = {
				...shadowState,
				[property]: newValue,
			};
			setShadowState( newShadowState );

			// Generate CSS shadow string and update normal attributes.
			const shadowString = generateShadowString( newShadowState );
			
			if ( setAttributes && attributeLabel ) {
				setAttributes( { [attributeLabel]: shadowString } );
			}
		}
	};

	// Memoize current shadow state to prevent excessive re-calculations.
	const getCurrentShadowState = useMemo( () => {
		const state = currentState === 'hover' && shadowHover ? shadowHoverState : shadowState;
		return state;
	}, [ currentState, shadowHover, shadowState, shadowHoverState ] );

	// Memoize current attribute value to prevent excessive re-calculations.
	const getCurrentAttributeValue = useMemo( () => {
		const value = currentState === 'hover' && shadowHover ? shadowHover.value : attributeValue;
		return value;
	}, [ currentState, shadowHover, attributeValue ] );


	// Check if all shadow properties are at their default values.
	const isAllPropertiesDefault = ( currentShadowState ) => {
		const defaultValues = {
			color: '',
			x: 0,
			y: 0,
			blur: 0,
			spread: 0,
			inset: false,
		};
		
		return Object.keys( defaultValues ).every(
			property => currentShadowState[property] === defaultValues[property]
		);
	};

	// Reset individual shadow properties to their default values.
	const resetShadowProperty = ( property, defaultValue ) => {
		if ( currentState === 'hover' && shadowHover ) {
			// Update hover shadow with default value for this property.
			const newShadowHoverState = {
				...shadowHoverState,
				[property]: defaultValue,
			};
			setShadowHoverState( newShadowHoverState );
			
			// Check if all properties are at default values.
			if ( isAllPropertiesDefault( newShadowHoverState ) ) {
				setAttributes( { [shadowHover.label]: undefined } );
			} else {
				const shadowString = generateShadowString( newShadowHoverState );
				setAttributes( { [shadowHover.label]: shadowString } );
			}
		} else {
			// Update normal shadow with default value for this property.
			const newShadowState = {
				...shadowState,
				[property]: defaultValue,
			};
			setShadowState( newShadowState );
			
			// Check if all properties are at default values.
			if ( isAllPropertiesDefault( newShadowState ) ) {
				setAttributes( { [attributeLabel]: undefined } );
			} else {
				const shadowString = generateShadowString( newShadowState );
				setAttributes( { [attributeLabel]: shadowString } );
			}
		}
	};

	// Check if individual property has non-default value (like background component).
	const hasPropertyValue = useCallback( ( property ) => {
		const defaultValues = {
			color: '',
			x: 0,
			y: 0,
			blur: 0,
			spread: 0,
			inset: false,
		};
		
		return getCurrentShadowState[property] !== defaultValues[property];
	}, [ getCurrentShadowState ] );


	// Allow extensions to add custom UI.
	const Extensions = applyFilters( 'spectra.shadowControl.extensions', null, {
		shadowState,
		shadowHoverState,
		currentState,
		setCurrentState,
		updateShadow,
		attributeLabel,
		attributeValue,
		shadowHover,
		clientId,
		setAttributes,
		getCurrentShadowState,
		getCurrentAttributeValue,
	} );

	// Render the shadow control as ToolsPanel with individual ToolsPanelItems.
	return (
		<InspectorControls group={ group }>
			<ToolsPanel
				label={ label }
				resetAll={ () => {
					// Only reset the currently active state (normal or hover).
					if ( currentState === 'hover' && shadowHover ) {
						// Reset only hover shadow.
						setAttributes( { [shadowHover.label]: undefined } );
					} else {
						// Reset only normal shadow.
						setAttributes( { [attributeLabel]: undefined } );
					}
				} }
				panelId={ clientId }
			>
				{/* State Toggle (Normal/Hover) */}
				{ showHoverState && shadowHover && (
					<ToolsPanelItem
						hasValue={ () => currentState === 'hover' }
						label={ __( 'Shadow State', 'spectra' ) }
						onDeselect={ () => setCurrentState( 'normal' ) }
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 2 }>
							<Text>
								{ __( 'Shadow State', 'spectra' ) }
							</Text>
							<ToggleGroupControl
								__nextHasNoMarginBottom
								value={ currentState }
								onChange={ setCurrentState }
								isBlock
							>
								<ToggleGroupControlOption
									value="normal"
									label={ __( 'Normal', 'spectra' ) }
								/>
								<ToggleGroupControlOption
									value="hover"
									label={ __( 'Hover', 'spectra' ) }
								/>
							</ToggleGroupControl>
						</VStack>
					</ToolsPanelItem>
				) }


				{/* Shadow Color ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'color' ) }
					label={ __( 'Shadow Color', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'color', '' ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 2 }>
						<Text weight={ 600 }>
							{ __( 'Color', 'spectra' ) }
						</Text>
						<ColorPalette
							colors={ colorPalette }
							value={ getCurrentShadowState.color }
							onChange={ ( color ) => {
								updateShadow( 'color', color || '' );
							} }
							enableAlpha={ true }
							clearable={ false }
						/>
					</VStack>
				</ToolsPanelItem>

				{/* X Position ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'x' ) }
					label={ __( 'X Position', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'x', 0 ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack alignment="stretch">
						<Text>
							{ __( 'X POSITION', 'spectra' ) }
						</Text>
						<DebouncedRangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							value={ getCurrentShadowState.x }
							onChange={ ( x ) => updateShadow( 'x', x ) }
							min={ -100 }
							max={ 100 }
							step={ 1 }
							withInputField={ true }
						/>
					</VStack>
				</ToolsPanelItem>

				{/* Y Position ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'y' ) }
					label={ __( 'Y Position', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'y', 0 ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack alignment="stretch">
						<Text>
							{ __( 'Y POSITION', 'spectra' ) }
						</Text>
						<DebouncedRangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							value={ getCurrentShadowState.y }
							onChange={ ( y ) => updateShadow( 'y', y ) }
							min={ -100 }
							max={ 100 }
							step={ 1 }
							withInputField={ true }
						/>
					</VStack>
				</ToolsPanelItem>

				{/* Blur ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'blur' ) }
					label={ __( 'Blur', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'blur', 0 ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 2 }>
						<Text>
							{ __( 'BLUR', 'spectra' ) }
						</Text>
						<DebouncedRangeControl
							__nextHasNoMarginBottom
							value={ getCurrentShadowState.blur }
							onChange={ ( blur ) => updateShadow( 'blur', blur ) }
							min={ 0 }
							max={ 100 }
							step={ 1 }
							withInputField={ true }
						/>
					</VStack>
				</ToolsPanelItem>

				{/* Spread ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'spread' ) }
					label={ __( 'Spread', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'spread', 0 ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 2 }>
						<Text>
							{ __( 'SPREAD', 'spectra' ) }
						</Text>
						<DebouncedRangeControl
							__nextHasNoMarginBottom
							value={ getCurrentShadowState.spread }
							onChange={ ( spread ) => updateShadow( 'spread', spread ) }
							min={ -50 }
							max={ 50 }
							step={ 1 }
							withInputField={ true }
						/>
					</VStack>
				</ToolsPanelItem>

				{/* Shadow Type ToolsPanelItem */}
				<ToolsPanelItem
					hasValue={ () => hasPropertyValue( 'inset' ) }
					label={ __( 'Shadow Type', 'spectra' ) }
					onDeselect={ () => resetShadowProperty( 'inset', false ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 2 }>
						<Text>
							{ __( 'SHADOW TYPE', 'spectra' ) }
						</Text>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							value={ getCurrentShadowState.inset ? 'inset' : 'outset' }
							onChange={ ( value ) => updateShadow( 'inset', value === 'inset' ) }
							isBlock
						>
							<ToggleGroupControlOption
								value="outset"
								label={ __( 'Outset', 'spectra' ) }
							/>
							<ToggleGroupControlOption
								value="inset"
								label={ __( 'Inset', 'spectra' ) }
							/>
						</ToggleGroupControl>
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>

			{/* Allow extensions to add custom UI */}
			{ Extensions }
		</InspectorControls>
	);
} );

ShadowControl.displayName = 'ShadowControl';

export default ShadowControl;
