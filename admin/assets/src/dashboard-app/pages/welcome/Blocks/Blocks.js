import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { Container } from '@bsf/force-ui';
import Spectra_Block_Icons from '@Common/block-icons';
import BlockItem from '@Common/components/BlockItem';

const Blocks = () => {
	const [ allBlocksData, setAllBlocksData ] = useState( null ); // Initialize state.

	useEffect( () => {
		const blocksData = convertToWidgetsArray( blocks );
		setAllBlocksData( blocksData );
	}, [] );

	const blocks = [
		{
			title: __( 'Container', 'spectra-blocks' ),
			slug: 'container',
			icon: 'container',
			is_pro: false,
			is_active: true,
			link: 'container-spectra-blocks'
		},
		{
			title: __( 'Button', 'spectra-blocks' ),
			slug: 'button',
			icon: 'button',
			is_pro: false,
			is_active: true,
			link: 'button-spectra-blocks'
		},
		{
			title: __( 'Text', 'spectra-blocks' ),
			slug: 'text',
			icon: 'content',
			is_pro: false,
			is_active: true,
			link: 'text-spectra-blocks'
		},
		{
			title: __( 'Icon', 'spectra-blocks' ),
			slug: 'icon',
			icon: 'icon',
			is_pro: false,
			is_active: true,
			link: 'icon-spectra-blocks'
		},
		{
			title: __( 'Accordions', 'spectra-blocks' ),
			slug: 'accordions',
			icon: 'accordion',
			is_pro: false,
			is_active: true,
			link: 'accordion-spectra-blocks'
		},
		{
			title: __( 'Countdown', 'spectra-blocks' ),
			slug: 'countdown',
			icon: 'countdown',
			is_pro: false,
			is_active: true,
			link: 'countdown-spectra-blocks'
		},
		{
			title: __( 'Slider', 'spectra-blocks' ),
			slug: 'slider',
			icon: 'slider',
			is_pro: false,
			is_active: true,
			link: 'slider-spectra-blocks'
		},
		{
			title: __( 'Tabs', 'spectra-blocks' ),
			slug: 'tabs',
			icon: 'tabs',
			is_pro: false,
			is_active: true,
			link: 'tabs-spectra-blocks'
		},
		{
			title: __( 'Google Map', 'spectra-blocks' ),
			slug: 'google-map',
			icon: 'google-map',
			is_pro: false,
			is_active: true,
			link: 'google-maps-spectra-blocks'
		},
		{
			title: __( 'Modal', 'spectra-blocks' ),
			slug: 'modal',
			icon: 'modal',
			is_pro: false,
			is_active: true,
			link: 'modal-spectra-blocks'
		},
		{
			title: __( 'List', 'spectra-blocks' ),
			slug: 'list',
			icon: 'list',
			is_pro: false,
			is_active: true,
			link: 'list-spectra-blocks'
		},
		{
			title: __( 'Separator', 'spectra-blocks' ),
			slug: 'separator',
			icon: 'separator',
			is_pro: false,
			is_active: true,
			link: 'separator-spectra-blocks'
		},
		{
			title: __( 'Post', 'spectra-blocks' ),
			slug: 'post',
			icon: React.cloneElement( Spectra_Block_Icons.post, { width: 24, height: 24 } ),
			is_pro: false,
			is_active: true,
			link: 'post-spectra-blocks'
		},
		{
			title: __( 'Counter', 'spectra-blocks' ),
			slug: 'counter',
			icon: React.cloneElement( Spectra_Block_Icons.counter, { width: 24, height: 24 } ),
			is_pro: false,
			is_active: true,
			link: 'counter-spectra-blocks'
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
					{ __( 'Free blocks', 'spectra-blocks' ) }
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
							<BlockItem block={ block } key={ block.id } updateCounter={ 0 } />
						</Container.Item>
					) ) }
				</Container>
			</div>
		</div>
	);
};

export default Blocks;
