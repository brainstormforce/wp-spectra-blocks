/**
 * KeyframesPanel — create and manage named CSS @keyframes animations.
 *
 * @since x.x.x
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner, Icon } from '@wordpress/components';
import { edit as editIcon } from '@wordpress/icons';
import { useKeyframes }        from '../../hooks/useKeyframes.js';
import { regenerateEditorCSS } from '../../utils/liveVars.js';
import CSSAutocomplete         from '../CSSAutocomplete.jsx';

const DEFAULT_CSS = '0%   { opacity: 0; transform: translateY(8px); }\n100% { opacity: 1; transform: translateY(0); }';

const DEFAULT_META = {
	defaultDuration:   '0.3s',
	defaultEasing:     'ease-out',
	defaultIterations: '1',
};

const EASING_PRESETS = [
	{ value: 'linear',                                   label: 'Linear' },
	{ value: 'ease',                                     label: 'Ease' },
	{ value: 'ease-in',                                  label: 'Ease In' },
	{ value: 'ease-out',                                 label: 'Ease Out' },
	{ value: 'ease-in-out',                              label: 'Ease In Out' },
	{ value: 'cubic-bezier(0.4, 0, 0.2, 1)',             label: 'Material Standard' },
	{ value: 'cubic-bezier(0, 0, 0.2, 1)',               label: 'Material Decelerate' },
	{ value: 'cubic-bezier(0.4, 0, 1, 1)',               label: 'Material Accelerate' },
	{ value: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',   label: 'Back' },
	{ value: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)',  label: 'Bounce' },
];

const DURATION_PRESETS = [
	{ value: '0.1s',  label: '0.1s (Very Fast)' },
	{ value: '0.15s', label: '0.15s (Fast)' },
	{ value: '0.2s',  label: '0.2s' },
	{ value: '0.3s',  label: '0.3s (Default)' },
	{ value: '0.4s',  label: '0.4s' },
	{ value: '0.5s',  label: '0.5s (Medium)' },
	{ value: '0.6s',  label: '0.6s' },
	{ value: '0.8s',  label: '0.8s' },
	{ value: '1s',    label: '1s (Slow)' },
	{ value: '1.5s',  label: '1.5s' },
	{ value: '2s',    label: '2s (Very Slow)' },
];

const ITERATION_PRESETS = [
	{ value: '1',        label: 'Once' },
	{ value: '2',        label: 'Twice' },
	{ value: '3',        label: '3 times' },
	{ value: 'infinite', label: 'Infinite' },
];

/**
 * AnimationMetaFields — shared Duration / Easing / Repeat selects.
 *
 * @param {Object}   root0
 * @param {Object}   root0.meta     Animation metadata (duration, easing, etc.).
 * @param {Function} root0.onChange Change handler.
 * @since x.x.x
 * @return {Element}
 */
const AnimationMetaFields = ( { meta, onChange } ) => (
	<div className="spectra-gbs-keyframe-settings">
		<div className="spectra-gbs-keyframe-settings__field">
			<span className="spectra-gbs-keyframe-settings__label">{ __( 'Duration', 'spectra-blocks' ) }</span>
			<select
				className="spectra-gbs-keyframe-settings__select"
				value={ meta.defaultDuration }
				onChange={ ( e ) => onChange( { ...meta, defaultDuration: e.target.value } ) }
			>
				{ DURATION_PRESETS.map( ( p ) => (
					<option key={ p.value } value={ p.value }>{ p.label }</option>
				) ) }
			</select>
		</div>
		<div className="spectra-gbs-keyframe-settings__field">
			<span className="spectra-gbs-keyframe-settings__label">{ __( 'Easing', 'spectra-blocks' ) }</span>
			<select
				className="spectra-gbs-keyframe-settings__select"
				value={ meta.defaultEasing }
				onChange={ ( e ) => onChange( { ...meta, defaultEasing: e.target.value } ) }
			>
				{ EASING_PRESETS.map( ( p ) => (
					<option key={ p.value } value={ p.value }>{ p.label }</option>
				) ) }
			</select>
		</div>
		<div className="spectra-gbs-keyframe-settings__field">
			<span className="spectra-gbs-keyframe-settings__label">{ __( 'Repeat', 'spectra-blocks' ) }</span>
			<select
				className="spectra-gbs-keyframe-settings__select"
				value={ meta.defaultIterations }
				onChange={ ( e ) => onChange( { ...meta, defaultIterations: e.target.value } ) }
			>
				{ ITERATION_PRESETS.map( ( p ) => (
					<option key={ p.value } value={ p.value }>{ p.label }</option>
				) ) }
			</select>
		</div>
	</div>
);

/**
 * KeyframeEditor — inline editor for editing an existing keyframe's CSS + meta.
 *
 * @param {Object}   root0
 * @param {string}   root0.name     Keyframe animation name.
 * @param {string}   root0.css      CSS body of the keyframe.
 * @param {Object}   root0.meta     Keyframe metadata (duration, easing, etc.).
 * @param {Function} root0.onSave   Save callback.
 * @param {Function} root0.onCancel Cancel callback.
 * @param {boolean}  root0.saving   Whether a save is in progress.
 * @since x.x.x
 * @return {Element}
 */
