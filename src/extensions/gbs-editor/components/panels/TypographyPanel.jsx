/**
 * TypographyPanel — orchestrator for the Typography tab in the GBS editor.
 *
 * Mirrors ColorsPanel in structure: owns a local draft config for live
 * font selection, routes section components, and persists on Save.
 *
 * Fonts are saved to the Style Guide option via /style-guide/config (like the
 * rest of the design system) and rendered from there; the editor canvas
 * reflects the new fonts after save.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useCallback, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { useGBSConfig }   from '../../hooks/useGBSConfig.js';
import { useGBSComputed } from '../../hooks/useGBSComputed.js';
import { regenerateEditorCSS } from '../../utils/liveVars.js';
import HeadingsSection from '../typography/HeadingsSection.jsx';
import BodySection     from '../typography/BodySection.jsx';

const SECTION_MAP = {
	headings: HeadingsSection,
	body:     BodySection,
};

/**
 * Deep merge — same util as ColorsPanel (kept local to avoid circular import).
 * @param {Object} base   Base object.
 * @param {Object} update Partial update to merge.
 * @return {Object} Merged result.
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

/**
 * TypographyPanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.sectionId      Active section id.
 * @param {Function} props.onRegisterSave Callback for panels to register their save fn.
 * @param {Function} props.onStatusChange Status change callback.
 * @return {Element}
 */
const TypographyPanel = ( { sectionId, onRegisterSave, onStatusChange } ) => {
	const { config, loading: configLoading, saving, save } = useGBSConfig();
	const { computed, loading: computedLoading } = useGBSComputed();
	const [ draftConfig, setDraftConfig ] = useState( null );
	const [ fontSaveNotice, setFontSaveNotice ] = useState( false );
	const [ isDirty, setIsDirty ] = useState( false );

	useEffect( () => {
		if ( config ) {
			setDraftConfig( config );
			setIsDirty( false );
		}
	}, [ config ] );

	const handleConfigChange = useCallback(
		( partialConfig ) => {
			setDraftConfig( ( prev ) => deepMerge( prev ?? {}, partialConfig ) );
			setIsDirty( true );
			// Font changes need a save — show a gentle notice.
			if ( partialConfig.typography ) {
				setFontSaveNotice( true );
			}
		},
		[]
	);

	const handleSave = useCallback( async () => {
		if ( ! draftConfig || saving ) {return;}
		await save( draftConfig );
		regenerateEditorCSS();
		setFontSaveNotice( false );
		setIsDirty( false );
	}, [ draftConfig, saving, save ] );

	useEffect( () => {
		if ( typeof onRegisterSave === 'function' ) {
			onRegisterSave( handleSave );
		}
	}, [ onRegisterSave, handleSave ] );

	// Report save/dirty status up to the shared modal footer.
	useEffect( () => {
		onStatusChange?.( { saving, previewing: false, dirty: isDirty } );
	}, [ saving, isDirty, onStatusChange ] );

	const isLoading = configLoading || computedLoading;
	const SectionComponent = SECTION_MAP[ sectionId ] ?? null;
	const activeConfig = draftConfig ?? config;

	if ( isLoading ) {
		return (
			<div className="spectra-gbs-panel__loading">
				<Spinner />
				<span>{ __( 'Loading typography settings…', 'spectra-blocks' ) }</span>
			</div>
		);
	}

	if ( ! activeConfig ) {
		return (
			<div className="spectra-gbs-panel__error">
				{ __( 'Failed to load typography configuration.', 'spectra-blocks' ) }
			</div>
		);
	}

	return (
		<div className="spectra-gbs-panel__body">
			{ fontSaveNotice && (
				<div className="spectra-gbs-typography__save-notice" role="status">
					{ __( 'Font changes apply after save + refresh.', 'spectra-blocks' ) }
				</div>
			) }
			<div className="spectra-gbs-panel__content">
				{ SectionComponent ? (
					<SectionComponent
						config={ activeConfig }
						computed={ computed }
						onConfigChange={ handleConfigChange }
					/>
				) : (
					<div className="spectra-gbs-panel__placeholder-text">
						{ sprintf(
							__( 'No editor for section "%s".', 'spectra-blocks' ),
							sectionId
						) }
					</div>
				) }
			</div>
		</div>
	);
};

export default TypographyPanel;
