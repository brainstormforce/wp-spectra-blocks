/**
 * ColorsPanel — orchestrator for the Colors tab in the GBS editor.
 *
 * Owns config and computed state for the tab. Renders the correct
 * section component based on the sidebar selection, and wires the
 * Save button through to the REST endpoint.
 *
 * Data flow:
 *   Load  → GET /style-guide/config  +  GET /style-guide/compute
 *   Edit  → localConfig update  →  debounced POST /style-guide/preview
 *   Save  → POST /style-guide/config  →  GET /style-guide/compute  (refresh)
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { useGBSConfig }   from '../../hooks/useGBSConfig.js';
import { useGBSComputed } from '../../hooks/useGBSComputed.js';
import { injectStyleSheet, buildWPPresetCSS, buildAstraAliasCSS, regenerateEditorCSS, syncEditorSwatches } from '../../utils/liveVars.js';
import PaletteSection from '../colors/PaletteSection.jsx';

/**
 * ColorsPanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Function} props.onRegisterSave Callback for panels to register their save fn.
 * @param {Function} props.onStatusChange Status change callback.
 * @param {Function} props.onSaveStart    Called when a save begins (to update Save button state in header).
 * @param {Function} props.onSaveEnd      Called when a save completes.
 * @return {Element}
 */
const ColorsPanel = ( { onSaveStart, onSaveEnd, onRegisterSave, onStatusChange } ) => {
	const { config, loading: configLoading, saving, save } = useGBSConfig();
	const { computed, loading: computedLoading, refresh } = useGBSComputed();

	// Local draft config — reflects unsaved edits for preview purposes.
	const [ draftConfig, setDraftConfig ] = useState( null );
	const [ isDirty, setIsDirty ] = useState( false );

	// Sync local draft whenever server config loads or reloads.
	useEffect( () => {
		if ( config ) {
			setDraftConfig( config );
			setIsDirty( false );
		}
	}, [ config ] );

	// Inject computed color CSS vars into the live editor canvas whenever they change.
	useEffect( () => {
		if ( computed?.css ) {
			injectStyleSheet( 'spectra-gbs-live-color-vars', computed.css );
		}
	}, [ computed?.css ] );

	// Inject --wp--preset--color--* vars so elements that reference
	// var(--wp--preset--color--primary) etc. update live without a page reload.
	// Edit-time ONLY (gated on isDirty): on plain open the server already renders
	// the correct state (saved colours when saved, theme colours when not), so
	// injecting here on open would wrongly apply the default palette to an unsaved
	// site. The injected sheet persists after Save, so applied colours stay put.
	useEffect( () => {
		if ( isDirty && computed?.tokens ) {
			const css = buildWPPresetCSS( computed.tokens );
			if ( css ) {
				injectStyleSheet( 'spectra-gbs-live-wp-preset-colors', css );
			}
		}
	}, [ computed?.tokens, isDirty ] );

	// Inject the Astra global-colour aliases (--ast-global-color-*) so elements
	// that resolve through the theme palette (e.g. button backgrounds) also
	// update live while editing — the token/preset injections above don't cover
	// them. Edit-time only (isDirty); the on-load path stays gated on "saved".
	useEffect( () => {
		if ( isDirty && computed?.tokens ) {
			const css = buildAstraAliasCSS( computed.tokens );
			if ( css ) {
				injectStyleSheet( 'spectra-gbs-live-astra-aliases', css );
			}
		}
	}, [ computed?.tokens, isDirty ] );

	// Keep the colour-picker swatches in step with the live computation so the
	// palette dropdown shows the same colour that gets applied — no reload needed.
	useEffect( () => {
		syncEditorSwatches( computed, draftConfig ?? config );
	}, [ computed, draftConfig, config ] );

	/**
	 * Handle partial config changes from section components.
	 * Updates local draft and fires a debounced preview.
	 *
	 * @since x.x.x
	 *
	 * @param {Object} partialConfig Partial config object to merge.
	 * @return {void}
	 */
	const handleConfigChange = useCallback(
		( partialConfig ) => {
			setDraftConfig( ( prev ) => deepMerge( prev ?? {}, partialConfig ) );
			setIsDirty( true );
		},
		[]
	);

	/**
	 * Save the current draft config to the server.
	 *
	 * @since x.x.x
	 *
	 * @return {Promise<void>}
	 */
	const handleSave = useCallback( async () => {
		if ( ! draftConfig || saving ) {return;}
		onSaveStart?.();
		try {
			await save( draftConfig );
			await refresh();
			regenerateEditorCSS();
			setIsDirty( false );
		} finally {
			onSaveEnd?.();
		}
	}, [ draftConfig, saving, save, refresh, onSaveStart, onSaveEnd ] );

	useEffect( () => {
		if ( typeof onRegisterSave === 'function' ) {
			onRegisterSave( handleSave );
		}
	}, [ onRegisterSave, handleSave ] );

	// Report save/preview/dirty status up to the shared modal footer.
	useEffect( () => {
		onStatusChange?.( { saving, previewing: false, dirty: isDirty } );
	}, [ saving, isDirty, onStatusChange ] );

	const isLoading    = configLoading || computedLoading;
	const activeConfig = draftConfig ?? config;

	if ( isLoading ) {
		return (
			<div className="spectra-gbs-panel__loading">
				<Spinner />
				<span>{ __( 'Loading style guide…', 'spectra-blocks' ) }</span>
			</div>
		);
	}

	if ( ! activeConfig ) {
		return (
			<div className="spectra-gbs-panel__error">
				{ __( 'Failed to load style guide configuration.', 'spectra-blocks' ) }
			</div>
		);
	}

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<PaletteSection
					config={ activeConfig }
					computed={ computed }
					onConfigChange={ handleConfigChange }
				/>
			</div>
		</div>
	);
};

/**
 * Shallow-deep merge — merges plain objects one level deep, replaces scalars.
 *
 * @since x.x.x
 *
 * @param {Object} base   Base object.
 * @param {Object} update Object with updates to apply.
 * @return {Object} Merged object.
 */
function deepMerge( base, update ) {
	const result = { ...base };
	for ( const [ key, value ] of Object.entries( update ) ) {
		if (
			value !== null &&
			typeof value === 'object' &&
			! Array.isArray( value ) &&
			typeof base[ key ] === 'object' &&
			base[ key ] !== null
		) {
			result[ key ] = deepMerge( base[ key ], value );
		} else {
			result[ key ] = value;
		}
	}
	return result;
}

export default ColorsPanel;
