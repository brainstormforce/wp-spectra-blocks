/**
 * Advanced Gradient Control Component
 *
 * A reusable component system for adding advanced gradient controls to Spectra blocks.
 * Provides precise gradient control with exact location points, theme color support,
 * and automatic dropdown state management to prevent overlapping dropdowns.
 *
 * @since x.x.x
 */

/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { memo, useRef, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanelItem as ToolsPanelItem,
	Button,
	RangeControl,
	ToggleControl,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	__experimentalDropdownContentWrapper as DropdownContentWrapper,
	Dropdown,
	ColorIndicator,
	ColorPicker,
	ColorPalette,
	Flex,
	FlexItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { reset as resetIcon } from '@wordpress/icons';

/**
 * Internal dependencies.
 */
import { useEnhancedGradient } from '@spectra-helpers/use-enhanced-gradient';

/**
 * ColorButton component for gradient color selection
 *
 * Displays a button that opens a color picker dropdown with theme color palette support.
 *
 * @param {Object} props Component props
 * @param {string} props.color Current color value (hex, rgb, or CSS variable)
 * @param {Function} props.onColorChange Callback when color changes
 * @param {string} props.colorLabel Label for the color button
 * @param {Array} props.colorGroups Array of color groups from WordPress (theme, default, custom)
 * @return {Element} ColorButton component
 * @since x.x.x
 */
const ColorButton = memo( ( { color, onColorChange, colorLabel, colorGroups } ) => {
	const getDisplayColor = ( colorValue ) => {
		if ( colorValue?.startsWith( 'var(--wp--preset--color--' ) ) {
			const slug = colorValue.match( /var\(--wp--preset--color--([^)]+)\)/ )?.[ 1 ];
			if ( slug && colorGroups ) {
				// Search across all color groups
				for ( const group of colorGroups ) {
					const themeColor = group?.colors?.find( ( tc ) => tc.slug === slug );
					if ( themeColor ) {
						return themeColor.color;
					}
				}
			}
		}
		return colorValue;
	};

	const popoverProps = {
		placement: 'left-start',
		offset: 36,
		shift: true,
	};

	return (
		<Dropdown
			popoverProps={ popoverProps }
			className="block-editor-tools-panel-color-gradient-settings__dropdown"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					onClick={ onToggle }
					className={ clsx( 'block-editor-panel-color-gradient-settings__dropdown', {
						'is-open': isOpen,
					} ) }
					aria-expanded={ isOpen }
					variant="secondary"
					__next40pxDefaultSize
				>
					<HStack justify="flex-start">
						<Flex expanded={ false }>
							<ColorIndicator colorValue={ getDisplayColor( color ) } />
						</Flex>
						<FlexItem className="block-editor-panel-color-gradient-settings__color-name">
							{ colorLabel }
						</FlexItem>
					</HStack>
				</Button>
			) }
			renderContent={ () => {
				const displayColor = getDisplayColor( color );

				return (
					<DropdownContentWrapper paddingSize="none">
						<div className="block-editor-panel-color-gradient-settings__dropdown-content">
							<div className="block-editor-color-gradient-control__panel">
								<ColorPicker
									color={ displayColor }
									onChange={ ( newColor ) => {
										onColorChange( newColor );
									} }
									enableAlpha={ true }
								/>
								{ colorGroups && colorGroups.length > 0 && (
									<ColorPalette
										colors={ colorGroups }
										value={ displayColor }
										onChange={ ( newColor ) => {
											// If newColor is already a CSS variable, use it directly
											if ( newColor?.startsWith( 'var(' ) ) {
												onColorChange( newColor );
												return;
											}

											// Find the matching color across all groups and use CSS variable
											let matchedColor = null;
											for ( const group of colorGroups ) {
												matchedColor = group?.colors?.find( ( tc ) => tc.color === newColor );
												if ( matchedColor ) break;
											}

											if ( matchedColor ) {
												onColorChange( `var(--wp--preset--color--${ matchedColor.slug })` );
											} else {
												onColorChange( newColor );
											}
										} }
										disableCustomColors={ true }
										clearable={ true }
									/>
								) }
							</div>
						</div>
					</DropdownContentWrapper>
				);
			} }
		/>
	);
} );

