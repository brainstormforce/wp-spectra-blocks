/**
 * External dependencies.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	ToggleControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { DEFAULT_RESPONSIVE_CONDITIONS, DEVICE_BREAKPOINTS } from './utils/constants';
import { isAllowedBlock } from './utils/helpers';

/**
 * Responsive Conditions Options Panel.
 *
 * Provides UI controls for managing block visibility across different devices,
 * allowing users to hide blocks on desktop, tablet, or mobile devices.
 *
 * @since x.x.x
 *
 * @param {Object} props - Component props.
 * @return {Element} Responsive conditions options panel component.
 */
const ResponsiveConditionsOptions = ( props ) => {
	const {
		setAttributes,
		attributes: { responsiveConditions = DEFAULT_RESPONSIVE_CONDITIONS },
	} = props;

	/**
	 * Updates responsive conditions attribute.
	 *
	 * @param {string}  device - Device type (hideOnDesktop, hideOnTablet, hideOnMobile).
	 * @param {boolean} value  - Whether to hide on this device.
	 */
	const updateResponsiveCondition = ( device, value ) => {
		setAttributes( {
			responsiveConditions: {
				...responsiveConditions,
				[ device ]: value,
			},
		} );
	};

	return (
		<>
			<ToolsPanelItem
				label={ __( 'Hide on Desktop', 'ultimate-addons-for-gutenberg' ) }
				hasValue={ () => responsiveConditions.hideOnDesktop }
				onDeselect={ () => updateResponsiveCondition( 'hideOnDesktop', false ) }
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Hide on Desktop', 'ultimate-addons-for-gutenberg' ) }
					checked={ responsiveConditions.hideOnDesktop }
					onChange={ ( value ) => updateResponsiveCondition( 'hideOnDesktop', value ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __( 'Hide on Tablet', 'ultimate-addons-for-gutenberg' ) }
				hasValue={ () => responsiveConditions.hideOnTablet }
				onDeselect={ () => updateResponsiveCondition( 'hideOnTablet', false ) }
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Hide on Tablet', 'ultimate-addons-for-gutenberg' ) }
					checked={ responsiveConditions.hideOnTablet }
					onChange={ ( value ) => updateResponsiveCondition( 'hideOnTablet', value ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __( 'Hide on Mobile', 'ultimate-addons-for-gutenberg' ) }
				hasValue={ () => responsiveConditions.hideOnMobile }
				onDeselect={ () => updateResponsiveCondition( 'hideOnMobile', false ) }
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Hide on Mobile', 'ultimate-addons-for-gutenberg' ) }
					checked={ responsiveConditions.hideOnMobile }
					onChange={ ( value ) => updateResponsiveCondition( 'hideOnMobile', value ) }
				/>
			</ToolsPanelItem>
		</>
	);
};

/**
 * Higher-order component to add responsive conditions controls to block settings.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - Original block edit component.
 * @return {Function} Enhanced block edit component with responsive conditions controls.
 */
const addResponsiveConditionsControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! isAllowedBlock( { name: props.name } ) ) {
			return <BlockEdit { ...props } />;
		}

		const { setAttributes, isSelected } = props;

		const resetAll = () => {
			setAttributes( {
				responsiveConditions: DEFAULT_RESPONSIVE_CONDITIONS,
			} );
		};

		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && (
					<InspectorControls>
						<ToolsPanel
							label={ __( 'Responsive Conditions', 'ultimate-addons-for-gutenberg' ) }
							resetAll={ resetAll }
						>
							<ResponsiveConditionsOptions { ...props } />

							<div
								style={ {
									gridColumn: 'span 2',
								} }
							>
								<BaseControl
									help={ __(
										'Hide this block on desktop, tablet, or mobile devices. Striped blocks in the editor indicate hidden blocks that will not appear on the live site.',
										'ultimate-addons-for-gutenberg'
									) }
								/>
							</div>
						</ToolsPanel>
					</InspectorControls>
				) }
			</>
		);
	};
}, 'addResponsiveConditionsControls' );

/**
 * Higher-order component to add responsive condition classes to block wrapper in editor.
 * Only adds the class for the current device preview mode.
 *
 * @since x.x.x
 *
 * @param {Function} BlockListBlock - Original BlockListBlock component.
 * @return {Function} Enhanced BlockListBlock component with responsive classes.
 */
export const addResponsiveConditionsClasses = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const { attributes, name } = props;

		if ( ! isAllowedBlock( { name } ) || ! attributes?.responsiveConditions ) {
			return <BlockListBlock { ...props } />;
		}

		const { responsiveConditions } = attributes;

		// Get current device preview mode from editor
		const deviceType = useSelect( ( select ) => {
			return select( 'core/editor' )?.getDeviceType?.() || DEVICE_BREAKPOINTS.DESKTOP;
		}, [] );

		// Determine if we should show the visual indicator based on current device and responsive conditions
		let shouldShowIndicator = false;
		const deviceTypeNormalized = deviceType.toLowerCase();

		if ( deviceTypeNormalized === DEVICE_BREAKPOINTS.DESKTOP && responsiveConditions.hideOnDesktop ) {
			shouldShowIndicator = true;
		} else if ( deviceTypeNormalized === DEVICE_BREAKPOINTS.TABLET && responsiveConditions.hideOnTablet ) {
			shouldShowIndicator = true;
		} else if ( deviceTypeNormalized === DEVICE_BREAKPOINTS.MOBILE && responsiveConditions.hideOnMobile ) {
			shouldShowIndicator = true;
		}

		const responsiveClassName = shouldShowIndicator ? 'spectra-responsive-hidden' : '';

		// Add the responsive class to the block wrapper only if needed
		const enhancedProps = responsiveClassName
			? {
					...props,
					className: props.className ? `${ props.className } ${ responsiveClassName }` : responsiveClassName,
			  }
			: props;

		return <BlockListBlock { ...enhancedProps } />;
	};
}, 'addResponsiveConditionsClasses' );

export default addResponsiveConditionsControls;
