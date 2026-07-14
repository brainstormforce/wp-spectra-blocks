/**
 * CheatSheetPanel — searchable reference of all GBS utility classes and CSS variables.
 *
 * Data is loaded from window.spectra_editor_gs.json_data, localized by
 * the Pro plugin's GlobalStyles extension. When Pro is inactive the panel
 * shows an empty state.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useMemo, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// ─── Static data ──────────────────────────────────────────────────────────────

const BADGE_COLORS = {
	border:     '#ec4899',
	colors:     '#a855f7',
	display:    '#6366f1',
	filters:    '#0ea5e9',
	typography: '#14b8a6',
	sizing:     '#22c55e',
	spacing:    '#eab308',
	variables:  '#64748b',
};

const BATCH_SIZE = 50;

// ─── Helpers ──────────────────────────────────────────────────────────────────

const cap = ( s ) => s ? s.charAt( 0 ).toUpperCase() + s.slice( 1 ) : s;

const toWords = ( slug ) =>
	slug.split( '--' ).map( ( p ) => p.replace( /-/g, ' ' ) ).join( ' ' );

const formatItem = ( item ) => {
	const clean  = item.name.replace( /\[.*?\]/g, '' );
	const tokens = clean.split( '--' );
	const main   = tokens.shift();
	const sub    = `${ toWords( tokens.join( '--' ) ) } ${ toWords( main ) }`.trim();
	const tags   = Array.isArray( item.tags )
		? item.tags
		: clean.replace( /--/g, '-' ).split( '-' );
	return {
		...item,
		formattedName: item.category === 'variables' ? `var(--${ item.name })` : `.${ clean }`,
		subtext: sub,
		tags,
	};
};

const copyText = async ( text ) => {
	try {
		if ( navigator.clipboard && window.isSecureContext ) {
			await navigator.clipboard.writeText( text );
		} else {
			const el = document.createElement( 'textarea' );
			el.value = text;
			Object.assign( el.style, { position: 'fixed', top: 0, left: 0, width: '1px', height: '1px', opacity: 0 } );
			document.body.appendChild( el );
			el.select();
			document.execCommand( 'copy' );
			document.body.removeChild( el );
		}
		return true;
	} catch {
		return false;
	}
};

// ─── Sub-components ───────────────────────────────────────────────────────────

const CopyIcon = () => (
	<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
		<rect x="9" y="9" width="13" height="13" rx="2" />
		<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
	</svg>
);

const CheckIcon = () => (
	<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
		<polyline points="20 6 9 17 4 12" />
	</svg>
);

/**
 * Single cheat-sheet entry row.
 *
 * @param {Object}   root0
 * @param {Object}   root0.item       The class/token data item to render.
 * @param {Function} root0.onTagClick Callback invoked when a tag is clicked.
 * @since x.x.x
 * @return {Element}
 */
const ClassCard = ( { item, onTagClick } ) => {
	const [ copied, setCopied ] = useState( false );

	const handleCopy = useCallback( async () => {
		const text = item.category === 'variables' ? item.formattedName : item.name;
		const ok = await copyText( text );
		if ( ok ) {
			setCopied( true );
			setTimeout( () => setCopied( false ), 1500 );
		}
	}, [ item ] );

	return (
		<div className="spectra-cs-card">
			<div className="spectra-cs-card__top">
				<div className="spectra-cs-card__left">
					<code className="spectra-cs-card__name">{ item.formattedName }</code>
					{ ( () => {
						const catColor = BADGE_COLORS[ item.category ] ?? '#64748b';
						return (
							<span
								className="spectra-cs-card__badge"
								style={ { color: catColor, background: `${ catColor }1f` } }
							>
								{ cap( item.category ) }
							</span>
						);
					} )() }
				</div>
				<button
					type="button"
					className={ `spectra-cs-card__copy${ copied ? ' is-copied' : '' }` }
					onClick={ handleCopy }
					aria-label={ __( 'Copy', 'spectra-blocks' ) }
					title={ __( 'Copy to clipboard', 'spectra-blocks' ) }
				>
					{ copied ? <CheckIcon /> : <CopyIcon /> }
				</button>
			</div>
			{ ( item.title || item.subtext ) && (
				<p className="spectra-cs-card__title">{ item.title || item.subtext }</p>
			) }
			{ item.description && (
				<p className="spectra-cs-card__desc">{ item.description }</p>
			) }
			{ item.tags.length > 0 && (
				<div className="spectra-cs-card__tags">
					{ item.tags.map( ( tag ) => (
						<button
							key={ tag }
							type="button"
							className="spectra-cs-card__tag"
							onClick={ () => onTagClick( tag ) }
						>
							{ tag }
						</button>
					) ) }
				</div>
			) }
		</div>
	);
};

// ─── Main panel ───────────────────────────────────────────────────────────────

/**
 * CheatSheetPanel component.
 *
 * @since x.x.x
 *
 * @return {Element} Cheat sheet panel.
 */
