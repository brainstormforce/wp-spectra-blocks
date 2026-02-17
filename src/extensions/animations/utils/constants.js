import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Array of blocks that should be excluded from animation.
 *
 * @since x.x.x
 */
export const EXCLUDED_BLOCKS = applyFilters( 'spectra.excludedAnimationBlocks', [
	'spectra/slider-child',
	'spectra/accordion-child-details',
	// Modal blocks
	'spectra/modal',
	'spectra/modal-popup',
	'spectra/modal-popup-content',
	'spectra/modal-child-trigger',
	'spectra/modal-child-popup-close-icon',
	'spectra/popup-builder',
	// SVG Animator has its own animation system (Web Animations API).
	'spectra/svg-animator',
] );

/**
 * Array of blocks that support animation.
 *
 * @since x.x.x
 */
export const SUPPORTED_BLOCKS = applyFilters( 'spectra.supportedAnimationBlocks', [] );

/**
 * Object containing the list of animations.
 *
 * @since x.x.x
 */
export const ANIMATION_LIST = [
	// None.
	{ value: '', label: __( 'None', 'ultimate-addons-for-gutenberg' ) },

	// Fade.
	{
		label: __( 'Fade', 'ultimate-addons-for-gutenberg' ),
		options: [
			{ value: 'fade', label: __( 'Fade', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'fade-down', label: __( 'Fade Down', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'fade-up', label: __( 'Fade Up', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'fade-left', label: __( 'Fade Left', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'fade-right', label: __( 'Fade Right', 'ultimate-addons-for-gutenberg' ) },
		],
	},

	// Flip.
	{
		label: __( 'Flip', 'ultimate-addons-for-gutenberg' ),
		options: [
			{ value: 'flip-down', label: __( 'Flip Down', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'flip-up', label: __( 'Flip Up', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'flip-left', label: __( 'Flip Left', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'flip-right', label: __( 'Flip Right', 'ultimate-addons-for-gutenberg' ) },
		],
	},

	// Slide.
	{
		label: __( 'Slide', 'ultimate-addons-for-gutenberg' ),
		options: [
			{ value: 'slide-down', label: __( 'Slide Down', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'slide-up', label: __( 'Slide Up', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'slide-left', label: __( 'Slide Left', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'slide-right', label: __( 'Slide Right', 'ultimate-addons-for-gutenberg' ) },
		],
	},

	// Zoom-In.
	{
		label: __( 'Zoom-In', 'ultimate-addons-for-gutenberg' ),
		options: [
			{ value: 'zoom-in', label: __( 'Zoom-In', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-in-down', label: __( 'Zoom-In Down', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-in-up', label: __( 'Zoom-In Up', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-in-left', label: __( 'Zoom-In Left', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-in-right', label: __( 'Zoom-In Right', 'ultimate-addons-for-gutenberg' ) },
		],
	},

	// Zoom-Out.
	{
		label: __( 'Zoom-Out', 'ultimate-addons-for-gutenberg' ),
		options: [
			{ value: 'zoom-out', label: __( 'Zoom-Out', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-out-down', label: __( 'Zoom-Out Down', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-out-up', label: __( 'Zoom-Out Up', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-out-left', label: __( 'Zoom-Out Left', 'ultimate-addons-for-gutenberg' ) },
			{ value: 'zoom-out-right', label: __( 'Zoom-Out Right', 'ultimate-addons-for-gutenberg' ) },
		],
	},
];
