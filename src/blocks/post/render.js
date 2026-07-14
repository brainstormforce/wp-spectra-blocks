/**
 * Post Block - Render Component
 *
 * Handles the editor preview rendering for the Post block.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';
import { useEffect, useMemo } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';

/**
 * Render component for the Post block editor preview.
 *
 * Generates a unique query ID for multi-block pagination support and applies
 * CSS custom properties for spacing.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @return {Element} The rendered block preview.
 */
const Render = ( props ) => {
	const { attributes, setAttributes } = props;
	const {
		queryId,
		layoutType,
		arrowColor,
		dotColor,
		arrowSize,
		arrowDistance,
		paginationTopMargin,
		arrowBackgroundColor,
		paginationType,
		paginationAlignment,
		paginationLayout,
		paginationColor,
		paginationBackgroundColor,
		paginationHoverColor,
		paginationBackgroundHoverColor,
		paginationActiveColor,
		paginationBackgroundActiveColor,
		paginationPrevLabel,
		paginationNextLabel,
		paginationButtonText,
		paginationLoadingText,
	} = attributes;

	const instanceId = useInstanceId( Render );

	useEffect( () => {
		if ( ! queryId || queryId === '' ) {
			const id = String( instanceId );
			setAttributes( {
				queryId: id,
				pageKey: `query-${ id }-page`,
			} );
		}
	}, [ queryId, instanceId, setAttributes ] );

	const inlineStyles = useMemo(
		() => ( {
			...( arrowColor && { '--spectra-carousel-arrow-color': arrowColor } ),
			...( arrowBackgroundColor && { '--spectra-carousel-arrow-bg-color': arrowBackgroundColor } ),
			...( dotColor && { '--spectra-carousel-dot-color': dotColor } ),
			'--spectra-carousel-arrow-size': arrowSize || '20px',
			'--spectra-carousel-arrow-distance': arrowDistance || '-20px',
			'--spectra-carousel-pagination-margin-top': paginationTopMargin || '-15px',
			...( paginationColor && { '--spectra-pagination-color': paginationColor } ),
			...( paginationBackgroundColor && { '--spectra-pagination-bg-color': paginationBackgroundColor } ),
			...( paginationHoverColor && { '--spectra-pagination-color-hover': paginationHoverColor } ),
			...( paginationBackgroundHoverColor && {
				'--spectra-pagination-bg-color-hover': paginationBackgroundHoverColor,
			} ),
			...( paginationActiveColor && { '--spectra-pagination-color-active': paginationActiveColor } ),
			...( paginationBackgroundActiveColor && {
				'--spectra-pagination-bg-color-active': paginationBackgroundActiveColor,
			} ),
		} ),
		[
			arrowColor,
			arrowBackgroundColor,
			dotColor,
			arrowSize,
			arrowDistance,
			paginationTopMargin,
			paginationColor,
			paginationBackgroundColor,
			paginationHoverColor,
			paginationBackgroundHoverColor,
			paginationActiveColor,
			paginationBackgroundActiveColor,
		]
	);

	const classNames = useMemo(
		() =>
			[
				`spectra-post-layout-${ layoutType }`,
				paginationType !== 'none' ? `spectra-pagination-align-${ paginationAlignment }` : '',
				paginationType === 'standard' ? `spectra-pagination-layout-${ paginationLayout }` : '',
			]
				.filter( Boolean )
				.join( ' ' ),
		[ layoutType, paginationType, paginationAlignment, paginationLayout ]
	);

	const blockProps = useBlockProps( {
		className: classNames,
		style: inlineStyles,
	} );

	// Use a dedicated container for inner blocks to separate them from the pagination
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'spectra-post-inner-blocks' },
		{
			templateLock: false,
		}
	);

	// Pagination Preview Logic
	let paginationPreview = null;
	if ( paginationType !== 'none' && layoutType !== 'carousel' ) {
		if ( paginationType === 'standard' ) {
			paginationPreview = (
				<nav
					className={ `spectra-post-pagination spectra-pagination-align-${ paginationAlignment } spectra-pagination-layout-${ paginationLayout }` }
				>
					<div className="spectra-post-pagination-numbers">
						<a
							className="prev page-numbers"
							href="#pagination-preview"
							onClick={ ( e ) => e.preventDefault() }
						>
							{ paginationPrevLabel }
						</a>
						<a
							className="page-numbers current"
							href="#pagination-preview"
							aria-current="page"
							onClick={ ( e ) => e.preventDefault() }
						>
							1
						</a>
						<a className="page-numbers" href="#pagination-preview" onClick={ ( e ) => e.preventDefault() }>
							2
						</a>
						<span className="page-numbers dots">…</span>
						<a className="page-numbers" href="#pagination-preview" onClick={ ( e ) => e.preventDefault() }>
							10
						</a>
						<a
							className="next page-numbers"
							href="#pagination-preview"
							onClick={ ( e ) => e.preventDefault() }
						>
							{ paginationNextLabel }
						</a>
					</div>
				</nav>
			);
		} else if ( paginationType === 'button' ) {
			paginationPreview = (
				<nav className={ `spectra-post-pagination spectra-pagination-align-${ paginationAlignment }` }>
					<div className="spectra-load-more-wrapper">
						<button className="spectra-load-more-button" onClick={ ( e ) => e.preventDefault() }>
							<span>{ paginationButtonText }</span>
						</button>
					</div>
				</nav>
			);
		} else if ( paginationType === 'infinite' ) {
			paginationPreview = (
				<div className="spectra-post-inf-loader">
					<div className="spectra-post-loader-1"></div>
					<div className="spectra-post-loader-2"></div>
					<div className="spectra-post-loader-3"></div>
					<span className="screen-reader-text">{ paginationLoadingText }</span>
				</div>
			);
		}
	}

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps }>
				{ innerBlocksProps.children }
				{ paginationPreview }
			</div>
		</div>
	);
};

export default Render;
