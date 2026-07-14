/**
 * NeutralSection — display and (Pro) editing of the neutral grey scale.
 *
 * The neutral scale is derived by tinting pure black towards a chosen
 * chromatic colour. Free tier: read-only view of neutral-0 through
 * neutral-7 with the current tint source shown. Pro tier: tint-source
 * selector (which chromatic) and a tint-strength slider.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import ShadeStrip from '../controls/ShadeStrip.jsx';

/** @type {boolean} */
const isPro = window.spectra_blocks_info?.spectra_pro_status === 'active';

/**
 * NeutralSection component.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {Object} props.config   Full GBS config.
 * @param {Object} props.computed Computed tokens.
 * @return {Element}
 */
const NeutralSection = ( { config, computed } ) => {
	const tokens = computed?.tokens ?? {};
	const neutralConfig = config?.neutral ?? {};
	const chromatics = config?.chromatics ?? {};
	const tintIndex = neutralConfig.tintIndex ?? null;
	const tintStrength = neutralConfig.tintStrength ?? 0.05;

	const neutralShades = [ 0, 1, 2, 3, 4, 5, 6, 7 ]
		.map( ( i ) => ( { index: i, hex: tokens[ `neutral-${ i }` ] } ) )
		.filter( ( s ) => s.hex );

	const tintSourceName =
		tintIndex && chromatics[ tintIndex ]
			? ( chromatics[ tintIndex ].name ?? `Color ${ tintIndex }` )
			: __( 'None (pure grey)', 'spectra-blocks' );

	return (
		<div className="spectra-gbs-section spectra-gbs-section--neutral">
			{/* Scale preview */}
			{ neutralShades.length > 0 && (
				<div className="spectra-gbs-section__field">
					<label className="spectra-gbs-section__label">
						{ __( 'Neutral scale', 'spectra-blocks' ) }
						<span className="spectra-gbs-section__label-meta">
							{ __( '0 (white) → 7 (near-black)', 'spectra-blocks' ) }
						</span>
					</label>
					<ShadeStrip
						shades={ neutralShades }
						label={ __( 'Neutral shades 0 to 7', 'spectra-blocks' ) }
					/>
				</div>
			) }

			<hr className="spectra-gbs-section__divider" />

			{/* Tint source (read-only in free) */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Tint source', 'spectra-blocks' ) }
				</label>
				<div className="spectra-gbs-neutral__tint-source">
					{ tintIndex && tokens[ `chromatic${ tintIndex }-7` ] && (
						<span
							className="spectra-gbs-neutral__tint-swatch"
							style={ { backgroundColor: tokens[ `chromatic${ tintIndex }-7` ] } }
						/>
					) }
					<span className="spectra-gbs-neutral__tint-name">{ tintSourceName }</span>
					{ ! isPro && (
						<span className="spectra-gbs-pro-badge">
							{ __( 'Pro', 'spectra-blocks' ) }
						</span>
					) }
				</div>
				<p className="spectra-gbs-section__hint">
					{ __( 'Adds a subtle hue to the grey scale so neutrals feel connected to your brand.', 'spectra-blocks' ) }
				</p>
			</div>

			{/* Tint strength (read-only in free) */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Tint strength', 'spectra-blocks' ) }
					{ ! isPro && (
						<span className="spectra-gbs-pro-badge">
							{ __( 'Pro', 'spectra-blocks' ) }
						</span>
					) }
				</label>
				<div className="spectra-gbs-neutral__strength-row">
					<input
						type="range"
						className="spectra-gbs-neutral__strength-slider"
						min="0"
						max="0.2"
						step="0.005"
						value={ tintStrength }
						disabled={ ! isPro }
						readOnly={ ! isPro }
						aria-label={ __( 'Tint strength', 'spectra-blocks' ) }
						onChange={ () => {} }
					/>
					<span className="spectra-gbs-neutral__strength-value">
						{ Math.round( tintStrength * 100 ) }%
					</span>
				</div>
			</div>
		</div>
	);
};

export default NeutralSection;
