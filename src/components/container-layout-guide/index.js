/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import {
	Guide,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import './style.scss';

// Remote GIFs hosted on wpspectra.com — matches the UAGB spectra-3-base
// implementation pixel-for-pixel. Kept remote (vs bundling locally) to avoid
// re-introducing the ~5 MB plugin-zip bloat that commit 89ce5f8b9e removed
// when migrating GIFs → local WebM. If wpspectra.com URLs ever change, only
// these constants need to be updated.
const GIF_BASE = 'https://wpspectra.com/wp-content/uploads/2026/02';

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
			image: <img src={ `${ GIF_BASE }/Understanding-Container-Layouts.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Introduction', 'spectra-blocks' ) } />,
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
				<img src={ `${ GIF_BASE }/Flow-Layout.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Flow Layout', 'spectra-blocks' ) } />
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
				<img src={ `${ GIF_BASE }/Flex-Layout.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Flex Layout', 'spectra-blocks' ) } />
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
				<img src={ `${ GIF_BASE }/Grid-Layout.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Grid Layout', 'spectra-blocks' ) } />
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
				<img src={ `${ GIF_BASE }/Constrained-Layout.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Constrained Layout', 'spectra-blocks' ) } />
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
				<img src={ `${ GIF_BASE }/Understanding-Layout-Changes-1.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Understanding Layout Changes', 'spectra-blocks' ) } />
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
				<img src={ `${ GIF_BASE }/Content-Alignment.gif` } width="520" height="400" alt={ __( 'Container Layout Guide Content Alignment', 'spectra-blocks' ) } />
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
