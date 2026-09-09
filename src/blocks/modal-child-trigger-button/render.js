/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { memo, useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { removeAnchorTag, spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles, buildSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';
import { getResponsivePreviewCss, iconDimensionStyles } from '@spectra-helpers/responsive-preview';

/**
 * The icon dimensions, painted on the icon children.
 *
 * Both the icon and the hover icon take the same size, so one entry per child
 * covers them. Named so the per-device preview emitter can re-derive per band —
 * see `helpers/responsive-preview.js`. The fallback mirrors the inline paint.
 *
 * @since 1.0.7
 * @param {Object} attrs The block's attributes, or a band's merge of them.
 * @return {Array} Selector-scoped style entries.
 */
export const getButtonIconStyles = ( attrs = {} ) => [
	...iconDimensionStyles( attrs.size || '16px', ' .spectra-button__icon' ),
	...iconDimensionStyles( attrs.size || '16px', ' .spectra-button__hover-icon' ),
];

const Render = ( props ) => {
	const {
		setAttributes,
		attributes,
		clientId,
		context: {
			'spectra/modal/modalTrigger': modalTrigger,
		}
	} = props;

	const { text, showText, icon, iconPosition, iconColor, iconColorHover, size, placeholder, ariaLabel } = attributes;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'iconColor' },
		{ key: 'iconColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
		{ key: 'gap', cssVar: '--spectra-icon-gap', className: null },
	];

	const customClassNames = [ 
		'button' !== modalTrigger ? 'is-hidden': '',
		'spectra-button',
		'wp-block-button__link'
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/modal-child-button',
		producers: [ getButtonIconStyles, ( attrs ) => buildSpectraStyles( attrs, config ).style ],
	} );

	// Compute aria-label for accessibility - prioritize user-defined ariaLabel, fallback to text content.
	const computedAriaLabel = useMemo( () => {
		if ( ariaLabel ) {
			return ariaLabel;
		}
		// Use the text content (strip HTML tags for better accessibility).
		return text ? text.replace( /<[^>]*>/g, '' ) : undefined;
	}, [ ariaLabel, text ] );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		'className': spectraClassNames( classNames ),
		style,
		'aria-label': computedAriaLabel,
		'role': 'button',
		'tabIndex': 0,
	} );

	const iconHtml = useCallback(
		( position ) => {
			const finalIconPosition = iconPosition === 'before' ? 'before' : 'after'; // Unknown or empty position must still paint, not vanish.
			// If there's no icon, or if the position given does not match the required one, abandon ship.
			if ( ! icon || position !== finalIconPosition ) {
				return null;
			}
			// Render the icon.
			return (
				<RenderSVG
					className={ spectraClassNames( [
						'spectra-button__icon',
						`spectra-button__icon-position-${ finalIconPosition }`,
						iconColor && 'spectra-icon-color',
						iconColorHover && 'spectra-icon-color-hover',
					] ) }
					svg={ icon }
					extraProps={ {
						color: 'currentColor',
						width: size,
						height: size,
						style: { width: size, height: size },
					} }
				/>
			);
		},
		[ icon, iconPosition, size, iconColor, iconColorHover ]
	);

	const btnText = useCallback( () => {
		// If we don't have to show text, abandon ship.
		if ( ! showText ) {
			return '';
		}
		return (
			<RichText
				placeholder={ placeholder }
				value={ text }
				tagName="div"
				onChange={ ( value ) => setAttributes( { text: removeAnchorTag( value ) } ) }
				className="spectra-button__link"
				withoutInteractiveFormatting
			/>
		);
	}, [ showText, text, setAttributes ] );

	return (
		<div { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			{ iconHtml( 'before' ) }
			{ btnText() }
			{ iconHtml( 'after' ) }
		</div>
	);
};

export default memo( Render );
