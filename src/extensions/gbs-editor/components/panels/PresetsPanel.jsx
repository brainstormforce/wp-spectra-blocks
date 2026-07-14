/**
 * PresetsPanel — UI preset selector for the Presets tab.
 *
 * Renders a chip-group picker for the active sidebar section's preset key.
 * All preset changes go into a local draft and are persisted on Save.
 * Because presets change CSS variable values (not fonts), the preview
 * endpoint is called after each change so computed token values update.
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
import { getPresetCatalog } from '../presets/presetCatalog.js';
import SpacingSection from '../presets/SpacingSection.jsx';
import { regenerateEditorCSS, refreshComputedCSS } from '../../utils/liveVars.js';

/**
 * Deep merge — same util used across panels.
 *
 * @param {Object} base   Base object.
 * @param {Object} update Partial update to merge.
 * @return {Object} Merged result.
 */
function deepMerge( base, update ) {
	const result = { ...base };
	for ( const [ key, value ] of Object.entries( update ) ) {
		if ( value !== null && typeof value === 'object' && ! Array.isArray( value )
			&& typeof base[ key ] === 'object' && base[ key ] !== null ) {
			result[ key ] = deepMerge( base[ key ], value );
		} else {
			result[ key ] = value;
		}
	}
	return result;
}

/**
 * Single preset option chip.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.option   { value, label, description }
 * @param {boolean}  props.isActive Whether this option is currently selected.
 * @param {Function} props.onClick  Click handler.
 * @return {Element}
 */
const PresetChip = ( { option, isActive, onClick } ) => (
	<button
		type="button"
		className={ `spectra-gbs-preset-chip${ isActive ? ' is-active' : '' }` }
		onClick={ onClick }
		aria-pressed={ isActive }
	>
		<span className="spectra-gbs-preset-chip__label">{ option.label }</span>
		{ option.description && (
			<span className="spectra-gbs-preset-chip__desc">{ option.description }</span>
		) }
	</button>
);

/**
 * Token preview rows shown below the chip picker, with a Copy button.
 * Reads affected tokens from the computed map.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {Object} props.tokens    Flat computed token map.
 * @param {Array}  props.tokenKeys Token names to display.
 * @return {Element|null}
 */
const TokenPreview = ( { tokens, tokenKeys } ) => {
	const [ copied, setCopied ] = useState( false );

	const rows = tokenKeys
		.map( ( k ) => ( { key: k, value: tokens[ k ] } ) )
		.filter( ( r ) => r.value );

	if ( ! rows.length ) {return null;}

	const handleCopy = async () => {
		const css = rows.map( ( { key, value } ) => `--spectra-${ key }: ${ value };` ).join( '\n' );
		try {
			if ( navigator.clipboard && window.isSecureContext ) {
				await navigator.clipboard.writeText( css );
			} else {
				const ta = document.createElement( 'textarea' );
				ta.value = css;
				ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
				document.body.appendChild( ta );
				ta.select();
				document.execCommand( 'copy' ); // eslint-disable-line
				document.body.removeChild( ta );
			}
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} catch ( e ) {
			// silent fail
		}
	};

	return (
		<div className="spectra-gbs-preset__token-preview">
			<div className="spectra-gbs-preset__token-preview-header">
				<span className="spectra-gbs-preset__token-preview-label">
					{ __( 'Computed tokens', 'spectra-blocks' ) }
				</span>
				<button
					type="button"
					className="spectra-gbs-preset__token-copy"
					onClick={ handleCopy }
					title={ __( 'Copy all token declarations', 'spectra-blocks' ) }
				>
					{ copied ? __( 'Copied!', 'spectra-blocks' ) : __( 'Copy', 'spectra-blocks' ) }
				</button>
			</div>
			{ rows.map( ( { key, value } ) => (
				<div key={ key } className="spectra-gbs-preset__token-row">
					<code className="spectra-gbs-preset__token-name">--spectra-{ key }</code>
					<code className="spectra-gbs-preset__token-value">{ value }</code>
				</div>
			) ) }
		</div>
	);
};

/** Which tokens to show as preview per preset section. */
const SECTION_PREVIEW_TOKENS = {
	buttons:    [ 'btn-bg', 'btn-text', 'btn-border-color', 'btn-shadow' ],
	cards:      [ 'card-bg', 'card-border-color', 'card-shadow' ],
	inputs:     [ 'input-bg', 'input-border-color', 'input-border-width' ],
	roundness:  [ 'radius-interactive', 'radius-card', 'radius-badge', 'radius-image' ],
	shadows:    [ 'shadow-sm', 'shadow-md', 'shadow-lg' ],
	spacing:    [ 'space-xs', 'space-sm', 'space-md', 'space-lg', 'space-xl' ],
	motion:     [ 'transition-fast', 'transition-normal', 'transition-slow', 'entrance-duration' ],
	badges:     [ 'badge-font-size', 'badge-font-weight', 'badge-letter-spacing' ],
	images:     [],
	hover:      [ 'btn-hover-translate-y', 'card-hover-shadow', 'card-hover-translate-y' ],
	shades:     [],
	saturation: [],
};

