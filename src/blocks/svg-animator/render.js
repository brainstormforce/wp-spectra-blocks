/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Placeholder, Icon, Spinner } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { spectraClassNames, processSpectraSVG } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes } = props;

	const {
		svgContent,
		svgUrl,
		svgSource,
	} = attributes;

	// State for fetched and sanitized SVG content.
	const [ renderedSvg, setRenderedSvg ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'strokeColor' },
		{ key: 'strokeColorHover' },
		{ key: 'fillColor' },
		{
			key: 'svgWidth',
			cssVar: '--spectra-svg-animator-width',
			className: null,
		},
		{
			key: 'svgHeight',
			cssVar: '--spectra-svg-animator-height',
			className: null,
		},
		{
			key: 'strokeWidth',
			cssVar: '--spectra-svg-animator-stroke-width',
			className: null,
		},
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Sanitize and set SVG content.
	const sanitizeSvg = useCallback( async ( content ) => {
		if ( ! content ) {
			setRenderedSvg( '' );
			return;
		}
		const sanitized = await processSpectraSVG( content );
		setRenderedSvg( sanitized || '' );
	}, [] );

	// Handle SVG content from code input.
	useEffect( () => {
		if ( svgSource === 'code' && svgContent ) {
			sanitizeSvg( svgContent );
		} else if ( svgSource === 'code' ) {
			setRenderedSvg( '' );
		}
	}, [ svgSource, svgContent, sanitizeSvg ] );

	// Handle SVG content from uploaded file URL.
	useEffect( () => {
		if ( svgSource === 'upload' && svgUrl ) {
			setIsLoading( true );
			fetch( svgUrl )
				.then( ( response ) => response.text() )
				.then( async ( text ) => {
					const sanitized = await processSpectraSVG( text );
					setRenderedSvg( sanitized || '' );
					setIsLoading( false );
				} )
				.catch( () => {
					setRenderedSvg( '' );
					setIsLoading( false );
				} );
		} else if ( svgSource === 'upload' ) {
			setRenderedSvg( '' );
		}
	}, [ svgSource, svgUrl ] );

	// Use the block props with CSS variables and class names.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	const hasSvg = ( svgSource === 'upload' && svgUrl ) || ( svgSource === 'code' && svgContent );

	// Show placeholder when no SVG is selected.
	if ( ! hasSvg ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Icon icon="format-image" /> }
					label={ __( 'SVG Animator', 'ultimate-addons-for-gutenberg' ) }
					instructions={ __( 'Upload an SVG file or paste SVG code from the sidebar settings.', 'ultimate-addons-for-gutenberg' ) }
				/>
			</div>
		);
	}

	// Show loading state while fetching SVG.
	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon={ <Spinner /> }
					label={ __( 'Loading SVG...', 'ultimate-addons-for-gutenberg' ) }
				/>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ renderedSvg && (
				<div
					className="spectra-svg-animator__container"
					dangerouslySetInnerHTML={ { __html: renderedSvg } }
				/>
			) }
		</div>
	);
};

export default memo( Render );
