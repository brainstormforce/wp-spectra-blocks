/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import RenderSVG from '@spectra-helpers/render-svg';

const HeaderContainer = ( props ) => {
	const { searchIconInputValue, onClickRemoveSearch, searchIcon, inputElement } = props;

	const removeTextIcon = () => ( '' === searchIconInputValue ? (
			<RenderSVG svg='sistrix'/>
		) : (
			<span onClick={ onClickRemoveSearch } className="dashicons dashicons-no-alt"></span>
		)
	);

	// Search input container.
	return (
		<section className="spectra-blocks-ip-header">
			<h2>{ __( 'Icon Library', 'spectra-blocks' ) }</h2>
			<div className="spectra-blocks-ip-search-container">
				<div className="spectra-blocks-ip-search-bar">
					{ removeTextIcon() }
					<input
						type="text"
						placeholder={ __( 'Search', 'spectra-blocks' ) }
						value={ searchIconInputValue }
						onChange={ searchIcon }
						ref={ inputElement }
					/>
				</div>
			</div>
		</section>
	);
};
export default HeaderContainer;
