/**
 * Post Block - Grid/Masonry Settings Panel
 *
 * Provides configuration options for grid and masonry layouts including columns
 * and spacing controls.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * Grid/Masonry settings panel component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The grid/masonry settings panel.
 */
export default function GridSettings( { attributes, setAttributes, clientId } ) {
	const { columns } = attributes;

	return (
		<ToolsPanel
			label={ __( 'Grid & Masonry', 'spectra-blocks' ) }
			resetAll={ () => {
				setAttributes( {
					columns: undefined,
				} );
			} }
			panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => columns !== undefined }
				label={ __( 'Columns', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { columns: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					label={ __( 'Columns', 'spectra-blocks' ) }
					value={ columns ?? 3 }
					onChange={ ( value ) => setAttributes( { columns: value } ) }
					min={ 1 }
					max={ 6 }
					step={ 1 }
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
}
