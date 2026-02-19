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
			label: __( 'Afrikaans', 'spectra-blocks' ),
		},
		{
			value: 'sq',
			label: __( 'Albanian', 'spectra-blocks' ),
		},
		{
			value: 'am',
			label: __( 'Amharic', 'spectra-blocks' ),
		},
		{
			value: 'ar',
			label: __( 'Arabic', 'spectra-blocks' ),
		},
		{
			value: 'hy',
			label: __( 'Armenian', 'spectra-blocks' ),
		},
		{
			value: 'az',
			label: __( 'Azerbaijani', 'spectra-blocks' ),
		},
		{
			value: 'eu',
			label: __( 'Basque', 'spectra-blocks' ),
		},
		{
			value: 'be',
			label: __( 'Belarusian', 'spectra-blocks' ),
		},
		{
			value: 'bn',
			label: __( 'Bengali', 'spectra-blocks' ),
		},
		{
			value: 'bs',
			label: __( 'Bosnian', 'spectra-blocks' ),
		},
		{
			value: 'bg',
			label: __( 'Bulgarian', 'spectra-blocks' ),
		},
		{
			value: 'my',
			label: __( 'Burmese', 'spectra-blocks' ),
		},
		{
			value: 'ca',
			label: __( 'Catalan', 'spectra-blocks' ),
		},
		{
			value: 'zh',
			label: __( 'Chinese', 'spectra-blocks' ),
		},
		{
			value: 'hr',
			label: __( 'Croatian', 'spectra-blocks' ),
		},
		{
			value: 'cs',
			label: __( 'Czech', 'spectra-blocks' ),
		},
		{
			value: 'da',
			label: __( 'Danish', 'spectra-blocks' ),
		},
		{
			value: 'nl',
			label: __( 'Dutch', 'spectra-blocks' ),
		},
		{
			value: 'en',
			label: __( 'English', 'spectra-blocks' ),
		},
		{
			value: 'et',
			label: __( 'Estonian', 'spectra-blocks' ),
		},
		{
			value: 'fa',
			label: __( 'Farsi', 'spectra-blocks' ),
		},
		{
			value: 'fi',
			label: __( 'Finnish', 'spectra-blocks' ),
		},
		{
			value: 'fr',
			label: __( 'French', 'spectra-blocks' ),
		},
		{
			value: 'gl',
			label: __( 'Galician', 'spectra-blocks' ),
		},
		{
			value: 'ka',
			label: __( 'Georgian', 'spectra-blocks' ),
		},
		{
			value: 'de',
			label: __( 'German', 'spectra-blocks' ),
		},
		{
			value: 'el',
			label: __( 'Greek', 'spectra-blocks' ),
		},
		{
			value: 'gu',
			label: __( 'Gujarati', 'spectra-blocks' ),
		},
		{
			value: 'iw',
			label: __( 'Hebrew', 'spectra-blocks' ),
		},
		{
			value: 'hi',
			label: __( 'Hindi', 'spectra-blocks' ),
		},
		{
			value: 'hu',
			label: __( 'Hungarian', 'spectra-blocks' ),
		},
		{
			value: 'is',
			label: __( 'Icelandic', 'spectra-blocks' ),
		},
		{
			value: 'id',
			label: __( 'Indonesian', 'spectra-blocks' ),
		},
		{
			value: 'it',
			label: __( 'Italian', 'spectra-blocks' ),
		},
		{
			value: 'ja',
			label: __( 'Japanese', 'spectra-blocks' ),
		},
		{
			value: 'kn',
			label: __( 'Kannada', 'spectra-blocks' ),
		},
		{
			value: 'kk',
			label: __( 'Kazakh', 'spectra-blocks' ),
		},
		{
			value: 'km',
			label: __( 'Khmer', 'spectra-blocks' ),
		},
		{
			value: 'ko',
			label: __( 'Korean', 'spectra-blocks' ),
		},
		{
			value: 'ky',
			label: __( 'Kyrgyz', 'spectra-blocks' ),
		},
		{
			value: 'lo',
			label: __( 'Lao', 'spectra-blocks' ),
		},
		{
			value: 'lv',
			label: __( 'Latvian', 'spectra-blocks' ),
		},
		{
			value: 'lt',
			label: __( 'Lithuanian', 'spectra-blocks' ),
		},
		{
			value: 'mk',
			label: __( 'Macedonian', 'spectra-blocks' ),
		},
		{
			value: 'ms',
			label: __( 'Malay', 'spectra-blocks' ),
		},
		{
			value: 'ml',
			label: __( 'Malayalam', 'spectra-blocks' ),
		},
		{
			value: 'mr',
			label: __( 'Marathi', 'spectra-blocks' ),
		},
		{
			value: 'mn',
			label: __( 'Mongolian', 'spectra-blocks' ),
		},
		{
			value: 'ne',
			label: __( 'Nepali', 'spectra-blocks' ),
		},
		{
			value: 'no',
			label: __( 'Norwegian', 'spectra-blocks' ),
		},
		{
			value: 'pl',
			label: __( 'Polish', 'spectra-blocks' ),
		},
		{
			value: 'pt',
			label: __( 'Portuguese', 'spectra-blocks' ),
		},
		{
			value: 'pa',
			label: __( 'Punjabi', 'spectra-blocks' ),
		},
		{
			value: 'ro',
			label: __( 'Romanian', 'spectra-blocks' ),
		},
		{
			value: 'ru',
			label: __( 'Russian', 'spectra-blocks' ),
		},
		{
			value: 'sr',
			label: __( 'Serbian', 'spectra-blocks' ),
		},
		{
			value: 'si',
			label: __( 'Sinhalese', 'spectra-blocks' ),
		},
		{
			value: 'sk',
			label: __( 'Slovak', 'spectra-blocks' ),
		},
		{
			value: 'sl',
			label: __( 'Slovenian', 'spectra-blocks' ),
		},
		{
			value: 'es',
			label: __( 'Spanish', 'spectra-blocks' ),
		},
		{
			value: 'sw',
			label: __( 'Swahili', 'spectra-blocks' ),
		},
		{
			value: 'sv',
			label: __( 'Swedish', 'spectra-blocks' ),
		},
		{
			value: 'ta',
			label: __( 'Tamil', 'spectra-blocks' ),
		},
		{
			value: 'te',
			label: __( 'Telugu', 'spectra-blocks' ),
		},
		{
			value: 'th',
			label: __( 'Thai', 'spectra-blocks' ),
		},
		{
			value: 'tr',
			label: __( 'Turkish', 'spectra-blocks' ),
		},
		{
			value: 'uk',
			label: __( 'Ukrainian', 'spectra-blocks' ),
		},
		{
			value: 'ur',
			label: __( 'Urdu', 'spectra-blocks' ),
		},
		{
			value: 'uz',
			label: __( 'Uzbek', 'spectra-blocks' ),
		},
		{
			value: 'vi',
			label: __( 'Vietnamese', 'spectra-blocks' ),
		},
		{
			value: 'zu',
			label: __( 'Zulu', 'spectra-blocks' ),
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
				label={ __( 'General', 'spectra-blocks' ) }
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
					label={ __( 'Address', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { address: 'Brainstorm Force' } ) }
					resetAllFilter={ () => ( {
						address: 'Brainstorm Force',
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<InputControl
							label={ __( 'Address', 'spectra-blocks' ) }
							value={ address }
							onChange={ ( value ) => setAttributes( { address: value } ) }
							placeholder={ __( 'Enter location address', 'spectra-blocks' ) }
							help={ __( 'Enter the address or location you want to display on the map.', 'spectra-blocks' ) }
						/>
					</VStack>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => enableSatelliteView !== false }
					label={ __( 'Satellite View', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { enableSatelliteView: false } ) }
					resetAllFilter={ () => ( {
						enableSatelliteView: false,
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<ToggleControl
							label={ __( 'Enable Satellite View', 'spectra-blocks' ) }
							checked={ enableSatelliteView }
							onChange={ ( value ) => setAttributes( { enableSatelliteView: value } ) }
							help={ __( 'Toggle between roadmap and satellite view.', 'spectra-blocks' ) }
						/>
					</VStack>
				</ToolsPanelItem>



				<ToolsPanelItem
					hasValue={ () => zoom !== 15 }
					label={ __( 'Zoom Level', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { zoom: 15 } ) }
					resetAllFilter={ () => ( {
						zoom: 15,
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<DebouncedRangeControl
							label={ __( 'Zoom Level', 'spectra-blocks' ) }
							value={ zoom }
							onChange={ ( value ) => setAttributes( { zoom: value } ) }
							min={ 1 }
							max={ 20 }
							step={ 1 }
							help={ __( 'Set the zoom level of the map.', 'spectra-blocks' ) }
							debounceDelay={ 150 }
						/>
					</VStack>
				</ToolsPanelItem>

				<ToolsPanelItem
					hasValue={ () => language !== 'en' }
					label={ __( 'Language', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { language: 'en' } ) }
					resetAllFilter={ () => ( {
						language: 'en',
					} ) }
					panelId={ clientId }
					isShownByDefault
				>
					<VStack spacing="4">
						<SelectControl
							label={ __( 'Language', 'spectra-blocks' ) }
							value={ language }
							options={ languageOptions }
							onChange={ ( value ) => setAttributes( { language: value } ) }
							help={ __( 'Select the language for map labels and controls.', 'spectra-blocks' ) }
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
				label={ __( 'Height', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { height: undefined } ) }
				resetAllFilter={ () => ( {
					height: undefined,
				} ) }
				panelId={ clientId }
				isShownByDefault
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Map Height', 'spectra-blocks' ) }
					labelPosition="top"
					value={ height }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { height: value } ) }
					units={ units }
					help={ __( 'Set the height of the Google Map. Supports responsive controls.', 'spectra-blocks' ) }
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