const KeyframeEditor = ( { name, css, meta = DEFAULT_META, onSave, onCancel, saving } ) => {
	const [ body, setBody ]           = useState( css || DEFAULT_CSS );
	const [ localMeta, setLocalMeta ] = useState( { ...DEFAULT_META, ...meta } );
	const [ copied, setCopied ]       = useState( false );

	const getAnimationShorthand = () => {
		const parts = [ name, localMeta.defaultDuration, localMeta.defaultEasing ];
		if ( localMeta.defaultIterations && localMeta.defaultIterations !== '1' ) {
			parts.push( localMeta.defaultIterations );
		}
		return parts.join( ' ' );
	};

	const handleCopy = async () => {
		const text = `animation: ${ getAnimationShorthand() };`;
		try {
			if ( navigator.clipboard && window.isSecureContext ) {
				await navigator.clipboard.writeText( text );
			} else {
				const ta = document.createElement( 'textarea' );
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.left = '-9999px';
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
		<div className="spectra-gbs-class-editor">
			<div className="spectra-gbs-class-editor__name">
				<code>@keyframes { name }</code>
			</div>

			{/* Animation settings */}
			<div className="spectra-gbs-class-editor__bucket">
				<label className="spectra-gbs-section__label">
					{ __( 'Animation settings', 'spectra-blocks' ) }
				</label>
				<AnimationMetaFields meta={ localMeta } onChange={ setLocalMeta } />
			</div>

			{/* CSS editor */}
			<div className="spectra-gbs-class-editor__bucket">
				<label className="spectra-gbs-section__label">
					{ __( 'Keyframe body', 'spectra-blocks' ) }
				</label>
				<CSSAutocomplete
					className="spectra-gbs-class-editor__textarea"
					value={ body }
					onChange={ ( e ) => setBody( e.target.value ) }
					rows={ 6 }
					spellCheck={ false }
					placeholder={ DEFAULT_CSS }
					isKeyframe
				/>
				<p className="spectra-gbs-section__hint">
					{ __( 'Write stops without the outer @keyframes wrapper.', 'spectra-blocks' ) }
				</p>
			</div>

			{/* Usage hint */}
			<div className="spectra-gbs-class-editor__bucket">
				<label className="spectra-gbs-section__label">{ __( 'Usage', 'spectra-blocks' ) }</label>
				<div className="spectra-gbs-keyframe-usage">
					<code className="spectra-gbs-keyframe-usage__code">
						animation: { getAnimationShorthand() };
					</code>
					<button
						type="button"
						className="spectra-gbs-btn--secondary spectra-gbs-keyframe-usage__copy"
						onClick={ handleCopy }
					>
						{ copied ? __( 'Copied!', 'spectra-blocks' ) : __( 'Copy', 'spectra-blocks' ) }
					</button>
				</div>
			</div>

			<div className="spectra-gbs-class-editor__actions spectra-gbs-panel__footer">
				<button type="button" className="spectra-gbs-btn--secondary" onClick={ onCancel } disabled={ saving }>
					{ __( 'Cancel', 'spectra-blocks' ) }
				</button>
				<button
					type="button"
					className="spectra-gbs-btn--primary"
					onClick={ () => onSave( name, { css: body, meta: localMeta } ) }
					disabled={ saving || ! body.trim() }
				>
					{ saving ? __( 'Saving…', 'spectra-blocks' ) : __( 'Save keyframe', 'spectra-blocks' ) }
				</button>
			</div>
		</div>
	);
};

/**
 * KeyframesPanel component.
 *
 * @since x.x.x
 *
 * @param {Object}   root0                Component props.
 * @param {Function} root0.onStatusChange Callback invoked when the panel's save/dirty status changes.
 * @return {Element}
 */
const KeyframesPanel = ( { onStatusChange } ) => {
	const { keyframes, loading, saving, saveKeyframe, deleteKeyframe } = useKeyframes();

	useEffect( () => {
		onStatusChange?.( { saving, previewing: false, dirty: false } );
	}, [ saving ] ); // eslint-disable-line react-hooks/exhaustive-deps
	const [ editing, setEditing ]             = useState( null );
	const [ newName, setNewName ]             = useState( '' );
	const [ newMeta, setNewMeta ]             = useState( { ...DEFAULT_META } );
	const [ nameError, setNameError ]         = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( null );

	const names = Object.keys( keyframes );

	const handleAdd = useCallback( async () => {
		const n = newName.trim().replace( /\s+/g, '-' );
		if ( ! n ) { setNameError( __( 'Name required.', 'spectra-blocks' ) ); return; }
		if ( ! /^[a-zA-Z][a-zA-Z0-9_-]*$/.test( n ) ) { setNameError( __( 'Must start with a letter; letters, digits, hyphens, underscores only.', 'spectra-blocks' ) ); return; }
		if ( keyframes[ n ] ) { setNameError( __( 'A keyframe with this name already exists.', 'spectra-blocks' ) ); return; }
		setNameError( '' );
		await saveKeyframe( n, { css: DEFAULT_CSS, meta: newMeta } );
		setNewName( '' );
		setNewMeta( { ...DEFAULT_META } );
		setEditing( n );
		regenerateEditorCSS();
	}, [ newName, newMeta, keyframes, saveKeyframe ] );

	const handleSave = useCallback( async ( name, data ) => {
		await saveKeyframe( name, data );
		setEditing( null );
		regenerateEditorCSS();
	}, [ saveKeyframe ] );

	const handleDelete = useCallback( async ( name ) => {
		await deleteKeyframe( name );
		setConfirmDelete( null );
		if ( editing === name ) {setEditing( null );}
		regenerateEditorCSS();
	}, [ deleteKeyframe, editing ] );

	if ( loading ) {
		return <div className="spectra-gbs-panel__loading"><Spinner /><span>{ __( 'Loading keyframes…', 'spectra-blocks' ) }</span></div>;
	}

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<div className="spectra-gbs-section spectra-gbs-section--keyframes">

					<div className="spectra-gbs-custom-css__info">
						<p className="spectra-gbs-custom-css__info-text">
							{ __( 'Named keyframes are injected into the site stylesheet and can be used via animation-name or the animate-{name} utility class on any block.', 'spectra-blocks' ) }
						</p>
					</div>

					{/* Existing keyframes */}
					{ names.length > 0 && (
						<div className="spectra-gbs-section__field">
							<label className="spectra-gbs-section__label">
								{ sprintf( __( '%d keyframe(s)', 'spectra-blocks' ), names.length ) }
							</label>
							<div className="spectra-gbs-class-list">
								{ names.map( ( name ) => (
									<div key={ name } className={ `spectra-gbs-class-row${ editing === name ? ' is-editing' : '' }` }>
										{ editing === name ? (
											<KeyframeEditor
												name={ name }
												css={ keyframes[ name ]?.css ?? '' }
												meta={ keyframes[ name ]?.meta ?? DEFAULT_META }
												onSave={ handleSave }
												onCancel={ () => setEditing( null ) }
												saving={ saving }
											/>
										) : (
											<>
												<code className="spectra-gbs-class-row__name">@keyframes { name }</code>
												<div className="spectra-gbs-class-row__actions">
													<button className="spectra-gbs-class-row__btn" onClick={ () => setEditing( name ) } title={ __( 'Edit', 'spectra-blocks' ) }><Icon icon={ editIcon } size={ 16 } /></button>
													{ confirmDelete === name ? (
														<>
															<button className="spectra-gbs-class-row__btn is-danger" onClick={ () => handleDelete( name ) } disabled={ saving }>{ __( 'Confirm', 'spectra-blocks' ) }</button>
															<button className="spectra-gbs-class-row__btn" onClick={ () => setConfirmDelete( null ) }>{ __( 'Cancel', 'spectra-blocks' ) }</button>
														</>
													) : (
														<button className="spectra-gbs-class-row__btn is-danger" onClick={ () => setConfirmDelete( name ) } title={ __( 'Delete', 'spectra-blocks' ) }>✕</button>
													) }
												</div>
											</>
										) }
									</div>
								) ) }
							</div>
						</div>
					) }

					{ names.length === 0 && (
						<div className="spectra-gbs-section__empty">{ __( 'No keyframes yet. Create one below.', 'spectra-blocks' ) }</div>
					) }

					{/* Create new keyframe — name + animation settings + Create button */}
					{ ! editing && (
						<div className="spectra-gbs-section__field">
							<label className="spectra-gbs-section__label">{ __( 'New keyframe', 'spectra-blocks' ) }</label>
							<div className="spectra-gbs-class-new">
								<input
									className="spectra-gbs-var-value-input"
									type="text"
									value={ newName }
									onChange={ ( e ) => { setNewName( e.target.value ); setNameError( '' ); } }
									placeholder="fadeIn"
									onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) {handleAdd();} } }
									aria-label={ __( 'Keyframe animation name', 'spectra-blocks' ) }
									style={ { flex: 1 } }
								/>
							</div>
							<AnimationMetaFields meta={ newMeta } onChange={ setNewMeta } />
							<div className="spectra-gbs-keyframe-create-footer">
								<button
									className="spectra-gbs-btn--primary"
									onClick={ handleAdd }
									disabled={ saving || ! newName.trim() }
								>
									{ saving ? __( 'Creating…', 'spectra-blocks' ) : __( 'Create', 'spectra-blocks' ) }
								</button>
							</div>
							{ nameError && <p className="spectra-gbs-class-new__error">{ nameError }</p> }
						</div>
					) }
				</div>
			</div>
		</div>
	);
};

export default KeyframesPanel;
