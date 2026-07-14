/**
 * PaletteSection — combined primary + extended palette editor.
 *
 * Renders the primary brand colour editor (chromatic1) followed by
 * the extended palettes (chromatic2–7, Pro-gated). Replaces the
 * previous separate "Primary" and "Extended palettes" sidebar sections.
 *
 * @since x.x.x
 */

import PrimarySection  from './PrimarySection.jsx';
import ExtendedSection from './ExtendedSection.jsx';
import { __ } from '@wordpress/i18n';

/**
 * PaletteSection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.config
 * @param {Object}   props.computed
 * @param {Function} props.onConfigChange
 * @return {Element}
 */
const PaletteSection = ( { config, computed, onConfigChange } ) => (
	<>
		<PrimarySection config={ config } computed={ computed } onConfigChange={ onConfigChange } />

		<div className="spectra-gbs-palette__extended-heading">
			<hr className="spectra-gbs-section__divider" />
			<label className="spectra-gbs-section__label">
				{ __( 'Extended palettes', 'spectra-blocks' ) }
			</label>
		</div>

		<ExtendedSection config={ config } computed={ computed } onConfigChange={ onConfigChange } />
	</>
);

export default PaletteSection;
