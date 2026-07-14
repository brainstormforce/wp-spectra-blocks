/**
 * Post Block - Save Component
 *
 * Saves the inner blocks content for the Post block.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Save component for the Post block.
 *
 * @return {Element} The saved block content.
 */
const Save = () => <InnerBlocks.Content />;

export default Save;
