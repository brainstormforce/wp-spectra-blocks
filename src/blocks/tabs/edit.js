/**
 * Internal dependencies.
 */
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';
import { VariationPicker } from '@spectra-components/variation-picker';
import blockIcons from '@spectra-helpers/block-icons';
import Settings from './settings';
import Render from './render';
import variations from './variations';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Edit = ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		name,
		isSelected,
		setAttributes,
		attributes: { isPreview, variationSelected },
	} = props;

	// When loading, reset the currentTab to 0.
	useEffect( () => {
		setAttributes( { currentTab: 0 } );
	}, [ clientId ] );

	// Get block variations and parents.
	const { defaultVariation, blockChildren } = useSelect( ( select ) => {
		// Use the selectors.
		const { getDefaultBlockVariation } = select( 'core/blocks' );
		const { getBlocks } = select( 'core/block-editor' );

		// Get the parent (if any), the children (if any), and the parent ID (if any).
		const currentBlockChildren = getBlocks( clientId );

		// Return the required data.
		return {
			defaultVariation: getDefaultBlockVariation( name ),
			blockChildren: currentBlockChildren,
		};
	} );

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// Propagate variation selection to inner blocks when required.
	useEffect( () => {
		if ( variationSelected ) {
			blockChildren.forEach( ( child ) => {
				// Mark this child as the default selected variation if it is a container.
				if (
					false === child.attributes?.variationSelected &&
					'spectra/container' === child.name
				) {
					updateBlockAttributes( child.clientId, {
						variationSelected: true,
					} );
				}
			} );
		}
	}, [ variationSelected, blockChildren ] );

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="tabs" />;
	}

	// If there is no variation selected, or no children - render the variation picker.
	// Else just render the block settings and block render.
	return ! variationSelected ? (
		<VariationPicker
			{ ...{
				...props,
				icon: blockIcons.tabs(),
				label: __( 'Tabs', 'spectra-blocks' ),
				instructions: __(
					'Select a tab layout to get started',
					'spectra-blocks'
				),
				variations,
				defaultVariation,
			} }
		/>
	) : (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;
