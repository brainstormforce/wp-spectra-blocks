/**
 * SpacingPanel — spacing token editor for the GBS editor modal.
 *
 * Matches the FontSizePanel interaction pattern: scale pills select the
 * active token, a single size input + unit selector + Reset button edit
 * it, and a proportional preview strip shows all six steps at once.
 *
 * Changes are draft-only until the footer Save button is clicked.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { useSystemSizes } from '../../hooks/useSystemSizes.js';
import { regenerateEditorCSS } from '../../utils/liveVars.js';

/**
 * Spacing scale tokens — display order matches the dashboard Spacing tab.
 *
 * @since x.x.x
 *
 * @type {Array<{key: string, label: string}>}
 */
const SPACING_SCALES = [
	{ key: 'space-xs',  label: 'XS' },
	{ key: 'space-sm',  label: 'SM' },
	{ key: 'space-md',  label: 'MD' },
	{ key: 'space-lg',  label: 'LG' },
	{ key: 'space-xl',  label: 'XL' },
	{ key: 'space-xxl', label: 'XXL' },
];

/**
 * Supported CSS units.
 *
 * @since x.x.x
 *
 * @type {string[]}
 */
const UNITS = [ 'rem', 'em', 'px' ];

/**
 * Convert any unit to an approximate pixel value for bar sizing.
 *
 * @since x.x.x
 *
 * @param {number} value Numeric value.
 * @param {string} unit  Unit string.
 * @return {number} Approximate pixel value.
 */
function toPx( value, unit ) {
	if ( unit === 'px' ) {
		return value;
	}
	return value * 16;
}

/**
 * SpacingPanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Function} props.onRegisterSave Callback to register the save handler.
 * @param {Function} props.onStatusChange Callback to report saving state to the footer.
 * @return {Element}
 */
const SpacingPanel = ( { onRegisterSave, onStatusChange } ) => {
	const { sizes, loading, saving, updateSize, resetKey, commitAll } = useSystemSizes();

	const [ active, setActive ] = useState( SPACING_SCALES[ 0 ].key );
	const [ isDirty, setIsDirty ] = useState( false );

	// Local editable state for the active token's input.
	const [ inputValue, setInputValue ] = useState( '' );
	const [ inputUnit,  setInputUnit  ] = useState( 'rem' );

	// Sync input with remote / draft data whenever active key or sizes change.
	useEffect( () => {
		const token = sizes?.spacing?.[ active ];
		if ( ! token ) {
			return;
		}
		setInputValue( String( token.value ?? '' ) );
		setInputUnit( token.unit ?? 'rem' );
	}, [ active, sizes ] );

	// Register save handler — commits current draft to the server.
	const handleSave = useCallback( async () => {
		await commitAll( sizes );
		regenerateEditorCSS();
		setIsDirty( false );
	}, [ commitAll, sizes ] );

	useEffect( () => {
		onRegisterSave?.( handleSave );
	}, [ onRegisterSave, handleSave ] );

	// Report saving/dirty status to the shared modal footer.
	useEffect( () => {
		onStatusChange?.( { saving, previewing: false, dirty: isDirty } );
	}, [ saving, isDirty, onStatusChange ] );

	const handleValueChange = ( raw ) => {
		setInputValue( raw );
		const numeric = parseFloat( raw );
		if ( ! isNaN( numeric ) ) {
			updateSize( 'spacing', active, numeric, inputUnit );
			setIsDirty( true );
		}
	};

	const handleUnitChange = ( unit ) => {
		setInputUnit( unit );
		const numeric = parseFloat( inputValue );
		if ( ! isNaN( numeric ) ) {
			updateSize( 'spacing', active, numeric, unit );
			setIsDirty( true );
		}
	};

	const handleReset = () => {
		resetKey( 'spacing', active );
		setIsDirty( true );
	};

	if ( loading ) {
		return (
			<div className="spectra-gbs-font-size-panel">
				<div className="spectra-gbs-panel__loading">
					<Spinner />
					<span>{ __( 'Loading spacing…', 'spectra-blocks' ) }</span>
				</div>
			</div>
		);
	}

	// Build preview data — proportional bars sized against the largest token.
	const previewData = SPACING_SCALES.map( ( s ) => {
		const token = sizes?.spacing?.[ s.key ];
		const value = parseFloat( token?.value ?? 0 );
		const unit  = token?.unit ?? 'rem';
		return { ...s, value, unit, changed: token?.changed ?? false };
	} );
	const maxPx = Math.max( ...previewData.map( ( t ) => toPx( t.value, t.unit ) ), 1 );

	return (
		<div className="spectra-gbs-font-size-panel">

			{ /* Scale pills */ }
			<div className="spectra-gbs-font-size-panel__scale-pills">
				{ SPACING_SCALES.map( ( scale ) => (
					<button
						key={ scale.key }
						type="button"
						className={ `spectra-gbs-font-size-panel__pill${ active === scale.key ? ' is-active' : '' }` }
						onClick={ () => setActive( scale.key ) }
						aria-pressed={ active === scale.key }
					>
						{ scale.label }
					</button>
				) ) }
			</div>

			{ /* Value editor */ }
			<div className="spectra-gbs-font-size-panel__editor">
				<label
					className="spectra-gbs-font-size-panel__editor-label"
					htmlFor="spectra-gbs-spacing-input"
				>
					{ __( 'Size', 'spectra-blocks' ) }
				</label>

				<div className="spectra-gbs-font-size-panel__editor-controls">
					<input
						id="spectra-gbs-spacing-input"
						type="number"
						className="spectra-gbs-font-size-panel__number-input"
						value={ inputValue }
						step="0.1"
						min="0"
						onChange={ ( e ) => handleValueChange( e.target.value ) }
					/>

					<select
						className="spectra-gbs-font-size-panel__unit-select"
						value={ inputUnit }
						onChange={ ( e ) => handleUnitChange( e.target.value ) }
					>
						{ UNITS.map( ( unit ) => (
							<option key={ unit } value={ unit }>{ unit }</option>
						) ) }
					</select>

					<button
						type="button"
						className="spectra-gbs-font-size-panel__reset-btn"
						onClick={ handleReset }
						disabled={ saving }
					>
						{ __( 'Reset', 'spectra-blocks' ) }
					</button>

					{ saving && <Spinner /> }
				</div>
			</div>

			{ /* Proportional preview strip */ }
			<div className="spectra-gbs-font-size-panel__preview">
				<p className="spectra-gbs-font-size-panel__preview-heading">
					{ __( 'Preview', 'spectra-blocks' ) }
				</p>

				{ previewData.map( ( token ) => {
					const widthPct = ( toPx( token.value, token.unit ) / maxPx ) * 100;
					return (
						<div
							key={ token.key }
							className={ `spectra-gbs-font-size-panel__preview-row${ active === token.key ? ' is-active' : '' }` }
						>
							<span className="spectra-gbs-font-size-panel__preview-label">
								{ token.label }
							</span>
							<div className="spectra-gbs-spacing__bar-track" aria-hidden="true">
								<div
									className={ `spectra-gbs-spacing__bar${ token.changed ? ' is-changed' : '' }` }
									style={ { width: `${ Math.max( widthPct, 2 ) }%` } }
								/>
							</div>
							<span className="spectra-gbs-font-size-panel__preview-text">
								{ sprintf(
									/* translators: %s: spacing value e.g. "1.5rem" */
									__( '%s', 'spectra-blocks' ),
									`${ token.value }${ token.unit }`
								) }
							</span>
						</div>
					);
				} ) }
			</div>

		</div>
	);
};

export default SpacingPanel;
