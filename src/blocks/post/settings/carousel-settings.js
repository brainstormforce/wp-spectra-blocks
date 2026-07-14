/**
 * Post Block - Carousel Settings Panel
 *
 * Provides configuration options for carousel layout including slides per view,
 * spacing, autoplay, and navigation controls.
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
	ToggleControl,
} from '@wordpress/components';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * Carousel settings panel component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The carousel settings panel.
 */
export default function CarouselSettings( { attributes, setAttributes, clientId } ) {
	const {
		slidesPerView,
		spaceBetween,
		loop,
		speed,
		autoplay,
		autoplaySpeed,
		autoplayPauseOnHover,
		navigation,
		pagination,
	} = attributes;

	return (
		<ToolsPanel
			label={ __( 'Carousel', 'spectra-blocks' ) }
			resetAll={ () => {
				setAttributes( {
					slidesPerView: undefined,
					spaceBetween: undefined,
					loop: true,
					speed: 500,
					autoplay: true,
					autoplaySpeed: 2000,
					autoplayPauseOnHover: true,
					navigation: true,
					pagination: true,
				} );
			} }
			panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => !! slidesPerView }
				label={ __( 'Slides Per View', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { slidesPerView: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					label={ __( 'Slides Per View', 'spectra-blocks' ) }
					value={ slidesPerView ?? 3 }
					onChange={ ( value ) => setAttributes( { slidesPerView: value } ) }
					min={ 1 }
					max={ 6 }
					step={ 1 }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => !! spaceBetween }
				label={ __( 'Space Between', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { spaceBetween: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					label={ __( 'Space Between', 'spectra-blocks' ) }
					value={ spaceBetween ?? 30 }
					onChange={ ( value ) => setAttributes( { spaceBetween: value } ) }
					min={ 1 }
					max={ 100 }
					step={ 1 }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => attributes.equalHeight }
				label={ __( 'Equal Height', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { equalHeight: true } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Equal Height', 'spectra-blocks' ) }
					checked={ attributes.equalHeight }
					onChange={ ( value ) => setAttributes( { equalHeight: value } ) }
					help={ __(
						'Make all slides equal height based on the tallest slide.',
						'spectra-blocks'
					) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => loop === false }
				label={ __( 'Infinite Loop', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { loop: true } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Infinite Loop', 'spectra-blocks' ) }
					checked={ loop }
					onChange={ ( value ) => setAttributes( { loop: value } ) }
					help={ __(
						'Enable continuous sliding by looping back to the first slide after the last.',
						'spectra-blocks'
					) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => autoplay === false }
				label={ __( 'Autoplay', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { autoplay: true } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Autoplay', 'spectra-blocks' ) }
					checked={ autoplay }
					onChange={ ( value ) => setAttributes( { autoplay: value } ) }
					help={ __(
						'Automatically cycle through posts at a chosen interval.',
						'spectra-blocks'
					) }
				/>
			</ToolsPanelItem>

			{ autoplay && (
				<>
					<ToolsPanelItem
						hasValue={ () => ( autoplaySpeed ?? 2000 ) !== 2000 }
						label={ __( 'Autoplay Speed', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { autoplaySpeed: 2000 } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<DebouncedRangeControl
							label={ __( 'Autoplay Speed (ms)', 'spectra-blocks' ) }
							value={ autoplaySpeed }
							onChange={ ( value ) => setAttributes( { autoplaySpeed: value } ) }
							min={ 100 }
							max={ 10000 }
							step={ 100 }
							help={ __( 'Set the wait time between each auto-slide.', 'spectra-blocks' ) }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => autoplayPauseOnHover === false }
						label={ __( 'Pause On Hover', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { autoplayPauseOnHover: true } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Pause On Hover', 'spectra-blocks' ) }
							checked={ autoplayPauseOnHover }
							onChange={ ( value ) => setAttributes( { autoplayPauseOnHover: value } ) }
						/>
					</ToolsPanelItem>
				</>
			) }

			<ToolsPanelItem
				hasValue={ () => ( speed ?? 500 ) !== 500 }
				label={ __( 'Transition Speed', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { speed: 500 } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<DebouncedRangeControl
					label={ __( 'Transition Speed (ms)', 'spectra-blocks' ) }
					value={ speed }
					onChange={ ( value ) => setAttributes( { speed: value } ) }
					min={ 100 }
					max={ 5000 }
					step={ 100 }
					help={ __( 'Define how fast the sliding animation occurs.', 'spectra-blocks' ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => navigation === false }
				label={ __( 'Show Arrows', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { navigation: true } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Arrows', 'spectra-blocks' ) }
					checked={ navigation }
					onChange={ ( value ) => setAttributes( { navigation: value } ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => pagination === false }
				label={ __( 'Show Dots', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { pagination: true } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Show Dots', 'spectra-blocks' ) }
					checked={ pagination }
					onChange={ ( value ) => setAttributes( { pagination: value } ) }
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
}
