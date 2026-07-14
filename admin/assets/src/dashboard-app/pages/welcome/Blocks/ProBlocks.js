import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { Container } from '@bsf/force-ui';
import UnlockProItem from './UnlockProItem';
import Spectra_Block_Icons from '@Common/block-icons';

const ProBlocks = () => {
	const [ allBlocksData, setAllBlocksData ] = useState( null ); // Initialize state.

	useEffect( () => {
		const blocksData = convertToWidgetsArray( blocks );
		setAllBlocksData( blocksData );
	}, [] );

	const blocks = [
		{
			title: __( 'SVG Animator', 'spectra-blocks' ),
			slug: 'svg-animators',
			icon: React.cloneElement( Spectra_Block_Icons[ 'svg-animators' ], { width: 24, height: 24 } ),
			is_pro: true,
			is_active: false,
			link: 'svg-animator-spectra-blocks'
		},
		{
			title: __( 'Mega Menu', 'spectra-blocks' ),
			slug: 'mega-menu',
			icon: React.cloneElement( Spectra_Block_Icons[ 'mega-menu' ], { width: 24, height: 24 } ),
			is_pro: true,
			is_active: false,
			link: 'header-spectra-blocks'
		},
		{
			title: __( 'Loop Builder', 'spectra-blocks' ),
			slug: 'loop-builder',
			icon: 'loop',
			is_pro: true,
			is_active: false,
			link: 'loop-builder-spectra-blocks'
		},
		{
			title: __( 'Dynamic Content', 'spectra-blocks' ),
			slug: 'dynamic-content',
			icon: 'dynamic-content',
			is_pro: true,
			is_active: false,
			link: 'dynamic-content-extension-spectra-blocks'
		},
		{
			title: __( 'Login', 'spectra-blocks' ),
			slug: 'login',
			icon: 'login',
			is_pro: true,
			is_active: false,
			link: 'login-spectra-blocks'
		},
		{
			title: __( 'Register', 'spectra-blocks' ),
			slug: 'register',
			icon: 'register',
			is_pro: true,
			is_active: false,
			link: 'register-spectra-blocks'
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
				{ 'Activated' !== spectra_blocks_react.pro_plugin_status ? ( <p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Do more with Spectra Pro', 'spectra-blocks' ) }
				</p>
				) : ( <p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Pro blocks', 'spectra-blocks' ) }
				</p> ) }
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
							<UnlockProItem blockInfo={ block }/>
						</Container.Item>
					) ) }
				</Container>
			</div>
		</div>
	);
};

export default ProBlocks;
