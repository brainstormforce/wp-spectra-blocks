/**
 * The Render Block Preview component.
 *
 * @since x.x.x
 *
 * @param {Object} props The component props.
 * @return {Element|null} The rendered block preview, or null.
 */
const RenderBlockPreview = ( props ) => {
	const {
		blockName,
		childPath = '',
		pluginName = 'spectra',
		width = 24,
		height = 24,
	} = props;
	
	// If the url for the current block's assets isn't available, abandon ship.
	if (
		!['spectra', 'spectra-pro'].includes( pluginName )
		|| ( 'spectra' === pluginName && !spectra_blocks_react?.plugin_dir )
	) {
		
		return null;
	}
	// If there's no name, abandon ship.
	if ( !blockName ) {
		return null;
	}

	// Set the path to the image.
	let imagePath = '';
	switch ( pluginName ) {
		case 'spectra':
			imagePath = `${spectra_blocks_react?.plugin_dir}/admin`;
			break;
	}
	// Create the path to the block preview image.
	// Note that if this block is a child block, add the parent block to the path with a trailing shash.
	const imageSource = `${imagePath}/assets/block-previews/${childPath ? childPath + '/' : ''}${blockName}.webp`;
	// Return the rendered block preview.
	return <img width={width} height={height} src={imageSource} alt="" />;
};

export default RenderBlockPreview;