/**
 * GradientDropdown component for gradient selection
 *
 * Renders a dropdown with advanced gradient controls including color pickers,
 * gradient type selector (linear/radial), location sliders, and angle control.
 *
 * @param {Object} props Component props
 * @param {string} props.label Label for the dropdown
 * @param {string} props.value Current gradient value
 * @param {Function} props.onChange Callback when gradient value changes
 * @param {Object} props.hook Enhanced gradient hook with parsed gradient data
 * @param {boolean} props.enableAdvBg Whether advanced mode is enabled
 * @param {Function} props.onToggleAdvBg Callback when advanced mode toggles
 * @param {Array} props.colorGroups Array of color groups (theme, default, custom)
 * @param {Object} props.popoverProps Props for popover positioning
 * @param {boolean} props.showToggle Whether to show the "Use Advanced Gradient" toggle (default: true)
 * @param {string} props.dropdownId Unique ID for controlled dropdown state
 * @param {string|null} props.openDropdown Currently open dropdown ID
 * @param {Function} props.setOpenDropdown Function to set open dropdown ID
 * @return {Element} GradientDropdown component
 * @since x.x.x
 */
const GradientDropdown = memo(
	( { label, value, onChange, hook, enableAdvBg, onToggleAdvBg, colorGroups, popoverProps, showToggle = true } ) => {
		const dropdownButtonRef = useRef();

		// Handle advanced mode toggle
		const handleToggleAdvBg = useCallback(
			( newValue ) => {
				onToggleAdvBg( newValue );

				// If enabling advanced mode and no gradient exists, create a default one.
				if ( newValue && ! value ) {
					onChange( 'linear-gradient(0deg, #06558a 0%, #0063A1 100%)' );
				}
			},
			[ onToggleAdvBg, value, onChange ]
		);

		const hasValue = () => !! value;

		const resetValue = () => {
			onChange( undefined );
			if ( showToggle ) {
				onToggleAdvBg( false );
			}
		};

		const shouldShowControls = showToggle ? enableAdvBg : true;

		return (
			<Dropdown
				popoverProps={ popoverProps }
				className="block-editor-tools-panel-color-gradient-settings__dropdown"
				renderToggle={ ( { onToggle, isOpen } ) => (
					<>
						<Button
							onClick={ onToggle }
							className={ clsx( 'block-editor-panel-color-gradient-settings__dropdown', {
								'is-open': isOpen,
							} ) }
							aria-expanded={ isOpen }
							ref={ dropdownButtonRef }
							__next40pxDefaultSize
						>
							<HStack justify="flex-start">
								<Flex expanded={ false }>
									<ColorIndicator colorValue={ shouldShowControls ? value : undefined } />
								</Flex>
								<FlexItem className="block-editor-panel-color-gradient-settings__color-name">
									{ label }
								</FlexItem>
							</HStack>
						</Button>
						{ hasValue() && (
							<Button
								__next40pxDefaultSize
								label={ __( 'Reset', 'spectra' ) }
								className="block-editor-panel-color-gradient-settings__reset"
								size="small"
								icon={ resetIcon }
								onClick={ () => {
									resetValue();
									if ( isOpen ) {
										onToggle();
									}
									dropdownButtonRef.current?.focus();
								} }
							/>
						) }
					</>
				) }
				renderContent={ ( {} ) => (
					<DropdownContentWrapper paddingSize="medium">
						<div className="block-editor-global-styles__shadow-popover-container">
							<VStack spacing={ 4 }>
								{ showToggle && (
									<ToggleControl
										label={ __( 'Use Advanced Gradient', 'spectra' ) }
										checked={ enableAdvBg }
										onChange={ handleToggleAdvBg }
									/>
								) }
								{ shouldShowControls && (
									<>
										<ColorButton
											color={ hook.parsed?.colors?.[ 0 ]?.color || '#06558a' }
											onColorChange={ ( color ) => {
												hook.setColorAtIndex( 0, color );
											} }
											colorLabel={ __( 'Color 1', 'spectra' ) }
											colorGroups={ colorGroups }
											popoverProps={ popoverProps }
										/>
										<ColorButton
											color={ hook.parsed?.colors?.[ 1 ]?.color || '#0063A1' }
											onColorChange={ ( color ) => {
												hook.setColorAtIndex( 1, color );
											} }
											colorLabel={ __( 'Color 2', 'spectra' ) }
											colorGroups={ colorGroups }
											popoverProps={ popoverProps }
										/>
										<ToggleGroupControl
											label={ __( 'Type', 'spectra' ) }
											value={ hook.parsed?.type || 'linear' }
											onChange={ hook.setType }
											isBlock
										>
											<ToggleGroupControlOption
												value="linear"
												label={ __( 'Linear', 'spectra' ) }
											/>
											<ToggleGroupControlOption
												value="radial"
												label={ __( 'Radial', 'spectra' ) }
											/>
										</ToggleGroupControl>
										<RangeControl
											label={ __( 'Location 1', 'spectra' ) }
											value={ hook.parsed?.colors?.[ 0 ]?.position ?? 0 }
											onChange={ ( position ) => hook.setPositionAtIndex( 0, position ) }
											min={ -100 }
											max={ 100 }
											__next40pxDefaultSize
											__nextHasNoMarginBottom
										/>
										<RangeControl
											label={ __( 'Location 2', 'spectra' ) }
											value={ hook.parsed?.colors?.[ 1 ]?.position ?? 100 }
											onChange={ ( position ) => hook.setPositionAtIndex( 1, position ) }
											min={ -100 }
											max={ 100 }
											__next40pxDefaultSize
											__nextHasNoMarginBottom
										/>
										{ ( hook.parsed?.type === 'linear' || ! hook.parsed ) && (
											<RangeControl
												label={ __( 'Angle', 'spectra' ) }
												value={ hook.parsed?.angle ?? 0 }
												onChange={ hook.setAngle }
												min={ 0 }
												max={ 360 }
												step={ 1 }
											/>
										) }
									</>
								) }
							</VStack>
						</div>
					</DropdownContentWrapper>
				) }
			/>
		);
	}
);

