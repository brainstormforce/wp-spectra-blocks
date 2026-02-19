/**
 * External dependencies.
 */
import { memo, isValidElement, useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanelItem as ToolsPanelItem,
    __experimentalToolsPanel as ToolsPanel,
    SelectControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { TabBlockControls } from '@spectra/modal/helpers';

/**
 * Element Sub-settings: General settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {

	// Destructure the required props.
	const {
		setAttributes,
		attributes: {
			modalTrigger,
			escPress,
			overlayClick,
			closeIconPosition,
			openModalAs,
			modalPosition,
            // eslint-disable-next-line no-unused-vars
			cssClass, cssId, exitIntent, showAfterSeconds, hideForDays, enableCookies, setCookiesOn,
		},
		clientId
	} = props;

	const isProActivated = 'active' === spectra_blocks_info.spectra_pro_status? true : false;

	// Options from Free and Pro 
	const freeAndProOptions = [
		{ label: __( 'Button', 'spectra-blocks' ), value: 'button', disabled: false },
		{ label: __( 'Icon', 'spectra-blocks' ), value: 'icon', disabled: false },
		{ label: __( 'Text', 'spectra-blocks' ), value: 'text', disabled: false },
		{ label: __( 'Custom Class (Pro)', 'spectra-blocks' ), value: 'custom-class', disabled: ! isProActivated },
		{ label: __( 'Custom ID (Pro)', 'spectra-blocks' ), value: 'custom-id', disabled: ! isProActivated },
		{ label: __( 'Automatic (Pro)', 'spectra-blocks' ), value: 'automatic', disabled: ! isProActivated }
	];

	let proModalControls = [];

	let proModalTriggerSettings = [];

	if ( true === isProActivated ) {
		const proTriggerSettings = applyFilters( 'spectra.modal-child-trigger.options', props );
		proModalTriggerSettings =
		proTriggerSettings.$$typeof === Symbol.for( 'react.element' ) && proTriggerSettings?.props?.children;

		const proModalSetup = applyFilters( 'spectra.modal-popup.options', props );
		proModalControls = proModalSetup.$$typeof === Symbol.for( 'react.element' ) && proModalSetup?.props?.children;

	}
	
	const proModalSettings = [];

	const [proValidIconPositionOptions, setProValidIconPositionOptions] = useState( [
		{ value: 'popup-top-left', disabled: false, label: __( 'Top Left', 'spectra-blocks' ) },
		{ value: 'popup-top-right', disabled: false, label: __( 'Top Right', 'spectra-blocks' ) },
	] );
	
	useEffect( () => {
		if ( true !== isProActivated ) return;
	
		const updatedProps = { ...props, openModalAs, modalPosition };
	
		// Fetch icon position options
		const iconPositionOptions = applyFilters(
			'spectra-blocks.modal-popup.styles.icon-position.children',
			updatedProps
		);
	
		let updatedOptions = [
			{ value: 'popup-top-left', disabled: false, label: __( 'Top Left', 'spectra-blocks' ) },
			{ value: 'popup-top-right', disabled: false, label: __( 'Top Right', 'spectra-blocks' ) },
		];
	
		if ( isValidElement( iconPositionOptions ) ) {
			const children = iconPositionOptions.props?.children || [];
			updatedOptions = Array.isArray( children )
				? children.map( ( option ) => {
						const { value, disabled, children: label } = option.props;
						return {
							value,
							disabled: !!disabled,
							label,
						};
				  } )
				: updatedOptions;
		} else if ( Array.isArray( iconPositionOptions ) ) {
			updatedOptions = iconPositionOptions.map( ( { value, disabled, label } ) => ( {
				value,
				disabled: !!disabled,
				label,
			} ) );
		}
	
		setProValidIconPositionOptions( updatedOptions );
	
	}, [ openModalAs, modalPosition ] ); // Dependency array
	
	return (
		<InspectorControls group="settings">
			{ proModalControls }
			<ToolsPanel
				label={ __( 'Modal Type', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						modalTrigger: 'button',
						escPress: true,
						overlayClick: false,
						cssClass: undefined,
						cssId: undefined,
						exitIntent: false,
						showAfterSeconds: false,
						noOfSecondsToShow: 5,
						enableCookies: false,
						setCookiesOn: 'close-action',
						hideForDays: 2,
					} );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! modalTrigger }
					label={ __( 'Trigger Type', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { modalTrigger: undefined } ) }
					resetAllFilter={ () => ( {
						modalTrigger: 'button',
					} ) }
					isShownByDefault
					panelId={clientId}
				>
					<SelectControl
						label={ __( 'Trigger Type', 'spectra-blocks' ) }
						value={ modalTrigger }
						options={ freeAndProOptions }
						onChange={ ( value ) => setAttributes( { modalTrigger: value } ) }
					/>
				</ToolsPanelItem>
				{ proModalTriggerSettings }
				<ToolsPanelItem
					hasValue={ () => !! escPress }
					label={ __( 'Close on ESC Keypress','spectra-blocks' ) }
					onDeselect={ () => setAttributes( { escPress: undefined } ) }
					resetAllFilter={ () => ( {
						escPress: true,
					} ) }
					isShownByDefault
					panelId={clientId}
				>
					<ToggleControl
						checked={ escPress }
						label={ __( 'Close on ESC Keypress', 'spectra-blocks' ) }
						onChange={ () => setAttributes( { escPress: ! escPress } ) }
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					hasValue={ () => !! overlayClick }
					label={ __( 'Close on Overlay Click','spectra-blocks' ) }
					onDeselect={ () => setAttributes( { overlayClick: undefined } ) }
					resetAllFilter={ () => ( {
						overlayClick: false,
					} ) }
					isShownByDefault
					panelId={clientId}
				>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ overlayClick }
						label={ __( 'Close on Overlay Click', 'spectra-blocks' ) }
						onChange={ () => setAttributes( { overlayClick: ! overlayClick } ) }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
			{ proModalSettings }
			<ToolsPanel
				label={ __( 'Close Button', 'spectra-blocks' ) }
				resetAll={ () => setAttributes( { closeIconPosition: 'popup-top-right' } ) }
				panelId={ clientId }
            >
				<ToolsPanelItem
					hasValue={ () => !! closeIconPosition }
					label={ __( 'Icon Position', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { closeIconPosition: 'popup-top-right' } ) }
					resetAllFilter={ () => ( {
						closeIconPosition: 'popup-top-right',
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						label={ __( 'Icon Position', 'spectra-blocks' ) }
						value={ closeIconPosition }
						options={proValidIconPositionOptions}
						onChange={ ( value ) => setAttributes( { closeIconPosition: value } ) }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
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
const Settings = ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<TabBlockControls { ...{ ...props } } />
	</>
);

export default memo( Settings );
