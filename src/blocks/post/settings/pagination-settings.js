/**
 * Post Block - Pagination Settings Panel
 *
 * Provides pagination configuration options including type selection,
 * labels, alignment, and layout styles.
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
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { alignLeft, alignCenter, alignRight } from '@wordpress/icons';

/**
 * Pagination settings panel component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The pagination settings panel.
 */
const PaginationSettings = ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const {
		paginationType,
		paginationPrevLabel,
		paginationNextLabel,
		paginationButtonText,
		paginationLoadingText,
		paginationAlignment,
		paginationLayout,
	} = attributes;

	return (
		<ToolsPanel
			label={ __( 'Pagination', 'spectra-blocks' ) }
			resetAll={ () => {
				setAttributes( {
					paginationType: 'none',
					paginationPrevLabel: '« Previous',
					paginationNextLabel: 'Next »',
					paginationButtonText: 'Load More',
					paginationLoadingText: 'Loading...',
					paginationAlignment: 'center',
					paginationLayout: 'filled',
					paginationColor: undefined,
					paginationBackgroundColor: undefined,
					paginationHoverColor: undefined,
					paginationBackgroundHoverColor: undefined,
					paginationActiveColor: undefined,
					paginationBackgroundActiveColor: undefined,
				} );
			} }
			panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => paginationType !== 'none' }
				label={ __( 'Pagination Type', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { paginationType: 'none' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<SelectControl
					label={ __( 'Pagination Type', 'spectra-blocks' ) }
					value={ paginationType }
					options={ [
						{ label: __( 'None', 'spectra-blocks' ), value: 'none' },
						{ label: __( 'Numbers', 'spectra-blocks' ), value: 'standard' },
						{ label: __( 'Load More Button', 'spectra-blocks' ), value: 'button' },
						{ label: __( 'Infinite Scroll', 'spectra-blocks' ), value: 'infinite' },
					] }
					onChange={ ( value ) => setAttributes( { paginationType: value } ) }
					help={
						{
							none: __(
								'Choose how pagination should work for this block.',
								'spectra-blocks'
							),
							standard: __(
								'Classic numbered pagination links (great for SEO).',
								'spectra-blocks'
							),
							button: __(
								'Users click a button to load more posts without reloading the page.',
								'spectra-blocks'
							),
							infinite: __(
								'New posts load automatically as the user scrolls down the page.',
								'spectra-blocks'
							),
						}[ paginationType ]
					}
				/>
			</ToolsPanelItem>

			{ paginationType === 'standard' && (
				<>
					<ToolsPanelItem
						hasValue={ () => paginationPrevLabel !== '« Previous' }
						label={ __( 'Previous Label', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { paginationPrevLabel: '« Previous' } ) }
						panelId={ clientId }
						isShownByDefault
					>
						<TextControl
							label={ __( 'Previous Label', 'spectra-blocks' ) }
							value={ paginationPrevLabel }
							onChange={ ( value ) => setAttributes( { paginationPrevLabel: value } ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => paginationNextLabel !== 'Next »' }
						label={ __( 'Next Label', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { paginationNextLabel: 'Next »' } ) }
						panelId={ clientId }
						isShownByDefault
					>
						<TextControl
							label={ __( 'Next Label', 'spectra-blocks' ) }
							value={ paginationNextLabel }
							onChange={ ( value ) => setAttributes( { paginationNextLabel: value } ) }
						/>
					</ToolsPanelItem>
				</>
			) }

			{ paginationType === 'button' && (
				<>
					<ToolsPanelItem
						hasValue={ () => paginationButtonText !== 'Load More' }
						label={ __( 'Button Text', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { paginationButtonText: 'Load More' } ) }
						panelId={ clientId }
						isShownByDefault
					>
						<TextControl
							label={ __( 'Button Text', 'spectra-blocks' ) }
							value={ paginationButtonText }
							onChange={ ( value ) => setAttributes( { paginationButtonText: value } ) }
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						hasValue={ () => paginationLoadingText !== 'Loading...' }
						label={ __( 'Loading Text', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { paginationLoadingText: 'Loading...' } ) }
						panelId={ clientId }
						isShownByDefault
					>
						<TextControl
							label={ __( 'Loading Text', 'spectra-blocks' ) }
							value={ paginationLoadingText }
							onChange={ ( value ) => setAttributes( { paginationLoadingText: value } ) }
						/>
					</ToolsPanelItem>
				</>
			) }

			{ ( paginationType === 'standard' || paginationType === 'button' ) && (
				<ToolsPanelItem
					hasValue={ () => paginationAlignment !== 'center' }
					label={ __( 'Pagination Alignment', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { paginationAlignment: 'center' } ) }
					panelId={ clientId }
					isShownByDefault
				>
					<ToggleGroupControl
						label={ __( 'Pagination Alignment', 'spectra-blocks' ) }
						value={ paginationAlignment }
						onChange={ ( value ) => setAttributes( { paginationAlignment: value } ) }
						isBlock
					>
						<ToggleGroupControlOption
							value="left"
							icon={ alignLeft }
							label={ __( 'Left', 'spectra-blocks' ) }
						/>
						<ToggleGroupControlOption
							value="center"
							icon={ alignCenter }
							label={ __( 'Center', 'spectra-blocks' ) }
						/>
						<ToggleGroupControlOption
							value="right"
							icon={ alignRight }
							label={ __( 'Right', 'spectra-blocks' ) }
						/>
					</ToggleGroupControl>
				</ToolsPanelItem>
			) }

			{ paginationType === 'standard' && (
				<ToolsPanelItem
					hasValue={ () => paginationLayout !== 'filled' }
					label={ __( 'Pagination Layout', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { paginationLayout: 'filled' } ) }
					panelId={ clientId }
					isShownByDefault
				>
					<ToggleGroupControl
						label={ __( 'Pagination Layout', 'spectra-blocks' ) }
						value={ paginationLayout }
						onChange={ ( value ) => setAttributes( { paginationLayout: value } ) }
						isBlock
					>
						<ToggleGroupControlOption
							value="border"
							label={ __( 'Border', 'spectra-blocks' ) }
						/>
						<ToggleGroupControlOption
							value="filled"
							label={ __( 'Filled', 'spectra-blocks' ) }
						/>
					</ToggleGroupControl>
				</ToolsPanelItem>
			) }
		</ToolsPanel>
	);
};

export default PaginationSettings;