/**
 * Advanced Gradient Control Component (Individual control wrapper)
 *
 * Wraps a GradientDropdown in a ToolsPanelItem for integration with WordPress block settings.
 * This component is typically used internally by AdvancedGradientControlsGroup.
 *
 * @param {Object} props Component props
 * @param {string} props.label Label for the control
 * @param {string} props.gradientValue Current gradient value
 * @param {Function} props.onGradientChange Callback when gradient changes
 * @param {boolean} props.enableAdvBg Whether advanced mode is enabled
 * @param {Function} props.onToggleAdvBg Callback when advanced mode toggles
 * @param {Object} props.toolsPanelProps Additional props for ToolsPanelItem
 * @param {string} props.clientId Block client ID
 * @param {boolean} props.showTopBorder Whether to show top border (for first item)
 * @param {boolean} props.showToggle Whether to show "Use Advanced Gradient" toggle inside dropdown
 * @param {string} props.dropdownId Unique ID for controlled dropdown state
 * @param {string|null} props.openDropdown Currently open dropdown ID
 * @param {Function} props.setOpenDropdown Function to set open dropdown ID
 * @return {Element} AdvancedGradientControl component
 * @since x.x.x
 */
export const AdvancedGradientControl = memo(
	( {
		label,
		gradientValue,
		onGradientChange,
		enableAdvBg,
		onToggleAdvBg,
		toolsPanelProps = {},
		clientId,
		showTopBorder = false,
		enableAdvGradients = false,
		showToggle = true,
		shouldShowContent = true,
	} ) => {
		const colorGradientSettings = useMultipleOriginColorsAndGradients();
		// Get the colors array - it's already grouped by origin (theme, default, custom)
		const colorGroups = colorGradientSettings?.colors || [];

		const gradientHook = useEnhancedGradient( gradientValue, onGradientChange );

		const popoverProps = {
			placement: 'left-start',
			offset: 36,
			shift: true,
			flip: false,
		};

		// Build style object - hide completely when content shouldn't show
		const itemStyle = {
			...( showTopBorder && shouldShowContent
				? {
						borderTop: '1px solid #ddd',
						borderTopLeftRadius: '2px',
						borderTopRightRadius: '2px',
						marginTop: '24px',
				  }
				: {} ),
			...( ! shouldShowContent
				? {
						display: 'none',
				  }
				: {} ),
		};

		return (
			<ToolsPanelItem
				className="block-editor-tools-panel-color-gradient-settings__item"
				hasValue={ () => {
					if ( ! shouldShowContent ) {
						return false;
					}
					return !! gradientValue || ( enableAdvGradients && enableAdvBg );
				} }
				label={ label }
				onDeselect={ () => {
					onGradientChange( undefined );
					if ( showToggle ) {
						onToggleAdvBg( false );
					}
				} }
				resetAllFilter={ () => ( {
					[ toolsPanelProps.gradientAttr ]: undefined,
					[ toolsPanelProps.advancedAttr ]: false,
				} ) }
				isShownByDefault={ shouldShowContent }
				panelId={ clientId }
				style={ itemStyle }
				{ ...toolsPanelProps }
			>
				{ shouldShowContent && (
					<GradientDropdown
						label={ label }
						value={ gradientValue }
						onChange={ onGradientChange }
						hook={ gradientHook }
						enableAdvBg={ enableAdvBg }
						onToggleAdvBg={ onToggleAdvBg }
						colorGroups={ colorGroups }
						popoverProps={ popoverProps }
						showToggle={ showToggle }
					/>
				) }
			</ToolsPanelItem>
		);
	}
);

