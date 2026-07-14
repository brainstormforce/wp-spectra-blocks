/**
 * ShadeStrip — a row of colour swatches for a computed shade scale.
 *
 * Renders each shade as a coloured box with its index label below.
 * Hovering a swatch shows the hex value in a tooltip. An optional
 * `baseShade` prop adds a ring around the reference shade (the one
 * that came from the user's base colour picker).
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState } from '@wordpress/element';

/**
 * Determine whether white or black gives better contrast on a hex bg.
 *
 * Simple luminance heuristic — good enough for a UI label.
 *
 * @since x.x.x
 *
 * @param {string} hex 6-digit hex (with #).
 * @return {'#fff'|'#000'} Contrasting colour.
 */
function contrastColor( hex ) {
	const r = parseInt( hex.slice( 1, 3 ), 16 );
	const g = parseInt( hex.slice( 3, 5 ), 16 );
	const b = parseInt( hex.slice( 5, 7 ), 16 );
	const luminance = ( 0.299 * r + 0.587 * g + 0.114 * b ) / 255;
	return luminance > 0.55 ? '#000' : '#fff';
}

/**
 * ShadeStrip component.
 *
 * @since x.x.x
 *
 * @param {Object} props             Component props.
 * @param {Array}  props.shades      Array of `{ index, hex }` objects ordered light→dark.
 * @param {number} [props.baseShade] Index of the base/reference shade (gets a ring).
 * @param {string} [props.label]     Accessible label for the strip.
 * @return {Element} Shade strip element.
 */
const ShadeStrip = ( { shades, baseShade, label } ) => {
	const [ tooltip, setTooltip ] = useState( null ); // { index, hex }

	if ( ! shades?.length ) {return null;}

	return (
		<div
			className="spectra-gbs-shade-strip"
			role="list"
			aria-label={ label }
		>
			{ shades.map( ( { index, hex } ) => {
				if ( ! hex ) {return null;}
				const isBase = index === baseShade;
				const textColor = contrastColor( hex );

				return (
					<div
						key={ index }
						className={ `spectra-gbs-shade-strip__swatch${ isBase ? ' is-base' : '' }` }
						role="listitem"
						aria-label={ `Shade ${ index }: ${ hex }` }
						onMouseEnter={ () => setTooltip( { index, hex } ) }
						onMouseLeave={ () => setTooltip( null ) }
					>
						<div
							className="spectra-gbs-shade-strip__color"
							style={ { backgroundColor: hex } }
						>
							{ isBase && (
								<span
									className="spectra-gbs-shade-strip__base-mark"
									style={ { color: textColor } }
									aria-hidden="true"
								>
									●
								</span>
							) }
						</div>
						<span className="spectra-gbs-shade-strip__index">{ index }</span>

						{ tooltip?.index === index && (
							<div className="spectra-gbs-shade-strip__tooltip" role="tooltip">
								{ hex }
							</div>
						) }
					</div>
				);
			} ) }
		</div>
	);
};

export default ShadeStrip;
