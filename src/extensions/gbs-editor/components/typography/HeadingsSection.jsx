/**
 * HeadingsSection — heading font, size scale (editable), and spacing settings.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import FontPicker from './FontPicker.jsx';
import { useSystemSizes } from '../../hooks/useSystemSizes.js';
import { fontStack } from './fonts.js';

const ALLOWED_UNITS = [ 'rem', 'em', 'px' ];

/**
 * Editable heading size row.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.level    'heading-1' … 'heading-6'
 * @param {Object}   props.sizeData { value, unit, default, changed }
 * @param {string}   props.font     CSS font-family stack.
 * @param {Function} props.onUpdate (value, unit) → void
 * @param {Function} props.onReset  () → void
 * @return {Element}
 */
const HeadingRow = ( { level, sizeData, font, onUpdate, onReset } ) => {
	const tag   = level.replace( 'heading-', 'h' );
	const label = tag.toUpperCase();
	const val   = sizeData?.value ?? '';
	const unit  = sizeData?.unit  ?? 'rem';
	const isChanged = !! sizeData?.changed;

	const [ localVal, setLocalVal ] = useState( String( val ) );

	const handleBlur = () => {
		const num = parseFloat( localVal );
		if ( ! isNaN( num ) && num > 0 ) {
			onUpdate( num, unit );
		} else {
			setLocalVal( String( val ) );
		}
	};

	return (
		<div className="spectra-gbs-token-row" role="row">
			<span className="spectra-gbs-token-row__tag" aria-label={ tag }>{ label }</span>
			<span
				className="spectra-gbs-token-row__sample"
				style={ { fontFamily: font, fontSize: `${ val }${ unit }`, lineHeight: 1.2, fontWeight: 700 } }
			>
				The quick brown fox
			</span>
			<div className="spectra-gbs-font-size-panel__editor-controls spectra-gbs-token-row__control">
				<input
					type="number"
					className="spectra-gbs-font-size-panel__number-input"
					value={ localVal }
					min="0.1"
					step="0.125"
					onChange={ ( e ) => {
						const newVal = e.target.value;
						setLocalVal( newVal );
						const num = parseFloat( newVal );
						if ( ! isNaN( num ) && num > 0 ) {
							onUpdate( num, unit );
						}
					} }
					onBlur={ handleBlur }
					onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) { e.target.blur(); } } }
					aria-label={ `${ tag } font size value` }
				/>
				<select
					className="spectra-gbs-font-size-panel__unit-select"
					value={ unit }
					onChange={ ( e ) => onUpdate( parseFloat( localVal ) || val, e.target.value ) }
					aria-label={ `${ tag } font size unit` }
				>
					{ ALLOWED_UNITS.map( ( u ) => <option key={ u } value={ u }>{ u }</option> ) }
				</select>
				<button
					className="spectra-gbs-font-size-panel__reset-btn"
					onClick={ onReset }
					disabled={ ! isChanged }
					title={ __( 'Reset to default', 'spectra-blocks' ) }
					aria-label={ __( 'Reset to default', 'spectra-blocks' ) }
				>{ __( 'Reset', 'spectra-blocks' ) }</button>
			</div>
		</div>
	);
};

/**
 * HeadingsSection component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.config
 * @param {Object}   props.computed
 * @param {Function} props.onConfigChange
 * @return {Element}
 */
const HeadingsSection = ( { config, computed, onConfigChange } ) => {
	const headingSlug = config?.typography?.heading?.slug ?? 'inter';
	const headingName = config?.typography?.heading?.name ?? 'Inter';
	const tokens      = computed?.tokens ?? {};
	const font        = fontStack( headingSlug );

	const { sizes, loading: sizesLoading, saving, updateSize, resetKey } = useSystemSizes();

	const headingLevels = [ 'heading-1', 'heading-2', 'heading-3', 'heading-4', 'heading-5', 'heading-6' ];

	const handleFontChange = useCallback(
		( slug, name ) => {
			onConfigChange( {
				typography: { ...( config?.typography ?? {} ), heading: { slug, name } },
			} );
		},
		[ config, onConfigChange ]
	);

	return (
		<div className="spectra-gbs-section spectra-gbs-section--headings">

			{/* Current heading font badge */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Heading font', 'spectra-blocks' ) }
				</label>
				<div className="spectra-gbs-typography__current-font" style={ { fontFamily: font } }>
					{ headingName }
				</div>
			</div>

			<div className="spectra-gbs-section__field">
				<FontPicker value={ headingSlug } onChange={ handleFontChange } context="heading" />
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Editable heading scale */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">
					{ __( 'Size scale', 'spectra-blocks' ) }
					{ saving && <Spinner style={ { width: 14, height: 14 } } /> }
				</label>
				{ sizesLoading ? (
					<div className="spectra-gbs-panel__loading" style={ { padding: '12px 0' } }>
						<Spinner />
					</div>
				) : (
					<div className="spectra-gbs-heading-scale" role="table" aria-label={ __( 'Heading size scale', 'spectra-blocks' ) }>
						{ headingLevels.map( ( level ) => (
							<HeadingRow
								key={ level }
								level={ level }
								sizeData={ sizes?.fontsize?.[ level ] }
								font={ font }
								onUpdate={ ( value, unit ) => updateSize( 'fontsize', level, value, unit ) }
								onReset={ () => resetKey( 'fontsize', level ) }
							/>
						) ) }
					</div>
				) }
				<p className="spectra-gbs-section__hint">
					{ __( 'Changes apply immediately. Click ↺ to restore the default for any level.', 'spectra-blocks' ) }
				</p>
			</div>

			<hr className="spectra-gbs-section__divider" />

			{/* Line-height / letter-spacing (read-only) */}
			<div className="spectra-gbs-section__field">
				<label className="spectra-gbs-section__label">{ __( 'Spacing', 'spectra-blocks' ) }</label>
				<div className="spectra-gbs-typography__token-grid">
					{ [ [ __( 'Line height', 'spectra-blocks' ), tokens[ 'heading-line-height' ] || '1.2' ],
					    [ __( 'Letter spacing', 'spectra-blocks' ), tokens[ 'heading-letter-spacing' ] || '-0.02em' ] ]
						.map( ( [ name, val ] ) => (
							<div key={ name } className="spectra-gbs-typography__token-row">
								<span className="spectra-gbs-typography__token-name">{ name }</span>
								<code className="spectra-gbs-typography__token-value">{ val }</code>
							</div>
						) )
					}
				</div>
			</div>
		</div>
	);
};

export default HeadingsSection;
