/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Template for the countdown second block.
 */
const TEMPLATE = [
	[
		'spectra/countdown-child-number',
		{
			number: 0,
			lock: { move: true, remove: true },
		},
	],
	[
		'spectra/countdown-child-label',
		{
			text: __( 'Seconds', 'ultimate-addons-for-gutenberg' ),
			lock: { move: true, remove: true },
		},
	],
];

export default TEMPLATE;
