/**
 * VariablesPanel — read-only browser of all computed --spectra-* tokens.
 *
 * Groups tokens by category (colors, spacing, radius, shadows, motion)
 * and displays each as a `--spectra-{name}` → computed value row with
 * a one-click copy-to-clipboard button.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { useGBSComputed } from '../../hooks/useGBSComputed.js';

/**
 * Predicates that classify a token name into a display category.
 * Checked in order — first match wins.
 *
 * @since x.x.x
 */
const CATEGORIES = [
	{
		id: 'color-vars',
		label: __( 'Color tokens', 'spectra-blocks' ),
		match: ( k ) => /^(neutral|primary|secondary|accent|success|error|info|warning|opacity|white|transparent)/.test( k ),
	},
	{
		id: 'spacing-vars',
		label: __( 'Spacing tokens', 'spectra-blocks' ),
		match: ( k ) => /^(space-|content-max|section-|card-padding|card-gap)/.test( k ),
	},
	{
		id: 'radius-vars',
		label: __( 'Radius tokens', 'spectra-blocks' ),
		match: ( k ) => /^radius-/.test( k ),
	},
	{
		id: 'shadow-vars',
		label: __( 'Shadow tokens', 'spectra-blocks' ),
		match: ( k ) => /^shadow-/.test( k ),
	},
	{
		id: 'motion-vars',
		label: __( 'Motion tokens', 'spectra-blocks' ),
		match: ( k ) => /^(transition-|ease-|entrance-)/.test( k ),
	},
];

/**
 * Colour swatch shown next to hex / rgba values.
 *
 * @since x.x.x
 *
 * @param {string} value CSS colour value.
 * @return {Element|null}
 */
const ColourDot = ( { value } ) => {
	const isColor = /^#|^rgb|^hsl|^oklch/.test( value );
	if ( ! isColor ) {return null;}
	return (
		<span
			className="spectra-gbs-var__color-dot"
			style={ { backgroundColor: value } }
			aria-hidden="true"
		/>
	);
};

/**
 * Single token row.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {string} props.name  Token name (without --spectra- prefix).
 * @param {string} props.value Computed value.
 * @return {Element}
 */
const TokenRow = ( { name, value } ) => {
	const [ copied, setCopied ] = useState( false );

	const handleCopy = useCallback( async () => {
		const text = `--spectra-${ name }`;
		try {
			if ( navigator.clipboard && window.isSecureContext ) {
				await navigator.clipboard.writeText( text );
			} else {
				const ta = document.createElement( 'textarea' );
				ta.value = text;
				ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
				document.body.appendChild( ta );
				ta.select();
				document.execCommand( 'copy' ); // eslint-disable-line
				document.body.removeChild( ta );
			}
			setCopied( true );
			setTimeout( () => setCopied( false ), 1500 );
		} catch ( e ) {
			// silent fail
		}
	}, [ name ] );

	return (
		<div className="spectra-gbs-var__row">
			<ColourDot value={ value } />
			<code className="spectra-gbs-var__name">--spectra-{ name }</code>
			<code className="spectra-gbs-var__value">{ value }</code>
			<button
				type="button"
				className={ `spectra-gbs-var__copy${ copied ? ' is-copied' : '' }` }
				onClick={ handleCopy }
				aria-label={ sprintf( __( 'Copy %s', 'spectra-blocks' ), `--spectra-${ name }` ) }
				title={ __( 'Copy variable name', 'spectra-blocks' ) }
			>
				{ copied ? '✓' : '⧉' }
			</button>
		</div>
	);
};

/**
 * VariablesPanel component.
 *
 * @since x.x.x
 *
 * @param {Object} props
 * @param {string} props.sectionId Active sidebar section id.
 * @return {Element}
 */
const VariablesPanel = ( { sectionId } ) => {
	const { computed, loading } = useGBSComputed();
	const [ search, setSearch ] = useState( '' );

	if ( loading ) {
		return (
			<div className="spectra-gbs-panel__loading">
				<Spinner />
				<span>{ __( 'Loading tokens…', 'spectra-blocks' ) }</span>
			</div>
		);
	}

	const tokens = computed?.tokens ?? {};
	const category = CATEGORIES.find( ( c ) => c.id === sectionId ) ?? CATEGORIES[ 0 ];
	const q = search.trim().toLowerCase();

	const rows = Object.entries( tokens )
		.filter( ( [ k ] ) => category.match( k ) )
		.filter( ( [ k, v ] ) => ! q || k.includes( q ) || v.toLowerCase().includes( q ) )
		.sort( ( [ a ], [ b ] ) => a.localeCompare( b ) );

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<div className="spectra-gbs-section spectra-gbs-section--variables">

					{/* Category heading + search */}
					<div className="spectra-gbs-var__toolbar">
						<h3 className="spectra-gbs-var__heading">{ category.label }</h3>
						<span className="spectra-gbs-var__count">
							{ sprintf( __( '%d tokens', 'spectra-blocks' ), rows.length ) }
						</span>
						<input
							type="search"
							className="spectra-gbs-var__search"
							placeholder={ __( 'Filter tokens…', 'spectra-blocks' ) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
							aria-label={ __( 'Filter CSS variables', 'spectra-blocks' ) }
						/>
					</div>

					{/* Token list */}
					<div className="spectra-gbs-var__list">
						{ rows.map( ( [ name, value ] ) => (
							<TokenRow key={ name } name={ name } value={ value } />
						) ) }
						{ rows.length === 0 && (
							<div className="spectra-gbs-section__empty">
								{ __( 'No tokens match your filter.', 'spectra-blocks' ) }
							</div>
						) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default VariablesPanel;
