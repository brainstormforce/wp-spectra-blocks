/**
 * BodySection — body font, text scale preview, spacing, and link tokens.
 *
 * Free tier: font picker + read-only token display.
 * Pro tier: also edits text scale values, line-height, letter-spacing,
 *           and link colour/decoration.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import FontPicker from './FontPicker.jsx';
import { fontStack } from './fonts.js';
import { useSystemSizes } from '../../hooks/useSystemSizes.js';

const isPro = window.spectra_blocks_info?.spectra_pro_status === 'active';

/** Text scale steps to display — subset of the full token list. */
const TEXT_SCALE = [
	{ token: 'text-xs',  label: 'XS',  sample: 'Body XS' },
	{ token: 'text-sm',  label: 'SM',  sample: 'Body small' },
	{ token: 'text-md',  label: 'MD',  sample: 'Body base' },
	{ token: 'text-lg',  label: 'LG',  sample: 'Body large' },
	{ token: 'text-xl',  label: 'XL',  sample: 'Lead text' },
	{ token: 'text-2xl', label: '2XL', sample: 'Subheading' },
];

/**
 * BodySection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.config
 * @param {Object}   props.computed
 * @param {Function} props.onConfigChange
 * @return {Element}
 */
const ALLOWED_UNITS = [ 'rem', 'em', 'px' ];

const BodySection = ( { config, computed, onConfigChange } ) => {
	const bodySlug = config?.typography?.body?.slug ?? 'inter';
	const bodyName = config?.typography?.body?.name ?? 'Inter';
	const tokens   = computed?.tokens ?? {};
	const font     = fontStack( bodySlug );
	const { sizes, saving, updateSize, resetKey } = useSystemSizes();

	const handleFontChange = useCallback(
		( slug, name ) => {
			onConfigChange( {
				typography: {
					...( config?.typography ?? {} ),
					body: { slug, name },
				},
			} );
		},
		[ config, onConfigChange ]
	);

	return (
		<div className="spectra-gbs-section spectra-gbs-section--body">

			{/* Current body font badge */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Body font', 'spectra-blocks' ) }
				</label>
				<div
					className="spectra-gbs-typography__current-font"
					style={ { fontFamily: font } }
				>
					{ bodyName }
				</div>
			</div>

			{/* Font picker */}
			<div className="spectra-gbs-section__field">
				<FontPicker
					value={ bodySlug }
					onChange={ handleFontChange }
					context="body"
				/>
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Editable text scale */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Text scale', 'spectra-blocks' ) }
					{ saving && <Spinner style={ { width: 14, height: 14 } } /> }
				</label>
				<div className="spectra-gbs-text-scale">
					{ TEXT_SCALE.map( ( { token, label, sample } ) => {
						const sizeData = sizes?.fontsize?.[ token ];
						const val  = sizeData?.value ?? '';
						const unit = sizeData?.unit  ?? 'rem';
						const isChanged = !! sizeData?.changed;
						return (
							<div key={ token } className="spectra-gbs-token-row">
								<span className="spectra-gbs-token-row__tag">{ label }</span>
								<span
									className="spectra-gbs-token-row__sample"
									style={ { fontFamily: font, fontSize: `${ val }${ unit }` } }
								>
									{ sample }
								</span>
								<div className="spectra-gbs-font-size-panel__editor-controls spectra-gbs-token-row__control">
									<input
										type="number"
										className="spectra-gbs-font-size-panel__number-input"
										defaultValue={ val }
										key={ `${ token }-${ val }` }
										min="0.1"
										step="0.125"
										onChange={ ( e ) => {
											const num = parseFloat( e.target.value );
											if ( ! isNaN( num ) && num > 0 ) { updateSize( 'fontsize', token, num, unit ); }
										} }
										onBlur={ ( e ) => {
											const num = parseFloat( e.target.value );
											if ( isNaN( num ) || num <= 0 ) { e.target.value = val; }
										} }
										onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) { e.target.blur(); } } }
										aria-label={ `${ token } size` }
									/>
									<select
										className="spectra-gbs-font-size-panel__unit-select"
										value={ unit }
										onChange={ ( e ) => updateSize( 'fontsize', token, parseFloat( val ), e.target.value ) }
									>
										{ ALLOWED_UNITS.map( ( u ) => <option key={ u } value={ u }>{ u }</option> ) }
									</select>
									<button
										className="spectra-gbs-font-size-panel__reset-btn"
										onClick={ () => resetKey( 'fontsize', token ) }
										disabled={ ! isChanged }
										title={ __( 'Reset', 'spectra-blocks' ) }
									>{ __( 'Reset', 'spectra-blocks' ) }</button>
								</div>
							</div>
						);
					} ) }
				</div>
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Body spacing */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Spacing', 'spectra-blocks' ) }
					{ ! isPro && (
						<span className="spectra-gbs-typography__pro-tag">
							{ __( 'Edit in Pro', 'spectra-blocks' ) }
						</span>
					) }
				</label>
				<div className="spectra-gbs-typography__token-grid">
					<div className="spectra-gbs-typography__token-row">
						<span className="spectra-gbs-typography__token-name">
							{ __( 'Line height', 'spectra-blocks' ) }
						</span>
						<code className="spectra-gbs-typography__token-value">
							{ tokens[ 'body-line-height' ] || '1.65' }
						</code>
					</div>
					<div className="spectra-gbs-typography__token-row">
						<span className="spectra-gbs-typography__token-name">
							{ __( 'Letter spacing', 'spectra-blocks' ) }
						</span>
						<code className="spectra-gbs-typography__token-value">
							{ tokens[ 'body-letter-spacing' ] || '0em' }
						</code>
					</div>
				</div>
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Link tokens */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Links', 'spectra-blocks' ) }
				</label>
				<div className="spectra-gbs-typography__token-grid">
					{ [
						{ name: __( 'Color', 'spectra-blocks' ),          token: 'link-color' },
						{ name: __( 'Decoration', 'spectra-blocks' ),     token: 'link-decoration' },
						{ name: __( 'Hover color', 'spectra-blocks' ),    token: 'link-hover-color' },
						{ name: __( 'Hover decoration', 'spectra-blocks' ), token: 'link-hover-decoration' },
					].map( ( { name, token } ) => (
						<div key={ token } className="spectra-gbs-typography__token-row">
							<span className="spectra-gbs-typography__token-name">{ name }</span>
							<code className="spectra-gbs-typography__token-value">
								{ tokens[ token ] || '—' }
							</code>
						</div>
					) ) }
				</div>
			</div>
		</div>
	);
};

export default BodySection;
