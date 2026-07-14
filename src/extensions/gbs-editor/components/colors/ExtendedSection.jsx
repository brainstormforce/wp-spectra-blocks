/**
 * ExtendedSection — editor for chromatic2–7 (secondary/accent palettes).
 *
 * Free tier: locked state with upgrade prompt.
 * Pro tier (spectra_blocks_pro_status === 'active'): renders one
 * colour card per active chromatic slot, identical to PrimarySection
 * but without the "isMain" designation.
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

/** @type {string} */
const proStatus = window.spectra_blocks_info?.spectra_pro_status ?? 'not-installed';
const isPro = proStatus === 'active';

/**
 * Lock icon used in the upgrade prompt.
 *
 * @since x.x.x
 *
 * @return {Element} SVG element.
 */
const LockIcon = () => (
	<svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
		<rect x="5" y="13" width="22" height="16" rx="3" stroke="currentColor" strokeWidth="1.5" />
		<path
			d="M10 13V9.5C10 6.46 12.46 4 15.5 4H16.5C19.54 4 22 6.46 22 9.5V13"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
		<circle cx="16" cy="21" r="2.5" fill="currentColor" opacity="0.5" />
	</svg>
);

/**
 * Upgrade prompt rendered for free-tier users.
 *
 * @since x.x.x
 *
 * @return {Element} Upgrade prompt element.
 */
const UpgradeLock = () => (
	<div className="spectra-gbs-upgrade-lock">
		<LockIcon />
		<p className="spectra-gbs-upgrade-lock__heading">
			{ __( 'Extended Palettes require Spectra Pro', 'spectra-blocks' ) }
		</p>
		<p className="spectra-gbs-upgrade-lock__body">
			{ __( 'Unlock up to 7 independent brand colours. Each generates its own 11-shade scale and colour schemes.', 'spectra-blocks' ) }
		</p>
	</div>
);

/**
 * Single chromatic slot editor (reused for each of chromatic2–7).
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {number}   props.index     Chromatic index (2-7).
 * @param {Object}   props.chromatic The chromatic config object.
 * @param {Object}   props.tokens    Computed tokens.
 * @param {Function} props.onChange  Callback(index, field, value).
 * @return {Element}
 */
const ChromaticCard = ( { index, chromatic, tokens, onChange } ) => {
	const hex = chromatic?.hex ?? '#888888';
	const name = chromatic?.name ?? sprintf( __( 'Color %d', 'spectra-blocks' ), index );

	const lightShades = [ 1, 2, 3, 4, 5, 6, 7 ]
		.map( ( i ) => ( { index: i, hex: tokens[ `chromatic${ index }-${ i }` ] } ) )
		.filter( ( s ) => s.hex );

	return (
		<div className="spectra-gbs-chromatic-card">
			<div className="spectra-gbs-chromatic-card__header">
				<div
					className="spectra-gbs-chromatic-card__swatch"
					style={ { backgroundColor: hex } }
				/>
				<span className="spectra-gbs-chromatic-card__name">{ name }</span>
				<span className="spectra-gbs-chromatic-card__index">{ index }</span>
			</div>

			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Base color', 'spectra-blocks' ) }
				</label>
				<HexColorInput
					value={ hex }
					onChange={ ( v ) => onChange( index, 'hex', v ) }
					label={ sprintf( __( 'Chromatic %d base hex colour', 'spectra-blocks' ), index ) }
				/>
			</div>

			{ lightShades.length > 0 && (
				<div className="spectra-gbs-section__field">
					<ShadeStrip
						shades={ lightShades }
						baseShade={ 7 }
						label={ sprintf( __( 'Chromatic %d shades', 'spectra-blocks' ), index ) }
					/>
				</div>
			) }

			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Color name', 'spectra-blocks' ) }
				</label>
				<TextControl
					value={ name }
					onChange={ ( v ) => onChange( index, 'name', v ) }
					className="spectra-gbs-section__text-input"
					__nextHasNoMarginBottom
				/>
			</div>
		</div>
	);
};

/**
 * ExtendedSection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.config
 * @param {Object}   props.computed
 * @param {Function} props.onConfigChange
 * @return {Element}
 */
const ExtendedSection = ( { config, computed, onConfigChange } ) => {
	const tokens = computed?.tokens ?? {};

	const handleChange = useCallback(
		( index, field, value ) => {
			onConfigChange( {
				chromatics: {
					...( config?.chromatics ?? {} ),
					[ index ]: {
						...( config?.chromatics?.[ index ] ?? {} ),
						[ field ]: value,
					},
				},
			} );
		},
		[ config, onConfigChange ]
	);

	if ( ! isPro ) {
		return (
			<div className="spectra-gbs-section spectra-gbs-section--locked">
				<UpgradeLock />
			</div>
		);
	}

	return (
		<div className="spectra-gbs-section spectra-gbs-section--extended">
			{ [ 2, 3, 4, 5, 6, 7 ].map( ( index ) => (
				<ChromaticCard
					key={ index }
					index={ index }
					chromatic={ config?.chromatics?.[ index ] ?? {} }
					tokens={ tokens }
					onChange={ handleChange }
				/>
			) ) }
		</div>
	);
};

export default ExtendedSection;
