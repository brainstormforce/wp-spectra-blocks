
/**
 * External dependencies.
 */
import { useSelect, select, dispatch } from '@wordpress/data';
import { BlockControls } from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import RenderSVG from '@spectra-helpers/render-svg';

/**
 * Toggle visibility of the tabpanel that matches this button's index.
 *
 * @param {string} clientId The clientId of the clicked tab button.
 */
export const toggleVisibilityByClientId = ( clientId ) => {
	const { getBlock } = select( 'core/block-editor' );
	const block = getBlock( clientId );

	if ( ! block ) return;

	const isVisible = block.attributes?.isVisible || false;

	const { updateBlockAttributes } = dispatch( 'core/block-editor' );

	updateBlockAttributes( clientId, {
		isVisible: !isVisible,
	} );
};

/**
 * Element Sub-settings: The Block Controls for the tab blocks to insert and remove tabs.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block controls.
 */
export const TabBlockControls = ( props ) => {
	const { clientId } = props;

	const isVisible = useSelect(
		( selectFn ) => {
			const block = selectFn( 'core/block-editor' ).getBlock( clientId );
			return block?.attributes?.isVisible || false;
		},
		[ clientId ]
	);

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={ <RenderSVG svg={ isVisible ? 'eye-slash' : 'eye' } /> }
					label={ isVisible ? __( 'Preview/Close', 'spectra-blocks' ) : __( 'Show Tab Panel', 'spectra-blocks' ) }
					onClick={ () => toggleVisibilityByClientId( clientId ) }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
};
