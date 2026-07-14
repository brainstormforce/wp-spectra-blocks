/**
 * PrimarySection — editor for the main (chromatic1) brand colour.
 *
 * Shows the base hex picker, colour name input, the 7 light shades,
 * and the 4 dark shades (8-11). Changes are previewed immediately via
 * the debounced preview endpoint and persisted on Save.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import HexColorInput from '../controls/HexColorInput.jsx';
import ShadeStrip from '../controls/ShadeStrip.jsx';

/**
 * Extract an ordered shade array from the computed tokens object.
 *
 * @since x.x.x
 *
 * @param {Object} tokens    Flat token map from the compute endpoint.
 * @param {number} chromatic Chromatic index (1-7).
 * @param {Array}  indices   Shade indices to extract.
 * @return {Array<{index: number, hex: string}>} Ordered shade objects.
 */
function extractShades( tokens, chromatic, indices ) {
	return indices
		.map( ( i ) => ( { index: i, hex: tokens[ `chromatic${ chromatic }-${ i }` ] } ) )
		.filter( ( s ) => s.hex );
}

/**
 * Relative luminance of an sRGB hex colour, per WCAG 2.1.
 *
 * @since x.x.x
 *
 * @param {string} hex 6-digit hex (with #).
 * @return {number} Relative luminance 0–1.
 */
function relativeLuminance( hex ) {
	const channel = ( v ) => {
		const c = v / 255;
		return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	};
	const r = channel( parseInt( hex.slice( 1, 3 ), 16 ) );
	const g = channel( parseInt( hex.slice( 3, 5 ), 16 ) );
	const b = channel( parseInt( hex.slice( 5, 7 ), 16 ) );
	return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * WCAG contrast ratio of a hex colour against white.
 *
 * @since x.x.x
 *
 * @param {string} hex 6-digit hex (with #).
 * @return {number} Contrast ratio (1–21), or 0 for malformed input.
 */
function contrastRatioVsWhite( hex ) {
	if ( ! /^#[0-9a-f]{6}$/i.test( hex ) ) {
		return 0;
	}
	const lum = relativeLuminance( hex );
	return ( 1.0 + 0.05 ) / ( lum + 0.05 );
}

/**
 * PrimarySection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.config         Full GBS config from the REST endpoint.
 * @param {Object}   props.computed       Computed tokens/schemes from the REST endpoint.
 * @param {Function} props.onConfigChange Callback(partialConfig) for live preview.
 * @return {Element} Primary section element.
 */
const PrimarySection = ( { config, computed, onConfigChange } ) => {
	const chromatic = config?.chromatics?.[ 1 ] ?? {};
	const hex = chromatic.hex ?? '#6431f6';
	const name = chromatic.name ?? 'Primary';
	const tokens = computed?.tokens ?? {};

	// Single continuous scale 1–11 (light → base → deep dark).
	const allShades = extractShades( tokens, 1, [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ] );

	// WCAG contrast of the base colour against white — warn if it can't
	// carry body text. AA requires 4.5:1 for normal text.
	const textRatio = contrastRatioVsWhite( hex );
	const failsTextContrast = textRatio > 0 && textRatio < 4.5;

	const handleHexChange = useCallback(
		( newHex ) => {
			onConfigChange( {
				chromatics: {
					...( config?.chromatics ?? {} ),
					1: { ...chromatic, hex: newHex },
				},
			} );
		},
		[ config, chromatic, onConfigChange ]
	);

	const handleNameChange = useCallback(
		( newName ) => {
			onConfigChange( {
				chromatics: {
					...( config?.chromatics ?? {} ),
					1: { ...chromatic, name: newName },
				},
			} );
		},
		[ config, chromatic, onConfigChange ]
	);

	return (
		<div className="spectra-gbs-section spectra-gbs-section--primary">
			{/* Base colour picker */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Base color', 'spectra-blocks' ) }
					<span className="spectra-gbs-section__label-meta">
						{ __( 'shade-7 anchor', 'spectra-blocks' ) }
					</span>
				</label>
				<HexColorInput
					value={ hex }
					onChange={ handleHexChange }
					label={ __( 'Primary base hex colour', 'spectra-blocks' ) }
				/>
				{ failsTextContrast && (
					<p className="spectra-gbs-section__warning" role="status">
						{ sprintf(
							/* translators: %s: contrast ratio, e.g. 2.8 */
							__( 'Too light for body text (%s:1 on white) — safe as a fill or accent.', 'spectra-blocks' ),
							textRatio.toFixed( 1 )
						) }
					</p>
				) }
				<p className="spectra-gbs-section__hint">
					{ __( 'All other shades are computed from this value using OKLCH.', 'spectra-blocks' ) }
				</p>
			</div>

			{/* Full shade scale 1–11 */}
			{ allShades.length > 0 && (
				<div className="spectra-gbs-section__field">
					<label className="spectra-gbs-section__label">
						{ __( 'Shade scale', 'spectra-blocks' ) }
						<span className="spectra-gbs-section__label-meta">
							{ __( 'auto-generated (OKLCH)', 'spectra-blocks' ) }
						</span>
					</label>
					<ShadeStrip
						shades={ allShades }
						baseShade={ 7 }
						label={ __( 'Primary colour shades 1 to 11', 'spectra-blocks' ) }
					/>
					<p className="spectra-gbs-section__hint">
						{ __( '1 lightest → 7 base · 8–11 deep dark', 'spectra-blocks' ) }
					</p>
				</div>
			) }

			{/* Divider */}
			<hr className="spectra-gbs-section__divider" />

			{/* Colour name */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Color name', 'spectra-blocks' ) }
				</label>
				<TextControl
					value={ name }
					onChange={ handleNameChange }
					className="spectra-gbs-section__text-input"
					aria-label={ __( 'Primary colour name', 'spectra-blocks' ) }
					__nextHasNoMarginBottom
				/>
				<p className="spectra-gbs-section__hint">
					{ __( 'Used in the WordPress colour palette and as the CSS variable label.', 'spectra-blocks' ) }
				</p>
			</div>
		</div>
	);
};

export default PrimarySection;
