/**
 * Expose server-rendered Spectra block content to SEO content analysers.
 *
 * Spectra text and button blocks are dynamic (no `save()`), so the serialized
 * post content that Yoast SEO and Rank Math analyse holds only block comments —
 * their word and link counts ignore every Spectra block (GH #1120). This walks
 * the editor's live block tree and feeds the extracted text and links to both
 * analysers, so the counts match what actually renders on the front end.
 *
 * @since 1.0.13
 */

/**
 * External dependencies.
 */
import { select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

// Every Spectra block that stores user copy keeps it in `text` (rich-text HTML);
// link-wrapped blocks keep the destination in `linkURL`.
const TEXT_ATTR = 'text';
const LINK_ATTR = 'linkURL';

/**
 * Whether a block name belongs to Spectra.
 *
 * @param {string} name The block name.
 * @return {boolean} True for `spectra/*` blocks.
 */
const isSpectraBlock = ( name ) => 'string' === typeof name && name.startsWith( 'spectra/' );

/**
 * Walk a block tree and build an HTML fragment carrying each Spectra block's
 * text and link, so analysers can count its words and links.
 *
 * @param {Array} blocks Blocks from the block-editor store.
 * @return {string} HTML fragment of the extracted content.
 */
function extractSpectraContent( blocks ) {
	if ( ! Array.isArray( blocks ) ) {
		return '';
	}

	let html = '';

	blocks.forEach( ( block ) => {
		const { name, attributes = {}, innerBlocks = [] } = block;

		if ( isSpectraBlock( name ) ) {
			const text = 'string' === typeof attributes[ TEXT_ATTR ] ? attributes[ TEXT_ATTR ].trim() : '';
			const link = 'string' === typeof attributes[ LINK_ATTR ] ? attributes[ LINK_ATTR ].trim() : '';

			if ( text && link ) {
				html += `<a href="${ link }">${ text }</a>`;
			} else if ( text ) {
				// `text` is already rich-text HTML and may carry its own inline links.
				html += `<p>${ text }</p>`;
			} else if ( link ) {
				html += `<a href="${ link }">${ link }</a>`;
			}
		}

		// Text often lives in nested Spectra blocks (list items, tabs, containers).
		html += extractSpectraContent( innerBlocks );
	} );

	return html;
}

/**
 * Read the current editor blocks and return their extracted SEO content.
 *
 * @return {string} HTML fragment, or '' when the editor store is unavailable.
 */
function getSpectraContent() {
	const editor = select( 'core/block-editor' );

	if ( ! editor || 'function' !== typeof editor.getBlocks ) {
		return '';
	}

	return extractSpectraContent( editor.getBlocks() );
}

/**
 * Append the extracted Spectra content to an analyser's content string.
 *
 * @param {string} content The content the analyser is about to score.
 * @return {string} Content with Spectra text and links appended.
 */
function appendSpectraContent( content ) {
	const extra = getSpectraContent();
	return extra ? `${ content }\n${ extra }` : content;
}

// Rank Math: applies this filter to the content it analyses. Registering is inert
// when Rank Math is not active (nothing applies the filter).
addFilter( 'rank_math_content', 'spectra-blocks/seo-analysis', appendSpectraContent );

/**
 * Register the content modification with Yoast SEO.
 *
 * Yoast exposes its plugin API on `window.YoastSEO.app` once its app is ready.
 *
 * @return {boolean} True once the modification is registered.
 */
function registerYoast() {
	const app = window.YoastSEO && window.YoastSEO.app;

	if ( ! app || 'function' !== typeof app.registerPlugin || 'function' !== typeof app.registerModification ) {
		return false;
	}

	const pluginName = 'SpectraBlocks';

	app.registerPlugin( pluginName, { status: 'ready' } );
	app.registerModification( 'content', appendSpectraContent, pluginName, 5 );

	return true;
}

// Yoast loads asynchronously; poll briefly for its app, then give up. The poll
// is only armed when Yoast's editor data is present, so sites without Yoast pay
// nothing.
if ( window.wpseoScriptData || ( window.YoastSEO && window.YoastSEO.app ) ) {
	if ( ! registerYoast() ) {
		let attempts = 0;
		const timer = setInterval( () => {
			if ( registerYoast() || ++attempts > 40 ) {
				clearInterval( timer );
			}
		}, 500 );
	}
}
