/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Template for the countdown minute block.
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
			text: __( 'Minutes', 'spectra-blocks' ),
			lock: { move: true, remove: true },
		},
	],
];

export default TEMPLATE;
