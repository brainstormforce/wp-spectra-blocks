/**
 * Whether a List, or one item's icon, carries an icon setting of the owner's own: an icon
 * other than the default circle, its size at any device, its rotation or RTL flip.
 *
 * On a list ZIP AI's converter built (`spectra-no-list-icon`) the page's own marker shows
 * until then. The List's setting hands every item's marker to Spectra
 * (`spectra-list-styled`); an item's setting hands over that item's alone
 * (`spectra-list-icon-own`). See `converted/_list.scss`. Mirrors
 * `BlockAttributes::has_icon_setting()`, so the canvas and the site agree.
 *
 * @since 1.0.16
 * @param {Object} attributes The List's or the item icon's attributes.
 * @return {boolean} True when the attributes carry an icon setting.
 */
export const hasIconSetting = ( attributes = {} ) => {
	if ( ( attributes.icon && 'circle' !== attributes.icon ) || undefined !== attributes.rotation || attributes.flipForRTL ) {
		return true;
	}
	const layers = [
		attributes,
		attributes.style?.[ '@tablet' ],
		attributes.style?.[ '@mobile' ],
		...Object.values( attributes.responsiveControls || {} ),
	];
	return layers.some( ( layer ) => layer && 'object' === typeof layer && undefined !== layer.iconSize && '' !== layer.iconSize );
};