const CheatSheetPanel = () => {
	// Read at render time so the Pro-localized global is available.
	const allClasses = useMemo( () => window?.spectra_editor_gs?.json_data ?? [], [] );
	const items      = useMemo( () => allClasses.map( formatItem ), [ allClasses ] );
	const categories = useMemo( () => Array.from( new Set( items.map( ( i ) => i.category ) ) ), [ items ] );

	const [ search,         setSearch         ] = useState( '' );
	const [ activeCategory, setActiveCategory ] = useState( '' );
	const [ activeTags,     setActiveTags     ] = useState( [] );
	const [ page,           setPage           ] = useState( 1 );

	const filtered = useMemo( () => {
		return items.filter( ( item ) => {
			if ( activeCategory && item.category !== activeCategory ) { return false; }
			if ( activeTags.length && ! activeTags.every( ( t ) => item.tags.includes( t ) ) ) { return false; }
			if ( search ) {
				const hay = `${ item.formattedName } ${ item.subtext } ${ item.description ?? '' } ${ item.tags.join( ' ' ) } ${ item.category }`.toLowerCase();
				if ( ! hay.includes( search.toLowerCase() ) ) { return false; }
			}
			return true;
		} );
	}, [ search, activeCategory, activeTags, items ] );

	const totalPages = Math.ceil( filtered.length / BATCH_SIZE ) || 1;
	const visible    = filtered.slice( ( page - 1 ) * BATCH_SIZE, page * BATCH_SIZE );

	const resetPage = () => setPage( 1 );

	const handleSearch = ( e ) => { setSearch( e.target.value ); resetPage(); };

	const toggleCategory = ( cat ) => {
		setActiveCategory( ( prev ) => prev === cat ? '' : cat );
		resetPage();
	};

	const toggleTag = ( tag ) => {
		setActiveTags( ( prev ) =>
			prev.includes( tag ) ? prev.filter( ( t ) => t !== tag ) : [ ...prev, tag ]
		);
		resetPage();
	};

	const clearTag = ( tag ) => {
		setActiveTags( ( prev ) => prev.filter( ( t ) => t !== tag ) );
	};

	// Empty state when Pro data is unavailable.
	if ( ! allClasses.length ) {
		return (
			<div className="spectra-cs spectra-cs--empty">
				<p className="spectra-cs__empty-msg">
					{ __( 'Activate Spectra Blocks Pro to access the Cheat Sheet.', 'spectra-blocks' ) }
				</p>
			</div>
		);
	}

	return (
		<div className="spectra-cs">
			{/* ── Left: filters ───────────────────────────────────────── */}
			<div className="spectra-cs__filter-col">
				{/* Search */}
				<div className="spectra-cs__search-wrap">
					<svg className="spectra-cs__search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
						<circle cx="11" cy="11" r="8" />
						<line x1="21" y1="21" x2="16.65" y2="16.65" />
					</svg>
					<input
						type="search"
						className="spectra-cs__search"
						placeholder={ __( 'Search classes & variables…', 'spectra-blocks' ) }
						value={ search }
						onChange={ handleSearch }
					/>
				</div>

				{/* Category buttons */}
				<div className="spectra-cs__categories">
					<p className="spectra-cs__cat-label">{ __( 'Category', 'spectra-blocks' ) }</p>
					{ categories.map( ( cat ) => (
						<button
							key={ cat }
							type="button"
							className={ `spectra-cs__cat-btn${ activeCategory === cat ? ' is-active' : '' }` }
							onClick={ () => toggleCategory( cat ) }
						>
							<span
								className="spectra-cs__cat-dot"
								style={ { background: BADGE_COLORS[ cat ] ?? '#64748b' } }
							/>
							{ cap( cat ) }
						</button>
					) ) }
					{ activeCategory && (
						<button
							type="button"
							className="spectra-cs__cat-clear"
							onClick={ () => { setActiveCategory( '' ); resetPage(); } }
						>
							{ __( 'Clear filter', 'spectra-blocks' ) }
						</button>
					) }
				</div>
			</div>

			{/* ── Right: item list ────────────────────────────────────── */}
			<div className="spectra-cs__list-col">
				{/* Active filters strip */}
				{ ( search || activeTags.length > 0 ) && (
					<div className="spectra-cs__filters-strip">
						{ search && (
							<button
								type="button"
								className="spectra-cs__filter-chip"
								onClick={ () => { setSearch( '' ); resetPage(); } }
							>
								{ __( 'Search:', 'spectra-blocks' ) } { search } ×
							</button>
						) }
						{ activeTags.map( ( t ) => (
							<button
								key={ t }
								type="button"
								className="spectra-cs__filter-chip"
								onClick={ () => clearTag( t ) }
							>
								{ t } ×
							</button>
						) ) }
					</div>
				) }

				{/* Result count */}
				<p className="spectra-cs__count">
					{ filtered.length } { __( 'entries', 'spectra-blocks' ) }
				</p>

				{/* Cards */}
				<div className="spectra-cs__cards">
					{ visible.length === 0 ? (
						<p className="spectra-cs__no-results">
							{ __( 'No matches found.', 'spectra-blocks' ) }
						</p>
					) : (
						visible.map( ( item, i ) => (
							<ClassCard key={ `${ item.name }-${ i }` } item={ item } onTagClick={ toggleTag } />
						) )
					) }
				</div>

				{/* Pagination */}
				{ totalPages > 1 && (
					<div className="spectra-cs__pagination">
						<button
							type="button"
							className="spectra-cs__page-btn"
							disabled={ page === 1 }
							onClick={ () => setPage( ( p ) => p - 1 ) }
						>
							{ __( '← Prev', 'spectra-blocks' ) }
						</button>
						<span className="spectra-cs__page-info">{ page } / { totalPages }</span>
						<button
							type="button"
							className="spectra-cs__page-btn"
							disabled={ page === totalPages }
							onClick={ () => setPage( ( p ) => p + 1 ) }
						>
							{ __( 'Next →', 'spectra-blocks' ) }
						</button>
					</div>
				) }
			</div>
		</div>
	);
};

export default CheatSheetPanel;
