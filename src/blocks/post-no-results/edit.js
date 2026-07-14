/**
 * Post No Results Block - Edit Component
 *
 * Renders a customizable no results message when the parent Post block query
 * returns no posts.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';

/**
 * Default template for the no results block.
 */
const TEMPLATE = [
	[
		'spectra/content',
		{
			tagName: 'p',
			placeholder: __(
				'Add text or blocks that will display when a query returns no results.',
				'spectra-blocks'
			),
			text: __( 'No results found.', 'spectra-blocks' ),
			style: {
				typography: {
					textAlign: 'center',
				},
			},
		},
	],
];

/**
 * Edit component for the Post No Results block.
 *
 * @param {Object} props Component props.
 * @return {Element} The edit component.
 */
const Edit = ( props ) => {
	const { attributes } = props;
	const { isPreview } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	if ( isPreview ) {
		return <RenderBlockPreview blockName="no-results" childPath="post-children" />;
	}

	return <div { ...innerBlocksProps } />;
};

export default Edit;
