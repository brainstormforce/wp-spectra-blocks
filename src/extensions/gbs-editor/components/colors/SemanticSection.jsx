/**
 * SemanticSection — read-only map of semantic slugs to computed hex values.
 *
 * Shows every entry in `config.semantic_map` (e.g. "primary" → "chromatic1-7")
 * alongside the resolved colour swatch and the actual hex value from the
 * computed token registry. Slug-to-shade remapping is a Pro feature and is
 * not exposed here in the free tier.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Friendly display names for the semantic slugs.
 *
 * @since x.x.x
 */
const SLUG_LABELS = {
	'primary':       __( 'Primary', 'spectra-blocks' ),
	'secondary':     __( 'Secondary', 'spectra-blocks' ),
	'tertiary':      __( 'Tertiary', 'spectra-blocks' ),
	'quaternary':    __( 'Quaternary', 'spectra-blocks' ),
	'heading':       __( 'Heading text', 'spectra-blocks' ),
	'body':          __( 'Body text', 'spectra-blocks' ),
	'background':    __( 'Background', 'spectra-blocks' ),
	'foreground':    __( 'Foreground', 'spectra-blocks' ),
	'surface':       __( 'Surface', 'spectra-blocks' ),
	'outline':       __( 'Outline / border', 'spectra-blocks' ),
	'neutral':       __( 'Neutral', 'spectra-blocks' ),
	'sg-accent':      __( 'SG accent', 'spectra-blocks' ),
	'sg-secondary':   __( 'SG secondary', 'spectra-blocks' ),
	'sg-heading':     __( 'SG heading', 'spectra-blocks' ),
	'sg-body':        __( 'SG body', 'spectra-blocks' ),
	'sg-surface':     __( 'SG surface', 'spectra-blocks' ),
	'sg-background':  __( 'SG background', 'spectra-blocks' ),
	'sg-border':      __( 'SG border', 'spectra-blocks' ),
	'sg-neutral':     __( 'SG neutral', 'spectra-blocks' ),
	'sg-muted':       __( 'SG muted', 'spectra-blocks' ),
};

/**
 * SemanticSection component.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {Object} props.config   Full GBS config (contains semantic_map).
 * @param {Object} props.computed Computed tokens.
 * @return {Element}
 */
const SemanticSection = ( { config, computed } ) => {
	const semanticMap = config?.semantic_map ?? {};
	const tokens = computed?.tokens ?? {};

	const rows = Object.entries( semanticMap ).map( ( [ slug, shadeRef ] ) => {
		const hex = tokens[ shadeRef ] ?? null;
		const label = SLUG_LABELS[ slug ] ?? slug;
		return { slug, shadeRef, hex, label };
	} );

	// Group: core (no prefix) vs sg-* compat slugs.
	const core = rows.filter( ( r ) => ! r.slug.startsWith( 'sg-' ) );
	const sg = rows.filter( ( r ) => r.slug.startsWith( 'sg-' ) );

	const renderRow = ( { slug, shadeRef, hex, label } ) => (
		<tr key={ slug } className="spectra-gbs-semantic__row">
			<td className="spectra-gbs-semantic__col-swatch">
				{ hex ? (
					<span
						className="spectra-gbs-semantic__swatch"
						style={ { backgroundColor: hex } }
						title={ hex }
					/>
				) : (
					<span className="spectra-gbs-semantic__swatch spectra-gbs-semantic__swatch--missing" />
				) }
			</td>
			<td className="spectra-gbs-semantic__col-label">
				<span className="spectra-gbs-semantic__label">{ label }</span>
				<span className="spectra-gbs-semantic__slug">--spectra-{ slug }</span>
			</td>
			<td className="spectra-gbs-semantic__col-ref">
				<code className="spectra-gbs-semantic__shade-ref">{ shadeRef }</code>
			</td>
			<td className="spectra-gbs-semantic__col-hex">
				{ hex ? (
					<code className="spectra-gbs-semantic__hex">{ hex }</code>
				) : (
					<span className="spectra-gbs-semantic__hex spectra-gbs-semantic__hex--missing">—</span>
				) }
			</td>
		</tr>
	);

	return (
		<div className="spectra-gbs-section spectra-gbs-section--semantic">
			<p className="spectra-gbs-section__intro">
				{ __( 'These slugs map semantic role names to specific shade tokens. Gutenberg blocks, theme.json palette entries, and CSS variables all use these aliases.', 'spectra-blocks' ) }
			</p>

			{/* Core slugs */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Core semantic roles', 'spectra-blocks' ) }
				</label>
				<table className="spectra-gbs-semantic__table" aria-label={ __( 'Core semantic colour map', 'spectra-blocks' ) }>
					<thead className="spectra-gbs-semantic__thead">
						<tr>
							<th>{ __( '', 'spectra-blocks' ) }</th>
							<th>{ __( 'Role', 'spectra-blocks' ) }</th>
							<th>{ __( 'Shade ref', 'spectra-blocks' ) }</th>
							<th>{ __( 'Hex', 'spectra-blocks' ) }</th>
						</tr>
					</thead>
					<tbody>{ core.map( renderRow ) }</tbody>
				</table>
			</div>

			{/* sg-* compat slugs */}
			{ sg.length > 0 && (
				<div className="spectra-gbs-section__field">
					<label className="spectra-gbs-section__label">
						{ __( 'Astra compatibility slugs', 'spectra-blocks' ) }
						<span className="spectra-gbs-section__label-meta">
							{ __( 'sg-*', 'spectra-blocks' ) }
						</span>
					</label>
					<table className="spectra-gbs-semantic__table" aria-label={ __( 'sg-* semantic colour map', 'spectra-blocks' ) }>
						<thead className="spectra-gbs-semantic__thead">
							<tr>
								<th />
								<th>{ __( 'Role', 'spectra-blocks' ) }</th>
								<th>{ __( 'Shade ref', 'spectra-blocks' ) }</th>
								<th>{ __( 'Hex', 'spectra-blocks' ) }</th>
							</tr>
						</thead>
						<tbody>{ sg.map( renderRow ) }</tbody>
					</table>
				</div>
			) }
		</div>
	);
};

export default SemanticSection;
