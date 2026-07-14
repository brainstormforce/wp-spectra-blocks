/**
 * SchemesSection — grid of computed colour scheme swatches.
 *
 * Each scheme object from the compute endpoint describes one possible
 * block background: background colour, text colour, accent, and whether
 * it is classified as a "dark" scheme. The grid gives designers a quick
 * visual overview of every available scheme before applying them to
 * blocks via the `data-spectra-scheme` attribute.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Resolve a `spectra-*` token reference to a hex string.
 *
 * The scheme object stores CSS variable references like "spectra-chromatic1-7".
 * We strip the "spectra-" prefix to look up in the tokens map.
 *
 * @since x.x.x
 *
 * @param {string} ref    Token reference string (e.g. "spectra-chromatic1-7").
 * @param {Object} tokens Flat token map.
 * @return {string} Hex colour or fallback '#888888'.
 */
function resolveRef( ref, tokens ) {
	const key = ref?.replace( /^spectra-/, '' );
	return ( key && tokens[ key ] ) ? tokens[ key ] : '#888888';
}

/**
 * Single scheme card.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {Object} props.scheme Scheme object from compute endpoint.
 * @param {Object} props.tokens Computed tokens map.
 * @return {Element}
 */
const SchemeCard = ( { scheme, tokens } ) => {
	const bg   = resolveRef( scheme.background, tokens );
	const text = resolveRef( scheme.text, tokens );
	const key  = scheme.background?.replace( /^spectra-/, '' ) ?? '';

	return (
		<div
			className={ `spectra-gbs-scheme-card${ scheme.isDark ? ' is-dark-scheme' : '' }` }
			style={ { backgroundColor: bg } }
			title={ key }
		>
			<span
				className="spectra-gbs-scheme-card__sample-text"
				style={ { color: text } }
			>
				Aa
			</span>
			<div className="spectra-gbs-scheme-card__meta">
				<code
					className="spectra-gbs-scheme-card__key"
					style={ { color: text, opacity: 0.75 } }
				>
					{ key }
				</code>
				{ scheme.isDark && (
					<span
						className="spectra-gbs-scheme-card__dark-badge"
						style={ { color: text, borderColor: text, opacity: 0.6 } }
					>
						{ __( 'dark', 'spectra-blocks' ) }
					</span>
				) }
			</div>
		</div>
	);
};

/**
 * SchemesSection component.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {Object} props.computed Computed tokens and schemes.
 * @return {Element}
 */
const SchemesSection = ( { computed } ) => {
	const schemes = computed?.schemes ?? [];
	const tokens  = computed?.tokens ?? {};

	if ( ! schemes.length ) {
		return (
			<div className="spectra-gbs-section spectra-gbs-section--schemes">
				<p className="spectra-gbs-section__empty">
					{ __( 'No schemes computed yet. Save a config to generate colour schemes.', 'spectra-blocks' ) }
				</p>
			</div>
		);
	}

	// Partition into palette groups for display.
	const chromatic = schemes.filter( ( s ) => s.palette === 'chromatic' );
	const neutral   = schemes.filter( ( s ) => s.palette === 'neutral' );

	return (
		<div className="spectra-gbs-section spectra-gbs-section--schemes">
			<p className="spectra-gbs-section__intro">
				{ __( 'Apply a scheme to any container block using the "Color Scheme" control. The scheme sets background, text, accent, and border colours automatically via CSS variables.', 'spectra-blocks' ) }
			</p>

			{ chromatic.length > 0 && (
				<div className="spectra-gbs-section__field">
					<label className="spectra-gbs-section__label">
						{ __( 'Chromatic schemes', 'spectra-blocks' ) }
						<span className="spectra-gbs-section__label-meta">
							{ chromatic.length }
						</span>
					</label>
					<div className="spectra-gbs-scheme-grid">
						{ chromatic.map( ( s ) => (
							<SchemeCard
								key={ s.background }
								scheme={ s }
								tokens={ tokens }
							/>
						) ) }
					</div>
				</div>
			) }

			{ neutral.length > 0 && (
				<div className="spectra-gbs-section__field">
					<label className="spectra-gbs-section__label">
						{ __( 'Neutral schemes', 'spectra-blocks' ) }
						<span className="spectra-gbs-section__label-meta">
							{ neutral.length }
						</span>
					</label>
					<div className="spectra-gbs-scheme-grid">
						{ neutral.map( ( s ) => (
							<SchemeCard
								key={ s.background }
								scheme={ s }
								tokens={ tokens }
							/>
						) ) }
					</div>
				</div>
			) }
		</div>
	);
};

export default SchemesSection;
