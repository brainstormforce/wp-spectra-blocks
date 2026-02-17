import { __ } from '@wordpress/i18n';

// when click for first time on button this defaultContent will get loaded.
export const defaultContent = [
	[ 'spectra/content', {
		tagName: 'p',
		placeholder: __( 'You can add text to content!', 'ultimate-addons-for-gutenberg' ),
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
	'uagb/modal',
	// Spectra Child Blocks.
	'uagb/how-to-step',
	'uagb/buttons-child',
	'uagb/faq-child',
	'uagb/content-timeline-child',
	'uagb/icon-list-child',
	'uagb/social-share-child',
	'uagb/restaurant-menu-child',
	'uagb/tabs-child',
	'uagb/post-image',
	'uagb/post-taxonomy',
	'uagb/post-title',
	'uagb/post-meta',
	'uagb/post-excerpt',
	'uagb/post-button',
	'uagb/forms-name',
	'uagb/forms-email',
	'uagb/forms-hidden',
	'uagb/forms-phone',
	'uagb/forms-textarea',
	'uagb/forms-url',
	'uagb/forms-select',
	'uagb/forms-radio',
	'uagb/forms-checkbox',
	'uagb/forms-toggle',
	'uagb/forms-date',
	'uagb/forms-accept',
	'uagb/slider-child',
	// Spectra Pro Child Blocks.
	'uagb/register-email',
	'uagb/register-first-name',
	'uagb/register-last-name',
	'uagb/register-password',
	'uagb/register-reenter-password',
	'uagb/register-terms',
	'uagb/register-username',
];
