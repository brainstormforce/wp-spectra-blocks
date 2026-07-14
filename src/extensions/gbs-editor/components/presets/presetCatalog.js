/**
 * Preset catalog — defines every UI preset key, its allowed options,
 * display labels, and visual hints for the picker UI.
 *
 * Mirrors the PHP $allowed_presets array in class-engine.php so the JS
 * and PHP sides stay in sync. When adding a new preset key, update both.
 *
 * @since x.x.x
 */

import { __ } from '@wordpress/i18n';

/**
 * Full preset catalog keyed by sidebar section id.
 *
 * Each entry has:
 *   - key      : config.presets key (sent to REST on save)
 *   - label    : section heading
 *   - hint     : one-line description shown below the picker
 *   - options  : array of { value, label, description? }
 *
 * @since x.x.x
 *
 * @return {Object} Catalog object.
 */
export function getPresetCatalog() {
	return {
		buttons: {
			key: 'buttonStyle',
			label: __( 'Button style', 'spectra-blocks' ),
			hint: __( 'Controls the visual treatment for all primary and secondary buttons across the site.', 'spectra-blocks' ),
			options: [
				{ value: 'solid',    label: __( 'Solid', 'spectra-blocks' ),    description: __( 'Filled background', 'spectra-blocks' ) },
				{ value: 'soft',     label: __( 'Soft', 'spectra-blocks' ),     description: __( 'Tinted light fill', 'spectra-blocks' ) },
				{ value: 'outline',  label: __( 'Outline', 'spectra-blocks' ),  description: __( 'Border only', 'spectra-blocks' ) },
				{ value: 'elevated', label: __( 'Elevated', 'spectra-blocks' ), description: __( 'Filled + shadow', 'spectra-blocks' ) },
			],
		},
		cards: {
			key: 'cardStyle',
			label: __( 'Card style', 'spectra-blocks' ),
			hint: __( 'Controls the default card background, border, and shadow.', 'spectra-blocks' ),
			options: [
				{ value: 'flat',     label: __( 'Flat', 'spectra-blocks' ),     description: __( 'Transparent bg', 'spectra-blocks' ) },
				{ value: 'outlined', label: __( 'Outlined', 'spectra-blocks' ), description: __( 'Border + white bg', 'spectra-blocks' ) },
				{ value: 'tinted',   label: __( 'Tinted', 'spectra-blocks' ),   description: __( 'Surface bg fill', 'spectra-blocks' ) },
				{ value: 'elevated', label: __( 'Elevated', 'spectra-blocks' ), description: __( 'White + shadow', 'spectra-blocks' ) },
			],
		},
		inputs: {
			key: 'inputStyle',
			label: __( 'Input style', 'spectra-blocks' ),
			hint: __( 'Controls form field borders and backgrounds across all blocks.', 'spectra-blocks' ),
			options: [
				{ value: 'boxed',      label: __( 'Boxed', 'spectra-blocks' ),      description: __( 'Full border box', 'spectra-blocks' ) },
				{ value: 'soft',       label: __( 'Soft', 'spectra-blocks' ),       description: __( 'Tinted fill, no border', 'spectra-blocks' ) },
				{ value: 'underlined', label: __( 'Underlined', 'spectra-blocks' ), description: __( 'Bottom border only', 'spectra-blocks' ) },
			],
		},
		roundness: {
			key: 'roundness',
			label: __( 'Roundness', 'spectra-blocks' ),
			hint: __( 'Sets border-radius for interactive elements, cards, images, and badges.', 'spectra-blocks' ),
			options: [
				{ value: 'sharp',   label: __( 'Sharp', 'spectra-blocks' ),   description: __( '2px — minimal curves', 'spectra-blocks' ) },
				{ value: 'default', label: __( 'Default', 'spectra-blocks' ), description: __( '8–12px — balanced', 'spectra-blocks' ) },
				{ value: 'soft',    label: __( 'Soft', 'spectra-blocks' ),    description: __( '12–20px — rounded', 'spectra-blocks' ) },
				{ value: 'full',    label: __( 'Full', 'spectra-blocks' ),    description: __( 'Pill shapes', 'spectra-blocks' ) },
			],
		},
		shadows: {
			key: 'shadowDepth',
			label: __( 'Shadow depth', 'spectra-blocks' ),
			hint: __( 'Controls the shadow-sm / shadow-md / shadow-lg token values used on cards and buttons.', 'spectra-blocks' ),
			options: [
				{ value: 'none',   label: __( 'None', 'spectra-blocks' ),   description: __( 'Flat, no shadow', 'spectra-blocks' ) },
				{ value: 'subtle', label: __( 'Subtle', 'spectra-blocks' ), description: __( 'Light ambient', 'spectra-blocks' ) },
				{ value: 'medium', label: __( 'Medium', 'spectra-blocks' ), description: __( 'Clear depth', 'spectra-blocks' ) },
				{ value: 'deep',   label: __( 'Deep', 'spectra-blocks' ),   description: __( 'High contrast lift', 'spectra-blocks' ) },
			],
		},
		spacing: {
			key: 'spacingDensity',
			label: __( 'Spacing density', 'spectra-blocks' ),
			hint: __( 'Scales all space-xs → space-2xl tokens by a density multiplier (0.75× / 1× / 1.5×).', 'spectra-blocks' ),
			options: [
				{ value: 'compact',  label: __( 'Compact', 'spectra-blocks' ),  description: __( '0.75× — tight layout', 'spectra-blocks' ) },
				{ value: 'default',  label: __( 'Default', 'spectra-blocks' ),  description: __( '1× — balanced spacing', 'spectra-blocks' ) },
				{ value: 'spacious', label: __( 'Spacious', 'spectra-blocks' ), description: __( '1.5× — open layout', 'spectra-blocks' ) },
			],
		},
		motion: {
			key: 'motionStyle',
			label: __( 'Motion style', 'spectra-blocks' ),
			hint: __( 'Sets transition durations and entrance animation parameters.', 'spectra-blocks' ),
			options: [
				{ value: 'none',        label: __( 'None', 'spectra-blocks' ),        description: __( 'Instant transitions', 'spectra-blocks' ) },
				{ value: 'subtle',      label: __( 'Subtle', 'spectra-blocks' ),      description: __( '100–200ms transitions', 'spectra-blocks' ) },
				{ value: 'standard',    label: __( 'Standard', 'spectra-blocks' ),    description: __( '150–250ms transitions', 'spectra-blocks' ) },
				{ value: 'expressive',  label: __( 'Expressive', 'spectra-blocks' ),  description: __( '200–350ms + spring', 'spectra-blocks' ) },
			],
		},
		badges: {
			key: 'badgeStyle',
			label: __( 'Badge style', 'spectra-blocks' ),
			hint: __( 'Controls padding, border-radius, and fill for tag/badge elements.', 'spectra-blocks' ),
			options: [
				{ value: 'subtle',  label: __( 'Subtle', 'spectra-blocks' ),  description: __( 'Soft tinted fill', 'spectra-blocks' ) },
				{ value: 'solid',   label: __( 'Solid', 'spectra-blocks' ),   description: __( 'Filled background', 'spectra-blocks' ) },
				{ value: 'outline', label: __( 'Outline', 'spectra-blocks' ), description: __( 'Border only', 'spectra-blocks' ) },
				{ value: 'pill',    label: __( 'Pill', 'spectra-blocks' ),    description: __( 'Full-radius pill', 'spectra-blocks' ) },
			],
		},
		images: {
			key: 'imageTreatment',
			label: __( 'Image treatment', 'spectra-blocks' ),
			hint: __( 'Adjusts image filter, contrast, and saturation for consistent visual tone.', 'spectra-blocks' ),
			options: [
				{ value: 'natural',   label: __( 'Natural', 'spectra-blocks' ),   description: __( 'No filter applied', 'spectra-blocks' ) },
				{ value: 'polished',  label: __( 'Polished', 'spectra-blocks' ),  description: __( 'Subtle contrast boost', 'spectra-blocks' ) },
				{ value: 'dramatic',  label: __( 'Dramatic', 'spectra-blocks' ),  description: __( 'High contrast + vignette', 'spectra-blocks' ) },
				{ value: 'editorial', label: __( 'Editorial', 'spectra-blocks' ), description: __( 'Desaturated editorial look', 'spectra-blocks' ) },
			],
		},
		hover: {
			key: 'hoverIntensity',
			label: __( 'Hover intensity', 'spectra-blocks' ),
			hint: __( 'Controls how much buttons, cards, and links animate on hover.', 'spectra-blocks' ),
			options: [
				{ value: 'none',        label: __( 'None', 'spectra-blocks' ),        description: __( 'No hover effects', 'spectra-blocks' ) },
				{ value: 'subtle',      label: __( 'Subtle', 'spectra-blocks' ),      description: __( 'Light darken + lift', 'spectra-blocks' ) },
				{ value: 'standard',    label: __( 'Standard', 'spectra-blocks' ),    description: __( 'Clear darken + lift', 'spectra-blocks' ) },
				{ value: 'expressive',  label: __( 'Expressive', 'spectra-blocks' ),  description: __( 'Strong lift + shadow', 'spectra-blocks' ) },
			],
		},
		shades: {
			key: 'shadeProfile',
			label: __( 'Shade profile', 'spectra-blocks' ),
			hint: __( 'Controls the lightness curve used when generating chromatic shade steps.', 'spectra-blocks' ),
			options: [
				{ value: 'punchy',   label: __( 'Punchy', 'spectra-blocks' ),   description: __( 'High contrast steps', 'spectra-blocks' ) },
				{ value: 'balanced', label: __( 'Balanced', 'spectra-blocks' ), description: __( 'Even perceptual spread', 'spectra-blocks' ) },
				{ value: 'soft',     label: __( 'Soft', 'spectra-blocks' ),     description: __( 'Gentle, low contrast', 'spectra-blocks' ) },
			],
		},
		saturation: {
			key: 'saturation',
			label: __( 'Saturation', 'spectra-blocks' ),
			hint: __( 'Scales the chroma of generated chromatic shades. Affects all palette colours.', 'spectra-blocks' ),
			options: [
				{ value: 'vivid',    label: __( 'Vivid', 'spectra-blocks' ),    description: __( 'Full chroma', 'spectra-blocks' ) },
				{ value: 'balanced', label: __( 'Balanced', 'spectra-blocks' ), description: __( 'Slightly muted', 'spectra-blocks' ) },
				{ value: 'muted',    label: __( 'Muted', 'spectra-blocks' ),    description: __( 'Low saturation', 'spectra-blocks' ) },
			],
		},
	};
}
