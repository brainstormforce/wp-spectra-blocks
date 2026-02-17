/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	RangeControl,
	ToggleControl,
	TextControl,
	Button,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';

/**
 * Block Settings: Images, labels, orientation, offset, interaction.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const {
		beforeImageId,
		beforeImageUrl,
		afterImageId,
		afterImageUrl,
		beforeLabel,
		afterLabel,
		orientation,
		initialOffset,
		moveOnHover,
		showLabels,
	} = attributes;

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Before Image', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						beforeImageId: 0,
						beforeImageUrl: '',
						beforeImageAlt: '',
						beforeLabel: 'Before',
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- beforeImageId is set (non-zero).
				- When reset, beforeImageId returns to 0, beforeImageUrl and beforeImageAlt return to ''.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! beforeImageId }
					label={ __( 'Image', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						beforeImageId: 0,
						beforeImageUrl: '',
						beforeImageAlt: '',
					} ) }
					resetAllFilter={ () => ( {
						beforeImageId: 0,
						beforeImageUrl: '',
						beforeImageAlt: '',
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => setAttributes( {
								beforeImageId: media.id,
								beforeImageUrl: media.url,
								beforeImageAlt: media.alt || '',
							} ) }
							allowedTypes={ [ 'image' ] }
							value={ beforeImageId }
							render={ ( { open } ) => (
								<div className="spectra-ba-slider__image-control">
									{ beforeImageUrl && (
										<img
											src={ beforeImageUrl }
											alt=""
											className="spectra-ba-slider__preview-image"
										/>
									) }
									<Button
										variant="secondary"
										onClick={ open }
										className="spectra-ba-slider__image-button"
									>
										{ beforeImageUrl
											? __( 'Replace Image', 'ultimate-addons-for-gutenberg' )
											: __( 'Select Image', 'ultimate-addons-for-gutenberg' )
										}
									</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- beforeLabel is changed from its default value 'Before'.
				- When reset, beforeLabel returns to 'Before'.
				*/}
				<ToolsPanelItem
					hasValue={ () => beforeLabel !== 'Before' }
					label={ __( 'Label', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { beforeLabel: 'Before' } ) }
					resetAllFilter={ () => ( { beforeLabel: 'Before' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Before Label', 'ultimate-addons-for-gutenberg' ) }
						value={ beforeLabel }
						onChange={ ( value ) => setAttributes( { beforeLabel: value } ) }
					/>
				</ToolsPanelItem>
			</ToolsPanel>

			<ToolsPanel
				label={ __( 'After Image', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						afterImageId: 0,
						afterImageUrl: '',
						afterImageAlt: '',
						afterLabel: 'After',
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- afterImageId is set (non-zero).
				- When reset, afterImageId returns to 0, afterImageUrl and afterImageAlt return to ''.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! afterImageId }
					label={ __( 'Image', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						afterImageId: 0,
						afterImageUrl: '',
						afterImageAlt: '',
					} ) }
					resetAllFilter={ () => ( {
						afterImageId: 0,
						afterImageUrl: '',
						afterImageAlt: '',
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) => setAttributes( {
								afterImageId: media.id,
								afterImageUrl: media.url,
								afterImageAlt: media.alt || '',
							} ) }
							allowedTypes={ [ 'image' ] }
							value={ afterImageId }
							render={ ( { open } ) => (
								<div className="spectra-ba-slider__image-control">
									{ afterImageUrl && (
										<img
											src={ afterImageUrl }
											alt=""
											className="spectra-ba-slider__preview-image"
										/>
									) }
									<Button
										variant="secondary"
										onClick={ open }
										className="spectra-ba-slider__image-button"
									>
										{ afterImageUrl
											? __( 'Replace Image', 'ultimate-addons-for-gutenberg' )
											: __( 'Select Image', 'ultimate-addons-for-gutenberg' )
										}
									</Button>
								</div>
							) }
						/>
					</MediaUploadCheck>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- afterLabel is changed from its default value 'After'.
				- When reset, afterLabel returns to 'After'.
				*/}
				<ToolsPanelItem
					hasValue={ () => afterLabel !== 'After' }
					label={ __( 'Label', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { afterLabel: 'After' } ) }
					resetAllFilter={ () => ( { afterLabel: 'After' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'After Label', 'ultimate-addons-for-gutenberg' ) }
						value={ afterLabel }
						onChange={ ( value ) => setAttributes( { afterLabel: value } ) }
					/>
				</ToolsPanelItem>
			</ToolsPanel>

			<ToolsPanel
				label={ __( 'Slider', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						orientation: 'horizontal',
						initialOffset: 50,
						moveOnHover: false,
						showLabels: 'hover',
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- orientation is changed from its default value 'horizontal'.
				- When reset, orientation returns to 'horizontal'.
				*/}
				<ToolsPanelItem
					hasValue={ () => orientation !== 'horizontal' }
					label={ __( 'Orientation', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { orientation: 'horizontal' } ) }
					resetAllFilter={ () => ( { orientation: 'horizontal' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Orientation', 'ultimate-addons-for-gutenberg' ) }
						value={ orientation }
						onChange={ ( value ) => setAttributes( { orientation: value } ) }
						isBlock
					>
						<ToggleGroupControlOption value="horizontal" label={ __( 'Horizontal', 'ultimate-addons-for-gutenberg' ) } />
						<ToggleGroupControlOption value="vertical" label={ __( 'Vertical', 'ultimate-addons-for-gutenberg' ) } />
					</ToggleGroupControl>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- initialOffset is changed from its default value 50.
				- When reset, initialOffset returns to 50.
				*/}
				<ToolsPanelItem
					hasValue={ () => initialOffset !== 50 }
					label={ __( 'Initial Offset', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { initialOffset: 50 } ) }
					resetAllFilter={ () => ( { initialOffset: 50 } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Initial Offset (%)', 'ultimate-addons-for-gutenberg' ) }
						value={ initialOffset }
						onChange={ ( value ) => setAttributes( { initialOffset: value } ) }
						min={ 0 }
						max={ 100 }
					/>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- moveOnHover is true. Default: false.
				- When reset, moveOnHover returns to false.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! moveOnHover }
					label={ __( 'Move on Hover', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { moveOnHover: false } ) }
					resetAllFilter={ () => ( { moveOnHover: false } ) }
					panelId={ clientId }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ moveOnHover }
						label={ __( 'Move on Hover', 'ultimate-addons-for-gutenberg' ) }
						onChange={ () => setAttributes( { moveOnHover: ! moveOnHover } ) }
						help={ __( 'Move the slider handle by hovering over the image instead of dragging.', 'ultimate-addons-for-gutenberg' ) }
					/>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- showLabels is changed from its default value 'hover'.
				- When reset, showLabels returns to 'hover'.
				*/}
				<ToolsPanelItem
					hasValue={ () => showLabels !== 'hover' }
					label={ __( 'Show Labels', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { showLabels: 'hover' } ) }
					resetAllFilter={ () => ( { showLabels: 'hover' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Show Labels', 'ultimate-addons-for-gutenberg' ) }
						value={ showLabels }
						onChange={ ( value ) => setAttributes( { showLabels: value } ) }
						isBlock
					>
						<ToggleGroupControlOption value="hover" label={ __( 'Hover', 'ultimate-addons-for-gutenberg' ) } />
						<ToggleGroupControlOption value="always" label={ __( 'Always', 'ultimate-addons-for-gutenberg' ) } />
						<ToggleGroupControlOption value="none" label={ __( 'None', 'ultimate-addons-for-gutenberg' ) } />
					</ToggleGroupControl>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Dimension Settings: Handle thickness and circle size.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const DimensionSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			handleThickness,
			handleCircleSize,
		},
	} = props;

	return (
		<InspectorControls group="dimensions">
			{/* This tool panel item will require reset when any of these conditions are met:
			- handleThickness is set. Default: undefined.
			- When reset, handleThickness returns to undefined.
			*/}
			<ToolsPanelItem
				hasValue={ () => handleThickness !== undefined }
				label={ __( 'Handle Thickness', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { handleThickness: undefined } ) }
				resetAllFilter={ () => ( { handleThickness: undefined } ) }
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Handle Thickness', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ handleThickness !== undefined ? `${ handleThickness }px` : '' }
					min={ 1 }
					max={ 20 }
					onChange={ ( value ) => setAttributes( { handleThickness: parseInt( value ) || undefined } ) }
					units={ [ { value: 'px', label: 'px' } ] }
				/>
			</ToolsPanelItem>
			{/* This tool panel item will require reset when any of these conditions are met:
			- handleCircleSize is set. Default: undefined.
			- When reset, handleCircleSize returns to undefined.
			*/}
			<ToolsPanelItem
				hasValue={ () => handleCircleSize !== undefined }
				label={ __( 'Handle Circle Size', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { handleCircleSize: undefined } ) }
				resetAllFilter={ () => ( { handleCircleSize: undefined } ) }
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Handle Circle Size', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ handleCircleSize !== undefined ? `${ handleCircleSize }px` : '' }
					min={ 20 }
					max={ 150 }
					onChange={ ( value ) => setAttributes( { handleCircleSize: parseInt( value ) || undefined } ) }
					units={ [ { value: 'px', label: 'px' } ] }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Color Settings: Overlay, handle, label colors.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ColorSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			overlayColor,
			beforeOverlayColor,
			afterOverlayColor,
			handleColor,
			labelColor,
			labelBackgroundColor,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: overlayColor,
					label: __( 'Overlay (Both)', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { overlayColor: value } ),
					resetAllFilter: () => setAttributes( { overlayColor: undefined } ),
				},
				{
					colorValue: beforeOverlayColor,
					label: __( 'Before Overlay', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { beforeOverlayColor: value } ),
					resetAllFilter: () => setAttributes( { beforeOverlayColor: undefined } ),
				},
				{
					colorValue: afterOverlayColor,
					label: __( 'After Overlay', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { afterOverlayColor: value } ),
					resetAllFilter: () => setAttributes( { afterOverlayColor: undefined } ),
				},
				{
					colorValue: handleColor,
					label: __( 'Handle', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { handleColor: value } ),
					resetAllFilter: () => setAttributes( { handleColor: undefined } ),
				},
				{
					colorValue: labelColor,
					label: __( 'Label Text', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { labelColor: value } ),
					resetAllFilter: () => setAttributes( { labelColor: undefined } ),
				},
				{
					colorValue: labelBackgroundColor,
					label: __( 'Label Background', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { labelBackgroundColor: value } ),
					resetAllFilter: () => setAttributes( { labelBackgroundColor: undefined } ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * The Editor settings for this block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
