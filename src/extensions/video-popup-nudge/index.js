import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { useState } from '@wordpress/element';
import { Guide, Button } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorAdvancedControls } from '@wordpress/blockEditor';

const withSpectraPluginControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const blockName = props.name;
		// Checks for the standard Spectra (Free) namespace
		const isSpectraCore = blockName.includes( 'spectra/' );

		// Checks for the Spectra Pro namespace
		const isSpectraPro = blockName.includes( 'spectra-pro/' );

		// Checks if it is ANY Spectra block (Free OR Pro)
		const isAnySpectra = isSpectraCore || isSpectraPro;
		const [ isOpen, setOpen ] = useState( false );

		const openGuide = () => setOpen( true );
		const closeGuide = () => setOpen( false );

		return (
			<>
				{ isAnySpectra && (
					<InspectorAdvancedControls>
						<div className="spectra-help-box">
							<p className="spectra-help-title">
								{ __(
									'Need Help!',
									'spectra-blocks'
								) }
							</p>
							<div className="spectra-video-link">
								<span className="spectra-help-icon">
									<svg className="spectra-play-icon" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
										<g clipPath="url(#clip0_909_2763)">
											<path d="M7.91667 1.25H2.08333C1.6231 1.25 1.25 1.6231 1.25 2.08333V7.91667C1.25 8.3769 1.6231 8.75 2.08333 8.75H7.91667C8.3769 8.75 8.75 8.3769 8.75 7.91667V2.08333C8.75 1.6231 8.3769 1.25 7.91667 1.25Z" fill="#1B6096" stroke="#1B6096" strokeWidth="0.833333" strokeLinecap="round" strokeLinejoin="round" />
											<path d="M3.75 3.75124C3.74978 3.67729 3.76924 3.60462 3.80638 3.54068C3.84353 3.47674 3.89702 3.42384 3.96136 3.38741C4.02571 3.35097 4.09859 3.33231 4.17253 3.33335C4.24646 3.33439 4.31879 3.35509 4.38209 3.39332L6.46417 4.64207C6.52615 4.67901 6.57748 4.73142 6.61313 4.79416C6.64877 4.8569 6.66751 4.92783 6.66751 4.99999C6.66751 5.07215 6.64877 5.14307 6.61313 5.20581C6.57748 5.26855 6.52615 5.32096 6.46417 5.3579L4.38209 6.60665C4.31876 6.6449 4.24639 6.6656 4.17242 6.66662C4.09845 6.66764 4.02554 6.64895 3.96118 6.61247C3.89682 6.57598 3.84334 6.52302 3.80622 6.45902C3.76911 6.39502 3.74971 6.3223 3.75 6.24832V3.75124Z" fill="white" stroke="white" strokeWidth="0.3" strokeLinecap="round" strokeLinejoin="round" />
										</g>
										<defs>
											<clipPath id="clip0_909_2763">
												<rect width="10" height="10" fill="white" />
											</clipPath>
										</defs>
									</svg>
								</span>
								<Button onClick={ openGuide }>
									{ __(
										'Watch Video Guide',
										'spectra-blocks'
									) }
								</Button>
							</div>
						</div>
						{ isOpen && (
							<Guide
								className="spectra-video-popup-guide"
								contentLabel={ __( 'Guide', 'spectra-blocks' ) }
								onFinish={ closeGuide }
								pages={ [
									{
										image: (
											<iframe
												width="560"
												height="315"
												src="https://www.youtube-nocookie.com/embed/y_tsLWV6QRM?autoplay=1&mute=1"
												title="YouTube video player"
												frameBorder="0"
												allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
												allowFullScreen
												className="edit-site-welcome-guide__video"
											></iframe>
										)
									},
								] }
							/>
						) }
					</InspectorAdvancedControls>
				) }
				<BlockEdit key="edit" { ...props } />
			</>
		);
	};
}, 'withSpectraPluginControls' );

addFilter( 'editor.BlockEdit', 'spectra/with-inspector-controls', withSpectraPluginControls );