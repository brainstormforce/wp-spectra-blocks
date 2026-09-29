/**
 * CustomVarsPanel — create and manage custom CSS variables.
 *
 * Variables are stored as { "--name": "value" } and emitted as a :root {}
 * block in the site stylesheet. They can then be used in any CSS as
 * `var(--name)` — useful for design tokens that blocks can reference.
 *
 * @since 1.0.9
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useCustomVars }        from '../../hooks/useCustomVars.js';
import { regenerateEditorCSS, refreshCustomVarsCSS }  from '../../utils/liveVars.js';
import { gbsNotices }             from '../../notices/gbsNotices.js';
import {
	validateName,
	validateCustomPropertyValue,
	parseVarDeclarations,
	hasBlockingIssue,
}                                 from '../../utils/validators.js';

/**
 * Serialize a { '--name': 'value' } map to a human-readable CSS block.
 *
 * @since 1.0.9
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
 * @since 1.0.9
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

	// Per-row issues for the saved/drafted variables, recomputed as they are
	// edited. Inline edits used to bypass validation entirely — only the "Add"
	// form checked anything — so a good variable could be edited into a broken
	// one and saved without a word.
	const rowIssues = useMemo( () => {
		const issues = {};
		Object.entries( active ).forEach( ( [ name, value ] ) => {
			const valueIssue = validateCustomPropertyValue( value );
			if ( valueIssue.level ) {
				issues[ name ] = valueIssue;
			}
		} );
		return issues;
	}, [ active ] );

	const blockingRows = hasBlockingIssue( Object.values( rowIssues ) );

	// Issues for the bulk textarea, so Apply can refuse a block that would
	// silently drop lines.
	const bulkIssues = useMemo(
		() => ( bulkMode ? parseVarDeclarations( bulkText ).issues : [] ),
		[ bulkMode, bulkText ]
	);
	const blockingBulk = hasBlockingIssue( bulkIssues );

	const handleOpenBulk = () => {
		setBulkText( serializeVars( active ) );
		setBulkMode( true );
	};

	const handleApplyBulk = () => {
		const { variables: parsed, issues } = parseVarDeclarations( bulkText );
		// Errors are surfaced under the textarea and the button is disabled, so
		// this is only a guard against a stale click.
		if ( hasBlockingIssue( issues ) ) {
			return;
		}
		setDraft( parsed );
		setBulkMode( false );
	};

	const handleCancelBulk = () => {
		setBulkMode( false );
	};

	const handleAdd = useCallback( () => {
		const name = newName.trim().replace( /^--/, '' );

		// Duplicates previously slipped through and silently overwrote the
		// existing variable via the object spread below.
		const taken = Object.keys( active ).map( ( k ) => k.replace( /^--/, '' ) );
		const nameProblem = validateName( name, { kind: 'cssVar', taken } );
		if ( nameProblem ) {
			setNameError( nameProblem );
			return;
		}

		const valueIssue = validateCustomPropertyValue( newValue );
		if ( 'error' === valueIssue.level ) {
			setNameError( valueIssue.message );
			return;
		}

		setNameError( '' );
		setDraft( { ...active, [ `--${ name }` ]: newValue.trim() } );
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
		const count = Object.keys( active ).length;
		try {
			await save( active );
		} catch ( err ) {
			gbsNotices.error(
				err?.message || __( 'Could not save your CSS variables.', 'spectra-blocks' )
			);
			// Keep the draft so the user's edits survive a failed save.
			return;
		}
		setDraft( null );
		refreshCustomVarsCSS();
		regenerateEditorCSS();
		// A save that empties the list is a removal, not a save of nothing —
		// "0 CSS variables saved" reads like the save silently failed.
		gbsNotices.success(
			count === 0
				? __( 'All custom CSS variables removed.', 'spectra-blocks' )
				: sprintf(
					/* translators: %d: number of saved CSS variables. */
					_n( '%d CSS variable saved.', '%d CSS variables saved.', count, 'spectra-blocks' ),
					count
				)
		);
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
							{ bulkIssues.length > 0 && (
								<ul className="spectra-gbs-issues" aria-label={ __( 'Problems in the bulk edit', 'spectra-blocks' ) }>
									{ bulkIssues.map( ( issue ) => (
										<li
											key={ `${ issue.line }-${ issue.message }` }
											className={ `spectra-gbs-issue is-${ issue.level }` }
										>
											<span className="spectra-gbs-issue__line">
												{ sprintf(
													/* translators: %d: line number in the bulk edit textarea. */
													__( 'Line %d', 'spectra-blocks' ),
													issue.line
												) }
											</span>
											{ issue.message }
										</li>
									) ) }
								</ul>
							) }
							<button
								className="spectra-gbs-btn--primary"
								onClick={ handleApplyBulk }
								disabled={ blockingBulk }
							>
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
										{ entries.map( ( [ name, value ] ) => {
											const issue = rowIssues[ name ];
											return (
												<div key={ name } className="spectra-gbs-var-row">
													<div className="spectra-gbs-var-entry">
														<code className="spectra-gbs-var-entry__name">{ name }</code>
														<input
															className={ `spectra-gbs-var-entry__value${ issue ? ` is-${ issue.level }` : '' }` }
															type="text"
															value={ value }
															onChange={ ( e ) => handleValueChange( name, e.target.value ) }
															aria-label={ sprintf( __( 'Value for %s', 'spectra-blocks' ), name ) }
															aria-invalid={ issue && 'error' === issue.level ? 'true' : undefined }
															aria-describedby={ issue ? `spectra-gbs-var-issue-${ name.replace( /[^a-z0-9]/gi, '-' ) }` : undefined }
														/>
														<button
															className="spectra-gbs-class-row__btn is-danger"
															onClick={ () => handleDelete( name ) }
															title={ __( 'Delete', 'spectra-blocks' ) }
														>✕</button>
													</div>
													{ issue && (
														<p
															id={ `spectra-gbs-var-issue-${ name.replace( /[^a-z0-9]/gi, '-' ) }` }
															className={ `spectra-gbs-issue is-${ issue.level }` }
														>
															{ issue.message }
														</p>
													) }
												</div>
											);
										} ) }
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
									{ /* Deliberately NOT disabled on empty input: handleAdd names the
									     actual problem, which teaches more than a dead button. */ }
									<button className="spectra-gbs-btn--secondary" onClick={ handleAdd }>
										{ __( 'Add', 'spectra-blocks' ) }
									</button>
								</div>
								{ nameError && <p className="spectra-gbs-class-new__error">{ nameError }</p> }
								<p className="spectra-gbs-section__hint">
									{ __( 'The -- prefix is added automatically. Values can be any valid CSS value — semicolons and braces are not allowed.', 'spectra-blocks' ) }
								</p>
							</div>
						</>
					) }
				</div>
			</div>
			{ isDirty && (
				<div className="spectra-gbs-panel__footer">
					{ blockingRows && (
						<span className="spectra-gbs-issue is-error" role="status">
							{ __( 'Fix the highlighted values before saving.', 'spectra-blocks' ) }
						</span>
					) }
					<button className="spectra-gbs-btn--secondary" onClick={ handleCancel } disabled={ saving }>
						{ __( 'Cancel', 'spectra-blocks' ) }
					</button>
					<button
						className="spectra-gbs-btn--primary"
						onClick={ handleSave }
						disabled={ saving || blockingRows }
					>
						{ saving ? __( 'Saving…', 'spectra-blocks' ) : __( 'Save', 'spectra-blocks' ) }
					</button>
				</div>
			) }
		</div>
	);
};

export default CustomVarsPanel;
