/**
 * SpacingSection — editable spacing scale for the Presets → Spacing section.
 *
 * Combines the density chip picker (compact/default/spacious) with individual
 * value overrides for each spacing step. The density preset sets the baseline;
 * overrides apply on top and are shown with a reset button.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { useSystemSizes } from '../../hooks/useSystemSizes.js';

const SPACING_KEYS = [
	{ key: 'space-xs',  label: 'XS',  description: __( 'Extra small — icons, tight gaps', 'spectra-blocks' ) },
	{ key: 'space-sm',  label: 'SM',  description: __( 'Small — component inner padding', 'spectra-blocks' ) },
	{ key: 'space-md',  label: 'MD',  description: __( 'Medium — section inner padding', 'spectra-blocks' ) },
	{ key: 'space-lg',  label: 'LG',  description: __( 'Large — between components', 'spectra-blocks' ) },
	{ key: 'space-xl',  label: 'XL',  description: __( 'Extra large — section margins', 'spectra-blocks' ) },
	{ key: 'space-2xl', label: '2XL', description: __( '2× extra large — hero / full-bleed', 'spectra-blocks' ) },
];

const UNITS = [ 'rem', 'em', 'px' ];

/**
 * SpacingSection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.config         Full GBS config (for density chip).
 * @param {Object}   props.computed       Computed tokens.
 * @param {Function} props.onConfigChange Partial config callback for density preset.
 * @return {Element}
 */
const SpacingSection = ( { config, computed, onConfigChange } ) => {
	const { sizes, loading, saving, updateSize, resetKey } = useSystemSizes();
	const density  = config?.presets?.spacingDensity ?? 'default';
	const tokens   = computed?.tokens ?? {};

	const DENSITY_CHIPS = [
		{ value: 'compact',  label: __( 'Compact', 'spectra-blocks' ),  description: '0.75×' },
		{ value: 'default',  label: __( 'Default', 'spectra-blocks' ),  description: '1×' },
		{ value: 'spacious', label: __( 'Spacious', 'spectra-blocks' ), description: '1.5×' },
	];

	return (
		<div className="spectra-gbs-section spectra-gbs-section--spacing">

			{/* Density multiplier chips */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Density Multiplier', 'spectra-blocks' ) }
				</label>
				<p className="spectra-gbs-section__hint">
					{ __( 'Scales all spacing steps by a global factor. Individual overrides below apply on top.', 'spectra-blocks' ) }
				</p>
				<div className="spectra-gbs-preset-chips">
					{ DENSITY_CHIPS.map( ( chip ) => (
						<button
							key={ chip.value }
							type="button"
							className={ `spectra-gbs-preset-chip${ density === chip.value ? ' is-active' : '' }` }
							onClick={ () => onConfigChange( { presets: { ...( config?.presets ?? {} ), spacingDensity: chip.value } } ) }
						>
							<span className="spectra-gbs-preset-chip__label">{ chip.label }</span>
							<span className="spectra-gbs-preset-chip__desc">{ chip.description }</span>
						</button>
					) ) }
				</div>
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Individual spacing overrides */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Step Overrides', 'spectra-blocks' ) }
					{ saving && <Spinner style={ { width: 14, height: 14 } } /> }
				</label>
				<p className="spectra-gbs-section__hint">
					{ __( 'Set absolute values for individual steps, overriding the density multiplier. Click ↺ to restore.', 'spectra-blocks' ) }
				</p>

				{ loading ? (
					<div style={ { display: 'flex', alignItems: 'center', gap: 8, padding: '8px 0', fontSize: 12, color: '#757575' } }>
						<Spinner /> { __( 'Loading spacing…', 'spectra-blocks' ) }
					</div>
				) : (
					<div className="spectra-gbs-spacing-table">
						{ SPACING_KEYS.map( ( { key, label, description } ) => {
							const sizeData  = sizes?.spacing?.[ key ];
							const val       = sizeData?.value ?? '';
							const unit      = sizeData?.unit  ?? 'rem';
							const computedValue = tokens[ key ] ?? '—';
							const isChanged = !! sizeData?.changed;

							return (
								<div key={ key } className="spectra-gbs-token-row">
									<span className="spectra-gbs-token-row__tag">
										{ label }
										<span className="spectra-gbs-token-row__desc">{ description }</span>
									</span>
									<div
										className="spectra-gbs-token-row__sample"
										title={ `--spectra-${ key }: ${ computedValue }` }
									>
										<div
											className={ `spectra-gbs-token-row__bar${ isChanged ? ' is-changed' : '' }` }
											style={ { width: `clamp(4px, calc(${ parseFloat( computedValue ) || 0 } * 18px), 120px)` } }
										/>
										<code className="spectra-gbs-token-row__computed">{ computedValue }</code>
									</div>
									<div className="spectra-gbs-size-input spectra-gbs-size-input--sm spectra-gbs-token-row__control">
										<input
											type="number"
											className="spectra-gbs-size-input__number"
											defaultValue={ isChanged ? val : '' }
											key={ `${ key }-${ val }-${ isChanged }` }
											min="0"
											step="0.25"
											placeholder={ __( 'Auto', 'spectra-blocks' ) }
											onBlur={ ( e ) => {
												const num = parseFloat( e.target.value );
												if ( ! isNaN( num ) && num >= 0 ) {updateSize( 'spacing', key, num, unit );}
												else if ( e.target.value === '' && isChanged ) {resetKey( 'spacing', key );}
											} }
											onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) {e.target.blur();} } }
											aria-label={ sprintf( __( '%s spacing value', 'spectra-blocks' ), label ) }
										/>
										<select
											className="spectra-gbs-size-input__unit"
											value={ isChanged ? unit : 'rem' }
											onChange={ ( e ) => { if ( isChanged ) {updateSize( 'spacing', key, parseFloat( val ), e.target.value );} } }
										>
											{ UNITS.map( ( u ) => <option key={ u } value={ u }>{ u }</option> ) }
										</select>
										{ isChanged && (
											<button className="spectra-gbs-size-input__reset" onClick={ () => resetKey( 'spacing', key ) } title={ __( 'Reset', 'spectra-blocks' ) }>↺</button>
										) }
									</div>
								</div>
							);
						} ) }
					</div>
				) }
			</div>
		</div>
	);
};

export default SpacingSection;
