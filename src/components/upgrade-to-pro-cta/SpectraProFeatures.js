import { __ } from '@wordpress/i18n';

const UpsellImage = () => {
	let imgUrl = spectra_blocks_info.plugin_url;
	imgUrl += 'assets/images/upsell/globalBanner.svg';

	return (
		<img
			src={ imgUrl }
			alt="Upsell Cover"
			className="max-w-full h-auto"
		/>
	);
};

export const spectraProFeatures = {
	'advanced-animations': {
		image: <UpsellImage />,
		title: __( 'Unlock Advanced Animations', 'spectra-blocks' ),
		header: __( 'Make your pages visually stunning with advanced animations that capture attention.', 'spectra-blocks' ),
		description: __( 'Take your website\'s visual appeal to the next level with smooth, highly customizable animations. Control pacing, delays, and effects effortlessly.', 'spectra-blocks' ),
		shortDesc: __( 'Smooth animations with precise control.', 'spectra-blocks' ),
		features: [
			__( 'Set delay and duration for animations', 'spectra-blocks' ),
			__( 'Customize animation pacing and easing', 'spectra-blocks' ),
			__( 'Repeat animations on scroll', 'spectra-blocks' ),
			__( 'Animate nested blocks seamlessly', 'spectra-blocks' ),
		],
	},
	'modal': {
		image: <UpsellImage />,
		title: __( 'Get Modal Pro', 'spectra-blocks' ),
		header: __( 'Boost engagement with highly customizable modals that demand attention. Create modals with triggers, transitions, and animations.', 'spectra-blocks' ),
		description: __( 'Add professional, high-converting modals with advanced triggers, seamless animations, and flexible customization.', 'spectra-blocks' ),
		shortDesc: __( 'Customizable modals with dynamic triggers.', 'spectra-blocks' ),
		features: [
			__( 'Advanced triggers like exit intent and delay', 'spectra-blocks' ),
			__( 'Off-canvas and full-screen display options', 'spectra-blocks' ),
			__( 'Smooth entrance and exit effects', 'spectra-blocks' ),
			__( 'Cookie-based automatic triggering for conversions', 'spectra-blocks' ),
		],
	},
	'popup-builder': {
		image: <UpsellImage />,
		title: __( 'Unlock Popup Builder Pro', 'spectra-blocks' ),
		header: __( 'Create high-converting popups with powerful targeting and trigger options.', 'spectra-blocks' ),
		description: __( 'Design and display engaging popups with advanced triggers, precise targeting, and seamless controls to boost conversions and engagement.', 'spectra-blocks' ),
		shortDesc: __( 'Advanced popup triggers and targeting options.', 'spectra-blocks' ),
		features: [
			__( 'Control triggers: page load, exit intent, or custom class', 'spectra-blocks' ),
			__( 'Display popups site-wide or on specific pages', 'spectra-blocks' ),
			__( 'Set custom delays and frequency controls', 'spectra-blocks' ),
		],
	},
	'countdown': {
		image: <UpsellImage />,
		title: __( 'Get Countdown Pro', 'spectra-blocks' ),
		header: __( 'Increase urgency and boost conversions with advanced countdown timers. Perfect for sales, events, and promotions.', 'spectra-blocks' ),
		description: __( 'Create real-time urgency with highly customizable countdown timers, evergreen deadlines, and smart expiry options.', 'spectra-blocks' ),
		shortDesc: __( 'Advanced countdown timers with smart options.', 'spectra-blocks' ),
		features: [
			__( 'Evergreen countdown with auto-reset', 'spectra-blocks' ),
			__( 'Fixed date and time countdowns', 'spectra-blocks' ),
			__( 'Customizable expiry actions', 'spectra-blocks' ),
		],
	},
	'image-gallery': {
		image: <UpsellImage />,
		title: __( 'Get Image Gallery Pro', 'spectra-blocks' ),
		header: __( 'Transform your image galleries into interactive experiences with custom click actions and animations.', 'spectra-blocks' ),
		description: __( 'Engage your audience with interactive image galleries featuring custom redirections, lightboxes, and effects.', 'spectra-blocks' ),
		shortDesc: __( 'Clickable images with custom actions.', 'spectra-blocks' ),
		features: [
			__( 'Redirect users with custom click actions', 'spectra-blocks' ),
			__( 'Built-in lightbox and modal options', 'spectra-blocks' ),
			__( 'Multiple layout styles and hover effects', 'spectra-blocks' ),
		],
	},
	'post-grid': {
		image: <UpsellImage />,
		title: __( 'Unlock Loop Builder', 'spectra-blocks' ),
		header: __( 'Customize post layouts like never before with powerful loop builder options.', 'spectra-blocks' ),
		description: __( 'Design stunning post grids with dynamic content, flexible layouts, and seamless integrations.', 'spectra-blocks' ),
		shortDesc: __( 'Fully customizable post loops with Loop Builder.', 'spectra-blocks' ),
		features: [
			__( 'Drag-and-drop design with Spectra blocks', 'spectra-blocks' ),
			__( 'ACF, Custom Fields, and Taxonomy integration', 'spectra-blocks' ),
			__( 'Advanced filtering and sorting', 'spectra-blocks' ),
		],
	},
	'dynamic-content': {
		image: <UpsellImage />,
		title: __( 'Unlock Dynamic Content Pro', 'spectra-blocks' ),
		header: __( 'Deliver personalized content dynamically based on user behavior. Make your pages more relevant and engaging.', 'spectra-blocks' ),
		description: __( 'Tailor content dynamically for users based on preferences, interactions, and data sources. Create truly personalized experiences.', 'spectra-blocks' ),
		shortDesc: __( 'Experience dynamic content with Spectra Pro. No more static displays.', 'spectra-blocks' ),
		features: [
			__( 'Dynamic text and images from any source', 'spectra-blocks' ),
			__( 'Global updates: Change once, reflect everywhere', 'spectra-blocks' ),
			__( 'Smart fallback options for dynamic content', 'spectra-blocks' ),
		],
	},
	'slider': {
		image: <UpsellImage />,
		title: __( 'Get Slider Pro', 'spectra-blocks' ),
		header: __( 'Create Stunning Sliders with Enhanced Customization', 'spectra-blocks' ),
		description: __( 'Take full control over your slider designs with advanced settings.', 'spectra-blocks' ),
		shortDesc: __( 'Fully customizable sliders.', 'spectra-blocks' ),
		features: [
			__( 'Slide-per-view option', 'spectra-blocks' ),
			__( 'Custom navigation styles', 'spectra-blocks' ),
			__( 'Unique navigation slugs', 'spectra-blocks' ),
		],
	},
};
