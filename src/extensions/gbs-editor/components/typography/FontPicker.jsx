/**
 * FontPicker — scrollable grid of font cards for heading/body selection.
 *
 * Each card shows the font name rendered in that typeface so the user
 * can evaluate it at a glance. Cards are grouped by category (sans /
 * serif) with a sticky section label.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { FONTS, fontStack } from './fonts.js';

const SANS  = FONTS.filter( ( f ) => f.category === 'sans' );
const SERIF = FONTS.filter( ( f ) => f.category === 'serif' );

/**
 * FontPicker component.
 *
 * @since x.x.x
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.value    Currently selected font slug.
 * @param {Function} props.onChange Called with the new font slug + name.
 * @param {string}   props.context  'heading' or 'body' — used for aria labels.
 * @return {Element} Font picker element.
 */
const FontPicker = ( { value, onChange, context } ) => {
	const renderGroup = ( fonts, groupLabel ) => (
		<div className="spectra-gbs-font-picker__group">
			<span className="spectra-gbs-font-picker__group-label" aria-hidden="true">
				{ groupLabel }
			</span>
			<div className="spectra-gbs-font-picker__grid">
				{ fonts.map( ( font ) => {
					const isActive = font.slug === value;
					return (
						<button
							key={ font.slug }
							type="button"
							className={ `spectra-gbs-font-card${ isActive ? ' is-active' : '' }` }
							onClick={ () => onChange( font.slug, font.name ) }
							aria-pressed={ isActive }
							aria-label={ `${ font.name }${ isActive ? ', ' + __( 'selected', 'spectra-blocks' ) : '' }` }
						>
							{/* Font name rendered in its own typeface */}
							<span
								className="spectra-gbs-font-card__preview"
								style={ { fontFamily: fontStack( font.slug ) } }
							>
								Aa
							</span>
							<span className="spectra-gbs-font-card__name">
								{ font.name }
							</span>
							{ isActive && (
								<span className="spectra-gbs-font-card__check" aria-hidden="true">✓</span>
							) }
						</button>
					);
				} ) }
			</div>
		</div>
	);

	return (
		<div
			className="spectra-gbs-font-picker"
			role="group"
			aria-label={ context === 'heading'
				? __( 'Heading font family', 'spectra-blocks' )
				: __( 'Body font family', 'spectra-blocks' ) }
		>
			{ renderGroup( SANS, __( 'Sans-serif', 'spectra-blocks' ) ) }
			{ renderGroup( SERIF, __( 'Serif', 'spectra-blocks' ) ) }
		</div>
	);
};

export default FontPicker;
