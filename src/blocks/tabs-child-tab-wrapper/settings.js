/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { TabBlockControls } from '@spectra-blocks/tabs/helpers';

/**
 * The Editor settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<TabBlockControls { ...{ ...props, removalType: 'tab-wrapper' } } />
);

export default memo( Settings );
