/**
 * External dependencies.
 */
import { useState, useMemo, useCallback, memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	useBaseControlProps,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

/**
 * Internal dependencies.
 */
import RenderSVG from '@spectra-helpers/render-svg';
import ModalContainer from './modal-container';

/**
 * Reusable button component for consistent styling and behavior.
 */
const PickerButton = memo( ( { onClick, onKeyDown, className, ariaLabel, children } ) => {
	const handleKeyDown = useCallback( ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			onClick();
		}
	}, [ onClick ] );

	return (
		<div 
			className={ className }
			onClick={ onClick }
			role="button"
			tabIndex={ 0 }
			onKeyDown={ onKeyDown || handleKeyDown }
			aria-label={ ariaLabel }
		>
			{ children }
		</div>
	);
} );

/**
 * Reusable remove button component.
 */
const RemoveButton = memo( ( { onRemove, type = 'icon' } ) => {
	// Simple string comparison - no memoization needed
	const ariaLabel = type === 'SVG' ? __( 'Remove SVG', 'ultimate-addons-for-gutenberg' ) : __( 'Remove icon', 'ultimate-addons-for-gutenberg' );
	
	return (
		<button
			className="spectra-icon-picker__button--remove"
			onClick={ onRemove }
			aria-label={ ariaLabel }
		>
			<RenderSVG svg='xmark'/>
		</button>
	);
} );

/**
 * Reusable select button component.
 */
const SelectButton = memo( ( { onClick, isAvailable, type = 'icon' } ) => {
	// Simple conditional logic - no memoization needed for string operations
	const getText = () => {
		if ( type === 'SVG' ) {
			return isAvailable ? __( 'Change SVG', 'ultimate-addons-for-gutenberg' ) : __( 'Choose SVG', 'ultimate-addons-for-gutenberg' );
		}
		return isAvailable ? __( 'Change Icon', 'ultimate-addons-for-gutenberg' ) : __( 'Choose Icon', 'ultimate-addons-for-gutenberg' );
	};
	
	const buttonText = getText();

	return (
		<button 
			className="spectra-icon-picker__button--select" 
			onClick={ onClick }
			aria-label={ buttonText }
		>
			{ buttonText }
		</button>
	);
} );

/**
 * The Icon Picker Setting component.
 * Extracted as a separate component to prevent unnecessary re-renders.
 *
 * @param {Object} props The component props.
 * @since x.x.x
 * @return {Element} The icon picker setting component.
 */
const IconPickerSetting = memo( ( props ) => {
	const {
		enableImage,
		currentSourceType,
		handleSourceTypeChange,
		isIconAvailable,
		isSvgAvailable,
		value,
		onChange,
		openModal,
		handleSvgSelect,
		handleSvgRemove,
	} = props;

	const wrapperClasses = [
		'spectra-icon-picker',
		enableImage && 'spectra-icon-picker--with-toggle'
	].filter( Boolean ).join( ' ' );


	return (
		<>
			{ /* Source type toggle when image is enabled */ }
			{ enableImage && (
				<ToggleGroupControl
					label={ __( 'Source Type', 'ultimate-addons-for-gutenberg' ) }
					value={ currentSourceType }
					onChange={ handleSourceTypeChange }
					isBlock
					className="spectra-icon-picker-toggle"
				>
					<ToggleGroupControlOption
						value="icon"
						label={ __( 'Icon Library', 'ultimate-addons-for-gutenberg' ) }
					/>
					<ToggleGroupControlOption
						value="image"
						label={ __( 'Upload SVG', 'ultimate-addons-for-gutenberg' ) }
					/>
				</ToggleGroupControl>
			) }

			<div className={ wrapperClasses }>
				{ /* Icon picker interface */ }
				{ currentSourceType === 'icon' && (
					<>
						{ isIconAvailable && <RemoveButton onRemove={ () => onChange( undefined ) } type="icon" /> }
						<PickerButton
							className="spectra-icon-picker__control"
							onClick={ openModal }
							ariaLabel={ isIconAvailable ? __( 'Change icon', 'ultimate-addons-for-gutenberg' ) : __( 'Choose icon', 'ultimate-addons-for-gutenberg' ) }
						>
							{ isIconAvailable ? (
								<div className="spectra-icon-picker__control--value">
									<RenderSVG svg={ value }/>
								</div>
							) : (
								<div className="spectra-icon-picker__control--empty">
									<RenderSVG svg='circle-plus'/>
								</div>
							) }
						</PickerButton>
						<SelectButton onClick={ openModal } isAvailable={ isIconAvailable } type="icon" />
					</>
				) }

				{ /* SVG picker interface */ }
				{ currentSourceType === 'image' && (
					<>
						{ isSvgAvailable && <RemoveButton onRemove={ handleSvgRemove } type="SVG" /> }
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ handleSvgSelect }
								allowedTypes={ [ 'image/svg+xml' ] }
								render={ ( { open } ) => (
									<>
										<PickerButton
											className="spectra-icon-picker__control"
											onClick={ open }
											ariaLabel={ isSvgAvailable ? __( 'Change SVG', 'ultimate-addons-for-gutenberg' ) : __( 'Choose SVG', 'ultimate-addons-for-gutenberg' ) }
										>
											{ isSvgAvailable ? (
												<div className="spectra-icon-picker__control--value spectra-icon-picker__control--image">
													<RenderSVG 
														svg={ value }
														extraProps={ {
															style: {
																maxWidth: '32px',
																maxHeight: '32px',
																width: '100%',
																height: 'auto',
															}
														} }
													/>
												</div>
											) : (
												<div className="spectra-icon-picker__control--empty">
													<RenderSVG svg='image'/>
												</div>
											) }
										</PickerButton>
										<SelectButton onClick={ open } isAvailable={ isSvgAvailable } type="SVG" />
									</>
								) }
							/>
						</MediaUploadCheck>
					</>
				) }
			</div>
		</>
	);
} );

