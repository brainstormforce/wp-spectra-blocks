/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { 
	Guide
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import './style.scss';

/**
 * Container Layout Guide Component
 *
 * This component provides a NUX (New User Experience) guide that explains
 * the different layout types available in the Container block.
 *
 * @param {Object}   props           Component props.
 * @param {boolean}  props.isVisible Whether the guide is visible.
 * @param {Function} props.onClose   Function to close the guide.
 * @since x.x.x
 * @return {Element} The rendered Container Layout Guide component.
 */
const ContainerLayoutGuide = ( { isVisible, onClose } ) => {

	// Guide pages explaining each layout type.
	const guidePages = [
		{
			image: <img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-1.png` } alt={ __( 'Container Layout Guide Introduction', 'spectra' ) } />,
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Understanding Container Layouts', 'spectra' ) }</h2>
					<p>
						{ __( 
							'Container blocks offer different layout types to help you create beautiful designs. Let\'s explore when to use each layout type.', 
							'spectra' 
						) }
					</p>
					<div className="spectra-layout-guide__tip">
						<strong>{ __( 'Pro Tip:', 'spectra' ) }</strong>
						<p>
							{ __( 
								'You can always change the layout type in the sidebar settings after selecting a variation. Each layout type offers different controls and behavior.', 
								'spectra' 
							) }
						</p>
					</div>
				</div>
			),
		},
		{
			image: (
				<img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-2.png` } alt={ __( 'Container Layout Guide Flow Layout', 'spectra' ) } />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Flow Layout', 'spectra' ) }</h2>
					<p>
						{ __( 
							'Content flows naturally from top to bottom, like a normal document. Perfect for simple content sections, text with images, and basic page layouts.', 
							'spectra' 
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-3.png` } alt={ __( 'Container Layout Guide Flex Layout', 'spectra' ) } />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Flex Layout', 'spectra' ) }</h2>
					<p>
						{ __( 
							'Gives you control over how items line up and space out. Items can be arranged horizontally or vertically with precise alignment control.', 
							'spectra' 
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-4.png` } alt={ __( 'Container Layout Guide Grid Layout', 'spectra' ) } />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Grid Layout', 'spectra' ) }</h2>
					<p>
						{ __( 
							'Creates a precise grid with defined rows and columns. Perfect for complex layouts where you need exact control over positioning.', 
							'spectra' 
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-5.png` } alt={ __( 'Container Layout Guide Constrained Layout', 'spectra' ) } />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Constrained Layout', 'spectra' ) }</h2>
					<p>
						{ __( 
							'Centers content and limits its maximum width. Content stays centered and never gets too wide, even on large screens.', 
							'spectra' 
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<img src={ `${ window?.uagb_blocks_info?.uagb_url || '' }/spectra-v3/assets/images/guide/spectra-layout-6.png` } alt={ __( 'Container Layout Guide Understanding Layout Changes', 'spectra' ) } />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Understanding Layout Changes', 'spectra' ) }</h2>
					<p>
						{ __( 
							'When you select a pre-built variation (like two-column or three-column), the layout automatically changes to Grid because these patterns work best with grid layouts.', 
							'spectra' 
						) }
					</p>
					<div className="spectra-layout-guide__tip">
						<strong>{ __( 'Pro Tip:', 'spectra' ) }</strong>
						<p>
							{ __( 
								'You can always change the layout type in the sidebar settings after selecting a variation. Each layout type offers different controls and behavior.', 
								'spectra' 
							) }
						</p>
					</div>
				</div>
			),
		},
	];

	// Don't render if guide is not visible.
	if ( ! isVisible ) {
		return null;
	}

	return (
		<Guide
			className="spectra-layout-guide"
			contentLabel={ __( 'Container Layout Guide', 'spectra' ) }
			finishButtonText={ __( 'Get Started', 'spectra' ) }
			onFinish={ onClose }
			pages={ guidePages }
		/>
	);
};

export default ContainerLayoutGuide;
