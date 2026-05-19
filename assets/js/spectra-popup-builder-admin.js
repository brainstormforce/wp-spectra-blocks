/**
 * Spectra Blocks — Popup Builder admin toggle script.
 *
 * Handles the enable/disable toggle switch on the Popup Builder post-type
 * list table. Sends an AJAX request to update the popup's enabled status
 * and updates the toggle UI on success.
 *
 * @since x.x.x
 */

const SpectraBlocksToggleSwitch = ( event ) => {
	const toggle    = event.target;
	const isActive  = toggle.classList.contains( 'spectra-popup-builder__switch--active' );
	const newStatus = isActive ? 'false' : 'true';

	const formData = new FormData();
	formData.append( 'action', 'spectra_blocks_update_popup_status' );
	formData.append( 'nonce', spectra_blocks_popup_builder_admin.spectra_blocks_popup_builder_admin_nonce );
	formData.append( 'post_id', toggle.dataset.post_id );
	formData.append( 'enabled', newStatus );

	fetch( spectra_blocks_popup_builder_admin.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	} )
		.then( ( response ) => response.json() )
		.then( ( data ) => {
			if ( false === data.success ) {
				return;
			}
			if ( 'false' === newStatus ) {
				toggle.classList.remove( 'spectra-popup-builder__switch--active' );
			} else {
				toggle.classList.add( 'spectra-popup-builder__switch--active' );
			}
		} );
};

document.addEventListener( 'DOMContentLoaded', () => {
	const switches = document.querySelectorAll( '.spectra-popup-builder__switch' );
	for ( let i = 0; i < switches.length; i++ ) {
		switches[ i ].addEventListener( 'click', ( event ) => SpectraBlocksToggleSwitch( event ), false );
	}
} );
