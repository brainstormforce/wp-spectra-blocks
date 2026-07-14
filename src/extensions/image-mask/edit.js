/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, MediaUpload } from '@wordpress/block-editor';
import {
	Button,
	SelectControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalHStack as HStack,
	FocalPointPicker,
} from '@wordpress/components';

// Get the plugin URL from the localized data.
const pluginUrl = window?.spectraExtensions?.pluginUrl || '';

// Define SVG paths relative to the plugin URL, this is used to get the SVG for the mask, we are not importing the SVGs as they are converted to data URIs when imported and is causing issues with the image path in focal point picker.
const SVG_PATHS = pluginUrl ? {
        circle: `${pluginUrl}assets/masks/circle.svg`,
        diamond: `${pluginUrl}assets/masks/diamond.svg`,
        hexagon: `${pluginUrl}assets/masks/hexagon.svg`,
        rounded: `${pluginUrl}assets/masks/rounded.svg`,
        blob1: `${pluginUrl}assets/masks/blob1.svg`,
        blob2: `${pluginUrl}assets/masks/blob2.svg`,
        blob3: `${pluginUrl}assets/masks/blob3.svg`,
        blob4: `${pluginUrl}assets/masks/blob4.svg`,
} : null;

// Predefined SVG path strings for shape previews, matching the actual SVG files in assets/masks.
const SHAPE_PATHS = {
       circle: '<circle cx="240" cy="190" r="184" fill="#000"/>',
       diamond: '<rect x="106.001" y="56.001" transform="matrix(-0.7071 -0.7071 0.7071 -0.7071 275.3553 494.0559)" width="267.998" height="267.999" fill="#000"/>',
       hexagon: '<polygon points="79.386,97.269 240,4.538 400.614,97.269 400.614,282.73 240,375.462 79.386,282.73" fill="#000"/>',
       rounded: '<path d="M421,309.436C421,343.437,393.437,371,359.436,371H120.564C86.563,371,59,343.437,59,309.436V70.564C59,36.563,86.563,9,120.564,9h238.871C393.437,9,421,36.563,421,70.564V309.436z" fill="#000"/>',
       blob1: '<path d="M47.846,184.442c-87.942,134.709,80.073,196.702,186.331,196.702c104.494,0,222.582-39.417,222.582-160.557C456.758-91.25,198.783-46.776,47.846,184.442z" fill="#000"/>',
       blob2: '<path d="M393.879,31.896c96.935,41.811,41.553,265.103-29.118,320.414c-74.443,58.259-320.428,32.36-330.586-185.032C29.551,68.561,183.588-58.822,393.879,31.896z" fill="#000"/>',
       blob3: '<path d="M141.699,9.958c37.611-41.211,253.977,90.988,263.995,181.115c10.016,90.134-215.692,232.896-280.453,172.106C69.045,310.428,39.531,121.932,141.699,9.958z" fill="#000"/>',
       blob4: '<path d="M69.19,26.334C54.496,39.876,42.91,57.185,35.302,75.221c-10.718,25.408-15.268,52.962-18.384,80.363c-10.069,88.57,17.375,190.72,112.557,217.96c63.844,18.273,133.074-0.437,191.492-27.517c85.828-39.789,206.786-163.646,105.685-255.719C372.3,40.81,284.499,59.485,220.248,32.528c-30.103-12.63-58.445-35.896-92.778-33.125C105.078,1.21,85.272,11.517,69.19,26.334z" fill="#000"/>',
};

/**
 * Edit the image mask block.
 *
 * @param {Object} props - Props.
 * @return {Object} React element.
 */
