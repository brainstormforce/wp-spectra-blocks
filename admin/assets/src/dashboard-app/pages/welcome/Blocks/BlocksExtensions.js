import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { Container } from '@bsf/force-ui';
import Spectra_Block_Icons from '@Common/block-icons';
import BlockItem from '@Common/components/BlockItem';
import UnlockProItem from './UnlockProItem';

const BlocksExtensions = () => {
	const [ allBlocksData, setAllBlocksData ] = useState( null ); // Initialize state.

	useEffect( () => {
		const blocksData = convertToWidgetsArray( blocks );
		setAllBlocksData( blocksData );
	}, [] );

	const blocks = [
		{
			title: __( 'Animations', 'spectra-blocks' ),
			slug: 'animations-extension',
			icon: 'animation',
			is_pro: false,
			is_extension: true,
			is_active: true,
			link: 'animations-spectra-blocks'
		},
		{
			title: __( 'Image Masking', 'spectra-blocks' ),
			slug: 'image-masking',
			icon: 'image-masking',
			is_pro: false,
			is_extension: true,
			is_active: true,
			link: 'image-mask-extension-spectra-blocks'
		},
		{
			title: __( 'Motion Effects', 'spectra-blocks' ),
			slug: 'motion-effects',
			icon: React.cloneElement( Spectra_Block_Icons[ 'motion-effects' ], { width: 24, height: 24 } ),
			is_pro: true,
			is_extension: true,
			is_active: false,
			link: 'motion-effects-spectra-blocks'
		},
		{
			title: __( 'Block Positioning', 'spectra-blocks' ),
			slug: 'block-positioning',
			icon: React.cloneElement( Spectra_Block_Icons[ 'block-positioning' ], { width: 24, height: 24 } ),
			is_pro: true,
			is_extension: true,
			is_active: false,
			link: 'block-positioning-spectra-blocks'
		},
	];

	function convertToWidgetsArray( data ) {
		const blocksArray = [];

		for ( const key in data ) {
			if ( data.hasOwnProperty( key ) ) {
				const block = data[ key ];

				blocksArray.push( {
					id: key, // Using the key as 'widgetTitle'
					slug: block.slug,
					title: block.title,
					icon: block.icon,
					is_pro: block.is_pro,
					is_active: block.is_active !== undefined ? block.is_active : true, // Check if is_activate is set
					link: block.link
				} );
			}
		}

		return blocksArray;
	}

	return (
		<div className="rounded-lg bg-white w-full border border-solid border-border-subtle">
			<div
				className="flex items-center justify-between p-4"
				style={ {
					paddingBottom: '0',
				} }
			>
				<p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Extensions', 'spectra-blocks' ) }
				</p>
			</div>
			<div className="flex flex-col rounded-lg p-4">
				<Container
					className="p-1 bg-background-secondary rounded-lg gap-1"
					containerType="grid"
					cols={{ sm: 2, md: 3, lg: 4 }}
				>
					{ allBlocksData?.slice( 0, 16 ).map( ( block ) => (
						<Container.Item
							key={ block.id }
							alignSelf="auto"
							className="text-wrap card-border rounded-md bg-background-primary p-2 hover:border-border-subtle block-item hover:shadow-sm "
						>
							{ block.is_pro
						? <UnlockProItem blockInfo={ block } />
						: <BlockItem block={ block } is_extension={ true } key={ block.id } updateCounter={ 0 } />
					}
						</Container.Item>
					) ) }
				</Container>
			</div>
		</div>
	);
};

export default BlocksExtensions;