/**
 * AdvancedGradientControlsGroup - Primary API for using advanced gradient controls
 *
 * Manages multiple gradient controls with shared dropdown state to prevent overlapping dropdowns.
 * This is the recommended way to add gradient controls to any Spectra block.
 *
 * @param {Object} props Component props
 * @param {string} props.clientId Block client ID
 * @param {Function} props.setAttributes Function to set block attributes
 * @param {Object} props.attributes Block attributes object
 * @param {Array<Object>} props.gradients Array of gradient configuration objects
 * @param {string} props.enableAttr Attribute name for global enable toggle (e.g., 'enableAdvGradients')
 * @param {string} props.enableLabel Label for the enable toggle (optional, default: 'Enable Advanced Gradients')
 * @param {boolean} props.showGlobalToggle Whether to show global toggle (default: true)
 * @param {boolean} props.hideIndividualToggles Whether to hide individual "Use Advanced Gradient" toggles (default: false)
 *
 * @example
 * ```jsx
 * // Example 1: Multiple gradients with global AND individual toggles (container block)
 * const gradientConfigs = [
 *   {
 *     label: __( 'Background Hover' ),
 *     valueAttr: 'advBgGradientHover',
 *     useAdvancedAttr: 'enableAdvBgGradientHover',
 *     showTopBorder: true,
 *   },
 *   {
 *     label: __( 'Background' ),
 *     valueAttr: 'advBgGradient',
 *     useAdvancedAttr: 'enableAdvBgGradient',
 *   },
 * ];
 *
 * <AdvancedGradientControlsGroup
 *   clientId={ clientId }
 *   setAttributes={ setAttributes }
 *   attributes={ attributes }
 *   gradients={ gradientConfigs }
 *   enableAttr="enableAdvGradients"
 * />
 *
 * // Example 2: Single gradient with ONLY global toggle (slider/slider-child)
 * const gradientConfigs = [
 *   {
 *     label: __( 'Background' ),
 *     valueAttr: 'advBgGradient',
 *   },
 * ];
 *
 * <AdvancedGradientControlsGroup
 *   clientId={ clientId }
 *   setAttributes={ setAttributes }
 *   attributes={ attributes }
 *   gradients={ gradientConfigs }
 *   enableAttr="enableAdvGradients"
 *   hideIndividualToggles={ true }
 * />
 * ```
 *
 * Each gradient config object should contain:
 * @property {string} label - Label for the gradient control (e.g., 'Background', 'Border')
 * @property {string} valueAttr - Attribute name for gradient value (e.g., 'advBgGradient')
 * @property {string} [useAdvancedAttr] - Attribute name for useAdvanced flag (e.g., 'enableAdvBgGradient') - only needed when hideIndividualToggles is false
 * @property {boolean} [showTopBorder] - Whether to show top border (optional, default: false)
 *
 * @return {Element} Group of gradient controls
 * @since x.x.x
 */
