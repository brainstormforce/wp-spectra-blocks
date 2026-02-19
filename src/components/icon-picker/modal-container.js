/**
 * External dependencies.
 */
import { Modal, Tooltip } from '@wordpress/components';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Grid } from 'react-virtualized';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import RenderSVG from '@spectra-helpers/render-svg';
import { spectraClassNames } from '@spectra-helpers';
import HeaderContainer from './header-container';

const ModalContainer = ( props ) => {
	const { value, onChange, closeModal, defaultIcons, iconCategoryList } = props;
	const defaultIconsWithKeys = { ...spectra_blocks_info.spectra_blocks_svg_icons };
	const columns = 8;

	const setIconListWithChunks = ( icons ) => (
		Array.from(
			{ length: Math.ceil( icons.length / columns ) },
			( v, i ) => ( icons.slice( i * columns, i * columns + columns ) )
		)
	);

	const [ searchIconInputValue, setSearchIconInputValue ] = useState( '' );
	const [ iconList, setIconList ] = useState( setIconListWithChunks( defaultIcons ) );
	const [ categoryListName, setCategoryListName ] = useState( 'all' );
	const [ iconListByCategory, setIconListByCategory ] = useState( defaultIcons );
	const [ insertIcon, setInsertIcon ] = useState( '' );
	const inputElement = useRef();

	// Container states
	const iconContainerRef = useRef();
	const [ iconContainerHeight, setIconContainerHeight ] = useState( 0 );
	const [ iconContainerWidth, setIconContainerWidth ] = useState( 0 );

	/**
	 * This is value when modal mount then we will show selected icon rest of time this will set to null.
	 */
	const [ rowIndexForFirstTime, setRowIndexForFirstTime ] = useState( null );
	const getContainerHeight = ( property ) => {
		const element = iconContainerRef?.current;
		if ( ! element ) {
			return null;
		}
		return 'w' === property ? element.offsetWidth : element.offsetHeight;
	};

	// Extract the raw icon name string from either the new object format or legacy string.
	const iconName = ( typeof value === 'object' && value?.name ) ? value.name : value;

	useEffect( () => {
		inputElement.current.focus();
		setIconContainerHeight( getContainerHeight( 'h' ) );
		setIconContainerWidth( getContainerHeight( 'w' ) );
		const selectedIconRowIndex = iconList.findIndex( ( row_value ) => row_value.includes( iconName ) );
		setRowIndexForFirstTime( selectedIconRowIndex );
	}, [] );

	// Click on category list.
	const clickToCategoryList = ( category ) => {
		let findIconsByCategory = [];
		if ( 'all' === category ) {
			findIconsByCategory = defaultIcons;
		} else if ( 'no-category' === category ) {
			for ( const defaultIcon in defaultIconsWithKeys ) {
				if ( 0 === defaultIconsWithKeys[ defaultIcon ].custom_categories.length ) {
					findIconsByCategory.push( defaultIcon );
				}
			}
		} else {
			for ( const defaultIcon in defaultIconsWithKeys ) {
				if ( defaultIconsWithKeys[ defaultIcon ].custom_categories.includes( category ) ) {
					findIconsByCategory.push( defaultIcon );
				}
			}
		}
		setCategoryListName( category );
		setIconListByCategory( findIconsByCategory );
		setRowIndexForFirstTime( null );
		setIconList( setIconListWithChunks( findIconsByCategory ) );
		setSearchIconInputValue( '' );
	};

	// Search from input icon.
	const searchIcon = ( e ) => {
		const inputValue = e.target.value.toLowerCase();
		if ( '' !== inputValue ) {
			const filterIcons = ( icons ) =>
				defaultIconsWithKeys[ icons ]?.label
					? -1 !== defaultIconsWithKeys[ icons ].label.toLowerCase().indexOf( inputValue )
					: false;

			const resultIcons = [ ...iconListByCategory ].filter( filterIcons );
			setIconList( setIconListWithChunks( resultIcons ) );
		} else {
			clickToCategoryList( categoryListName );
		}
		setSearchIconInputValue( inputValue );
	};

	// Render icon list.
	const renderIconList = () => {
		if ( ! iconList.length ) {
			return (
				<div className="spectra-blocks-ip-icons icon-not-found">
					<div className="spectra-blocks-icon-not-available">
						<span>{ __( 'No Icons Found', 'spectra-blocks' ) }</span>
					</div>
				</div>
			);
		}
		const iconTitle = ( actualTitle ) => {
			if ( ! actualTitle ) {
				return '';
			}
			return actualTitle.length < 11 ? actualTitle : actualTitle.slice( 0, 10 ) + '..';
		};
		// renderer.
		function cellRenderer( renderer ) {
			const { columnIndex, key, rowIndex, style } = renderer;
			const currentIcon = iconList[ rowIndex ][ columnIndex ];

			if ( ! currentIcon ) {
				return null;
			}

			const iconClass = spectraClassNames( [
				'spectra-blocks-icon-item',
				iconName === currentIcon && 'default',
				currentIcon === insertIcon && 'selected',
			] );

			const actualTitle = defaultIconsWithKeys[ currentIcon ]?.label
				? defaultIconsWithKeys[ currentIcon ].label
				: '';
			return (
				<div key={ key } style={ style }>
					<div
						className={ iconClass }
						onClick={ () => {
							if ( value !== currentIcon ) {
								setInsertIcon( currentIcon );
							}
						} }
					>
						<RenderSVG svg={ currentIcon }/>
						<Tooltip text={ actualTitle }>
							<span>
								{ iconTitle( actualTitle ) }
							</span>
						</Tooltip>
					</div>
				</div>
			);
		}
		const heightAndWidth = iconContainerWidth / columns;
		return (
			<div className="spectra-blocks-ip-icons">
				<Grid
					cellRenderer={ cellRenderer }
					columnCount={ iconList[ 0 ].length }
					columnWidth={ columns === iconList[ 0 ].length ? heightAndWidth - 2 : 100 }
					height={ iconContainerHeight }
					rowCount={ iconList.length }
					rowHeight={ columns === iconList[ 0 ].length ? heightAndWidth : 100 }
					width={ iconContainerWidth }
					scrollToRow={ rowIndexForFirstTime }
					//
					autoContainerWidth={ true }
				/>
			</div>
		);
	};

	// List of categories.
	const listOfCategory = () => (
		<div className="spectra-blocks-ip-categories-list">
			<div
				key="all"
				className={ 'all' === categoryListName ? 'selected' : null }
				onClick={ () => clickToCategoryList( 'all' ) }
			>
				{ __( 'All Icons', 'spectra-blocks' ) }
			</div>
			{ iconCategoryList.map( ( cateValue, key ) => (
				<div
					key={ key }
					className={ cateValue.slug === categoryListName ? 'selected' : null }
					onClick={ () => clickToCategoryList( cateValue.slug ) }
				>
					{ cateValue.title }
				</div>
			) ) }
			<div
				key="no-category"
				className={ 'no-category' === categoryListName ? 'selected' : null }
				onClick={ () => clickToCategoryList( 'no-category' ) }
			>
				{ __( 'Other', 'spectra-blocks' ) }
			</div>
		</div>
	);

	// Modal component.
	return (
		<Modal
			className="spectra-blocks-ip-modal-wrapper"
			onRequestClose={ closeModal }
			overlayClassName="spectra-blocks-ip-modal-wrapper-overlay"
			shouldCloseOnClickOutside={ false }
			closeButtonLabel={ __( 'Close', 'spectra-blocks' ) }
		>
			{ /* Header  */ }
			<HeaderContainer
				searchIconInputValue={ searchIconInputValue }
				onClickRemoveSearch={ () => {
					clickToCategoryList( categoryListName, false );
					setSearchIconInputValue( '' );
				} }
				searchIcon={ searchIcon }
				inputElement={ inputElement }
			/>
			{ /* middle  */ }
			<section className="spectra-blocks-ip-lr-container">
				<div className="spectra-blocks-ip-left">{ listOfCategory() }</div>
				<div className="spectra-blocks-ip-right">
					<div className="spectra-blocks-ip-modal-container" ref={ iconContainerRef }>
						{ renderIconList() }
					</div>
				</div>
			</section>
			{ /* Footer */ }
			<section className="spectra-blocks-ip-footer">
				<button
					className={ '' === insertIcon ? 'disable' : null }
					onClick={
						'' !== insertIcon
							? () => {
									const iconData = defaultIconsWithKeys[ insertIcon ];
									const svgEntry = iconData?.svg?.brands ?? iconData?.svg?.solid ?? iconData?.svg?.regular ?? null;
									onChange(
										svgEntry
											? { name: insertIcon, svg: { width: svgEntry.width, height: svgEntry.height, path: svgEntry.path } }
											: insertIcon
									);
									closeModal();
							  }
							: null
					}
				>
					{ __( 'Insert Icon', 'spectra-blocks' ) }
				</button>
			</section>
		</Modal>
	);
};
export default ModalContainer;
