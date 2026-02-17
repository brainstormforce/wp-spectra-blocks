/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Template for the countdown day block.
 */
const TEMPLATE = [
	[
		'spectra/countdown-child-number',
		{
			number: 5,
			lock: { move: true, remove: true },
		},
	],
	[
		'spectra/countdown-child-label',
		{
			text: __( 'Days', 'ultimate-addons-for-gutenberg' ),
			lock: { move: true, remove: true },
		},
	],
];

export default TEMPLATE;
