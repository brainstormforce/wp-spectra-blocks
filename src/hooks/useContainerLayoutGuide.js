/**
 * External dependencies.
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Custom hook to manage the Container Layout Guide NUX experience
 *
 * This hook integrates with WordPress's NUX system to provide a guided
 * experience for users when they first use the Container block.
 *
 * @return {Object} Hook return object with guide state and controls.
 */
const useContainerLayoutGuide = () => {
	const [ showGuide, setShowGuide ] = useState( false );
	
	// NUX tip ID for the container layout guide
	const TIP_ID = 'spectra/container-layout-guide';

	const { 
		areTipsEnabled,
		isTipVisible,
		nuxAvailable
	} = useSelect( ( select ) => {
		try {
			const nuxSelect = select( 'core/nux' );
			
			// Check if NUX store is available
			if ( ! nuxSelect ) {
				return {
					areTipsEnabled: false,
					isTipVisible: false,
					nuxAvailable: false,
				};
			}

			const tipsEnabled = nuxSelect.areTipsEnabled?.() || false;
			const tipVisible = nuxSelect.isTipVisible?.( TIP_ID ) || false;
			
			return {
				areTipsEnabled: tipsEnabled,
				isTipVisible: tipVisible,
				nuxAvailable: true,
			};
		} catch ( error ) {
			// Fallback if NUX store is not available
			return {
				areTipsEnabled: false,
				isTipVisible: false,
				nuxAvailable: false,
			};
		}
	}, [ TIP_ID ] );

	// Calculate hasSeenGuide after we have the values
	const hasSeenGuide = ! isTipVisible && areTipsEnabled;

	// Get dispatch functions with error handling
	let dismissTip = null;
	try {
		const dispatch = useDispatch( 'core/nux' );
		dismissTip = dispatch?.dismissTip;
	} catch ( error ) {
		// NUX store not available, dismissTip will remain null
		dismissTip = null;
	}

	/**
	 * Check if we should show the guide for first-time users
	 */
	useEffect( () => {
		// Only show guide if:
		// 1. NUX is available
		// 2. Tips are enabled
		// 3. This specific tip is visible (not dismissed)
		// 4. User hasn't seen the guide before
		if ( nuxAvailable && areTipsEnabled && isTipVisible && ! hasSeenGuide ) {
			// Small delay to ensure the block is properly rendered
			const timer = setTimeout( () => {
				setShowGuide( true );
			}, 500 );

			return () => clearTimeout( timer );
		}
	}, [ nuxAvailable, areTipsEnabled, isTipVisible, hasSeenGuide ] );

	/**
	 * Handle guide completion
	 */
	const handleGuideComplete = () => {
		setShowGuide( false );
		
		// Mark the tip as dismissed so it doesn't show again
		if ( dismissTip ) {
			dismissTip( TIP_ID );
		}
	};

	/**
	 * Manually trigger the guide (e.g., from a help button)
	 */
	const triggerGuide = () => {
		setShowGuide( true );
	};

	/**
	 * Check if this is the user's first time with Container block
	 */
	const isFirstTime = nuxAvailable && areTipsEnabled && isTipVisible && ! hasSeenGuide;

	return {
		showGuide,
		triggerGuide,
		handleGuideComplete,
		isFirstTime,
		areTipsEnabled,
		nuxAvailable,
	};
};

export default useContainerLayoutGuide;
