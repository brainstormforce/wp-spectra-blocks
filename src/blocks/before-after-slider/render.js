/**
 * External dependencies.
 */
import { useBlockProps, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Placeholder } from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes, setAttributes } = props;

	const {
		beforeImageId,
		beforeImageUrl,
		beforeImageAlt,
		afterImageId,
		afterImageUrl,
		afterImageAlt,
		beforeLabel,
		afterLabel,
		orientation,
		initialOffset,
		showLabels,
	} = attributes;

	// Configuration for the useSpectraStyles hook.
	// MUST mirror PHP controller's $config exactly.
	const config = [
		{ key: 'overlayColor', css_var: '--spectra-ba-overlay-color' },
		{ key: 'beforeOverlayColor', css_var: '--spectra-ba-before-overlay-color' },
		{ key: 'afterOverlayColor', css_var: '--spectra-ba-after-overlay-color' },
		{ key: 'handleColor', css_var: '--spectra-ba-handle-color' },
		{ key: 'handleThickness', css_var: '--spectra-ba-handle-thickness' },
		{ key: 'handleCircleSize', css_var: '--spectra-ba-handle-circle-size' },
		{ key: 'labelColor', css_var: '--spectra-ba-label-color' },
		{ key: 'labelBackgroundColor', css_var: '--spectra-ba-label-bg-color' },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	const isHorizontal = orientation === 'horizontal';
	const hasImages = beforeImageUrl && afterImageUrl;

	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames, {
			'spectra-ba-slider--horizontal': isHorizontal,
			'spectra-ba-slider--vertical': ! isHorizontal,
			[ `spectra-ba-slider--labels-${ showLabels }` ]: true,
		} ),
	} );

	// Clip path for the before image based on orientation and offset.
	const clipPath = isHorizontal
		? `inset(0 ${ 100 - initialOffset }% 0 0)`
		: `inset(0 0 ${ 100 - initialOffset }% 0)`;

	// Handle position style.
	const handleStyle = isHorizontal
		? { left: `${ initialOffset }%` }
		: { top: `${ initialOffset }%` };

	if ( ! hasImages ) {
		return (
			<div { ...blockProps }>
				<div className="spectra-ba-slider__placeholder">
					<MediaUploadCheck>
						<div className="spectra-ba-slider__placeholder-item">
							<Placeholder
								label={ __( 'Before Image', 'ultimate-addons-for-gutenberg' ) }
								instructions={ __( 'Select or upload the before image.', 'ultimate-addons-for-gutenberg' ) }
							>
								<MediaUpload
									onSelect={ ( media ) => setAttributes( {
										beforeImageId: media.id,
										beforeImageUrl: media.url,
										beforeImageAlt: media.alt || '',
									} ) }
									allowedTypes={ [ 'image' ] }
									value={ beforeImageId }
									render={ ( { open } ) => (
										<Button
											variant="primary"
											onClick={ open }
										>
											{ beforeImageUrl
												? __( 'Replace Image', 'ultimate-addons-for-gutenberg' )
												: __( 'Select Image', 'ultimate-addons-for-gutenberg' )
											}
										</Button>
									) }
								/>
							</Placeholder>
						</div>
						<div className="spectra-ba-slider__placeholder-item">
							<Placeholder
								label={ __( 'After Image', 'ultimate-addons-for-gutenberg' ) }
								instructions={ __( 'Select or upload the after image.', 'ultimate-addons-for-gutenberg' ) }
							>
								<MediaUpload
									onSelect={ ( media ) => setAttributes( {
										afterImageId: media.id,
										afterImageUrl: media.url,
										afterImageAlt: media.alt || '',
									} ) }
									allowedTypes={ [ 'image' ] }
									value={ afterImageId }
									render={ ( { open } ) => (
										<Button
											variant="primary"
											onClick={ open }
										>
											{ afterImageUrl
												? __( 'Replace Image', 'ultimate-addons-for-gutenberg' )
												: __( 'Select Image', 'ultimate-addons-for-gutenberg' )
											}
										</Button>
									) }
								/>
							</Placeholder>
						</div>
					</MediaUploadCheck>
				</div>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<div className="spectra-ba-slider__container">
				{ /* After image (base layer) */ }
				<div className="spectra-ba-slider__after">
					<img
						src={ afterImageUrl }
						alt={ afterImageAlt }
						className="spectra-ba-slider__image"
					/>
					<div className="spectra-ba-slider__overlay" />
					{ 'none' !== showLabels && afterLabel && (
						<span className="spectra-ba-slider__label spectra-ba-slider__label--after">
							{ afterLabel }
						</span>
					) }
				</div>
				{ /* Before image (clipped layer) */ }
				<div
					className="spectra-ba-slider__before"
					style={ { clipPath } }
				>
					<img
						src={ beforeImageUrl }
						alt={ beforeImageAlt }
						className="spectra-ba-slider__image"
					/>
					<div className="spectra-ba-slider__overlay" />
					{ 'none' !== showLabels && beforeLabel && (
						<span className="spectra-ba-slider__label spectra-ba-slider__label--before">
							{ beforeLabel }
						</span>
					) }
				</div>
				{ /* Handle */ }
				<div className="spectra-ba-slider__handle" style={ handleStyle }>
					<div className="spectra-ba-slider__handle-line" />
					<div className="spectra-ba-slider__handle-circle">
						{ isHorizontal ? (
							<>
								<span className="spectra-ba-slider__arrow spectra-ba-slider__arrow--left" />
								<span className="spectra-ba-slider__arrow spectra-ba-slider__arrow--right" />
							</>
						) : (
							<>
								<span className="spectra-ba-slider__arrow spectra-ba-slider__arrow--up" />
								<span className="spectra-ba-slider__arrow spectra-ba-slider__arrow--down" />
							</>
						) }
					</div>
					<div className="spectra-ba-slider__handle-line" />
				</div>
			</div>
		</div>
	);
};

export default memo( Render );
