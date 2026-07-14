/**
 * External dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';
import { memo, useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalSpacer as Spacer,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import { AccordionItemBlockControls } from '@spectra-blocks/accordion/helper';

/**
 * Get description for HTML element types.
 *
 * @param {string} element The HTML element type.
 * @since x.x.x
 * @return {string} The element description.
 */
const getElementDescription = ( element ) => {
	switch ( element ) {
		case 'button':
			return __( 'provides semantic button functionality with built-in keyboard and screen reader support for accordion interactions.', 'spectra-blocks' );
		case 'div':
			return __( 'creates a generic container element with custom ARIA attributes for accordion functionality.', 'spectra-blocks' );
		default:
			return __( 'provides the structural foundation for the accordion header.', 'spectra-blocks' );
	}
};

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
		clientId,
		setAttributes,
		attributes: {
			headerElement,
		},
	} = props;

	// State for showing the element description notice - always show by default.
	const [ showElementNotice ] = useState( true );

	return (
		<InspectorControls group='settings'>
			<ToolsPanel
				label={ __( 'Header', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						headerElement: 'button',
					} )
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => headerElement && headerElement !== 'button' }
					label={ __( 'HTML Element', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { headerElement: 'button' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'HTML Element', 'spectra-blocks' ) }
						value={ headerElement || 'button' }
						onChange={ ( value ) => setAttributes( { headerElement: value } ) }
						isBlock
					>
						<ToggleGroupControlOption value="button" label={ __( 'Button', 'spectra-blocks' ) } />
						<ToggleGroupControlOption value="div" label={ __( 'Div', 'spectra-blocks' ) } />
					</ToggleGroupControl>

					{/* Notice showing element description */}
					{ showElementNotice && (
						<>
							<Spacer marginY={ 3 } />
							<Notice 
								status="info" 
								isDismissible={ false }
							>
								{ sprintf(
								/* translators: 1: HTML element type (Button or Div), 2: element description */
									__(
										'The %1$s HTML element %2$s',
										'spectra-blocks'
									),
									headerElement === 'button' ? 'Button' : 'Div',
									getElementDescription( headerElement || 'button' )
								) }
							</Notice>
						</>
					) }
				</ToolsPanelItem>
			</ToolsPanel>
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
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
			] }
			panelId={ clientId }
		/>
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
		<AccordionItemBlockControls { ...props } />
		<BlockSettings { ...props } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
