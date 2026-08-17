import { Container, Button, DropdownMenu, Text, Label } from '@bsf/force-ui';
import { ChevronDown, CheckCircle2 } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { Fragment } from 'react';
import { useEffect, useState } from '@wordpress/element';
import RenderBlockPreview from '@Common/components/RenderBlockPreview';

const defaultUpgradeItems = [
	__( 'Advanced blocks', 'spectra-blocks' ),
	__( 'Premium pre-built templates', 'spectra-blocks' ),
	__( 'Consistent design with Global Styles', 'spectra-blocks' ),
	__( 'Advanced animations', 'spectra-blocks' ),
	__( 'Priority support and updates', 'spectra-blocks' ),
];

/**
 * Returns the class names.
 *
 * @param {...string} classes The class names.
 *
 * @return {string} Returns the class names.
 */
const classNames = ( ...classes ) => classes.filter( Boolean ).join( ' ' );

const MyAccountUpgradeToPro = ( {
	className = '',
	title = __( 'Build Beyond Boundaries', 'spectra-blocks' ),
	description = __(
		'Get access to advanced blocks and premium features.',
		'spectra-blocks'
	),
	items = defaultUpgradeItems,
	columnView = false,
	freeVPro = false,
} ) => {
	const [productsList, setProductsList] = useState( [] );
	const [selectedProduct, setSelectedProduct] = useState( '' );
	const contryCode = spectra_blocks_admin_react.contry_code;

	useEffect( () => {
		// Fetch pricing data from the API
		const fetchPricingData = async () => {
			try {
				const response = await fetch( 'https://store.brainstormforce.com/wp-json/pse/v1/pricing', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( {} ),
				} );
				const data = await response.json();

				// Filter products based on required names
				const filteredData = Object.entries( data.data ).reduce( ( acc, [key, value] ) => {
					if (
						( value.product.includes( 'Spectra Pro - Annual Subscription' ) &&
							value.variant.includes( '1 Site' ) ) ||
						( value.product.includes( 'Essential Toolkit for Spectra - Annual Subscription' ) &&
							value.variant.includes( '1 Site' ) ) ||
						( value.product.includes( 'Business Toolkit - Annual Subscription' ) &&
							value.variant.includes( '1 Site' ) )
					) {
						acc[key] = value;
					}
					return acc;
				}, {} );

				// Set filtered pricing data
				setProductsList( filteredData );

				// Set the first product as default
				if ( Object.keys( filteredData ).length > 0 ) {
					setSelectedProduct( Object.keys( filteredData )[0] );
				}
				// Loading complete
			} catch ( error ) {
				setSelectedProduct( '' );
			}
		};

		fetchPricingData();
	}, [] );

	// Define UTM parameters
	const utmParams = '&utm_medium=spectra-dashboard&utm_campaign=upsell-free-vs-pro-buy-now';

	const UpgradeButtonItem = ( { className: itemClassName = '' } ) => (
		<Container.Item className={itemClassName}>
			{freeVPro ? (
				<div className="flex justify-between items-center mt-4 unlock-pro-features-dropdown-container">
					<div style={{ width: '45%' }} className="dropdown-container">
						<DropdownMenu placement="bottom-start" className="w-60">
							<DropdownMenu.Trigger style={{ width: '100%' }}>
								<div
									className="p-2 cursor-pointer rounded-lg outline-none shadow-none w-full flex justify-center items-center border-border-subtle font-semibold text-text-primary"
									style={{ border: '1px solid #E5E7EB', width: '100%' }}
								>
									<div className="text-sm text-text-primary flex items-center justify-between w-full">
										{productsList[selectedProduct]?.product &&
											productsList[selectedProduct]?.product
												?.split( ' - ' )[0]
												?.replace( ' for Spectra', '' )}
										<span>
											<ChevronDown size={14} />
										</span>
									</div>
								</div>
							</DropdownMenu.Trigger>
							<DropdownMenu.ContentWrapper>
							<DropdownMenu.Content className="w-60" style={{ zIndex: '99999999' }}>
								<DropdownMenu.List className="dropdown-list">
									{Object.entries( productsList ).map( ( [key, value] ) => (
										<DropdownMenu.Item
											key={value.product + value.variant}
											onClick={() => setSelectedProduct( key )}
										>
											{value.product?.split( ' - ' )[0]?.replace( ' for Spectra', '' )}
										</DropdownMenu.Item>
									) )}
								</DropdownMenu.List>
							</DropdownMenu.Content>
							</DropdownMenu.ContentWrapper>
						</DropdownMenu>
					</div>

					<div className="flex items-center justify-between gap-3">
						<Button variant="ghost" size="md">
							{'$' + productsList[selectedProduct]?.price?.[contryCode]?.discounted}
							{productsList[selectedProduct]?.variant?.includes( 'Annual Subscription' ) ||
								productsList[selectedProduct]?.product?.includes( 'Annual Subscription' ) ? (
								<span className="text-text-tertiary">
									{__( '/year', 'spectra-blocks' )}
								</span>
							) : null}
						</Button>

						<a
							href={productsList[selectedProduct]?.checkout_url + utmParams}
							target="_blank"
							rel="noreferrer"
							className="no-underline text-text-on-color"
						>
							<Button className="" size="sm" tag="button" type="button" variant="primary">
								{__( 'Buy Now', 'spectra-blocks' )}
							</Button>
						</a>
					</div>
				</div>
			) : (
				<div className="flex gap-3 my-2">
					<a
						className="no-underline"
						href={spectra_blocks_admin_react.spectra_website?.uagDashboard}
						target="_blank"
						rel="noreferrer"
					>
						<Button variant="primary" className="spectra-blocks-remove-ring">
							{__( 'Upgrade Now', 'spectra-blocks' )}
						</Button>
					</a>

					{/* <Link
						className="no-underline"
						to={ {
							pathname: 'admin.php',
							search: '?page=spectra-blocks&path=free-vs-pro',
						} }
					>
						<Button variant="ghost" className="spectra-blocks-remove-ring">
							{ __( 'Free VS Pro', 'spectra-blocks' ) }
						</Button>
					</Link> */}
				</div>
			)}

			{freeVPro && (
				<a
					href={spectra_blocks_admin_react.spectra_website?.freeVsPro}
					target="_blank"
					rel="noreferrer"
					className="text-xxs text-brand-primary-600 w-full flex justify-end md:pr-[10px] pr-2 -mt-2"
				>
					{__( 'View plans', 'spectra-blocks' )}
				</a>
			)}
		</Container.Item>
	);

	return (
					<section className={classNames( 'flex flex-col gap-3', className )} >
				 			<div className={columnView ? 'mr-0 xl:mr-0' : ''}>
				 				<Container
						className={columnView ? 'flex flex-col gap-1' : 'flex flex-col'}
				 					gap={columnView ? 'sm' : 'lg'}
				 				>
				 					<Container.Item className={columnView ? 'flex flex-col gap-1 items-center' : 'flex flex-col gap-1 items-center mt-3 px-6'}>
									{!columnView && <RenderBlockPreview blockName={'cta_and_nudges'} width={250} height={280} />}
				 						<div className={columnView ? 'flex flex-col gap-1' : 'flex flex-col gap-1 text-center'}>
											<Label className="font-semibold mb-1" htmlFor="default-width" size="md">
												{title}
											</Label>
											<Text color="secondary">{description}</Text>
				 						</div>
				 					</Container.Item>

				 					{!columnView && <UpgradeButtonItem className="m-auto" Icon={false} />}

				 					<Container.Item
				 						className={ 'mt-3 flex flex-col gap-2.5'}
				 					>
				 						{items.map( ( item, i ) => (
				 							<Fragment key={i}>
				 								<div className="flex items-center gap-1">
				 									<CheckCircle2 size={16} className="text-background-brand" />
													<Text size={12} as="span" className='text-sm text-text-secondary'>
				 										{item}
				 									</Text>
				 								</div>

				 								{!columnView && i !== items.length - 1 && (
				 									<div className="border-0.5 border-solid border-border-subtle"></div>
				 								)}
				 							</Fragment>
				 						) )}
				 					</Container.Item>

									{columnView && <UpgradeButtonItem className="m-0" Icon={false} />}
				 				</Container>
				 			</div>

				 			{columnView && (
				 				<div className="px-4 py-4 w-full max-w-[200px] xl:max-w-[280px]">
									 <RenderBlockPreview blockName={ 'cta_and_nudges' } width={250} height={280} />
				 				</div>
				 			)}
				 	</section>
	);
};

export default MyAccountUpgradeToPro;
