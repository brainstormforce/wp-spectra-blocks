/**
 * FontSizePanel — GBS Editor panel for font-size system tokens.
 *
 * Mirrors the dashboard "Font Size" tab: Text / Headings toggle,
 * scale pills (XS–XXL or H1–H6), numeric + unit input with Reset,
 * and a live preview row for every scale.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useSystemSizes } from '../../hooks/useSystemSizes.js';
import { regenerateEditorCSS } from '../../utils/liveVars.js';

/** @since x.x.x */
const TEXT_SCALES = [
	{ key: 'text-xs',   label: 'XS' },
	{ key: 'text-sm',   label: 'SM' },
	{ key: 'text-md',   label: 'MD' },
	{ key: 'text-base', label: 'Base' },
	{ key: 'text-lg',   label: 'LG' },
	{ key: 'text-xl',   label: 'XL' },
	{ key: 'text-xxl',  label: 'XXL' },
];

/** @since x.x.x */
const HEADING_SCALES = [
	{ key: 'heading-1', label: 'H1' },
	{ key: 'heading-2', label: 'H2' },
	{ key: 'heading-3', label: 'H3' },
	{ key: 'heading-4', label: 'H4' },
	{ key: 'heading-5', label: 'H5' },
	{ key: 'heading-6', label: 'H6' },
];

/** @since x.x.x */
const UNITS = [ 'rem', 'em', 'px' ];

/**
 * FontSizePanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   props                Component props.
 * @param {Function} props.onRegisterSave Optional callback to register save handler with parent.
 * @param {Function} props.onStatusChange Optional callback to report saving state to footer.
 * @return {JSX.Element} Rendered panel.
 */
export default function FontSizePanel( { onRegisterSave, onStatusChange } ) {
	const { sizes, loading, saving, updateSize, resetKey, commitAll } = useSystemSizes();

	/** @type {'text'|'headings'} */
	const [ mode, setMode ] = useState( 'text' );
	const [ isDirty, setIsDirty ] = useState( false );

	const scales     = mode === 'text' ? TEXT_SCALES : HEADING_SCALES;
	const [ active, setActive ] = useState( scales[ 0 ].key );

	// Keep active key valid when mode changes.
	useEffect( () => {
		const newScales = mode === 'text' ? TEXT_SCALES : HEADING_SCALES;
		setActive( newScales[ 0 ].key );
	}, [ mode ] );

	// Local editable state for the numeric input + unit.
	const [ inputValue, setInputValue ] = useState( '' );
	const [ inputUnit,  setInputUnit  ] = useState( 'rem' );

	// Sync local input with remote data whenever active key or sizes change.
	useEffect( () => {
		if ( ! sizes?.fontsize?.[ active ] ) {
			return;
		}
		const token = sizes.fontsize[ active ];
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

	/**
	 * Handle numeric value change: update local state and persist.
	 *
	 * @since x.x.x
	 *
	 * @param {string} raw Raw input string.
	 */
	const handleValueChange = ( raw ) => {
		setInputValue( raw );
		const numeric = parseFloat( raw );
		if ( ! isNaN( numeric ) ) {
			updateSize( 'fontsize', active, numeric, inputUnit );
			setIsDirty( true );
		}
	};

	/**
	 * Handle unit change: persist with current numeric value.
	 *
	 * @since x.x.x
	 *
	 * @param {string} unit New unit string.
	 */
	const handleUnitChange = ( unit ) => {
		setInputUnit( unit );
		const numeric = parseFloat( inputValue );
		if ( ! isNaN( numeric ) ) {
			updateSize( 'fontsize', active, numeric, unit );
			setIsDirty( true );
		}
	};

	/**
	 * Reset the active token to its default value.
	 *
	 * @since x.x.x
	 */
	const handleReset = () => {
		resetKey( 'fontsize', active );
		setIsDirty( true );
	};

	/**
	 * Return a CSS value string for a given token key.
	 *
	 * @since x.x.x
	 *
	 * @param {string} key Token key.
	 * @return {string} CSS font-size value, e.g. "1rem".
	 */
	const tokenCss = ( key ) => {
		const token = sizes?.fontsize?.[ key ];
		if ( ! token ) {
			return 'inherit';
		}
		return `${ token.value }${ token.unit }`;
	};

	if ( loading ) {
		return (
			<div className="spectra-gbs-font-size-panel">
				<div className="spectra-gbs-panel__loading">
					<Spinner />
					<span>{ __( 'Loading font sizes…', 'spectra-blocks' ) }</span>
				</div>
			</div>
		);
	}

	return (
		<div className="spectra-gbs-font-size-panel">

			{ /* Mode toggle */ }
			<div className="spectra-gbs-font-size-panel__mode-toggle">
				<button
					type="button"
					className={ `spectra-gbs-font-size-panel__mode-btn${ mode === 'text' ? ' is-active' : '' }` }
					onClick={ () => setMode( 'text' ) }
				>
					{ __( 'Text', 'spectra-blocks' ) }
				</button>
				<button
					type="button"
					className={ `spectra-gbs-font-size-panel__mode-btn${ mode === 'headings' ? ' is-active' : '' }` }
					onClick={ () => setMode( 'headings' ) }
				>
					{ __( 'Headings', 'spectra-blocks' ) }
				</button>
			</div>

			{ /* Scale pills */ }
			<div className="spectra-gbs-font-size-panel__scale-pills">
				{ scales.map( ( scale ) => (
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
					htmlFor="spectra-gbs-font-size-input"
				>
					{ __( 'Size', 'spectra-blocks' ) }
				</label>

				<div className="spectra-gbs-font-size-panel__editor-controls">
					<input
						id="spectra-gbs-font-size-input"
						type="number"
						className="spectra-gbs-font-size-panel__number-input"
						value={ inputValue }
						step="0.01"
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

			{ /* Live preview */ }
			<div className="spectra-gbs-font-size-panel__preview">
				<p className="spectra-gbs-font-size-panel__preview-heading">
					{ __( 'Preview', 'spectra-blocks' ) }
				</p>

				{ scales.map( ( scale ) => (
					<div
						key={ scale.key }
						className={ `spectra-gbs-font-size-panel__preview-row${ active === scale.key ? ' is-active' : '' }` }
					>
						<span className="spectra-gbs-font-size-panel__preview-label">
							{ scale.label }
						</span>
						<span
							className="spectra-gbs-font-size-panel__preview-text"
							style={ { fontSize: tokenCss( scale.key ) } }
						>
							{ mode === 'text'
								/* translators: font-size scale label e.g. "XS" */
								? sprintf( __( 'Sample text at %s', 'spectra-blocks' ), scale.label )
								/* translators: heading number 1–6 */
								: sprintf( __( 'Preview of a Heading %s', 'spectra-blocks' ), scale.label.replace( 'H', '' ) )
							}
						</span>
					</div>
				) ) }
			</div>

		</div>
	);
}
