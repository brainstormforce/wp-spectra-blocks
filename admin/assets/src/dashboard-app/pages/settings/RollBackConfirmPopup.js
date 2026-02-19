/* This popup appears to confirm whether or not the user wishes to rollback to their selected version. */
import { useRef } from 'react';
import { X } from 'lucide-react';
import { Button } from '@bsf/force-ui';

const RollBackConfirmPopup = ( props ) => {
	const { openPopup, setopenPopup, previousVersionSelect, setconfirmPopup, popupContent, popupAccept, popupCancel } = props;

	const onCancelClick = () => {
		setopenPopup( false );
	};

	const onContinueClick = () => {
		const rollbackUrl = spectra_blocks_react.rollback_url.replace( 'VERSION', previousVersionSelect );
		setopenPopup( false );
		setconfirmPopup( true );
		window.location.href = rollbackUrl;
	};

	// Create popup content with proper callback handling
	const confirmationPopupContent = {
		title: popupContent?.title,
		description: popupContent?.description,
	};

	const confirmationPopupAccept = {
		label: popupAccept?.label,
		callback: onContinueClick,
	};

	const confirmationPopupCancel = {
		label: popupCancel?.label,
		callback: onCancelClick,
	};

	return (
		<ConfirmationPopup
			showPopup={ openPopup }
			popupContent={ confirmationPopupContent }
			popupAccept={ confirmationPopupAccept }
			popupCancel={ confirmationPopupCancel }
		/>
	);
};

// ConfirmationPopup component implementation based on AIPage.js
const ConfirmationPopup = ( props ) => {
	const { showPopup, popupContent, popupAccept, popupCancel } = props;

	const cancelButtonRef = useRef( null );

	if ( ! showPopup ) return null;

	return (
		<div
			className="w-full h-screen fixed top-0 left-0 flex items-end sm:items-center p-4 justify-center bg-black bg-opacity-30"
			onClick={ () => popupCancel.callback() }
			style={ { zIndex: 99999 } }
		>
			<div
				className="inline-block p-3 rounded-lg bg-background-primary w-120 font-[Figtree]"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="mb-2 p-2">
					<div className="flex w-full justify-between items-center mb-2">
						<div className="text-base font-semibold text-text-primary">{ popupContent.title }</div>

						<X size={ 16 } onClick={ popupCancel.callback } className="cursor-pointer" />
					</div>

					<div className="text-sm text-text-secondary font-normal w-full text-left">
						{ popupContent.description }
					</div>
				</div>

				<div className="p-2">
					<div className="flex justify-end items-center w-full gap-3">
						<Button
							className="spectra-blocks-outline-button"
							size="md"
							tag="button"
							type="button"
							variant="outline"
							onClick={ popupCancel.callback }
							ref={ cancelButtonRef }
						>
							{ popupCancel.label }
						</Button>

						<Button
							className="bg-button-primary text-text-on-color spectra-blocks-remove-ring hover:bg-button-primary-hover"
							size="md"
							tag="button"
							type="button"
							variant="primary"
							onClick={ popupAccept.callback }
						>
							{ popupAccept.label }
						</Button>
					</div>
				</div>
			</div>
		</div>
	);
};

export default RollBackConfirmPopup;
