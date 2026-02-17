/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalInputControl as InputControl,
	__experimentalVStack as VStack,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

// Language options for the dropdown - moved outside component for performance
	const languageOptions = [
		{
			value: 'af',
			label: __( 'Afrikaans', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sq',
			label: __( 'Albanian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'am',
			label: __( 'Amharic', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ar',
			label: __( 'Arabic', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'hy',
			label: __( 'Armenian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'az',
			label: __( 'Azerbaijani', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'eu',
			label: __( 'Basque', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'be',
			label: __( 'Belarusian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'bn',
			label: __( 'Bengali', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'bs',
			label: __( 'Bosnian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'bg',
			label: __( 'Bulgarian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'my',
			label: __( 'Burmese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ca',
			label: __( 'Catalan', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'zh',
			label: __( 'Chinese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'hr',
			label: __( 'Croatian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'cs',
			label: __( 'Czech', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'da',
			label: __( 'Danish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'nl',
			label: __( 'Dutch', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'en',
			label: __( 'English', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'et',
			label: __( 'Estonian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'fa',
			label: __( 'Farsi', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'fi',
			label: __( 'Finnish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'fr',
			label: __( 'French', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'gl',
			label: __( 'Galician', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ka',
			label: __( 'Georgian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'de',
			label: __( 'German', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'el',
			label: __( 'Greek', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'gu',
			label: __( 'Gujarati', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'iw',
			label: __( 'Hebrew', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'hi',
			label: __( 'Hindi', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'hu',
			label: __( 'Hungarian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'is',
			label: __( 'Icelandic', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'id',
			label: __( 'Indonesian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'it',
			label: __( 'Italian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ja',
			label: __( 'Japanese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'kn',
			label: __( 'Kannada', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'kk',
			label: __( 'Kazakh', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'km',
			label: __( 'Khmer', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ko',
			label: __( 'Korean', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ky',
			label: __( 'Kyrgyz', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'lo',
			label: __( 'Lao', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'lv',
			label: __( 'Latvian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'lt',
			label: __( 'Lithuanian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'mk',
			label: __( 'Macedonian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ms',
			label: __( 'Malay', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ml',
			label: __( 'Malayalam', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'mr',
			label: __( 'Marathi', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'mn',
			label: __( 'Mongolian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ne',
			label: __( 'Nepali', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'no',
			label: __( 'Norwegian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'pl',
			label: __( 'Polish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'pt',
			label: __( 'Portuguese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'pa',
			label: __( 'Punjabi', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ro',
			label: __( 'Romanian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ru',
			label: __( 'Russian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sr',
			label: __( 'Serbian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'si',
			label: __( 'Sinhalese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sk',
			label: __( 'Slovak', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sl',
			label: __( 'Slovenian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'es',
			label: __( 'Spanish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sw',
			label: __( 'Swahili', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'sv',
			label: __( 'Swedish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ta',
			label: __( 'Tamil', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'te',
			label: __( 'Telugu', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'th',
			label: __( 'Thai', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'tr',
			label: __( 'Turkish', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'uk',
			label: __( 'Ukrainian', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'ur',
			label: __( 'Urdu', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'uz',
			label: __( 'Uzbek', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'vi',
			label: __( 'Vietnamese', 'ultimate-addons-for-gutenberg' ),
		},
		{
			value: 'zu',
			label: __( 'Zulu', 'ultimate-addons-for-gutenberg' ),
		},
];

/**
 * Element Sub-settings: General settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {

	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;

	const {
		address,
		enableSatelliteView,
		language,
		zoom,
	} = attributes;

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'General', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						address: 'Brainstorm Force',
						enableSatelliteView: false,
						height: '400px',
						language: 'en',
						zoom: 15,
					} );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! address }
					label={ __( 'Address', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { address: 'Brainstorm Force' } ) }
					resetAllFilter={ () => ( {
						address: 'Brainstorm Force',
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<InputControl
							label={ __( 'Address', 'ultimate-addons-for-gutenberg' ) }
							value={ address }
							onChange={ ( value ) => setAttributes( { address: value } ) }
							placeholder={ __( 'Enter location address', 'ultimate-addons-for-gutenberg' ) }
							help={ __( 'Enter the address or location you want to display on the map.', 'ultimate-addons-for-gutenberg' ) }
						/>
					</VStack>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => enableSatelliteView !== false }
					label={ __( 'Satellite View', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { enableSatelliteView: false } ) }
					resetAllFilter={ () => ( {
						enableSatelliteView: false,
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<ToggleControl
							label={ __( 'Enable Satellite View', 'ultimate-addons-for-gutenberg' ) }
							checked={ enableSatelliteView }
							onChange={ ( value ) => setAttributes( { enableSatelliteView: value } ) }
							help={ __( 'Toggle between roadmap and satellite view.', 'ultimate-addons-for-gutenberg' ) }
						/>
					</VStack>
				</ToolsPanelItem>



				<ToolsPanelItem
					hasValue={ () => zoom !== 15 }
					label={ __( 'Zoom Level', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { zoom: 15 } ) }
					resetAllFilter={ () => ( {
						zoom: 15,
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<DebouncedRangeControl
							label={ __( 'Zoom Level', 'ultimate-addons-for-gutenberg' ) }
							value={ zoom }
							onChange={ ( value ) => setAttributes( { zoom: value } ) }
							min={ 1 }
							max={ 20 }
							step={ 1 }
							help={ __( 'Set the zoom level of the map.', 'ultimate-addons-for-gutenberg' ) }
							debounceDelay={ 150 }
						/>
					</VStack>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => language !== 'en' }
					label={ __( 'Language', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { language: 'en' } ) }
					resetAllFilter={ () => ( {
						language: 'en',
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<SelectControl
							label={ __( 'Language', 'ultimate-addons-for-gutenberg' ) }
							value={ language }
							options={ languageOptions }
							onChange={ ( value ) => setAttributes( { language: value } ) }
							help={ __( 'Select the language for map labels and controls.', 'ultimate-addons-for-gutenberg' ) }
						/>
					</VStack>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Dimensions settings for responsive height control.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered dimension settings.
 */
const DimensionSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			height,
		},
	} = props;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( { availableUnits: availableUnits || ['px', '%', 'vh', 'em', 'rem'] } );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! height }
				label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { height: undefined } ) }
				resetAllFilter={ () => ( {
					height: undefined,
				} ) }
				panelId={ clientId }
				isShownByDefault
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Map Height', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ height }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { height: value } ) }
					units={ units }
					help={ __( 'Set the height of the Google Map. Supports responsive controls.', 'ultimate-addons-for-gutenberg' ) }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Export the settings.
 */
export default memo( ( props ) => {
	return (
		<>
			<BlockSettings { ...props } />
			<DimensionSettings { ...props } />
		</>
	);
} );