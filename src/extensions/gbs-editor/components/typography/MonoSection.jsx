/**
 * MonoSection — monospace / code typography placeholder.
 *
 * No custom GBS tokens exist for code typography yet. This section
 * documents what the browser default looks like and reserves space
 * for Pro token editing in a future release.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

const MONO_STACK = '"SF Mono", "Fira Code", "Fira Mono", "Roboto Mono", Menlo, Courier, monospace';

/**
 * MonoSection component.
 *
 * @since x.x.x
 *
 * @return {Element}
 */
const MonoSection = () => (
	<div className="spectra-gbs-section spectra-gbs-section--mono">
		<div className="spectra-gbs-section__field">
			<label className="spectra-gbs-section__label">
				{ __( 'Code & monospace', 'spectra-blocks' ) }
			</label>
			<div
				className="spectra-gbs-typography__current-font"
				style={ { fontFamily: MONO_STACK } }
			>
				{ __( 'System monospace', 'spectra-blocks' ) }
			</div>
			<p className="spectra-gbs-section__hint">
				{ __( 'Uses the browser\'s system monospace stack. Custom monospace font selection is coming in a future release.', 'spectra-blocks' ) }
			</p>
		</div>

		<hr className="spectra-gbs-section__divider" />

		{/* Preview */}
		<div className="spectra-gbs-section__field">
			<label className="spectra-gbs-section__label">
				{ __( 'Preview', 'spectra-blocks' ) }
			</label>
			<pre
				className="spectra-gbs-mono__preview"
				style={ { fontFamily: MONO_STACK } }
				aria-label={ __( 'Monospace font preview', 'spectra-blocks' ) }
			>
				{ `const theme = {\n  color: 'var(--spectra-primary)',\n  radius: 'var(--spectra-radius-card)',\n};` }
			</pre>
		</div>
	</div>
);

export default MonoSection;