const Edit = ( props ) => {
	if ( ! pluginUrl ) {
		return null;
	}

	const { attributes, setAttributes, clientId } = props;
	const { spectraMask } = attributes;

	const maskShapeOptions = [
		{ 
			label: __( 'None', 'spectra-blocks' ),
			value: 'none',
		},
		{
			label: __( 'Circle', 'spectra-blocks' ),
			value: 'circle',
		},
		{
			label: __( 'Diamond', 'spectra-blocks' ),
			value: 'diamond',
		},
		{
			label: __( 'Hexagon', 'spectra-blocks' ),
			value: 'hexagon',
		},
		{
			label: __( 'Rounded', 'spectra-blocks' ),
			value: 'rounded',
		},
		{
			label: __( 'Blob 1', 'spectra-blocks' ),
			value: 'blob1',
		},
		{
			label: __( 'Blob 2', 'spectra-blocks' ),
			value: 'blob2',
		},
		{
			label: __( 'Blob 3', 'spectra-blocks' ),
			value: 'blob3',
		},
		{
			label: __( 'Blob 4', 'spectra-blocks' ),
			value: 'blob4',
		},
		{
			label: __( 'Custom', 'spectra-blocks' ),
			value: 'custom',
		},
	];

	const maskSizeOptions = [
		{
			label: __( 'Auto', 'spectra-blocks' ),
			value: 'auto',
		},
		{
			label: __( 'Cover', 'spectra-blocks' ),
			value: 'cover',
		},
		{
			label: __( 'Contain', 'spectra-blocks' ),
			value: 'contain',
		},
	];

	const maskRepeatOptions = [
		{
			label: __( 'No Repeat', 'spectra-blocks' ),
			value: 'no-repeat',
		},
		{
			label: __( 'Repeat', 'spectra-blocks' ),
			value: 'repeat',
		},
		{
			label: __( 'Repeat X', 'spectra-blocks' ),
			value: 'repeat-x',
		},
		{
			label: __( 'Repeat Y', 'spectra-blocks' ),
			value: 'repeat-y',
		},
	];

	// Get mask SVG based on shape.
	const getMaskSVG = ( shape ) => ( SVG_PATHS[shape] || '' );

	// Update mask when shape changes.
	const handleShapeChange = ( value ) => {
		const newMask = { ...spectraMask, shape: value };

		if ( value !== 'custom' && value !== 'none' ) {
			newMask.image = {
				id: value,
				url: getMaskSVG( value ),
			};
		} else if ( value === 'none' ) {
			newMask.image = null;
		}

		setAttributes( { spectraMask: newMask } );
	};

	// Get preview URL for focal point picker.
	const getPreviewUrl = () => {
		// For custom mask, use the uploaded image URL.
		if ( spectraMask.shape === 'custom' ) {
			return spectraMask.image?.url || createShapeDataUri( 'circle' );
		}
		
		// For predefined shapes, create a normalized data URI.
		if ( spectraMask.shape && spectraMask.shape !== 'none' ) {
			return createShapeDataUri( spectraMask.shape );
		}
		
		// Default to circle SVG if no shape is selected.
		return createShapeDataUri( 'circle' );
	};

       // Create a data URI for mask shapes for FocalPointPicker preview as the SVGs are converted to data URIs when imported and this affects the image size in the picker.
       const createShapeDataUri = ( shape ) => {
               const shapePath = SHAPE_PATHS[ shape ] || SHAPE_PATHS.circle;

               const svg = `
                       <svg width="480" height="380" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 380">
                               ${shapePath}
                       </svg>
               `.replace( /\s+/g, ' ' ).trim();

               return `data:image/svg+xml;charset=utf-8,${encodeURIComponent( svg )}`;
       };

	const handleFocalPointChange = ( newFocalPoint ) => {
		setAttributes( {
			spectraMask: {
				...spectraMask,
				position: newFocalPoint
			}
		} );
	};

	const resetAll = () => {
		setAttributes( {
			spectraMask: {
				shape: 'none',
				image: null,
				size: 'auto',
				position: { x: 0.5, y: 0.5 },
				repeat: 'no-repeat',
			}
		} );
	};

	// Check if shape is selected to show additional options.
	const isShapeSelected = spectraMask.shape && spectraMask.shape !== 'none';

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Image Mask from Spectra', 'spectra-blocks' ) }
				resetAll={ resetAll }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The mask shape is not set to 'none'. Default: 'none'.
				- The mask shape is custom and a custom image is uploaded.
				- When reset, all mask-related properties are set to their defaults.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! spectraMask.shape && spectraMask.shape !== 'none' }
					label={ __( 'Shape', 'spectra-blocks' ) }
					onDeselect={ () => handleShapeChange( 'none' ) }
					resetAllFilter={ () => ( {
						spectraMask: { ...spectraMask, shape: 'none' }
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						__next40pxDefaultSize
						label={ __(
							'Shape',
							'spectra-blocks'
						) }
						value={ spectraMask.shape }
						options={ maskShapeOptions }
						onChange={ ( value ) => {
							if ( value === 'custom' ) {
								setAttributes( {
									spectraMask: {
										...spectraMask,
										image: null,
										shape: value,
									}
								} );
							} else {
								handleShapeChange( value );
							}
						} }
						help={ __(
							'Choose a predefined shape, or upload a transparent PNG or SVG as a mask.',
							'spectra-blocks'
						) }
					/>
					{ spectraMask.shape === 'custom' && (
						<MediaUpload
							onSelect={ ( media ) => {
								setAttributes( {
									spectraMask: {
										...spectraMask,
										image: {
											id: media.id,
											url: media.url,
										}
									}
								} );
							} }
							allowedTypes={ [ 'image' ] }
							value={ spectraMask.image?.id }
							render={ ( { open } ) => (
								<>
									{ ! spectraMask.image?.url ? (
										<Button
											onClick={ open }
											variant="secondary"
										>
											{ __(
												'Select Mask Image',
												'spectra-blocks'
											) }
										</Button>
									) : (
										<>
											<img
												src={ spectraMask.image.url }
												alt={ spectraMask.image.alt }
											/>
											<HStack alignment="edge">
												<Button
													onClick={ open }
													variant="secondary"
												>
													{ __(
														'Replace',
														'spectra-blocks'
													) }
												</Button>
												<Button
													onClick={ () =>
														setAttributes( {
															spectraMask: {
																...spectraMask,
																image: null
															}
														} )
													}
													variant="link"
													isDestructive
												>
													{ __(
														'Remove',
														'spectra-blocks'
													) }
												</Button>
											</HStack>
										</>
									) }
								</>
							) }
						/>
					) }
				</ToolsPanelItem>

				{ isShapeSelected && (
					<>
						{/* This tool panel item will require reset when any of these conditions are met:
						- The mask size is not set to 'auto'. Default: 'auto'.
						- Can be set to 'cover' or 'contain' to change how the mask fills the container.
						- When reset, the size is set back to 'auto'.
						*/}
						<ToolsPanelItem
							hasValue={ () => !! spectraMask.size && 'auto' !== spectraMask.size }
							label={ __( 'Size', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { spectraMask: { ...spectraMask, size: 'auto' } } ) }
							resetAllFilter={ () => ( { spectraMask: { ...spectraMask, size: 'auto' } } ) }
							isShownByDefault
							panelId={ clientId }
						>
							<SelectControl
								__next40pxDefaultSize
								label={ __(
									'Size',
									'spectra-blocks'
								) }
								value={ spectraMask.size }
								options={ maskSizeOptions }
								onChange={ ( value ) =>
									setAttributes( { spectraMask: { ...spectraMask, size: value } } )
								}
								help={ __(
									'Control how the mask fills the container.',
									'spectra-blocks'
								) }
							/>
						</ToolsPanelItem>

						{/* This tool panel item will require reset when any of these conditions are met:
						- The mask position is not centered (x: 0.5, y: 0.5). Default: { x: 0.5, y: 0.5 }.
						- Position is controlled via a visual focal point picker for intuitive positioning.
						- When reset, the position is set back to center (x: 0.5, y: 0.5).
						*/}
						<ToolsPanelItem
							hasValue={ () =>
								!! spectraMask.position &&
								( spectraMask.position.x !== 0.5 || spectraMask.position.y !== 0.5 )
							}
							label={ __( 'Position', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( {
									spectraMask: { ...spectraMask, position: { x: 0.5, y: 0.5 } }
								} )
							}
							resetAllFilter={ () => ( { spectraMask: { ...spectraMask, position: { x: 0.5, y: 0.5 } } } ) }
							isShownByDefault
							panelId={ clientId }
						>
							{( spectraMask.shape && spectraMask.shape !== 'none' && (
								( spectraMask.shape === 'custom' && spectraMask.image ) || 
								( spectraMask.shape !== 'custom' )
							) ) && (
								<FocalPointPicker
									__nextHasNoMarginBottom
									help={ __( 'Set the position of the mask within the container.', 'spectra-blocks' ) }
									url={ getPreviewUrl() }
									value={ spectraMask.position || { x: 0.5, y: 0.5 } }
									onDragStart={ handleFocalPointChange }
									onDrag={ handleFocalPointChange }
									onChange={ handleFocalPointChange }
								/>
							)}
						</ToolsPanelItem>

						{/* This tool panel item will require reset when any of these conditions are met:
						- The mask repeat is not set to 'no-repeat'. Default: 'no-repeat'.
						- Can be set to 'repeat', 'repeat-x', or 'repeat-y' to control tiling behavior.
						- When reset, the repeat value is set back to 'no-repeat'.
						*/}
						<ToolsPanelItem
							hasValue={ () => !! spectraMask.repeat && 'no-repeat' !== spectraMask.repeat }
							label={ __( 'Repeat', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( {
									spectraMask: { ...spectraMask, repeat: 'no-repeat' }
								} )
							}
							resetAllFilter={ () => ( { spectraMask: { ...spectraMask, repeat: 'no-repeat' } } ) }
							isShownByDefault
							panelId={ clientId }
						>
							<SelectControl
								__next40pxDefaultSize
								label={ __(
									'Repeat',
									'spectra-blocks'
								) }
								value={ spectraMask.repeat }
								options={ maskRepeatOptions }
								onChange={ ( value ) =>
									setAttributes( {
										spectraMask: { ...spectraMask, repeat: value }
									} )
								}
								help={ __(
									'Choose how the mask should repeat.',
									'spectra-blocks'
								) }
								__nextHasNoMarginBottom
							/>
						</ToolsPanelItem>
					</>
				) }
			</ToolsPanel>
		</InspectorControls>
	);
};

export default Edit;
