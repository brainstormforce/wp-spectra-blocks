import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { Container } from '@bsf/force-ui';
import UnlockProItem from './UnlockProItem';

const ProBlocks = () => {
	const [ allBlocksData, setAllBlocksData ] = useState( null ); // Initialize state.

	useEffect( () => {
		const blocksData = convertToWidgetsArray( blocks );
		setAllBlocksData( blocksData );
	}, [] );

	const blocks = [
		{
			title: __( 'Loop Builder', 'ultimate-addons-for-gutenberg' ),
			slug: 'loop-builder',
			icon: 'loop',
			is_pro: true,
			is_active: false,
			link: 'loop-builder-v3'
		},
		{
			title: __( 'Dynamic Content', 'ultimate-addons-for-gutenberg' ),
			slug: 'dynamic-content',
			icon: 'dynamic-content',
			is_pro: true,
			is_active: false,
			link: 'dynamic-content-extension-v3'
		},
		{
			title: __( 'Login', 'ultimate-addons-for-gutenberg' ),
			slug: 'login',
			icon: 'login',
			is_pro: true,
			is_active: false,
			link: 'login-v3'
		},
		{
			title: __( 'Register', 'ultimate-addons-for-gutenberg' ),
			slug: 'register',
			icon: 'register',
			is_pro: true,
			is_active: false,
			link: 'register-v3'
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
				{ 'Activated' !== uag_react.pro_plugin_status ? ( <p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Do more with Spectra Pro', 'ultimate-addons-for-gutenberg' ) }
				</p>
				) : ( <p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Pro blocks', 'ultimate-addons-for-gutenberg' ) }
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
