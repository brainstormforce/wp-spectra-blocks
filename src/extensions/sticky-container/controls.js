/**
 * External dependencies.
 */
import { InspectorControls, useSettings, store as blockEditorStore } from '@wordpress/block-editor';
import {
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent, useInstanceId } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { DEFAULT_STICKY_SETTINGS, DEFAULT_OFFSET, STICKY_POSITION, MIN_OFFSET, MAX_OFFSET } from './utils/constants';
import { isAllowedBlock } from './utils/helpers';

/**
 * Sticky Container Options Panel.
 *
 * Provides UI controls for managing sticky positioning on container blocks,
 * allowing users to enable sticky behavior, choose position (top/bottom), and set offset.
 *
 * @since x.x.x
 *
 * @param {Object} props - Component props.
 * @return {Element} Sticky container options panel component.
 */
const StickyContainerOptions = ( props ) => {
	const {
		setAttributes,
		clientId,
		attributes: { stickyContainer = DEFAULT_STICKY_SETTINGS },
	} = props;

	// Generate unique instance ID for this component.
	const instanceId = useInstanceId( StickyContainerOptions );
	const stickAtId = `spectra-sticky-container-stick-at-${ clientId }-${ instanceId }`;

	// Check if this is an inner container (nested within another container).
	const isInnerContainer = useSelect(
		( select ) => {
			const { getBlockParents } = select( blockEditorStore );
			const parentIds = getBlockParents( clientId );

			// Check if this block has any parent blocks.
			return parentIds.length > 0;
		},
		[ clientId ]
	);

	// Get available units from theme settings.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', 'rem', 'em', '%', 'vh', 'vw' ],
	} );

	/**
	 * Updates sticky container settings.
	 *
	 * @param {string} key   - Setting key to update.
	 * @param {*}      value - New value for the setting.
	 */
	const updateStickySettings = ( key, value ) => {
		setAttributes( {
			stickyContainer: {
				...stickyContainer,
				[ key ]: value,
			},
		} );
	};

	return (
		<>
			<ToolsPanelItem
				label={ __( 'Sticky Container', 'ultimate-addons-for-gutenberg' ) }
				hasValue={ () => stickyContainer.stickyEnabled }
				onDeselect={ () => updateStickySettings( 'stickyEnabled', false ) }
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Sticky Container', 'ultimate-addons-for-gutenberg' ) }
					help={ __(
						'Keeps this container fixed while scrolling. Note: Sticky behavior works on the frontend only.',
						'ultimate-addons-for-gutenberg'
					) }
					checked={ stickyContainer.stickyEnabled }
					onChange={ ( value ) => updateStickySettings( 'stickyEnabled', value ) }
				/>
			</ToolsPanelItem>

			{ stickyContainer.stickyEnabled && (
				<>
					<ToolsPanelItem
						label={ __( 'Stick at', 'ultimate-addons-for-gutenberg' ) }
						hasValue={ () => stickyContainer.stickAt !== DEFAULT_STICKY_SETTINGS.stickAt }
						onDeselect={ () => updateStickySettings( 'stickAt', DEFAULT_STICKY_SETTINGS.stickAt ) }
						isShownByDefault={ true }
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							label={ __( 'Stick at', 'ultimate-addons-for-gutenberg' ) }
							help={ __(
								'Choose whether the container sticks to the top or bottom of the screen.',
								'ultimate-addons-for-gutenberg'
							) }
							value={ stickyContainer.stickAt }
							onChange={ ( value ) => updateStickySettings( 'stickAt', value ) }
							isBlock
							id={ stickAtId }
						>
							<ToggleGroupControlOption
								value={ STICKY_POSITION.TOP }
								label={ __( 'Top', 'ultimate-addons-for-gutenberg' ) }
							/>
							<ToggleGroupControlOption
								value={ STICKY_POSITION.BOTTOM }
								label={ __( 'Bottom', 'ultimate-addons-for-gutenberg' ) }
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>

					<ToolsPanelItem
						label={ __( 'Offset', 'ultimate-addons-for-gutenberg' ) }
						hasValue={ () => stickyContainer.offset !== DEFAULT_OFFSET }
						onDeselect={ () => updateStickySettings( 'offset', DEFAULT_OFFSET ) }
						isShownByDefault={ true }
					>
						<UnitControl
							__next40pxDefaultSize
							label={ __( 'Offset', 'ultimate-addons-for-gutenberg' ) }
							help={ __(
								'Set how far from the edge the container stays when sticky. Positive values add space; negative values allow overlap.',
								'ultimate-addons-for-gutenberg'
							) }
							onChange={ ( value ) => {
								updateStickySettings( 'offset', value || '0px' );
							} }
							value={
								stickyContainer.offset !== null && stickyContainer.offset !== undefined
									? stickyContainer.offset
									: '0px'
							}
							units={ units }
							max={ MAX_OFFSET }
							min={ MIN_OFFSET }
						/>
					</ToolsPanelItem>

					{ stickyContainer.stickAt === STICKY_POSITION.TOP && (
						<ToolsPanelItem
							label={ __( 'Keep Inside Parent', 'ultimate-addons-for-gutenberg' ) }
							hasValue={ () =>
								stickyContainer.keepInsideParent !== DEFAULT_STICKY_SETTINGS.keepInsideParent
							}
							onDeselect={ () =>
								updateStickySettings( 'keepInsideParent', DEFAULT_STICKY_SETTINGS.keepInsideParent )
							}
							isShownByDefault={ isInnerContainer }
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Keep Inside Parent', 'ultimate-addons-for-gutenberg' ) }
								help={ __(
									'Restricts the sticky container to stay within its parent container boundaries.',
									'ultimate-addons-for-gutenberg'
								) }
								checked={ stickyContainer.keepInsideParent }
								onChange={ ( value ) => updateStickySettings( 'keepInsideParent', value ) }
							/>
						</ToolsPanelItem>
					) }
				</>
			) }
		</>
	);
};

/**
 * Higher-order component to add sticky container controls to block settings.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - Original block edit component.
 * @return {Function} Enhanced block edit component with sticky container controls.
 */
const addStickyContainerControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! isAllowedBlock( { name: props.name } ) ) {
			return <BlockEdit { ...props } />;
		}

		const { setAttributes, isSelected } = props;

		const resetAll = () => {
			setAttributes( {
				stickyContainer: DEFAULT_STICKY_SETTINGS,
			} );
		};

		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && (
					<InspectorControls>
						<ToolsPanel label={ __( 'Sticky', 'ultimate-addons-for-gutenberg' ) } resetAll={ resetAll }>
							<StickyContainerOptions { ...props } />
						</ToolsPanel>
					</InspectorControls>
				) }
			</>
		);
	};
}, 'addStickyContainerControls' );

export default addStickyContainerControls;
