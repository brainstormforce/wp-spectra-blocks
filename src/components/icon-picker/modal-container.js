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

/**
 * Inner grid panel — owns its own container-dimension state so that the
 * measurement re-render never propagates up to ModalContainer.
 *
 * When ModalContainer mounts, WordPress's useFocusReturn (inside <Modal>)
 * attaches its ref and saves document.activeElement (the PickerButton div).
 * If ModalContainer re-renders right away, useFocusReturn's cleanup fires
 * and returns focus to that outside element, causing the Modal to close.
 * By isolating the dimension state here, ModalContainer itself never
 * re-renders on mount, so useFocusReturn cleanup never fires spuriously.
 *
 * @param {Object} props - Component props.
 * @since x.x.x
 * @return {Element} The icon grid panel.
 */
const IconGridPanel = ( props ) => {
	const {
		iconList,
		columns,
		iconName,
		insertIcon,
		setInsertIcon,
		value,
		defaultIconsWithKeys,
		rowIndexForFirstTime,
	} = props;

	const containerRef = useRef();
	const [ height, setHeight ] = useState( 0 );
	const [ width, setWidth ] = useState( 0 );

	useEffect( () => {
		const el = containerRef.current;
		if ( el ) {
			setHeight( el.offsetHeight );
			setWidth( el.offsetWidth );
		}
	}, [] );

	if ( ! iconList.length ) {
		return (
			<div className="spectra-blocks-ip-modal-container" ref={ containerRef }>
				<div className="spectra-blocks-ip-icons icon-not-found">
					<div className="spectra-blocks-icon-not-available">
						<span>{ __( 'No Icons Found', 'spectra-blocks' ) }</span>
					</div>
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

	const heightAndWidth = width / columns;

	return (
		<div className="spectra-blocks-ip-modal-container" ref={ containerRef }>
			<div className="spectra-blocks-ip-icons">
				<Grid
					cellRenderer={ cellRenderer }
					columnCount={ iconList[ 0 ].length }
					columnWidth={ columns === iconList[ 0 ].length ? heightAndWidth - 2 : 100 }
					height={ height }
					rowCount={ iconList.length }
					rowHeight={ columns === iconList[ 0 ].length ? heightAndWidth : 100 }
					width={ width }
					scrollToRow={ rowIndexForFirstTime }
					autoContainerWidth={ true }
				/>
			</div>
		</div>
	);
};

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

	// Extract the raw icon name string from either the new object format or legacy string.
	const iconName = ( typeof value === 'object' && value?.name ) ? value.name : value;

	/**
	 * rowIndexForFirstTime: scroll-to row when the modal first opens.
	 * Computed once via lazy initializer — no setState on mount — so
	 * ModalContainer never re-renders after mount, which prevents
	 * WordPress's useFocusReturn cleanup from returning focus to the
	 * PickerButton (outside the modal) and closing the modal.
	 */
	const [ rowIndexForFirstTime, setRowIndexForFirstTime ] = useState( () => {
		const initialList = setIconListWithChunks( defaultIcons );
		return initialList.findIndex( ( row_value ) => row_value.includes( iconName ) );
	} );

	// Only focus the search input — no state updates here, so ModalContainer
	// never re-renders on mount and useFocusReturn stays undisturbed.
	useEffect( () => {
		inputElement.current.focus();
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
					<IconGridPanel
						iconList={ iconList }
						columns={ columns }
						iconName={ iconName }
						insertIcon={ insertIcon }
						setInsertIcon={ setInsertIcon }
						value={ value }
						defaultIconsWithKeys={ defaultIconsWithKeys }
						rowIndexForFirstTime={ rowIndexForFirstTime }
					/>
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
