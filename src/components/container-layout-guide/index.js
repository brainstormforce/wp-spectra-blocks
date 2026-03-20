/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { 
	Guide
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import './style.scss';
import { getPluginUrl } from '@spectra-config';

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

	const guideVideoBase = `${ getPluginUrl() }assets/images/layout-guide/`;

	// Guide pages explaining each layout type.
	const guidePages = [
		{
			image: <video src={ `${ guideVideoBase }Understanding-Container-Layouts.webm` } width="520" height="400" autoPlay loop muted playsInline />,
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Understanding Container Layouts', 'spectra-blocks' ) }</h2>
					<p>
						{ __( 
							'Container layouts control how content is placed and aligned inside a container. Choosing the right layout helps you build pages faster and keeps things looking neat across devices.', 
							'spectra-blocks' 
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Flow-Layout.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Flow Layout', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
								'Arrange blocks in a natural <b>top-to-bottom</b> order, with minimal layout controls. Best for simple, readable content and quick layouts with simple content stacking.', 
								'spectra-blocks' 
							),
							{
								b: <strong />,
							}
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Flex-Layout.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Flex Layout', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
								'Arrange blocks <b>horizontally or vertically</b>, adjust their alignment, and allow them to wrap onto multiple lines. Justification controls how blocks are spaced and aligned.', 
								'spectra-blocks' 
							),
							{
								b: <strong />,
							}
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Grid-Layout.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Grid Layout', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
								'Grid layout arranges blocks into <b>rows and columns</b>, giving you precise control over where each item appears. You can set a minimum column width and adjust individual item positions.', 
								'spectra-blocks' 
							),
							{
								b: <strong />,
							}
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Constrained-Layout.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Constrained Layout', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
							'Constrained layout <b>keeps your content centered</b> and limits how wide it can grow. Even on large screens, content stays comfortable to read and visually balanced.', 
							'spectra-blocks' 
						),
							{
								b: <strong />,
							}
						) }
					</p>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Understanding-Layout-Changes-1.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Understanding Layout Changes', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
								'You can always change layout type from the <b>Sidebar → Layout Settings</b>. Each layout type offers different controls and behaviour.',
								'spectra-blocks' 
							),
							{
								b: <strong />,
							}
						) }
					</p>
					<p>
						{ createInterpolateElement(
							__( 
								'You can also use the <b>Change Layout</b> <img /> option from the toolbar to change layout variation/style.', 
								'spectra-blocks' 
							),
							{
								b: <strong />,
								img: <img src={ `${ window?.spectra_blocks_info?.plugin_url || '' }/assets/images/guide/layoutstyle.svg` } alt={ __( 'Change Layout', 'spectra-blocks' ) } style={ { height: '1.2em', verticalAlign: 'middle', margin: '0 0.25em' } } />,
							}
						) }
					</p>
					<div className="spectra-layout-guide__tip">
						<strong>{ __( 'Note:', 'spectra-blocks' ) }</strong>
						<p>
							{ __( 
								'When you choose a column layout styles like Two Columns or Three Columns, the editor switches to Grid automatically. Grid keeps columns aligned and evenly spaced, so you don\'t have to adjust anything manually.', 
								'spectra-blocks' 
							) }
						</p>
					</div>
				</div>
			),
		},
		{
			image: (
				<video src={ `${ guideVideoBase }Content-Alignment.webm` } width="520" height="400" autoPlay loop muted playsInline />
			),
			content: (
				<div className="spectra-layout-guide__page">
					<h2>{ __( 'Content Alignment', 'spectra-blocks' ) }</h2>
					<p>
						{ createInterpolateElement(
							__( 
								'Use <b>Align items</b> and <b>Justify items</b> in the block toolbar to position and space content inside the container.', 
								'spectra-blocks' 
							),
							{
								b: <strong />,
							}
						) }
					</p>
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
			contentLabel={ __( 'Container Layout Guide', 'spectra-blocks' ) }
			finishButtonText={ __( 'Get Started', 'spectra-blocks' ) }
			onFinish={ onClose }
			pages={ guidePages }
		/>
	);
};

export default ContainerLayoutGuide;