/**
 * Internal Icon Picker component with all hooks.
 *
 * @param {Object} props The component props.
 */
const IconPickerInternal = ( props ) => {

	const {
		label,
		value,
		onChange,
		help = false,
		enableImage = true, // Enable SVG uploads by default for all icon pickers
		sourceType = 'icon',
		onSourceTypeChange = null,
	} = props;

	const [ isOpen, setOpen ] = useState( false );
	const [ manualSourceType, setManualSourceType ] = useState( null );
	
	// Source type detection with manual override support
	const currentSourceType = useMemo( () => {
		// If manually set, use that
		if ( manualSourceType ) {
			return manualSourceType;
		}
		
		// Auto-detect from value
		if ( value && typeof value === 'object' && value.library === 'svg' ) {
			return 'image';
		}
		if ( value && typeof value === 'string' ) {
			return 'icon';
		}
		
		// Default to the provided sourceType
		return sourceType;
	}, [ value, sourceType, manualSourceType ] );
	

	// Get the base control props to create this custom control.
	const { baseControlProps, controlProps } = useBaseControlProps( {
		label,
		help,
	} );

	// Get the stored icons and category lists (memoized to prevent recreation)
	const defaultIcons = useMemo( () => [ ...wp.UAGBSvgIcons ], [] );
	const iconCategoryList = useMemo( () => [ ...wp.uagb_icon_category_list ], [] );

	// State and functions for the modal.
	const openModal = useCallback( () => {
		if ( currentSourceType === 'icon' ) {
			setOpen( true );
		}
	}, [ currentSourceType ] );
	const closeModal = useCallback( () => setOpen( false ), [] );

	// Check availability - simplified
	const isIconAvailable = useMemo( () => {
		return !! value && currentSourceType === 'icon';
	}, [ value, currentSourceType ] );
	
	const isSvgAvailable = useMemo( () => {
		return !! value && currentSourceType === 'image';
	}, [ value, currentSourceType ] );


	// Handle source type change (simplified)
	const handleSourceTypeChange = useCallback( ( newType ) => {
		// Close any open modal when switching modes
		if ( isOpen ) {
			setOpen( false );
		}
		
		// Set manual source type to override auto-detection
		setManualSourceType( newType );
		
		if ( onSourceTypeChange ) {
			onSourceTypeChange( newType );
		}
		// Clear value when manually switching types to avoid confusion
		if ( value ) {
			onChange( undefined );
		}
	}, [ onSourceTypeChange, value, onChange, isOpen ] );

	// Handle SVG selection - store in Elementor format
	const handleSvgSelect = useCallback( ( media ) => {
		if ( ! media?.id ) {
			onChange( undefined );
			return;
		}

		// Store in Elementor format: { library: 'svg', value: { id: attachmentId, url: mediaUrl } }
		onChange( {
			library: 'svg',
			value: {
				id: media.id,
				url: media.url,
				source_url: media.source_url || media.url
			}
		} );
	}, [ onChange ] );

	// Handle SVG removal
	const handleSvgRemove = useCallback( () => {
		onChange( undefined );
	}, [ onChange ] );

	// Return the control.
	return (
		<BaseControl
			{ ...baseControlProps }
			__nextHasNoMarginBottom
		>
			<div {...controlProps}>
				<IconPickerSetting 
					enableImage={ enableImage }
					currentSourceType={ currentSourceType }
					handleSourceTypeChange={ handleSourceTypeChange }
					isIconAvailable={ isIconAvailable }
					isSvgAvailable={ isSvgAvailable }
					value={ value }
					onChange={ onChange }
					openModal={ openModal }
					handleSvgSelect={ handleSvgSelect }
					handleSvgRemove={ handleSvgRemove }
				/>
				{ isOpen && currentSourceType === 'icon' && (
					<ModalContainer
						{ ...{
							...props,
							closeModal,
							defaultIcons,
							iconCategoryList,
						} }
					/>
				) }
			</div>
		</BaseControl>
	);
};

/**
 * The Icon Picker component wrapper.
 *
 * @param {Object} props The component props.
 * @since x.x.x
 * @return {Element|null} The icon picker, or null.
 */
const IconPicker = ( props ) => {
	// If the required localization asset isn't available, abandon ship.
	if ( ! wp.UAGBSvgIcons || ! wp.uagb_icon_category_list || ! window?.uagb_blocks_info?.uagb_svg_icons ) {
		return null;
	}

	return <IconPickerInternal { ...props } />;
};

export default memo( IconPicker );
