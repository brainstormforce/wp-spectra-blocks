/**
 * External dependencies.
 */
import { useDispatch } from '@wordpress/data';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import {
	useBlockProps,
	__experimentalBlockVariationPicker as BlockVariationPicker,
} from '@wordpress/block-editor';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';

/**
 * The variation picker component.
 *
 * @since x.x.x
 *
 * @param {Object} props The required props.
 * @return {Element} The rendered variation picker.
 */
export const VariationPicker = ( props ) => {
	const {
		clientId,
		icon,
		label,
		instructions,
		variations,
		defaultVariation,
		iconSize,
		setAttributes,
		shouldSetAttributes = true,
		shouldReplaceInnerBlocks = true,
		onSelect,
	} = props;

	// Get the required methods.
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	// Function to update the variation when run.
	const blockVariationPickerOnSelect = ( nextVariation = defaultVariation ) => {
		// If custom onSelect handler is provided, use it
		if ( onSelect && typeof onSelect === 'function' ) {
			onSelect( nextVariation );
			return;
		}
		
		// Otherwise use default behavior
		// Set the attributes if required.
		if ( nextVariation.attributes && shouldSetAttributes ) {
			setAttributes( nextVariation.attributes );
		}

		// Set the innerblocks if required.
		if ( nextVariation.innerBlocks && shouldReplaceInnerBlocks ) {
			replaceInnerBlocks( clientId, createBlocksFromInnerBlocksTemplate( nextVariation.innerBlocks ) );
		}
	};

	// Render the block variation picker.
	return (
		<div { ...useBlockProps() } className={ spectraClassNames( [
			'spectra-variation-picker',
			'small' === iconSize && 'spectra-variation-picker--small-icons',
			'big' === iconSize && 'spectra-variation-picker--big-icons',
		] ) } >
			<BlockVariationPicker { ...{
				icon,
				label,
				instructions,
				variations,
				onSelect: ( nextVariation ) => blockVariationPickerOnSelect( nextVariation ),
			} } />
		</div>
	);
};

/**
 * The Render Block Variation component.
 *
 * Renders an image preview for the required block's variation.
 *
 * @since x.x.x
 *
 * @param {Object} props The component props.
 * @return {Element|null} The rendered block variation, or null.
 */
export const RenderBlockVariation = ( props ) => {
	// Destructure the required props.
	const {
		blockName,
		variationName,
		pluginName = 'spectra',
	} = props;

	// If there's no name, abandon ship.
	if ( ! blockName || ! variationName ) {
		return null;
	}

	// Get the plugin URL based on the plugin name.
	// Check for standalone Spectra first, then fall back to UAGB's spectra-v3, then Spectra Pro.
	let imagePath = '';
	switch ( pluginName ) {
		case 'spectra':
			// Check for standalone Spectra plugin first
			if ( window?.spectra_blocks_info?.spectra_url ) {
				imagePath = window.spectra_blocks_info.spectra_url;
			}
			// Fall back to UAGB's spectra-v3 if available
			else if ( window?.uagb_blocks_info?.uagb_url ) {
				imagePath = `${ window.uagb_blocks_info.uagb_url }/spectra-v3`;
			}
			break;
		case 'spectra-pro':
			if ( window?.spectra_pro_blocks_info?.spectra_pro_url ) {
				imagePath = `${ window.spectra_pro_blocks_info.spectra_pro_url }/spectra-pro-v2`;
			}
			break;
	}

	// If no valid path found, return null.
	if ( ! imagePath ) {
		return null;
	}

	// Create the path to the block variation preview image.
	const imageSource = `${ imagePath }/assets/block-variations/${ blockName }/${ variationName }.webp`;

	// Return the rendered block variation preview.
	return <img width="100%" height="100%" src={ imageSource } alt=""/>;
};

export default RenderBlockVariation;
