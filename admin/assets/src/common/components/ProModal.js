import { __ } from '@wordpress/i18n';
import React from 'react';
import { useEffect, useState } from '@wordpress/element';
import { Button, DropdownMenu, Skeleton } from '@bsf/force-ui';
import { Zap, X, Check, ChevronDown } from 'lucide-react';

const ProModal = ( { modalData, setIsModalOpen } ) => {
	const [ productsList, setProductsList ] = useState( {} );
	const [ selectedProduct, setSelectedProduct ] = useState( 'spectra-pro' );
	const [ loading, setLoading ] = useState( false );
	const [ selectedTitle, setSelectedTitle ] = useState( 'Spectra Pro' );

	const { title, Image, header, description, features } = modalData[ selectedTitle ];
	const contryCode = 'US'; // uag_admin_react.contry_code; // For now we're keeping US pricing for all user. TODO Uncomment the code to keep dynamic pricing.
	
	// Static product data
	const staticProductData = {
		'spectra-pro': {
			product: 'Spectra Pro',
			price: { US: { discounted: 69 } },
			checkout_url: 'https://store.brainstormforce.com/checkout/?edd_action=add_to_cart&download_id=11120154&edd_options%5Bprice_id%5D=7',
			variant: 'Annual Subscription'
		},
		'essential-toolkit': {
			product: 'Essential Toolkit',
			price: { US: { discounted: 119 } },
			checkout_url: 'https://store.brainstormforce.com/checkout/?edd_action=add_to_cart&download_id=11140953&edd_options%5Bprice_id%5D=13',
			variant: 'Annual Subscription'
		},
		'business-toolkit': {
			product: 'Business Toolkit',
			price: { US: { discounted: 159 } },
			checkout_url: 'https://store.brainstormforce.com/checkout/?edd_action=add_to_cart&download_id=11416197&edd_options%5Bprice_id%5D=8&discount=GIVE3AB',
			variant: 'Annual Subscription'
		}
	};

	useEffect( () => {
		// Set static product data
		setProductsList( staticProductData );
		setLoading( false );
	}, [] );

	useEffect( () => {
		const checkDropdown = () => {
			const modalContainers = document.querySelectorAll( '.uagb-upsell-modal, [data-floating-ui-focusable]' );

			if ( modalContainers.length === 0 ) {
				return;
			}

			const preventScroll = ( e ) => {
				e.preventDefault();
			};

			modalContainers.forEach( ( modalContainer ) => {
				if ( modalContainer ) {
					modalContainer.style.zIndex = '9999';
					modalContainer.style.overflow = 'hidden';

					modalContainer.addEventListener( 'wheel', preventScroll, { passive: false } );
					modalContainer.addEventListener( 'touchmove', preventScroll, { passive: false } );

					const childElements = modalContainer.querySelectorAll( '*' );
					childElements.forEach( ( child ) => {
						child.style.overflow = 'hidden';
						child.addEventListener( 'wheel', preventScroll, { passive: false } );
						child.addEventListener( 'touchmove', preventScroll, { passive: false } );
					} );
				}
			} );

			return () => {
				modalContainers.forEach( ( modalContainer ) => {
					if ( modalContainer ) {
						modalContainer.style.overflow = '';
						modalContainer.removeEventListener( 'wheel', preventScroll );
						modalContainer.removeEventListener( 'touchmove', preventScroll );

						const childElements = modalContainer.querySelectorAll( '*' );
						childElements.forEach( ( child ) => {
							child.style.overflow = '';
							child.removeEventListener( 'wheel', preventScroll );
							child.removeEventListener( 'touchmove', preventScroll );
						} );
					}
				} );
			};
		};

		if ( typeof MutationObserver !== 'undefined' ) {
			const observer = new MutationObserver( checkDropdown );
			observer.observe( document.body, { childList: true, subtree: true } );

			return () => observer.disconnect();
		}
	}, [] );

	useEffect( () => {
		const productName = productsList[ selectedProduct ]?.product || '';
		const titleMapping = {
			'Spectra Pro': 'Spectra Pro',
			'Essential Toolkit': 'Essential Toolkit',
			'Business Toolkit': 'Business Toolkit',
		};

		const newTitle =
			Object.keys( titleMapping ).find( ( key ) => productName.includes( key ) ) || 'Spectra Pro';
		setSelectedTitle( newTitle );
	}, [ selectedProduct ] );

	const closeModal = ( e ) => {
		e.stopPropagation();
	};

	// Define UTM parameters
	const utmParams = '&utm_medium=spectra-dashboard&utm_campaign=upsell-popup-buy-now';
	
	// Handle Buy Now button click
	const handleBuyNowClick = () => {
		const currentProduct = productsList[selectedProduct];
		if ( currentProduct?.checkout_url ) {
			const redirectUrl = currentProduct.checkout_url + utmParams;
			window.open( redirectUrl, '_blank' );
		}
		setIsModalOpen( false );
	};
	return (
		<div
			onClick={ () => setIsModalOpen( false ) }
			className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-999999 uagb-upsell-modal"
		>
			<div
				className={ 'bg-white rounded-lg p-5 sm:w-[500px] w-[400px]' }
				onClick={ closeModal }
			>
				<div className="flex w-full justify-between items-center">
					<div className="text-brand-primary-600 flex space-x-1">
						<Zap size={ 14 } className="flex items-center justify-center" />
						<div className="font-semibold text-xs">{ title }</div>
					</div>

					<div onClick={ () => setIsModalOpen( false ) } className="p-1">
						<X size={ 14 } className="cursor-pointer" />
					</div>
				</div>

				{ loading ? (
					<>
						<Skeleton className="w-48 h-6 rounded-md mb-6 mt-6" />
						<Skeleton className="w-60 h-6 rounded-md mb-6" />
						<Skeleton className="w-48 h-6 rounded-md mb-6" />
					</>
				) : (
					<>
						<div className="w-full flex justify-center items-center mt-4">
							<Image />
						</div>

						<div className="mt-4">
							<h5 className="text-lg font-medium m-0">{ header }</h5>
							<p className="text-sm text-text-secondary mt-1 m-0">{ description }</p>
						</div>

						<div
							className={ `${
								features.length > 4 ? 'grid grid-cols-2' : 'flex flex-col'
							} w-full gap-1 mt-4` }
						>
							{ features.map( ( ele, idx ) => (
								<div
									key={ idx }
									className={ `text-brand-primary-600 flex gap-1.5 items-center ${
										idx % 2 !== 0 && 'mr-3'
									}` }
								>
									<Check size={ 12 } />
									<p className="text-field-label m-0">{ ele }</p>
								</div>
							) ) }
						</div>

						<hr className="w-full border-b-0 border-x-0 border-t border-solid border-t-border-subtle mt-4" />

						{ Object.keys( productsList ).length > 0 && (
							<div className="flex justify-between items-center mt-4 flex-col sm:flex-row gap-2">
								<div className="sm:w-1/2 w-full dropdown-container">
									<DropdownMenu placement="bottom-start" style={ { width: '100%' } }>
										<DropdownMenu.Trigger style={ { width: '100%' } }>
											<div
												className="p-2 cursor-pointer rounded-lg outline-none shadow-none w-full flex justify-center items-center border-border-subtle font-semibold text-text-primary"
												style={ { border: '1px solid #E5E7EB', width: '100%' } }
											>
												<div className="text-sm text-text-primary flex items-center justify-between w-full">
													{ productsList[ selectedProduct ]?.product }
													<span>
														<ChevronDown size={ 14 } />
													</span>
												</div>
											</div>
										</DropdownMenu.Trigger>
										<DropdownMenu.ContentWrapper>
										<DropdownMenu.Content className="w-60 dropdown-list">
											<DropdownMenu.List
											// style={ { zIndex: '99999999' } }
											>
												{ Object.entries( productsList ).map( ( [ key, value ] ) => (
													<DropdownMenu.Item
														onClick={ () => setSelectedProduct( key ) }
														// style={ { zIndex: '99999999' } }
														key={ key }
													>
														{ value.product }
													</DropdownMenu.Item>
												) ) }
											</DropdownMenu.List>
										</DropdownMenu.Content>
										</DropdownMenu.ContentWrapper>
									</DropdownMenu>
								</div>

								<div className="flex items-center justify-between sm:gap-0 gap-[88px]">
									<Button variant="ghost" size="md" className="uagb-remove-ring">
											{'$' + productsList[selectedProduct]?.price?.[contryCode]?.discounted }
										{ productsList[ selectedProduct ]?.variant?.includes( 'Annual Subscription' ) ||
										productsList[ selectedProduct ]?.product?.includes( 'Annual Subscription' ) ? (
											<span className="text-text-tertiary">
												{ __( '/year', 'ultimate-addons-for-gutenberg' ) }
											</span>
										) : null }
									</Button>

									<Button
										className=""
										size="sm"
										tag="button"
										type="button"
										variant="primary"
										onClick={ handleBuyNowClick }
									>
										{ __( 'Buy Now', 'ultimate-addons-for-gutenberg' ) }
									</Button>
								</div>
							</div>
						) }

						<div className="w-full flex justify-end md:pr-[10px] pr-2">
							<a
								href={uag_admin_react.spectra_website?.upsellModalAdmin}
								target="_blank"
								rel="noreferrer"
								className="text-xxs text-brand-primary-600"
							>
								{ __( 'View plans', 'ultimate-addons-for-gutenberg' ) }
							</a>
						</div>
					</>
				) }
			</div>
		</div>
	);
};

export default ProModal;
