/**
 * External dependencies.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { MIN_Z_INDEX, MAX_Z_INDEX, DEFAULT_Z_INDEX } from './utils/constants';
import { isAllowedBlock } from './utils/helpers';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
/**
 * Z-Index Options Panel.
 *
 * Provides UI controls for managing block z-index, allowing users to set
 * the stacking order of elements.
 *
 * @since 3.0.0
 *
 * @param {Object} props - Component props.
 * @return {Element} Z-index options panel component.
 */
const ZIndexOptions = ( props ) => {
	const {
		setAttributes,
		attributes: {
			spectraZIndex,
		},
	} = props;

	return (
		<>
			{ /* This ToolsPanelItem will reset when the following condition is met:
				- The "spectraZIndex" attribute is set to a non-default value.
			*/ }
			<ToolsPanelItem
				label={ __( 'Z-Index', 'spectra-blocks' ) }
				isShownByDefault
				hasValue={ () => spectraZIndex !== DEFAULT_Z_INDEX }
				onDeselect={ () => setAttributes( { spectraZIndex: DEFAULT_Z_INDEX } ) }
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={ __( 'Z-Index', 'spectra-blocks' ) }
					onChange={ ( value ) => {
						setAttributes( { spectraZIndex: value === '' ? null : value } );
					} }
					value={ spectraZIndex !== null && spectraZIndex !== undefined ? spectraZIndex : '' }
					min={ MIN_Z_INDEX }
					max={ MAX_Z_INDEX }
					step={ 1 }
					withInputField={ true }
					help={ __(
						'Above setting will only take effect once you are on the live page, and not while you\'re editing.',
						'spectra-blocks'
					) }
					debounceDelay={150}
				/>
			</ToolsPanelItem>
		</>
	);
};

/**
 * Higher-order component to add z-index controls to block settings.
 *
 * @since 3.0.0
 *
 * @param {Function} BlockEdit - Original block edit component.
 * @return {Function} Enhanced block edit component with z-index controls.
 */
const addZIndexControls = ( BlockEdit ) => ( props ) => {
	if ( ! isAllowedBlock( { name: props.name } ) ) {
		return <BlockEdit { ...props } />;
	}

	const { setAttributes, isSelected } = props;

	const resetAll = () => {
		setAttributes( {
			spectraZIndex: DEFAULT_Z_INDEX,
		} );
	};

	return (
		<>
			<BlockEdit { ...props } />
			{ isSelected && (
				<InspectorControls>
				{ /* ToolsPanel for z-index settings.
					- Provides controls to customize z-index value.
					- Default value: null
					- Includes a "Reset All" option to restore default z-index value.
				*/ }
				<ToolsPanel label={ __( 'Z-Index', 'spectra-blocks' ) } resetAll={ resetAll }>
					<ZIndexOptions { ...props } />
					</ToolsPanel>
				</InspectorControls>
			) }
		</>
	);
};

export default addZIndexControls;