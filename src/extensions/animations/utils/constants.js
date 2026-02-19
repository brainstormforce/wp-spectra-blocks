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
	{ value: '', label: __( 'None', 'spectra-blocks' ) },

	// Fade.
	{
		label: __( 'Fade', 'spectra-blocks' ),
		options: [
			{ value: 'fade', label: __( 'Fade', 'spectra-blocks' ) },
			{ value: 'fade-down', label: __( 'Fade Down', 'spectra-blocks' ) },
			{ value: 'fade-up', label: __( 'Fade Up', 'spectra-blocks' ) },
			{ value: 'fade-left', label: __( 'Fade Left', 'spectra-blocks' ) },
			{ value: 'fade-right', label: __( 'Fade Right', 'spectra-blocks' ) },
		],
	},

	// Flip.
	{
		label: __( 'Flip', 'spectra-blocks' ),
		options: [
			{ value: 'flip-down', label: __( 'Flip Down', 'spectra-blocks' ) },
			{ value: 'flip-up', label: __( 'Flip Up', 'spectra-blocks' ) },
			{ value: 'flip-left', label: __( 'Flip Left', 'spectra-blocks' ) },
			{ value: 'flip-right', label: __( 'Flip Right', 'spectra-blocks' ) },
		],
	},

	// Slide.
	{
		label: __( 'Slide', 'spectra-blocks' ),
		options: [
			{ value: 'slide-down', label: __( 'Slide Down', 'spectra-blocks' ) },
			{ value: 'slide-up', label: __( 'Slide Up', 'spectra-blocks' ) },
			{ value: 'slide-left', label: __( 'Slide Left', 'spectra-blocks' ) },
			{ value: 'slide-right', label: __( 'Slide Right', 'spectra-blocks' ) },
		],
	},

	// Zoom-In.
	{
		label: __( 'Zoom-In', 'spectra-blocks' ),
		options: [
			{ value: 'zoom-in', label: __( 'Zoom-In', 'spectra-blocks' ) },
			{ value: 'zoom-in-down', label: __( 'Zoom-In Down', 'spectra-blocks' ) },
			{ value: 'zoom-in-up', label: __( 'Zoom-In Up', 'spectra-blocks' ) },
			{ value: 'zoom-in-left', label: __( 'Zoom-In Left', 'spectra-blocks' ) },
			{ value: 'zoom-in-right', label: __( 'Zoom-In Right', 'spectra-blocks' ) },
		],
	},

	// Zoom-Out.
	{
		label: __( 'Zoom-Out', 'spectra-blocks' ),
		options: [
			{ value: 'zoom-out', label: __( 'Zoom-Out', 'spectra-blocks' ) },
			{ value: 'zoom-out-down', label: __( 'Zoom-Out Down', 'spectra-blocks' ) },
			{ value: 'zoom-out-up', label: __( 'Zoom-Out Up', 'spectra-blocks' ) },
			{ value: 'zoom-out-left', label: __( 'Zoom-Out Left', 'spectra-blocks' ) },
			{ value: 'zoom-out-right', label: __( 'Zoom-Out Right', 'spectra-blocks' ) },
		],
	},
];
