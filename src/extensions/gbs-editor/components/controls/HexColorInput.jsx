/**
 * HexColorInput — a hex text field paired with a native colour picker.
 *
 * Shows a coloured swatch, an editable hex value, and a hidden
 * <input type="color"> that the user opens by clicking the swatch or the
 * picker button. Validates hex on blur; rejects non-hex text.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Normalise a string to a 6-digit lowercase hex, or return null if invalid.
 *
 * @since x.x.x
 *
 * @param {string} value Raw input value.
 * @return {string|null} Normalised hex or null.
 */
function normalizeHex( value ) {
	const stripped = value.replace( /^#/, '' ).toLowerCase();
	// 3-digit shorthand → 6-digit.
	if ( /^[0-9a-f]{3}$/.test( stripped ) ) {
		return '#' + stripped.replace( /(.)/g, '$1$1' );
	}
	if ( /^[0-9a-f]{6}$/.test( stripped ) ) {
		return '#' + stripped;
	}
	return null;
}

/**
 * HexColorInput component.
 *
 * @since x.x.x
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.value      Current hex value (e.g. '#6431f6').
 * @param {Function} props.onChange   Called with a valid 6-digit hex string.
 * @param {string}   [props.label]    Accessible label for the text input.
 * @param {boolean}  [props.disabled] Whether the input is disabled.
 * @return {Element} Colour input element.
 */
const HexColorInput = ( { value, onChange, label, disabled = false } ) => {
	const nativePickerRef = useRef( null );

	const handleTextChange = useCallback(
		( e ) => {
			// Allow free typing; only fire onChange for valid hex.
			const normalized = normalizeHex( e.target.value );
			if ( normalized ) {
				onChange( normalized );
			}
		},
		[ onChange ]
	);

	const handleTextBlur = useCallback(
		( e ) => {
			const normalized = normalizeHex( e.target.value );
			if ( ! normalized ) {
				// Reset to the last valid value on invalid blur.
				e.target.value = value;
			}
		},
		[ value ]
	);

	const handlePickerChange = useCallback(
		( e ) => {
			onChange( e.target.value );
		},
		[ onChange ]
	);

	const openPicker = useCallback( () => {
		nativePickerRef.current?.click();
	}, [] );

	return (
		<div className={ `spectra-gbs-hex-input${ disabled ? ' is-disabled' : '' }` }>
			{/* Swatch — click to open native colour picker. */}
			<button
				type="button"
				className="spectra-gbs-hex-input__swatch"
				style={ { backgroundColor: value } }
				onClick={ openPicker }
				disabled={ disabled }
				aria-label={ __( 'Open colour picker', 'spectra-blocks' ) }
			/>

			{/* Hex text field. */}
			<input
				type="text"
				className="spectra-gbs-hex-input__text"
				defaultValue={ value }
				key={ value }
				onChange={ handleTextChange }
				onBlur={ handleTextBlur }
				maxLength={ 7 }
				spellCheck={ false }
				aria-label={ label ?? __( 'Hex colour value', 'spectra-blocks' ) }
				disabled={ disabled }
			/>

			{/* Hidden native picker — synced to current hex. */}
			<input
				ref={ nativePickerRef }
				type="color"
				className="spectra-gbs-hex-input__native"
				value={ value }
				onChange={ handlePickerChange }
				tabIndex={ -1 }
				aria-hidden="true"
				disabled={ disabled }
			/>

			{/* Visible picker trigger button. */}
			<button
				type="button"
				className="spectra-gbs-hex-input__picker-btn"
				onClick={ openPicker }
				disabled={ disabled }
				aria-label={ __( 'Open colour picker', 'spectra-blocks' ) }
				tabIndex={ -1 }
			>
				<svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
					<circle cx="6" cy="6" r="4.5" stroke="currentColor" strokeWidth="1.2" />
					<circle cx="6" cy="6" r="2" fill="currentColor" />
				</svg>
			</button>
		</div>
	);
};

export default HexColorInput;
