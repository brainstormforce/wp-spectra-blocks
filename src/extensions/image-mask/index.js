/**
 * External dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies.
 */
import Edit from './edit';
import { spectraClassNames } from '@spectra-helpers';

// Get the plugin URL from the localized data and convert to relative path.
const pluginUrl = window?.spectraBlocksExtensions?.pluginUrl || '';

// Extract relative path from full URL for portability (handles subdirectory installs).
const getRelativePath = ( url ) => {
	if ( !url ) {
		return '';
	}
	try {
		const urlObj = new URL( url );
		return urlObj.pathname; // Returns just the path part (e.g., /wp-content/plugins/...)
	} catch ( e ) {
		// If URL parsing fails, assume it's already a relative path.
		return url;
	}
};

const relativePluginPath = getRelativePath( pluginUrl );

/**
 * Add mask class to block in the editor.
 *
 * @param {Object} extraProps - Extra props.
 * @param {Object} blockType - Block type.
 * @param {Object} attributes - Attributes.
 * @return {Object} Extra props.
 */
const addMaskClass = ( extraProps, blockType, attributes ) => {
	if ( blockType.name !== 'core/image' ) {
		return extraProps;
	}

	const { spectraMask } = attributes;

	// Add class if mask shape is selected.
	if ( spectraMask?.shape && spectraMask.shape !== 'none' ) {
		return {
			...extraProps,
			className: spectraClassNames( [
				extraProps.className,
				'spectra-mask'
			] ),
		};
	}

	return extraProps;
};

/**
 * Add mask class to block in the editor.
 *
 * @param {Object} props - Props.
 * @param {Object} props.attributes - Attributes.
 * @param {string} props.name - Name.
 * @return {Object} React element.
 */
const withImageMask = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, isSelected } = props;

		if ( name !== 'core/image' ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && <Edit { ...props } /> }
			</>
		);
	};
}, 'withImageMask' );

/**
 * Add custom attributes and settings to the image block.
 *
 * @param {Object} settings - Settings to be modified.
 * @return {Object} Modified settings.
 */
const addAttributes = ( settings ) => {
	if ( settings.name !== 'core/image' ) {
		return settings;
	}

	const existingGetEditWrapperProps = settings.getEditWrapperProps;

	settings = {
		...settings,
		attributes: {
			...settings.attributes,
			spectraMask: {
				type: 'object',
				default: {
					shape: 'none',
					image: null,
					size: 'auto',
					position: { x: 0.5, y: 0.5 },
					repeat: 'no-repeat'
				}
			}
		},
		getEditWrapperProps: ( attributes ) => {
			const props = existingGetEditWrapperProps ? existingGetEditWrapperProps( attributes ) : {};
			const { spectraMask } = attributes;

			if ( !spectraMask?.shape || spectraMask.shape === 'none' ) {
				return props;
			}

			// Generate mask URL based on shape type.
			let maskImageUrl = '';
			if ( spectraMask.shape && spectraMask.shape !== 'none' && spectraMask.shape !== 'custom' && relativePluginPath ) {
				// For predefined shapes, always generate local path dynamically.
				maskImageUrl = `url( ${relativePluginPath}assets/masks/${spectraMask.shape}.svg )`;
			} else if ( spectraMask.shape === 'custom' && spectraMask.image?.url ) {
				// For custom masks, use the stored URL.
				maskImageUrl = `url( ${ spectraMask.image.url } )`;
			}

			// Convert focal point coordinates to percentage values.
			const position = spectraMask.position || { x: 0.5, y: 0.5 };
			const positionValue = `${position.x * 100}% ${position.y * 100}%`;

			return {
				...props,
				className: spectraClassNames( [
					props.className,
					'spectra-mask'
				] ),
				style: {
					...( props.style || {} ),
					'--spectra-mask-image': maskImageUrl,
					'--spectra-mask-size': spectraMask.size || 'contain',
					'--spectra-mask-position': positionValue,
					'--spectra-mask-repeat': spectraMask.repeat || 'no-repeat',
				},
			};
		},
	};

	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'spectra/image-mask/attributes',
	addAttributes
);
addFilter( 'editor.BlockEdit', 'spectra/image-mask/edit', withImageMask );
addFilter(
	'blocks.getSaveContent.extraProps',
	'spectra/image-mask/add-mask-class',
	addMaskClass
);
