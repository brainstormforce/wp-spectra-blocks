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

// Maps each AOS animation type to its CSS start state (matching aos.min.css).
// `opacity: null` means the animation does not change opacity (slide/flip groups).
const AOS_START_STATES = {
	'fade':           { opacity: '0', transform: 'none' },
	'fade-up':        { opacity: '0', transform: 'translate3d(0,100px,0)' },
	'fade-down':      { opacity: '0', transform: 'translate3d(0,-100px,0)' },
	'fade-right':     { opacity: '0', transform: 'translate3d(-100px,0,0)' },
	'fade-left':      { opacity: '0', transform: 'translate3d(100px,0,0)' },
	'slide-up':       { opacity: null, transform: 'translate3d(0,100%,0)' },
	'slide-down':     { opacity: null, transform: 'translate3d(0,-100%,0)' },
	'slide-right':    { opacity: null, transform: 'translate3d(-100%,0,0)' },
	'slide-left':     { opacity: null, transform: 'translate3d(100%,0,0)' },
	'zoom-in':        { opacity: '0', transform: 'scale(0.6)' },
	'zoom-in-up':     { opacity: '0', transform: 'translate3d(0,100px,0) scale(0.6)' },
	'zoom-in-down':   { opacity: '0', transform: 'translate3d(0,-100px,0) scale(0.6)' },
	'zoom-in-right':  { opacity: '0', transform: 'translate3d(-100px,0,0) scale(0.6)' },
	'zoom-in-left':   { opacity: '0', transform: 'translate3d(100px,0,0) scale(0.6)' },
	'zoom-out':       { opacity: '0', transform: 'scale(1.2)' },
	'zoom-out-up':    { opacity: '0', transform: 'translate3d(0,100px,0) scale(1.2)' },
	'zoom-out-down':  { opacity: '0', transform: 'translate3d(0,-100px,0) scale(1.2)' },
	'zoom-out-right': { opacity: '0', transform: 'translate3d(-100px,0,0) scale(1.2)' },
	'zoom-out-left':  { opacity: '0', transform: 'translate3d(100px,0,0) scale(1.2)' },
	'flip-left':      { opacity: null, transform: 'perspective(2500px) rotateY(-100deg)' },
	'flip-right':     { opacity: null, transform: 'perspective(2500px) rotateY(100deg)' },
	'flip-up':        { opacity: null, transform: 'perspective(2500px) rotateX(-100deg)' },
	'flip-down':      { opacity: null, transform: 'perspective(2500px) rotateX(100deg)' },
};

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
	 * Uses direct inline style manipulation instead of AOS data attributes so the
	 * preview works inside the editor, where AOS CSS is not loaded and the editor
	 * reset stylesheet forces `[data-aos]{opacity:1!important;transform:none!important;}`.
	 *
	 * @param {string} [animationType=spectraAnimationType] - Animation type to preview.
	 */
	const playAnimation = useCallback(
		( animationType = spectraAnimationType ) => {
			if ( ! props.isSelected ) { return; }

			const editorIframe = document.querySelector( 'iframe[name="editor-canvas"]' );
			const innerDoc     = editorIframe?.contentDocument || editorIframe?.contentWindow?.document;
			const animatedBlock = innerDoc
				? innerDoc.getElementById( `block-${ clientId }` )
				: document.getElementById( `block-${ clientId }` );

			if ( ! animatedBlock ) { return; }

			clearTimeout( animationTimeouts.current?.start );
			clearTimeout( animationTimeouts.current?.end );

			const startState      = AOS_START_STATES[ animationType ] || { opacity: '0', transform: 'none' };
			const dur             = `${ spectraAnimationTime / 1000 }s`;
			const animatesOpacity = startState.opacity !== null;

			// Apply start state with !important to beat the editor reset CSS.
			animatedBlock.style.setProperty( 'transition', 'none', 'important' );
			animatedBlock.style.setProperty( 'transform', startState.transform, 'important' );
			if ( animatesOpacity ) {
				animatedBlock.style.setProperty( 'opacity', startState.opacity, 'important' );
			}

			// Force reflow so the browser commits the start state before the transition.
			void animatedBlock.offsetHeight;

			// Animate to the final state after the configured delay.
			animationTimeouts.current.start = setTimeout( () => {
				const transitionValue = animatesOpacity
					? `opacity ${ dur } ${ spectraAnimationEasing }, transform ${ dur } ${ spectraAnimationEasing }`
					: `transform ${ dur } ${ spectraAnimationEasing }`;
				animatedBlock.style.setProperty( 'transition', transitionValue, 'important' );
				animatedBlock.style.setProperty( 'transform', 'none', 'important' );
				if ( animatesOpacity ) {
					animatedBlock.style.setProperty( 'opacity', '1', 'important' );
				}
			}, spectraAnimationDelay );

			// Remove inline overrides after the animation completes.
			animationTimeouts.current.end = setTimeout( () => {
				animatedBlock.style.removeProperty( 'transition' );
				animatedBlock.style.removeProperty( 'transform' );
				animatedBlock.style.removeProperty( 'opacity' );
			}, spectraAnimationDelay + spectraAnimationTime + 50 );
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
