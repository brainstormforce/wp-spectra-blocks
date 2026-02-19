import { __ } from '@wordpress/i18n';

// when click for first time on button this defaultContent will get loaded.
export const defaultContent = [
	[ 'spectra/content', {
		tagName: 'p',
		placeholder: __( 'You can add text to content!', 'spectra-blocks' ),
	} ]
];

// excludeBlocks are blocks that restricted to use as child block inside Modal.
export const excludeBlocks = [
	'spectra/modal',
	'spectra/modal-popup',
	'spectra/modal-child-trigger',
	'spectra/modal-popup-content',
	'spectra/modal-child-button',
	'spectra/modal-child-icon',
	'spectra/modal-child-popup-close-icon',
];