const AdvancedGradientControlsGroup = memo(
	( {
		clientId,
		setAttributes,
		attributes,
		gradients,
		enableAttr = 'enableAdvGradients',
		enableLabel = __( 'Enable Advanced Gradients', 'spectra' ),
		helpText = '',
		defaultGradient = 'linear-gradient(0deg, #06558a 0%, #0063A1 100%)',
		showGlobalToggle = true,
		hideIndividualToggles = false,
	} ) => {
		const enableAdvGradients = showGlobalToggle ? attributes[ enableAttr ] : true;

		// Create default gradients when advanced gradients are enabled
		useEffect( () => {
			if ( enableAdvGradients || ! showGlobalToggle ) {
				const updates = {};

				// Check each gradient config and create default if toggle is on but value is empty
				gradients.forEach( ( config ) => {
					const { valueAttr, useAdvancedAttr } = config;
					const currentValue = attributes[ valueAttr ];

					if ( hideIndividualToggles ) {
						// For simple mode, just create default gradient if value is empty
						if ( ! currentValue ) {
							updates[ valueAttr ] = defaultGradient;
						}
					} else {
						// For complex mode, check individual toggle
						const isEnabled = attributes[ useAdvancedAttr ];
						if ( isEnabled && ! currentValue ) {
							updates[ valueAttr ] = defaultGradient;
						}
					}
				} );

				// Only update if there are changes
				if ( Object.keys( updates ).length > 0 ) {
					setAttributes( updates );
				}
			}
		}, [
			enableAdvGradients,
			...( hideIndividualToggles ? [] : gradients.map( ( g ) => attributes[ g.useAdvancedAttr ] ) ),
		] );

		return (
			<InspectorControls group="color">
				{ showGlobalToggle && (
					<ToolsPanelItem
						hasValue={ () => !! enableAdvGradients }
						label={ __( 'Advanced Gradients', 'spectra' ) }
						onDeselect={ () => setAttributes( { [ enableAttr ]: false } ) }
						resetAllFilter={ () => ( { [ enableAttr ]: false } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleControl
							label={ enableLabel }
							checked={ enableAdvGradients }
							onChange={ ( value ) => setAttributes( { [ enableAttr ]: value } ) }
							help={ enableAdvGradients && helpText ? helpText : '' }
						/>
					</ToolsPanelItem>
				) }
				{ gradients.map( ( config ) => {
					const { label, valueAttr, useAdvancedAttr, showTopBorder = false } = config;
					const gradientValue = attributes[ valueAttr ];
					const enableAdvBg = hideIndividualToggles ? true : attributes[ useAdvancedAttr ];

					// Always render the ToolsPanelItem to maintain consistent ordering
					// Only show content when advanced gradients are enabled
					const shouldShowContent = enableAdvGradients || ! showGlobalToggle;

					return (
						<AdvancedGradientControl
							key={ valueAttr }
							label={ label }
							gradientValue={ gradientValue }
							onGradientChange={ ( value ) => setAttributes( { [ valueAttr ]: value } ) }
							enableAdvBg={ enableAdvBg }
							onToggleAdvBg={
								hideIndividualToggles
									? () => {}
									: ( value ) => setAttributes( { [ useAdvancedAttr ]: value } )
							}
							clientId={ clientId }
							showTopBorder={ showTopBorder }
							toolsPanelProps={ {
								gradientAttr: valueAttr,
								advancedAttr: useAdvancedAttr,
							} }
							enableAdvGradients={ enableAdvGradients }
							showToggle={ ! hideIndividualToggles }
							shouldShowContent={ shouldShowContent }
						/>
					);
				} ) }
			</InspectorControls>
		);
	}
);

export default AdvancedGradientControlsGroup;
