/**
 * External dependencies.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	SelectControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { useCallback, useRef } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { ANIMATION_LIST } from './utils/constants';
import { isAllowedBlock } from './utils/helpers';

/**
 * Animation Options Panel.
 *
 * Provides UI controls for managing block animations, allowing users to select
 * animation types, preview animations, and modify animation settings.
 *
 * @since x.x.x
 *
 * @param {Object} props - Component props.
 * @return {Element} Animation options panel component.
 */
const AnimationOptions = ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			spectraAnimationType,
			spectraAnimationTime = 400,
			spectraAnimationDelay = 0,
			spectraAnimationEasing = 'ease',
		},
	} = props;

	const animationTimeouts = useRef( {} );

	/**
	 * Triggers a block animation preview in the block editor.
	 *
	 * @param {string} [animationType=spectraAnimationType] - Animation type to preview.
	 */
	const playAnimation = useCallback(
		( animationType = spectraAnimationType ) => {
			// Only perform expensive DOM operations when block is selected
			if ( ! props.isSelected ) return;

			const editorIframe = document.querySelector( 'iframe[name="editor-canvas"]' );
			const innerDoc = editorIframe?.contentDocument || editorIframe?.contentWindow?.document;

			// Handle both iframe and non-iframe.
			const animatedBlock = innerDoc
				? innerDoc.getElementById( `block-${ clientId }` )
				: document.getElementById( `block-${ clientId }` );

			if ( ! animatedBlock ) return;

			// Clear previous timeouts.
			clearTimeout( animationTimeouts.current?.start );
			clearTimeout( animationTimeouts.current?.end );

			// Reset animation.
			animatedBlock.removeAttribute( 'data-aos' );
			animatedBlock.classList.remove( 'aos-animate' );

			// Set animation properties.
			animatedBlock.style.transitionDuration = '0s';
			animatedBlock.setAttribute( 'data-aos', animationType );
			animatedBlock.style.transitionTimingFunction = spectraAnimationEasing;

			// Delay the animation start.
			animationTimeouts.current.start = setTimeout( () => {
				animatedBlock.style.transitionDuration = `${ spectraAnimationTime / 1000 }s`;
				animatedBlock.classList.add( 'aos-animate' );
			}, spectraAnimationDelay );

			// Reset animation after completion.
			animationTimeouts.current.end = setTimeout( () => {
				animatedBlock.removeAttribute( 'data-aos' );
				animatedBlock.classList.remove( 'aos-animate' );
				animatedBlock.style.transitionDuration = '';
				animatedBlock.style.transitionTimingFunction = '';
			}, spectraAnimationDelay + spectraAnimationTime );
		},
		[ props.isSelected, clientId, spectraAnimationType, spectraAnimationTime, spectraAnimationDelay, spectraAnimationEasing ]
	);

	// Adding playAnimation function to props for using in the pro plugin.
	props = {
		...props,
		playAnimation,
	};

	/**
	 * Filters the additional animation options rendered in the AnimationOptions panel.
	 *
	 * The filter callback receives:
	 * - `null` as the default value (indicating no additional options by default).
	 * - `props` object containing block attributes and methods.
	 *
	 * Filter should return either:
	 * - A React element to render additional options.
	 * - null if no additional options are needed.
	 *
	 * @since x.x.x
	 *
	 * @param {null}   defaultValue Default null value.
	 * @param {Object} props        Component props.
	 * @return {Element|null}       Filtered JSX element or null.
	 */
	const animationOptions = applyFilters( 'spectra.animations-extension.options', null, props );

	return (
		<>
			{ /* This ToolsPanelItem will reset when the following condition is met:
				- The "spectraAnimationType" attribute is set. Default: ''.
			*/ }
			<ToolsPanelItem
				label={ __( 'Animation Type', 'spectra-blocks' ) }
				isShownByDefault
				hasValue={ () => !! spectraAnimationType }
				onDeselect={ () => setAttributes( { spectraAnimationType: '' } ) }
			>
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Animation Type', 'spectra-blocks' ) }
					onChange={ ( value ) => {
						setAttributes( {
							spectraAnimationType: value,
						} );

						playAnimation( value );
					} }
					value={ spectraAnimationType }
				>
					{ ANIMATION_LIST.map( ( group ) =>
						group.options ? (
							<optgroup key={ group.label } label={ group.label }>
								{ group.options.map( ( option ) => (
									<option key={ option.value } value={ option.value }>
										{ option.label }
									</option>
								) ) }
							</optgroup>
						) : (
							<option key={ group.value } value={ group.value }>
								{ group.label }
							</option>
						)
					) }
				</SelectControl>
			</ToolsPanelItem>

			{ spectraAnimationType && (
				<>
					{ animationOptions && animationOptions }

					<HStack>
						<Button
							variant="secondary"
							type="button"
							onClick={ () => playAnimation() }
							label={ __( 'Preview animation', 'spectra-blocks' ) }
							__next40pxDefaultSize
						>
							{ __( 'Preview', 'spectra-blocks' ) }
						</Button>
					</HStack>
				</>
			) }
		</>
	);
};

/**
 * Higher-order component to add animation controls to block settings.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - Original block edit component.
 * @return {Function} Enhanced block edit component with animation controls.
 */
const addAnimationControls = ( BlockEdit ) => ( props ) => {
	if ( ! isAllowedBlock( { name: props.name } ) ) {
		return <BlockEdit { ...props } />;
	}

	const { setAttributes } = props;

	const resetAll = () => {
		setAttributes( {
			spectraAnimationType: '',
			spectraAnimationTime: 400,
			spectraAnimationDelay: 0,
			spectraAnimationEasing: 'ease',
			spectraAnimationOnce: false,
		} );
	};

	return (
		<>
			<BlockEdit { ...props } />
			{/* Only render animation controls when block is selected for better performance */}
			{ props.isSelected && (
				<InspectorControls>
					{ /* ToolsPanel for animation settings.
						- Provides controls to customize animation type, duration, delay, and easing.
						- Default values:
						- Animation Type: '' (None)
						- Animation Duration: 400ms
						- Animation Delay: 0ms
						- Animation Easing: 'ease'
						- Includes a "Reset All" option to restore default animation values.
					*/ }
					<ToolsPanel label={ __( 'Animation', 'spectra-blocks' ) } resetAll={ resetAll }>
						<AnimationOptions { ...props } />
					</ToolsPanel>
				</InspectorControls>
			) }
		</>
	);
};

export default addAnimationControls;