/**
 * PresetsPanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.sectionId      Active sidebar section id.
 * @param {Function} props.onRegisterSave Callback for panels to register their save fn.
 * @param {Function} props.onStatusChange Status change callback.
 * @return {Element}
 */
const PresetsPanel = ( { sectionId, onRegisterSave, onStatusChange } ) => {
	const { config, loading: configLoading, saving, save } = useGBSConfig();
	const { computed, loading: computedLoading, refresh } = useGBSComputed();
	const [ draftConfig, setDraftConfig ] = useState( null );
	const [ isDirty, setIsDirty ] = useState( false );

	useEffect( () => {
		if ( config ) {
			setDraftConfig( config );
			setIsDirty( false );
		}
	}, [ config ] );

	const catalog = getPresetCatalog();
	const section = catalog[ sectionId ];

	const currentValue = draftConfig?.presets?.[ section?.key ] ?? '';

	const handleSelect = useCallback(
		( value ) => {
			if ( ! section ) {return;}
			const updated = deepMerge( draftConfig ?? {}, {
				presets: { [ section.key ]: value },
			} );
			setDraftConfig( updated );
			setIsDirty( true );
		},
		[ section, draftConfig ]
	);

	const handleSave = useCallback( async () => {
		if ( ! draftConfig || saving ) {return;}
		await save( draftConfig );
		await refresh();
		refreshComputedCSS();
		regenerateEditorCSS();
		setIsDirty( false );
	}, [ draftConfig, saving, save, refresh ] );

	useEffect( () => {
		if ( typeof onRegisterSave === 'function' ) {
			onRegisterSave( handleSave );
		}
	}, [ onRegisterSave, handleSave ] );

	// Report save/preview/dirty status up to the shared modal footer.
	useEffect( () => {
		onStatusChange?.( { saving, previewing: false, dirty: isDirty } );
	}, [ saving, isDirty, onStatusChange ] );

	const handleConfigChange = useCallback( ( partial ) => {
		const updated = deepMerge( draftConfig ?? {}, partial );
		setDraftConfig( updated );
		setIsDirty( true );
	}, [ draftConfig ] );

	const isLoading = configLoading || computedLoading;

	// Spacing section has its own dedicated component.
	if ( sectionId === 'spacing' ) {
		return (
			<div className="spectra-gbs-panel__body">
				<div className="spectra-gbs-panel__content">
					<SpacingSection
						config={ draftConfig ?? config }
						computed={ computed }
						onConfigChange={ handleConfigChange }
					/>
				</div>
			</div>
		);
	}

	if ( isLoading ) {
		return (
			<div className="spectra-gbs-panel__loading">
				<Spinner />
				<span>{ __( 'Loading presets…', 'spectra-blocks' ) }</span>
			</div>
		);
	}

	if ( ! section ) {
		return (
			<div className="spectra-gbs-panel__placeholder-text">
				{ sprintf( __( 'No preset for section "%s".', 'spectra-blocks' ), sectionId ) }
			</div>
		);
	}

	const previewTokens = SECTION_PREVIEW_TOKENS[ sectionId ] ?? [];

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<div className="spectra-gbs-section spectra-gbs-section--preset">

					{/* Preset label + hint */}
					<div className="spectra-gbs-section__field">
						<label className="spectra-gbs-section__label">
							{ section.label }
						</label>
						{ section.hint && (
							<p className="spectra-gbs-section__hint">{ section.hint }</p>
						) }
					</div>

					{/* Option chips */}
					<div className="spectra-gbs-section__field">
						<div className="spectra-gbs-preset-chips">
							{ section.options.map( ( option ) => (
								<PresetChip
									key={ option.value }
									option={ option }
									isActive={ currentValue === option.value }
									onClick={ () => handleSelect( option.value ) }
								/>
							) ) }
						</div>
					</div>

					{/* Computed token preview */}
					{ previewTokens.length > 0 && (
						<div className="spectra-gbs-section__field">
							<TokenPreview
								tokens={ computed?.tokens ?? {} }
								tokenKeys={ previewTokens }
							/>
						</div>
					) }
				</div>
			</div>
		</div>
	);
};

export default PresetsPanel;
