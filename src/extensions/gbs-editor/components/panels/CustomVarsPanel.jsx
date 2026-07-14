/**
 * CustomVarsPanel — create and manage custom CSS variables.
 *
 * Variables are stored as { "--name": "value" } and emitted as a :root {}
 * block in the site stylesheet. They can then be used in any CSS as
 * `var(--name)` — useful for design tokens that blocks can reference.
 *
 * @since x.x.x
 */

import { useState, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useCustomVars }        from '../../hooks/useCustomVars.js';
import { regenerateEditorCSS, refreshCustomVarsCSS }  from '../../utils/liveVars.js';

/**
 * Parse a bulk CSS variable text block into a { '--name': 'value' } map.
 * Accepts lines like: `--my-color: #f00;` or `--my-color: #f00`
 *
 * @since x.x.x
 *
 * @param {string} text Raw textarea content.
 * @return {Object} Parsed variable map.
 */
function parseBulkVars( text ) {
	const result = {};
	for ( const raw of text.split( '\n' ) ) {
		const line = raw.trim().replace( /;$/, '' );
		const match = line.match( /^(--[\w-]+)\s*:\s*(.+)$/ );
		if ( match ) {
			const name  = match[ 1 ].trim();
			const value = match[ 2 ].trim();
			if ( name && value ) {
				result[ name ] = value;
			}
		}
	}
	return result;
}

/**
 * Serialize a { '--name': 'value' } map to a human-readable CSS block.
 *
 * @since x.x.x
 *
 * @param {Object} vars Variable map.
 * @return {string} Multi-line text.
 */
function serializeVars( vars ) {
	return Object.entries( vars ).map( ( [ k, v ] ) => `${ k }: ${ v };` ).join( '\n' );
}

/**
 * CustomVarsPanel component.
 *
 * @since x.x.x
 *
 * @return {Element}
 */
