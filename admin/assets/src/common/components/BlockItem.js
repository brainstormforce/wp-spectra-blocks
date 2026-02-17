import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import { Container, Badge, Skeleton } from '@bsf/force-ui';
import getApiData from '@Controls/getApiData';
import { useCallback, useEffect, useState } from 'react';
import { debounce } from '@Helpers/Helpers';
import './BlockItem.scss';
import RenderBlockPreview from '@Common/components/RenderBlockPreview';

const BlockItem = ( { block, is_extension } ) => {
	const dispatch = useDispatch();
	const enableExtension = useSelector( ( state ) => state[ block.extensionKey ] || null );

	const blocksStatuses = useSelector( ( state ) => state.blocksStatuses );

	const [ isLoading, setIsLoading ] = useState( true );

	const ExtensionStatus = 'disabled' === enableExtension ? false : true;

	// Debounce function to limit the rate of execution of a function.
	const debouncedApiCall = useCallback(
		debounce( ( currentStatus ) => {
			const data = {
				security: block.nonce,
				value: currentStatus,
			};

			const getApiDataFetch = getApiData( {
				url: uag_react.ajax_url,
				action: block.action,
				data,
			} );

			getApiDataFetch.then( () => {} );
		}, 300 ), // Adjust the delay as needed.
		[ enableExtension ]
	);

	const updateExtensionStatus = ( trigger = null ) => {
		let assetStatus;

		if ( trigger === null ) {
			assetStatus = enableExtension === 'disabled' ? 'enabled' : 'disabled';
		} else {
			assetStatus = block.trigger ? 'enabled' : 'disabled';
		}

		dispatch( { type: block.actionType, payload: assetStatus } );
		dispatch( {
			type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
			payload: __( 'Successfully saved!', 'ultimate-addons-for-gutenberg' ),
		} );
		debouncedApiCall( assetStatus ); // Call the debounced function.
	};

	useEffect( () => {
		if ( block.is_extension && ! ( block.is_pro && uag_react.pro_plugin_status !== 'Activated' ) ) {
			block.onChange( ( prev ) => ( {
				...prev,
				[ block.slug ]: ExtensionStatus,
			} ) );
		}
	}, [ ExtensionStatus, block.trigger ] );

	useEffect( () => {
		if ( block.trigger === true || block.trigger === false ) {
			updateExtensionStatus( block.trigger );
		}
	}, [ block.trigger ] );

	useEffect( () => {
		if ( blocksStatuses.length === 0 ) {
			setIsLoading( true );
		} else {
			setIsLoading( false );
		}
	}, [ blocksStatuses, enableExtension ] );

	if ( ! is_extension ) {
		if ( isLoading ) {
			return (
				<>
					<Skeleton className="w-12 h-2 rounded-md mb-2" />
					<Skeleton className="w-16 h-2 rounded-md mb-2" />
					<Skeleton className="w-12 h-2 rounded-md mb-2" />
				</>
			);
		}

		return (
			<Container align="center" containerType="flex" direction="column" justify="between" gap="">
				<div className="flex items-center justify-between w-full mb-1 p-1">
					<div style={ { fontSize: '22px' } }>
						<RenderBlockPreview blockName={ block.icon } />
					</div>

					<div className="flex items-center gap-x-2">
						{ ( block.is_pro && uag_react.pro_plugin_status !== 'Activated' ) && (
							<Badge label={__( 'Pro', 'ultimate-addons-for-gutenberg' )} size="xs" type="pill" variant="inverse" />
						) }
					</div>
				</div>

				<div className="flex flex-col w-full p-1">
					<p className="text-sm font-medium text-text-primary m-0">{ block.title }</p>
					<div className="flex items-center justify-between w-full">
						<a
							href={ `https://wpspectra.com/blocks-and-extensions/${ block.link }` }
							target="_blank"
							rel="noreferrer"
							className="mt-1 text-text-tertiary no-underline"
						>
							{__( 'Documentation', 'ultimate-addons-for-gutenberg' ) }
						</a>
					</div>
				</div>
			</Container>
		);
	}

	if ( isLoading ) {
		return (
			<>
				<Skeleton className="w-12 h-2 rounded-md mb-2" />
				<Skeleton className="w-16 h-2 rounded-md mb-2" />
				<Skeleton className="w-12 h-2 rounded-md mb-2" />
			</>
		);
	}

	return (
		<Container align="center" containerType="flex" direction="column" justify="between" gap="">
			<div className="flex items-center justify-between w-full mb-1 p-1">
				<div style={{ fontSize: '22px' }}><RenderBlockPreview blockName={block.icon} /></div>

				<div className="flex items-center gap-x-2">
					{ (
						<Badge label={__( 'Extension', 'ultimate-addons-for-gutenberg' )} size="xs" type="pill" variant="blue" />
					) }
				</div>
			</div>

			<div className="flex flex-col w-full p-1">
				<p className="text-sm font-medium text-text-primary m-0">{ block.title }</p>
				<div className="flex items-center justify-between w-full">
					<a
						href={ `https://wpspectra.com/blocks-and-extensions/${ block.link }` }
						target="_blank"
						rel="noreferrer"
						className="mt-1 text-text-tertiary no-underline"
					>
						{ __( 'Documentation', 'ultimate-addons-for-gutenberg' ) }
					</a>
				</div>
			</div>
		</Container>
	);
};

export default BlockItem;
