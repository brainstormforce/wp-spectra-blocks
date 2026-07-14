import { __, sprintf } from '@wordpress/i18n';

import { Container, Title, Button, Badge, Text } from '@bsf/force-ui';
import { Plus, ExternalLink, X } from 'lucide-react';
import { useState, useEffect } from 'react';

const HeroSection = () => {
	const proVideoUrl = `https://www.youtube-nocookie.com/embed/3zDGUBaDwiU`; // eslint-disable-line no-unused-vars
	const proVideoThumbnailUrl = `${ spectra_blocks_react?.plugin_url || '' }assets/admin/images/video-thumb-pro.jpg`;

	const freeVideoUrl = `https://www.youtube-nocookie.com/embed/GLNzTxArR6Y`; // eslint-disable-line no-unused-vars
	const freeVideoThumbnailUrl = `${ spectra_blocks_react?.plugin_url || '' }assets/admin/images/video-thumb-free.jpg`;

	// temporary adding an doc link for video until we get new video.
	const docLink = 'https://wpspectra.com/docs/getting-started-v3/';

	const [ popupVideo, setPopupVideo ] = useState( null );

	useEffect( () => {
		const handleKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				setPopupVideo( null );
			}
		};

		window.addEventListener( 'keydown', handleKeyDown );
		return () => window.removeEventListener( 'keydown', handleKeyDown );
	}, [] );

	const onCreateNewPageClick = () => {
		window.open( spectra_blocks_react.wp_pages_url, '_blank' );
	};

	const onReadFullGuideClick = () => {
		window.open( docLink, '_blank' );
	};

	// Open doc link on watch video click until we have new video.
	const onWatchVideoClick = () => {
		window.open( 'https://wpspectra.com/', '_blank' );
	};


	return (
		<Container
			className="bg-background-primary p-4 rounded-xl border border-solid border-border-subtle items-center text-text-primary"
			cols={12}
			containerType="grid"
			gap="2xl"
		>
			<Container.Item className="xl:col-span-6 flex flex-col gap-4 p-2" colSpan={{ lg: 12, md: 12, sm: 12 }}>
				<Text className="sr-only">{__( 'Welcome Banner', 'spectra-blocks' )}</Text>
				<div className="flex flex-col gap-1">
					<Text size={18} color="secondary">
						{sprintf(
						/* translators: %s: user's display name */ __( 'Hello %s', 'spectra-blocks' ), spectra_blocks_user_data.displayName )}
					</Text>
					<div className="flex gap-3">
						<Title
							className="text-text-primary"
							size="lg"
							tag="h2"
							title={__( 'Welcome To Spectra Blocks', 'spectra-blocks' )}
						/>
						<Badge
							className="uppercase -translate-y-1/2 py-0 px-1 text-text-secondary bg-background-secondary"
							label={'Activated' === spectra_blocks_react.pro_plugin_status ? __( 'Pro Version', 'spectra-blocks' ) : __( 'Free Version', 'spectra-blocks' )}
							size="xs"
							variant="neutral"
							type="rounded"
						/>
					</div>
					<Text className="py-3" size={14} color="secondary">
						{ 'Activated' === spectra_blocks_react.pro_plugin_status
							? __(
									"Thanks for choosing Spectra Blocks Pro! You're now part of an exclusive community of website builders. Enjoy exploring the new features and creating something amazing. Let's take your site to the next level together.",
									'spectra-blocks'
							  )
							: __(
									'We designed Spectra Blocks to be intuitive but we do recommend learning how it works by checking our comprehensive documentation and watching the video below. Enjoy your time with Spectra Blocks!',
									'spectra-blocks'
							  ) }
					</Text>
				</div>

				<div className="flex gap-3">
					<Button
						icon={ <Plus /> }
						iconPosition="left"
						variant="primary"
						onClick={ onCreateNewPageClick }
						className="spectra-blocks-remove-ring"
					>
						{ __( 'Create New Page', 'spectra-blocks' ) }
					</Button>
					<Button
						icon={ <ExternalLink /> }
						iconPosition="right"
						variant="ghost"
						onClick={ onReadFullGuideClick }
						className="spectra-blocks-remove-ring"
					>
						{ __( 'Read Full Guide', 'spectra-blocks' ) }
					</Button>
				</div>
			</Container.Item>

			<Container.Item
				colSpan={{ lg: 12, md: 12, sm: 12 }}
				className="xl:col-span-6 relative xl:ml-4 p-2"
				// onClick={ () =>
				// 	setPopupVideo( 'Activated' === spectra_blocks_react.pro_plugin_status ? proVideoUrl : freeVideoUrl )
				// } // Disable direct popup on thumbnail click untill we have proper v3 video.
				onClick={ onWatchVideoClick }
			>
				<img
					src={ 'Activated' === spectra_blocks_react.pro_plugin_status ? proVideoThumbnailUrl : freeVideoThumbnailUrl }
					className="w-full h-full object-cover rounded-lg aspect-video cursor-pointer"
					alt="Video Thumbnail"
				/>

				{/* <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-black bg-opacity-50 rounded-full">
					<PlayFilledIcon className="w-6 h-6 text-white" /> Disable direct popup on thumbnail click icon untill we have proper v3 video.
				</div> */} 
			</Container.Item>

			{ /* Video Popup */ }
			{ popupVideo && (
				<div
					className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-70 cursor-pointer w-screen z-999999"
					onClick={ () => setPopupVideo( null ) }
				>
					<div className="text-text-inverse absolute top-4 right-4 flex items-center gap-2">
						<div className="text-sm font-medium">Esc</div>
						<X
							size={ 20 }
							onClick={ ( e ) => {
								e.stopPropagation();
								setPopupVideo( null );
							} }
						/>
					</div>
					<div
						className="relative rounded-lg shadow-lg w-4/5 cursor-default"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<iframe
							className="w-full lg:h-188 sm:h-120 h-60"
							src={ `${ popupVideo }?autoplay=1` }
							title="Video Popup"
							frameBorder="0"
							allow="autoplay; encrypted-media"
							allowFullScreen
						></iframe>
					</div>
				</div>
			) }
		</Container>
	);
};

// const PlayFilledIcon = ( props ) => (
// 	<svg { ...props } viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
// 		<path d="M8 5v14l11-7L8 5z" />
// 	</svg>
// );

export default HeroSection;