const CustomVarsPanel = () => {
	const { variables, loading, saving, save } = useCustomVars();
	const [ draft, setDraft ]         = useState( null );
	const [ newName, setNewName ]     = useState( '' );
	const [ newValue, setNewValue ]   = useState( '' );
	const [ nameError, setNameError ] = useState( '' );
	const [ bulkMode, setBulkMode ]   = useState( false );
	const [ bulkText, setBulkText ]   = useState( '' );

	const active = draft ?? variables;

	const handleOpenBulk = () => {
		setBulkText( serializeVars( active ) );
		setBulkMode( true );
	};

	const handleApplyBulk = () => {
		const parsed = parseBulkVars( bulkText );
		setDraft( parsed );
		setBulkMode( false );
	};

	const handleCancelBulk = () => {
		setBulkMode( false );
	};

	const handleAdd = useCallback( () => {
		const name = newName.trim().replace( /^--/, '' );
		if ( ! name ) { setNameError( __( 'Name required.', 'spectra-blocks' ) ); return; }
		if ( ! /^[a-zA-Z0-9_-]+$/.test( name ) ) { setNameError( __( 'Letters, digits, hyphens and underscores only.', 'spectra-blocks' ) ); return; }
		if ( ! newValue.trim() ) { setNameError( __( 'Value required.', 'spectra-blocks' ) ); return; }
		const fullName = `--${ name }`;
		setNameError( '' );
		setDraft( { ...active, [ fullName ]: newValue.trim() } );
		setNewName( '' );
		setNewValue( '' );
	}, [ newName, newValue, active ] );

	const handleValueChange = ( varName, value ) => {
		setDraft( { ...active, [ varName ]: value } );
	};

	const handleDelete = ( varName ) => {
		const next = { ...active };
		delete next[ varName ];
		setDraft( next );
	};

	const handleSave = useCallback( async () => {
		await save( active );
		setDraft( null );
		refreshCustomVarsCSS();
		regenerateEditorCSS();
	}, [ save, active ] );

	const isDirty = draft !== null;

	const handleCancel = () => setDraft( null );
	const entries = Object.entries( active );

	if ( loading ) {
		return <div className="spectra-gbs-panel__loading"><Spinner /><span>{ __( 'Loading variables…', 'spectra-blocks' ) }</span></div>;
	}

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<div className="spectra-gbs-section spectra-gbs-section--css-vars">

					<div className="spectra-gbs-custom-css__info">
						<p className="spectra-gbs-custom-css__info-text">
							{ __( 'Custom variables are emitted as a :root {} block so any block or CSS rule can use them with var(--name).', 'spectra-blocks' ) }
						</p>
					</div>

					{ bulkMode ? (
						<div className="spectra-gbs-section__field">
							<div className="spectra-gbs-var-bulk-header">
								<label className="spectra-gbs-section__label">
									{ __( 'Bulk edit', 'spectra-blocks' ) }
								</label>
								<button className="spectra-gbs-btn--secondary spectra-gbs-btn--sm" onClick={ handleCancelBulk }>
									{ __( 'Cancel', 'spectra-blocks' ) }
								</button>
							</div>
							<textarea
								className="spectra-gbs-var-bulk-textarea"
								value={ bulkText }
								onChange={ ( e ) => setBulkText( e.target.value ) }
								rows={ Math.max( 8, bulkText.split( '\n' ).length + 2 ) }
								placeholder={ '--my-color: #6431f6;\n--my-spacing: 1.5rem;' }
								spellCheck={ false }
								aria-label={ __( 'Bulk CSS variable declarations', 'spectra-blocks' ) }
							/>
							<p className="spectra-gbs-section__hint">
								{ __( 'One --name: value; per line. Existing variables will be replaced.', 'spectra-blocks' ) }
							</p>
							<button className="spectra-gbs-btn--primary" onClick={ handleApplyBulk }>
								{ __( 'Apply', 'spectra-blocks' ) }
							</button>
						</div>
					) : (
						<>
							{/* Variable list */}
							{ entries.length > 0 && (
								<div className="spectra-gbs-section__field">
									<div className="spectra-gbs-var-bulk-header">
										<label className="spectra-gbs-section__label">
											{ sprintf( __( '%d variable(s)', 'spectra-blocks' ), entries.length ) }
										</label>
										<button className="spectra-gbs-btn--secondary spectra-gbs-btn--sm" onClick={ handleOpenBulk }>
											{ __( 'Bulk edit', 'spectra-blocks' ) }
										</button>
									</div>
									<div className="spectra-gbs-var-list">
										{ entries.map( ( [ name, value ] ) => (
											<div key={ name } className="spectra-gbs-var-entry">
												<code className="spectra-gbs-var-entry__name">{ name }</code>
												<input
													className="spectra-gbs-var-entry__value"
													type="text"
													value={ value }
													onChange={ ( e ) => handleValueChange( name, e.target.value ) }
													aria-label={ sprintf( __( 'Value for %s', 'spectra-blocks' ), name ) }
												/>
												<button
													className="spectra-gbs-class-row__btn is-danger"
													onClick={ () => handleDelete( name ) }
													title={ __( 'Delete', 'spectra-blocks' ) }
												>✕</button>
											</div>
										) ) }
									</div>
								</div>
							) }

							{ entries.length === 0 && (
								<div className="spectra-gbs-section__empty">
									{ __( 'No custom variables yet. Add one below or use bulk edit.', 'spectra-blocks' ) }
								</div>
							) }

							{/* Add new variable */}
							<div className="spectra-gbs-section__field">
								<div className="spectra-gbs-var-bulk-header">
									<label className="spectra-gbs-section__label">{ __( 'Add variable', 'spectra-blocks' ) }</label>
									{ entries.length === 0 && (
										<button className="spectra-gbs-btn--secondary spectra-gbs-btn--sm" onClick={ handleOpenBulk }>
											{ __( 'Bulk edit', 'spectra-blocks' ) }
										</button>
									) }
								</div>
								<div className="spectra-gbs-var-add">
									<span className="spectra-gbs-class-new__prefix">--</span>
									<input
										className="spectra-gbs-var-value-input"
										type="text"
										value={ newName }
										onChange={ ( e ) => { setNewName( e.target.value ); setNameError( '' ); } }
										placeholder="brand-color"
										onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) {document.getElementById( 'spectra-gbs-var-value' )?.focus();} } }
										aria-label={ __( 'Variable name', 'spectra-blocks' ) }
									/>
									<input
										id="spectra-gbs-var-value"
										className="spectra-gbs-var-value-input"
										type="text"
										value={ newValue }
										onChange={ ( e ) => setNewValue( e.target.value ) }
										placeholder="#6431f6"
										onKeyDown={ ( e ) => { if ( e.key === 'Enter' ) {handleAdd();} } }
										aria-label={ __( 'Variable value', 'spectra-blocks' ) }
									/>
									<button className="spectra-gbs-btn--secondary" onClick={ handleAdd } disabled={ ! newName.trim() || ! newValue.trim() }>
										{ __( 'Add', 'spectra-blocks' ) }
									</button>
								</div>
								{ nameError && <p className="spectra-gbs-class-new__error">{ nameError }</p> }
								<p className="spectra-gbs-section__hint">
									{ __( 'The -- prefix is added automatically. Values can be any valid CSS value.', 'spectra-blocks' ) }
								</p>
							</div>
						</>
					) }
				</div>
			</div>
			{ isDirty && (
				<div className="spectra-gbs-panel__footer">
					<button className="spectra-gbs-btn--secondary" onClick={ handleCancel } disabled={ saving }>
						{ __( 'Cancel', 'spectra-blocks' ) }
					</button>
					<button className="spectra-gbs-btn--primary" onClick={ handleSave } disabled={ saving }>
						{ saving ? __( 'Saving…', 'spectra-blocks' ) : __( 'Save', 'spectra-blocks' ) }
					</button>
				</div>
			) }
		</div>
	);
};

export default CustomVarsPanel;
