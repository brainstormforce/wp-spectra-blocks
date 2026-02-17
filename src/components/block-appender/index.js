/**
 * External dependencies.
 */
import { ButtonBlockAppender } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

export const FullWidthBlockAppender = ( { clientId } ) => (
	<ButtonBlockAppender
		className="spectra-editor__block-appender spectra-editor__block-appender--full-width"
		rootClientId={ clientId }
	/>
);

export const RenderFullWidthAppenderWhenEmpty = ( clientId ) => {

	const hasChildren = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks.length > 0,
		[ clientId ]
	);

	return ( ! hasChildren ) ? () => <FullWidthBlockAppender { ...{ clientId } } /> : undefined;
};