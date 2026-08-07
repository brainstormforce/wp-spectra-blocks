/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { memo, useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { removeAnchorTag, spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';

const Render = ( props ) => {
	const {
		setAttributes,
		attributes,
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
			const finalIconPosition = iconPosition || 'after'; // Ensure a default value
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
				keepPlaceholderOnFocus
				withoutInteractiveFormatting
			/>
		);
	}, [ showText, text, setAttributes ] );

	return (
		<div { ...blockProps }>
			{ iconHtml( 'before' ) }
			{ btnText() }
			{ iconHtml( 'after' ) }
		</div>
	);
};

export default memo( Render );
