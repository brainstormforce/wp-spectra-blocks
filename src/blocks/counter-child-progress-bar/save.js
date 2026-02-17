/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Save = () => {
	return <InnerBlocks.Content/>;
};

export default Save;
