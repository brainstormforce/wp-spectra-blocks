/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

const Save = () => {
	// Server-side rendering handles the wrapper and structure
	// We only need to save the inner content
	return <InnerBlocks.Content />;
};

export default Save;
