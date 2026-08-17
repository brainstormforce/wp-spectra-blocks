/**
 * This file contains all the classes avaliable to use from the system.
 *
 * Any changes made in this file should be reflected in the gs-classes folder, and vice versa.
 * These two files should always contain the exact same classes.
 */

/**
 * Add any custom classes that have been created by the user.
 *
 * @param {Object|null} liveClasses
 * @since x.x.x
 *
 * @return {Object} An object with all the custom classes, or an empty object.
 */
const addCustomOptions = ( liveClasses = null ) => {
	// Prefer live classes passed from the hook; fall back to the PHP page-load snapshot.
	const userClasses = liveClasses ?? window?.spectra_editor_gs?.user_classes;

	if ( ! userClasses || Object.keys( userClasses ).length === 0 ) {
		return {};
	}

	const allClasses = []

	Object.keys( userClasses ).forEach( currentClass => {
		allClasses.push( { value: currentClass, label: currentClass }, )
	} );

	return {
		label: 'Custom Classes',
		options: allClasses,
	};
};

/**
 * Get all the translucent classes for all available colors.
 *
 * @since x.x.x
 *
 * @param {string} classType      The class type for which to get transparent classes.
 * @param {string} positionalChar A positional character with a hyphen after it, if needed.
 * @return {Object}               An object with the transparent class, and all the translucent class of all colors. 
 */
const getTranslucentColorClasses = ( classType, positionalChar = '' ) => ( {
	common: {
		transparent: [
			{ value: `${ classType }--${ positionalChar }transparent`, label: `${ classType }--${ positionalChar }transparent` },
		],
		white: [
			{ value: `${ classType }--${ positionalChar }white--10`, label: `${ classType }--${ positionalChar }white--10` },
			{ value: `${ classType }--${ positionalChar }white--20`, label: `${ classType }--${ positionalChar }white--20` },
			{ value: `${ classType }--${ positionalChar }white--30`, label: `${ classType }--${ positionalChar }white--30` },
			{ value: `${ classType }--${ positionalChar }white--40`, label: `${ classType }--${ positionalChar }white--40` },
			{ value: `${ classType }--${ positionalChar }white--50`, label: `${ classType }--${ positionalChar }white--50` },
			{ value: `${ classType }--${ positionalChar }white--60`, label: `${ classType }--${ positionalChar }white--60` },
			{ value: `${ classType }--${ positionalChar }white--70`, label: `${ classType }--${ positionalChar }white--70` },
			{ value: `${ classType }--${ positionalChar }white--80`, label: `${ classType }--${ positionalChar }white--80` },
			{ value: `${ classType }--${ positionalChar }white--90`, label: `${ classType }--${ positionalChar }white--90` },
		],
		black: [
			{ value: `${ classType }--${ positionalChar }black--10`, label: `${ classType }--${ positionalChar }black--10` },
			{ value: `${ classType }--${ positionalChar }black--20`, label: `${ classType }--${ positionalChar }black--20` },
			{ value: `${ classType }--${ positionalChar }black--30`, label: `${ classType }--${ positionalChar }black--30` },
			{ value: `${ classType }--${ positionalChar }black--40`, label: `${ classType }--${ positionalChar }black--40` },
			{ value: `${ classType }--${ positionalChar }black--50`, label: `${ classType }--${ positionalChar }black--50` },
			{ value: `${ classType }--${ positionalChar }black--60`, label: `${ classType }--${ positionalChar }black--60` },
			{ value: `${ classType }--${ positionalChar }black--70`, label: `${ classType }--${ positionalChar }black--70` },
			{ value: `${ classType }--${ positionalChar }black--80`, label: `${ classType }--${ positionalChar }black--80` },
			{ value: `${ classType }--${ positionalChar }black--90`, label: `${ classType }--${ positionalChar }black--90` },
		],
	},
	primary: {
		main: [
			{ value: `${ classType }--${ positionalChar }primary--10`, label: `${ classType }--${ positionalChar }primary--10` },
			{ value: `${ classType }--${ positionalChar }primary--20`, label: `${ classType }--${ positionalChar }primary--20` },
			{ value: `${ classType }--${ positionalChar }primary--30`, label: `${ classType }--${ positionalChar }primary--30` },
			{ value: `${ classType }--${ positionalChar }primary--40`, label: `${ classType }--${ positionalChar }primary--40` },
			{ value: `${ classType }--${ positionalChar }primary--50`, label: `${ classType }--${ positionalChar }primary--50` },
			{ value: `${ classType }--${ positionalChar }primary--60`, label: `${ classType }--${ positionalChar }primary--60` },
			{ value: `${ classType }--${ positionalChar }primary--70`, label: `${ classType }--${ positionalChar }primary--70` },
			{ value: `${ classType }--${ positionalChar }primary--80`, label: `${ classType }--${ positionalChar }primary--80` },
			{ value: `${ classType }--${ positionalChar }primary--90`, label: `${ classType }--${ positionalChar }primary--90` },
		],
		dark: [
			{ value: `${ classType }--${ positionalChar }primary-dark--10`, label: `${ classType }--${ positionalChar }primary-dark--10` },
			{ value: `${ classType }--${ positionalChar }primary-dark--20`, label: `${ classType }--${ positionalChar }primary-dark--20` },
			{ value: `${ classType }--${ positionalChar }primary-dark--30`, label: `${ classType }--${ positionalChar }primary-dark--30` },
			{ value: `${ classType }--${ positionalChar }primary-dark--40`, label: `${ classType }--${ positionalChar }primary-dark--40` },
			{ value: `${ classType }--${ positionalChar }primary-dark--50`, label: `${ classType }--${ positionalChar }primary-dark--50` },
			{ value: `${ classType }--${ positionalChar }primary-dark--60`, label: `${ classType }--${ positionalChar }primary-dark--60` },
			{ value: `${ classType }--${ positionalChar }primary-dark--70`, label: `${ classType }--${ positionalChar }primary-dark--70` },
			{ value: `${ classType }--${ positionalChar }primary-dark--80`, label: `${ classType }--${ positionalChar }primary-dark--80` },
			{ value: `${ classType }--${ positionalChar }primary-dark--90`, label: `${ classType }--${ positionalChar }primary-dark--90` },
		],
		darker: [
			{ value: `${ classType }--${ positionalChar }primary-darker--10`, label: `${ classType }--${ positionalChar }primary-darker--10` },
			{ value: `${ classType }--${ positionalChar }primary-darker--20`, label: `${ classType }--${ positionalChar }primary-darker--20` },
			{ value: `${ classType }--${ positionalChar }primary-darker--30`, label: `${ classType }--${ positionalChar }primary-darker--30` },
			{ value: `${ classType }--${ positionalChar }primary-darker--40`, label: `${ classType }--${ positionalChar }primary-darker--40` },
			{ value: `${ classType }--${ positionalChar }primary-darker--50`, label: `${ classType }--${ positionalChar }primary-darker--50` },
			{ value: `${ classType }--${ positionalChar }primary-darker--60`, label: `${ classType }--${ positionalChar }primary-darker--60` },
			{ value: `${ classType }--${ positionalChar }primary-darker--70`, label: `${ classType }--${ positionalChar }primary-darker--70` },
			{ value: `${ classType }--${ positionalChar }primary-darker--80`, label: `${ classType }--${ positionalChar }primary-darker--80` },
			{ value: `${ classType }--${ positionalChar }primary-darker--90`, label: `${ classType }--${ positionalChar }primary-darker--90` },
		],
		darkest: [
			{ value: `${ classType }--${ positionalChar }primary-darkest--10`, label: `${ classType }--${ positionalChar }primary-darkest--10` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--20`, label: `${ classType }--${ positionalChar }primary-darkest--20` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--30`, label: `${ classType }--${ positionalChar }primary-darkest--30` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--40`, label: `${ classType }--${ positionalChar }primary-darkest--40` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--50`, label: `${ classType }--${ positionalChar }primary-darkest--50` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--60`, label: `${ classType }--${ positionalChar }primary-darkest--60` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--70`, label: `${ classType }--${ positionalChar }primary-darkest--70` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--80`, label: `${ classType }--${ positionalChar }primary-darkest--80` },
			{ value: `${ classType }--${ positionalChar }primary-darkest--90`, label: `${ classType }--${ positionalChar }primary-darkest--90` },
		],
		nearBlack: [
			{ value: `${ classType }--${ positionalChar }primary-near-black--10`, label: `${ classType }--${ positionalChar }primary-near-black--10` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--20`, label: `${ classType }--${ positionalChar }primary-near-black--20` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--30`, label: `${ classType }--${ positionalChar }primary-near-black--30` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--40`, label: `${ classType }--${ positionalChar }primary-near-black--40` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--50`, label: `${ classType }--${ positionalChar }primary-near-black--50` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--60`, label: `${ classType }--${ positionalChar }primary-near-black--60` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--70`, label: `${ classType }--${ positionalChar }primary-near-black--70` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--80`, label: `${ classType }--${ positionalChar }primary-near-black--80` },
			{ value: `${ classType }--${ positionalChar }primary-near-black--90`, label: `${ classType }--${ positionalChar }primary-near-black--90` },
		],
		light: [
			{ value: `${ classType }--${ positionalChar }primary-light--10`, label: `${ classType }--${ positionalChar }primary-light--10` },
			{ value: `${ classType }--${ positionalChar }primary-light--20`, label: `${ classType }--${ positionalChar }primary-light--20` },
			{ value: `${ classType }--${ positionalChar }primary-light--30`, label: `${ classType }--${ positionalChar }primary-light--30` },
			{ value: `${ classType }--${ positionalChar }primary-light--40`, label: `${ classType }--${ positionalChar }primary-light--40` },
			{ value: `${ classType }--${ positionalChar }primary-light--50`, label: `${ classType }--${ positionalChar }primary-light--50` },
			{ value: `${ classType }--${ positionalChar }primary-light--60`, label: `${ classType }--${ positionalChar }primary-light--60` },
			{ value: `${ classType }--${ positionalChar }primary-light--70`, label: `${ classType }--${ positionalChar }primary-light--70` },
			{ value: `${ classType }--${ positionalChar }primary-light--80`, label: `${ classType }--${ positionalChar }primary-light--80` },
			{ value: `${ classType }--${ positionalChar }primary-light--90`, label: `${ classType }--${ positionalChar }primary-light--90` },
		],
		lighter: [
			{ value: `${ classType }--${ positionalChar }primary-lighter--10`, label: `${ classType }--${ positionalChar }primary-lighter--10` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--20`, label: `${ classType }--${ positionalChar }primary-lighter--20` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--30`, label: `${ classType }--${ positionalChar }primary-lighter--30` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--40`, label: `${ classType }--${ positionalChar }primary-lighter--40` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--50`, label: `${ classType }--${ positionalChar }primary-lighter--50` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--60`, label: `${ classType }--${ positionalChar }primary-lighter--60` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--70`, label: `${ classType }--${ positionalChar }primary-lighter--70` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--80`, label: `${ classType }--${ positionalChar }primary-lighter--80` },
			{ value: `${ classType }--${ positionalChar }primary-lighter--90`, label: `${ classType }--${ positionalChar }primary-lighter--90` },
		],
		lightest: [
			{ value: `${ classType }--${ positionalChar }primary-lightest--10`, label: `${ classType }--${ positionalChar }primary-lightest--10` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--20`, label: `${ classType }--${ positionalChar }primary-lightest--20` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--30`, label: `${ classType }--${ positionalChar }primary-lightest--30` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--40`, label: `${ classType }--${ positionalChar }primary-lightest--40` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--50`, label: `${ classType }--${ positionalChar }primary-lightest--50` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--60`, label: `${ classType }--${ positionalChar }primary-lightest--60` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--70`, label: `${ classType }--${ positionalChar }primary-lightest--70` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--80`, label: `${ classType }--${ positionalChar }primary-lightest--80` },
			{ value: `${ classType }--${ positionalChar }primary-lightest--90`, label: `${ classType }--${ positionalChar }primary-lightest--90` },
		],
		nearWhite: [
			{ value: `${ classType }--${ positionalChar }primary-near-white--10`, label: `${ classType }--${ positionalChar }primary-near-white--10` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--20`, label: `${ classType }--${ positionalChar }primary-near-white--20` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--30`, label: `${ classType }--${ positionalChar }primary-near-white--30` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--40`, label: `${ classType }--${ positionalChar }primary-near-white--40` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--50`, label: `${ classType }--${ positionalChar }primary-near-white--50` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--60`, label: `${ classType }--${ positionalChar }primary-near-white--60` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--70`, label: `${ classType }--${ positionalChar }primary-near-white--70` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--80`, label: `${ classType }--${ positionalChar }primary-near-white--80` },
			{ value: `${ classType }--${ positionalChar }primary-near-white--90`, label: `${ classType }--${ positionalChar }primary-near-white--90` },
		],
		complement: [
			{ value: `${ classType }--${ positionalChar }primary--10`, label: `${ classType }--${ positionalChar }primary--10` },
			{ value: `${ classType }--${ positionalChar }primary--20`, label: `${ classType }--${ positionalChar }primary--20` },
			{ value: `${ classType }--${ positionalChar }primary--30`, label: `${ classType }--${ positionalChar }primary--30` },
			{ value: `${ classType }--${ positionalChar }primary--40`, label: `${ classType }--${ positionalChar }primary--40` },
			{ value: `${ classType }--${ positionalChar }primary--50`, label: `${ classType }--${ positionalChar }primary--50` },
			{ value: `${ classType }--${ positionalChar }primary--60`, label: `${ classType }--${ positionalChar }primary--60` },
			{ value: `${ classType }--${ positionalChar }primary--70`, label: `${ classType }--${ positionalChar }primary--70` },
			{ value: `${ classType }--${ positionalChar }primary--80`, label: `${ classType }--${ positionalChar }primary--80` },
			{ value: `${ classType }--${ positionalChar }primary--90`, label: `${ classType }--${ positionalChar }primary--90` },
		],
		inverted: [
			{ value: `${ classType }--${ positionalChar }primary--10`, label: `${ classType }--${ positionalChar }primary--10` },
			{ value: `${ classType }--${ positionalChar }primary--20`, label: `${ classType }--${ positionalChar }primary--20` },
			{ value: `${ classType }--${ positionalChar }primary--30`, label: `${ classType }--${ positionalChar }primary--30` },
			{ value: `${ classType }--${ positionalChar }primary--40`, label: `${ classType }--${ positionalChar }primary--40` },
			{ value: `${ classType }--${ positionalChar }primary--50`, label: `${ classType }--${ positionalChar }primary--50` },
			{ value: `${ classType }--${ positionalChar }primary--60`, label: `${ classType }--${ positionalChar }primary--60` },
			{ value: `${ classType }--${ positionalChar }primary--70`, label: `${ classType }--${ positionalChar }primary--70` },
			{ value: `${ classType }--${ positionalChar }primary--80`, label: `${ classType }--${ positionalChar }primary--80` },
			{ value: `${ classType }--${ positionalChar }primary--90`, label: `${ classType }--${ positionalChar }primary--90` },
		],
	},
	secondary: {
		main: [
			{ value: `${ classType }--${ positionalChar }secondary--10`, label: `${ classType }--${ positionalChar }secondary--10` },
			{ value: `${ classType }--${ positionalChar }secondary--20`, label: `${ classType }--${ positionalChar }secondary--20` },
			{ value: `${ classType }--${ positionalChar }secondary--30`, label: `${ classType }--${ positionalChar }secondary--30` },
			{ value: `${ classType }--${ positionalChar }secondary--40`, label: `${ classType }--${ positionalChar }secondary--40` },
			{ value: `${ classType }--${ positionalChar }secondary--50`, label: `${ classType }--${ positionalChar }secondary--50` },
			{ value: `${ classType }--${ positionalChar }secondary--60`, label: `${ classType }--${ positionalChar }secondary--60` },
			{ value: `${ classType }--${ positionalChar }secondary--70`, label: `${ classType }--${ positionalChar }secondary--70` },
			{ value: `${ classType }--${ positionalChar }secondary--80`, label: `${ classType }--${ positionalChar }secondary--80` },
			{ value: `${ classType }--${ positionalChar }secondary--90`, label: `${ classType }--${ positionalChar }secondary--90` },
		],
		dark: [
			{ value: `${ classType }--${ positionalChar }secondary-dark--10`, label: `${ classType }--${ positionalChar }secondary-dark--10` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--20`, label: `${ classType }--${ positionalChar }secondary-dark--20` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--30`, label: `${ classType }--${ positionalChar }secondary-dark--30` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--40`, label: `${ classType }--${ positionalChar }secondary-dark--40` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--50`, label: `${ classType }--${ positionalChar }secondary-dark--50` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--60`, label: `${ classType }--${ positionalChar }secondary-dark--60` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--70`, label: `${ classType }--${ positionalChar }secondary-dark--70` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--80`, label: `${ classType }--${ positionalChar }secondary-dark--80` },
			{ value: `${ classType }--${ positionalChar }secondary-dark--90`, label: `${ classType }--${ positionalChar }secondary-dark--90` },
		],
		darker: [
			{ value: `${ classType }--${ positionalChar }secondary-darker--10`, label: `${ classType }--${ positionalChar }secondary-darker--10` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--20`, label: `${ classType }--${ positionalChar }secondary-darker--20` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--30`, label: `${ classType }--${ positionalChar }secondary-darker--30` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--40`, label: `${ classType }--${ positionalChar }secondary-darker--40` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--50`, label: `${ classType }--${ positionalChar }secondary-darker--50` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--60`, label: `${ classType }--${ positionalChar }secondary-darker--60` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--70`, label: `${ classType }--${ positionalChar }secondary-darker--70` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--80`, label: `${ classType }--${ positionalChar }secondary-darker--80` },
			{ value: `${ classType }--${ positionalChar }secondary-darker--90`, label: `${ classType }--${ positionalChar }secondary-darker--90` },
		],
		darkest: [
			{ value: `${ classType }--${ positionalChar }secondary-darkest--10`, label: `${ classType }--${ positionalChar }secondary-darkest--10` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--20`, label: `${ classType }--${ positionalChar }secondary-darkest--20` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--30`, label: `${ classType }--${ positionalChar }secondary-darkest--30` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--40`, label: `${ classType }--${ positionalChar }secondary-darkest--40` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--50`, label: `${ classType }--${ positionalChar }secondary-darkest--50` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--60`, label: `${ classType }--${ positionalChar }secondary-darkest--60` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--70`, label: `${ classType }--${ positionalChar }secondary-darkest--70` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--80`, label: `${ classType }--${ positionalChar }secondary-darkest--80` },
			{ value: `${ classType }--${ positionalChar }secondary-darkest--90`, label: `${ classType }--${ positionalChar }secondary-darkest--90` },
		],
		nearBlack: [
			{ value: `${ classType }--${ positionalChar }secondary-near-black--10`, label: `${ classType }--${ positionalChar }secondary-near-black--10` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--20`, label: `${ classType }--${ positionalChar }secondary-near-black--20` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--30`, label: `${ classType }--${ positionalChar }secondary-near-black--30` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--40`, label: `${ classType }--${ positionalChar }secondary-near-black--40` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--50`, label: `${ classType }--${ positionalChar }secondary-near-black--50` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--60`, label: `${ classType }--${ positionalChar }secondary-near-black--60` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--70`, label: `${ classType }--${ positionalChar }secondary-near-black--70` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--80`, label: `${ classType }--${ positionalChar }secondary-near-black--80` },
			{ value: `${ classType }--${ positionalChar }secondary-near-black--90`, label: `${ classType }--${ positionalChar }secondary-near-black--90` },
		],
		light: [
			{ value: `${ classType }--${ positionalChar }secondary-light--10`, label: `${ classType }--${ positionalChar }secondary-light--10` },
			{ value: `${ classType }--${ positionalChar }secondary-light--20`, label: `${ classType }--${ positionalChar }secondary-light--20` },
			{ value: `${ classType }--${ positionalChar }secondary-light--30`, label: `${ classType }--${ positionalChar }secondary-light--30` },
			{ value: `${ classType }--${ positionalChar }secondary-light--40`, label: `${ classType }--${ positionalChar }secondary-light--40` },
			{ value: `${ classType }--${ positionalChar }secondary-light--50`, label: `${ classType }--${ positionalChar }secondary-light--50` },
			{ value: `${ classType }--${ positionalChar }secondary-light--60`, label: `${ classType }--${ positionalChar }secondary-light--60` },
			{ value: `${ classType }--${ positionalChar }secondary-light--70`, label: `${ classType }--${ positionalChar }secondary-light--70` },
			{ value: `${ classType }--${ positionalChar }secondary-light--80`, label: `${ classType }--${ positionalChar }secondary-light--80` },
			{ value: `${ classType }--${ positionalChar }secondary-light--90`, label: `${ classType }--${ positionalChar }secondary-light--90` },
		],
		lighter: [
			{ value: `${ classType }--${ positionalChar }secondary-lighter--10`, label: `${ classType }--${ positionalChar }secondary-lighter--10` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--20`, label: `${ classType }--${ positionalChar }secondary-lighter--20` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--30`, label: `${ classType }--${ positionalChar }secondary-lighter--30` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--40`, label: `${ classType }--${ positionalChar }secondary-lighter--40` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--50`, label: `${ classType }--${ positionalChar }secondary-lighter--50` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--60`, label: `${ classType }--${ positionalChar }secondary-lighter--60` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--70`, label: `${ classType }--${ positionalChar }secondary-lighter--70` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--80`, label: `${ classType }--${ positionalChar }secondary-lighter--80` },
			{ value: `${ classType }--${ positionalChar }secondary-lighter--90`, label: `${ classType }--${ positionalChar }secondary-lighter--90` },
		],
		lightest: [
			{ value: `${ classType }--${ positionalChar }secondary-lightest--10`, label: `${ classType }--${ positionalChar }secondary-lightest--10` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--20`, label: `${ classType }--${ positionalChar }secondary-lightest--20` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--30`, label: `${ classType }--${ positionalChar }secondary-lightest--30` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--40`, label: `${ classType }--${ positionalChar }secondary-lightest--40` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--50`, label: `${ classType }--${ positionalChar }secondary-lightest--50` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--60`, label: `${ classType }--${ positionalChar }secondary-lightest--60` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--70`, label: `${ classType }--${ positionalChar }secondary-lightest--70` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--80`, label: `${ classType }--${ positionalChar }secondary-lightest--80` },
			{ value: `${ classType }--${ positionalChar }secondary-lightest--90`, label: `${ classType }--${ positionalChar }secondary-lightest--90` },
		],
		nearWhite: [
			{ value: `${ classType }--${ positionalChar }secondary-near-white--10`, label: `${ classType }--${ positionalChar }secondary-near-white--10` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--20`, label: `${ classType }--${ positionalChar }secondary-near-white--20` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--30`, label: `${ classType }--${ positionalChar }secondary-near-white--30` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--40`, label: `${ classType }--${ positionalChar }secondary-near-white--40` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--50`, label: `${ classType }--${ positionalChar }secondary-near-white--50` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--60`, label: `${ classType }--${ positionalChar }secondary-near-white--60` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--70`, label: `${ classType }--${ positionalChar }secondary-near-white--70` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--80`, label: `${ classType }--${ positionalChar }secondary-near-white--80` },
			{ value: `${ classType }--${ positionalChar }secondary-near-white--90`, label: `${ classType }--${ positionalChar }secondary-near-white--90` },
		],
		complement: [
			{ value: `${ classType }--${ positionalChar }secondary--10`, label: `${ classType }--${ positionalChar }secondary--10` },
			{ value: `${ classType }--${ positionalChar }secondary--20`, label: `${ classType }--${ positionalChar }secondary--20` },
			{ value: `${ classType }--${ positionalChar }secondary--30`, label: `${ classType }--${ positionalChar }secondary--30` },
			{ value: `${ classType }--${ positionalChar }secondary--40`, label: `${ classType }--${ positionalChar }secondary--40` },
			{ value: `${ classType }--${ positionalChar }secondary--50`, label: `${ classType }--${ positionalChar }secondary--50` },
			{ value: `${ classType }--${ positionalChar }secondary--60`, label: `${ classType }--${ positionalChar }secondary--60` },
			{ value: `${ classType }--${ positionalChar }secondary--70`, label: `${ classType }--${ positionalChar }secondary--70` },
			{ value: `${ classType }--${ positionalChar }secondary--80`, label: `${ classType }--${ positionalChar }secondary--80` },
			{ value: `${ classType }--${ positionalChar }secondary--90`, label: `${ classType }--${ positionalChar }secondary--90` },
		],
		inverted: [
			{ value: `${ classType }--${ positionalChar }secondary--10`, label: `${ classType }--${ positionalChar }secondary--10` },
			{ value: `${ classType }--${ positionalChar }secondary--20`, label: `${ classType }--${ positionalChar }secondary--20` },
			{ value: `${ classType }--${ positionalChar }secondary--30`, label: `${ classType }--${ positionalChar }secondary--30` },
			{ value: `${ classType }--${ positionalChar }secondary--40`, label: `${ classType }--${ positionalChar }secondary--40` },
			{ value: `${ classType }--${ positionalChar }secondary--50`, label: `${ classType }--${ positionalChar }secondary--50` },
			{ value: `${ classType }--${ positionalChar }secondary--60`, label: `${ classType }--${ positionalChar }secondary--60` },
			{ value: `${ classType }--${ positionalChar }secondary--70`, label: `${ classType }--${ positionalChar }secondary--70` },
			{ value: `${ classType }--${ positionalChar }secondary--80`, label: `${ classType }--${ positionalChar }secondary--80` },
			{ value: `${ classType }--${ positionalChar }secondary--90`, label: `${ classType }--${ positionalChar }secondary--90` },
		],
	},
	base: {
		main: [
			{ value: `${ classType }--${ positionalChar }base--10`, label: `${ classType }--${ positionalChar }base--10` },
			{ value: `${ classType }--${ positionalChar }base--20`, label: `${ classType }--${ positionalChar }base--20` },
			{ value: `${ classType }--${ positionalChar }base--30`, label: `${ classType }--${ positionalChar }base--30` },
			{ value: `${ classType }--${ positionalChar }base--40`, label: `${ classType }--${ positionalChar }base--40` },
			{ value: `${ classType }--${ positionalChar }base--50`, label: `${ classType }--${ positionalChar }base--50` },
			{ value: `${ classType }--${ positionalChar }base--60`, label: `${ classType }--${ positionalChar }base--60` },
			{ value: `${ classType }--${ positionalChar }base--70`, label: `${ classType }--${ positionalChar }base--70` },
			{ value: `${ classType }--${ positionalChar }base--80`, label: `${ classType }--${ positionalChar }base--80` },
			{ value: `${ classType }--${ positionalChar }base--90`, label: `${ classType }--${ positionalChar }base--90` },
		],
		dark: [
			{ value: `${ classType }--${ positionalChar }base-dark--10`, label: `${ classType }--${ positionalChar }base-dark--10` },
			{ value: `${ classType }--${ positionalChar }base-dark--20`, label: `${ classType }--${ positionalChar }base-dark--20` },
			{ value: `${ classType }--${ positionalChar }base-dark--30`, label: `${ classType }--${ positionalChar }base-dark--30` },
			{ value: `${ classType }--${ positionalChar }base-dark--40`, label: `${ classType }--${ positionalChar }base-dark--40` },
			{ value: `${ classType }--${ positionalChar }base-dark--50`, label: `${ classType }--${ positionalChar }base-dark--50` },
			{ value: `${ classType }--${ positionalChar }base-dark--60`, label: `${ classType }--${ positionalChar }base-dark--60` },
			{ value: `${ classType }--${ positionalChar }base-dark--70`, label: `${ classType }--${ positionalChar }base-dark--70` },
			{ value: `${ classType }--${ positionalChar }base-dark--80`, label: `${ classType }--${ positionalChar }base-dark--80` },
			{ value: `${ classType }--${ positionalChar }base-dark--90`, label: `${ classType }--${ positionalChar }base-dark--90` },
		],
		darker: [
			{ value: `${ classType }--${ positionalChar }base-darker--10`, label: `${ classType }--${ positionalChar }base-darker--10` },
			{ value: `${ classType }--${ positionalChar }base-darker--20`, label: `${ classType }--${ positionalChar }base-darker--20` },
			{ value: `${ classType }--${ positionalChar }base-darker--30`, label: `${ classType }--${ positionalChar }base-darker--30` },
			{ value: `${ classType }--${ positionalChar }base-darker--40`, label: `${ classType }--${ positionalChar }base-darker--40` },
			{ value: `${ classType }--${ positionalChar }base-darker--50`, label: `${ classType }--${ positionalChar }base-darker--50` },
			{ value: `${ classType }--${ positionalChar }base-darker--60`, label: `${ classType }--${ positionalChar }base-darker--60` },
			{ value: `${ classType }--${ positionalChar }base-darker--70`, label: `${ classType }--${ positionalChar }base-darker--70` },
			{ value: `${ classType }--${ positionalChar }base-darker--80`, label: `${ classType }--${ positionalChar }base-darker--80` },
			{ value: `${ classType }--${ positionalChar }base-darker--90`, label: `${ classType }--${ positionalChar }base-darker--90` },
		],
		darkest: [
			{ value: `${ classType }--${ positionalChar }base-darkest--10`, label: `${ classType }--${ positionalChar }base-darkest--10` },
			{ value: `${ classType }--${ positionalChar }base-darkest--20`, label: `${ classType }--${ positionalChar }base-darkest--20` },
			{ value: `${ classType }--${ positionalChar }base-darkest--30`, label: `${ classType }--${ positionalChar }base-darkest--30` },
			{ value: `${ classType }--${ positionalChar }base-darkest--40`, label: `${ classType }--${ positionalChar }base-darkest--40` },
			{ value: `${ classType }--${ positionalChar }base-darkest--50`, label: `${ classType }--${ positionalChar }base-darkest--50` },
			{ value: `${ classType }--${ positionalChar }base-darkest--60`, label: `${ classType }--${ positionalChar }base-darkest--60` },
			{ value: `${ classType }--${ positionalChar }base-darkest--70`, label: `${ classType }--${ positionalChar }base-darkest--70` },
			{ value: `${ classType }--${ positionalChar }base-darkest--80`, label: `${ classType }--${ positionalChar }base-darkest--80` },
			{ value: `${ classType }--${ positionalChar }base-darkest--90`, label: `${ classType }--${ positionalChar }base-darkest--90` },
		],
		nearBlack: [
			{ value: `${ classType }--${ positionalChar }base-near-black--10`, label: `${ classType }--${ positionalChar }base-near-black--10` },
			{ value: `${ classType }--${ positionalChar }base-near-black--20`, label: `${ classType }--${ positionalChar }base-near-black--20` },
			{ value: `${ classType }--${ positionalChar }base-near-black--30`, label: `${ classType }--${ positionalChar }base-near-black--30` },
			{ value: `${ classType }--${ positionalChar }base-near-black--40`, label: `${ classType }--${ positionalChar }base-near-black--40` },
			{ value: `${ classType }--${ positionalChar }base-near-black--50`, label: `${ classType }--${ positionalChar }base-near-black--50` },
			{ value: `${ classType }--${ positionalChar }base-near-black--60`, label: `${ classType }--${ positionalChar }base-near-black--60` },
			{ value: `${ classType }--${ positionalChar }base-near-black--70`, label: `${ classType }--${ positionalChar }base-near-black--70` },
			{ value: `${ classType }--${ positionalChar }base-near-black--80`, label: `${ classType }--${ positionalChar }base-near-black--80` },
			{ value: `${ classType }--${ positionalChar }base-near-black--90`, label: `${ classType }--${ positionalChar }base-near-black--90` },
		],
		light: [
			{ value: `${ classType }--${ positionalChar }base-light--10`, label: `${ classType }--${ positionalChar }base-light--10` },
			{ value: `${ classType }--${ positionalChar }base-light--20`, label: `${ classType }--${ positionalChar }base-light--20` },
			{ value: `${ classType }--${ positionalChar }base-light--30`, label: `${ classType }--${ positionalChar }base-light--30` },
			{ value: `${ classType }--${ positionalChar }base-light--40`, label: `${ classType }--${ positionalChar }base-light--40` },
			{ value: `${ classType }--${ positionalChar }base-light--50`, label: `${ classType }--${ positionalChar }base-light--50` },
			{ value: `${ classType }--${ positionalChar }base-light--60`, label: `${ classType }--${ positionalChar }base-light--60` },
			{ value: `${ classType }--${ positionalChar }base-light--70`, label: `${ classType }--${ positionalChar }base-light--70` },
			{ value: `${ classType }--${ positionalChar }base-light--80`, label: `${ classType }--${ positionalChar }base-light--80` },
			{ value: `${ classType }--${ positionalChar }base-light--90`, label: `${ classType }--${ positionalChar }base-light--90` },
		],
		lighter: [
			{ value: `${ classType }--${ positionalChar }base-lighter--10`, label: `${ classType }--${ positionalChar }base-lighter--10` },
			{ value: `${ classType }--${ positionalChar }base-lighter--20`, label: `${ classType }--${ positionalChar }base-lighter--20` },
			{ value: `${ classType }--${ positionalChar }base-lighter--30`, label: `${ classType }--${ positionalChar }base-lighter--30` },
			{ value: `${ classType }--${ positionalChar }base-lighter--40`, label: `${ classType }--${ positionalChar }base-lighter--40` },
			{ value: `${ classType }--${ positionalChar }base-lighter--50`, label: `${ classType }--${ positionalChar }base-lighter--50` },
			{ value: `${ classType }--${ positionalChar }base-lighter--60`, label: `${ classType }--${ positionalChar }base-lighter--60` },
			{ value: `${ classType }--${ positionalChar }base-lighter--70`, label: `${ classType }--${ positionalChar }base-lighter--70` },
			{ value: `${ classType }--${ positionalChar }base-lighter--80`, label: `${ classType }--${ positionalChar }base-lighter--80` },
			{ value: `${ classType }--${ positionalChar }base-lighter--90`, label: `${ classType }--${ positionalChar }base-lighter--90` },
		],
		lightest: [
			{ value: `${ classType }--${ positionalChar }base-lightest--10`, label: `${ classType }--${ positionalChar }base-lightest--10` },
			{ value: `${ classType }--${ positionalChar }base-lightest--20`, label: `${ classType }--${ positionalChar }base-lightest--20` },
			{ value: `${ classType }--${ positionalChar }base-lightest--30`, label: `${ classType }--${ positionalChar }base-lightest--30` },
			{ value: `${ classType }--${ positionalChar }base-lightest--40`, label: `${ classType }--${ positionalChar }base-lightest--40` },
			{ value: `${ classType }--${ positionalChar }base-lightest--50`, label: `${ classType }--${ positionalChar }base-lightest--50` },
			{ value: `${ classType }--${ positionalChar }base-lightest--60`, label: `${ classType }--${ positionalChar }base-lightest--60` },
			{ value: `${ classType }--${ positionalChar }base-lightest--70`, label: `${ classType }--${ positionalChar }base-lightest--70` },
			{ value: `${ classType }--${ positionalChar }base-lightest--80`, label: `${ classType }--${ positionalChar }base-lightest--80` },
			{ value: `${ classType }--${ positionalChar }base-lightest--90`, label: `${ classType }--${ positionalChar }base-lightest--90` },
		],
		nearWhite: [
			{ value: `${ classType }--${ positionalChar }base-near-white--10`, label: `${ classType }--${ positionalChar }base-near-white--10` },
			{ value: `${ classType }--${ positionalChar }base-near-white--20`, label: `${ classType }--${ positionalChar }base-near-white--20` },
			{ value: `${ classType }--${ positionalChar }base-near-white--30`, label: `${ classType }--${ positionalChar }base-near-white--30` },
			{ value: `${ classType }--${ positionalChar }base-near-white--40`, label: `${ classType }--${ positionalChar }base-near-white--40` },
			{ value: `${ classType }--${ positionalChar }base-near-white--50`, label: `${ classType }--${ positionalChar }base-near-white--50` },
			{ value: `${ classType }--${ positionalChar }base-near-white--60`, label: `${ classType }--${ positionalChar }base-near-white--60` },
			{ value: `${ classType }--${ positionalChar }base-near-white--70`, label: `${ classType }--${ positionalChar }base-near-white--70` },
			{ value: `${ classType }--${ positionalChar }base-near-white--80`, label: `${ classType }--${ positionalChar }base-near-white--80` },
			{ value: `${ classType }--${ positionalChar }base-near-white--90`, label: `${ classType }--${ positionalChar }base-near-white--90` },
		],
		complement: [
			{ value: `${ classType }--${ positionalChar }base--10`, label: `${ classType }--${ positionalChar }base--10` },
			{ value: `${ classType }--${ positionalChar }base--20`, label: `${ classType }--${ positionalChar }base--20` },
			{ value: `${ classType }--${ positionalChar }base--30`, label: `${ classType }--${ positionalChar }base--30` },
			{ value: `${ classType }--${ positionalChar }base--40`, label: `${ classType }--${ positionalChar }base--40` },
			{ value: `${ classType }--${ positionalChar }base--50`, label: `${ classType }--${ positionalChar }base--50` },
			{ value: `${ classType }--${ positionalChar }base--60`, label: `${ classType }--${ positionalChar }base--60` },
			{ value: `${ classType }--${ positionalChar }base--70`, label: `${ classType }--${ positionalChar }base--70` },
			{ value: `${ classType }--${ positionalChar }base--80`, label: `${ classType }--${ positionalChar }base--80` },
			{ value: `${ classType }--${ positionalChar }base--90`, label: `${ classType }--${ positionalChar }base--90` },
		],
		inverted: [
			{ value: `${ classType }--${ positionalChar }base--10`, label: `${ classType }--${ positionalChar }base--10` },
			{ value: `${ classType }--${ positionalChar }base--20`, label: `${ classType }--${ positionalChar }base--20` },
			{ value: `${ classType }--${ positionalChar }base--30`, label: `${ classType }--${ positionalChar }base--30` },
			{ value: `${ classType }--${ positionalChar }base--40`, label: `${ classType }--${ positionalChar }base--40` },
			{ value: `${ classType }--${ positionalChar }base--50`, label: `${ classType }--${ positionalChar }base--50` },
			{ value: `${ classType }--${ positionalChar }base--60`, label: `${ classType }--${ positionalChar }base--60` },
			{ value: `${ classType }--${ positionalChar }base--70`, label: `${ classType }--${ positionalChar }base--70` },
			{ value: `${ classType }--${ positionalChar }base--80`, label: `${ classType }--${ positionalChar }base--80` },
			{ value: `${ classType }--${ positionalChar }base--90`, label: `${ classType }--${ positionalChar }base--90` },
		],
	},
} );

/**
 * Generate all the color class options.
 *
 * @since x.x.x
 *
 * @param {string}  classType      The class type for which to get the color classes.
 * @param {boolean} isTranslucent  Whether the translucent classes are also required or not.
 * @param {string}  positionalChar A positional character with a hyphen after it, if needed.
 * @return {Object}                An object with all the color classes needed.
 */
const generateColorClasses = ( classType, isTranslucent, positionalChar = '' ) => {
	const translucentColors = isTranslucent ? getTranslucentColorClasses( classType, positionalChar ) : {
		common: {
			transparent: [],
			white: [],
			black: [],
		},
		primary: {
			main: [],
			dark: [],
			darker: [],
			darkest: [],
			nearBlack: [],
			light: [],
			lighter: [],
			lightest: [],
			nearWhite: [],
			complement: [],
			inverted: [],
		},
		secondary: {
			main: [],
			dark: [],
			darker: [],
			darkest: [],
			nearBlack: [],
			light: [],
			lighter: [],
			lightest: [],
			nearWhite: [],
			complement: [],
			inverted: [],
		},
		base: {
			main: [],
			dark: [],
			darker: [],
			darkest: [],
			nearBlack: [],
			light: [],
			lighter: [],
			lightest: [],
			nearWhite: [],
			complement: [],
			inverted: [],
		},
	};
	return {
		common: [
			...translucentColors.common?.transparent,
			{ value: `${ classType }--${ positionalChar }white`, label: `${ classType }--${ positionalChar }white` },
			...translucentColors.common.white,
			{ value: `${ classType }--${ positionalChar }black`, label: `${ classType }--${ positionalChar }black` },
			...translucentColors.common.black,
		],
		primary: [
			{ value: `${ classType }--${ positionalChar }primary`, label: `${ classType }--${ positionalChar }primary` },
			...translucentColors.primary.main,
			{ value: `${ classType }--${ positionalChar }primary-dark`, label: `${ classType }--${ positionalChar }primary-dark` },
			...translucentColors.primary.dark,
			{ value: `${ classType }--${ positionalChar }primary-darker`, label: `${ classType }--${ positionalChar }primary-darker` },
			...translucentColors.primary.darker,
			{ value: `${ classType }--${ positionalChar }primary-darkest`, label: `${ classType }--${ positionalChar }primary-darkest` },
			...translucentColors.primary.darkest,
			{ value: `${ classType }--${ positionalChar }primary-near-black`, label: `${ classType }--${ positionalChar }primary-near-black` },
			...translucentColors.primary.nearBlack,
			{ value: `${ classType }--${ positionalChar }primary-light`, label: `${ classType }--${ positionalChar }primary-light` },
			...translucentColors.primary.light,
			{ value: `${ classType }--${ positionalChar }primary-lighter`, label: `${ classType }--${ positionalChar }primary-lighter` },
			...translucentColors.primary.lighter,
			{ value: `${ classType }--${ positionalChar }primary-lightest`, label: `${ classType }--${ positionalChar }primary-lightest` },
			...translucentColors.primary.lightest,
			{ value: `${ classType }--${ positionalChar }primary-near-white`, label: `${ classType }--${ positionalChar }primary-near-white` },
			...translucentColors.primary.nearWhite,
			{ value: `${ classType }--${ positionalChar }primary-complement`, label: `${ classType }--${ positionalChar }primary-complement` },
			...translucentColors.primary.complement,
			{ value: `${ classType }--${ positionalChar }primary-inverted`, label: `${ classType }--${ positionalChar }primary-inverted` },
			...translucentColors.primary.inverted,
		],
		secondary: [
			{ value: `${ classType }--${ positionalChar }secondary`, label: `${ classType }--${ positionalChar }secondary` },
			...translucentColors.secondary.main,
			{ value: `${ classType }--${ positionalChar }secondary-dark`, label: `${ classType }--${ positionalChar }secondary-dark` },
			...translucentColors.secondary.dark,
			{ value: `${ classType }--${ positionalChar }secondary-darker`, label: `${ classType }--${ positionalChar }secondary-darker` },
			...translucentColors.secondary.darker,
			{ value: `${ classType }--${ positionalChar }secondary-darkest`, label: `${ classType }--${ positionalChar }secondary-darkest` },
			...translucentColors.secondary.darkest,
			{ value: `${ classType }--${ positionalChar }secondary-near-black`, label: `${ classType }--${ positionalChar }secondary-near-black` },
			...translucentColors.secondary.nearBlack,
			{ value: `${ classType }--${ positionalChar }secondary-light`, label: `${ classType }--${ positionalChar }secondary-light` },
			...translucentColors.secondary.light,
			{ value: `${ classType }--${ positionalChar }secondary-lighter`, label: `${ classType }--${ positionalChar }secondary-lighter` },
			...translucentColors.secondary.lighter,
			{ value: `${ classType }--${ positionalChar }secondary-lightest`, label: `${ classType }--${ positionalChar }secondary-lightest` },
			...translucentColors.secondary.lightest,
			{ value: `${ classType }--${ positionalChar }secondary-near-white`, label: `${ classType }--${ positionalChar }secondary-near-white` },
			...translucentColors.secondary.nearWhite,
			{ value: `${ classType }--${ positionalChar }secondary-complement`, label: `${ classType }--${ positionalChar }secondary-complement` },
			...translucentColors.secondary.complement,
			{ value: `${ classType }--${ positionalChar }secondary-inverted`, label: `${ classType }--${ positionalChar }secondary-inverted` },
			...translucentColors.secondary.inverted,
		],
		base: [
			{ value: `${ classType }--${ positionalChar }base`, label: `${ classType }--${ positionalChar }base` },
			...translucentColors.base.main,
			{ value: `${ classType }--${ positionalChar }base-dark`, label: `${ classType }--${ positionalChar }base-dark` },
			...translucentColors.base.dark,
			{ value: `${ classType }--${ positionalChar }base-darker`, label: `${ classType }--${ positionalChar }base-darker` },
			...translucentColors.base.darker,
			{ value: `${ classType }--${ positionalChar }base-darkest`, label: `${ classType }--${ positionalChar }base-darkest` },
			...translucentColors.base.darkest,
			{ value: `${ classType }--${ positionalChar }base-near-black`, label: `${ classType }--${ positionalChar }base-near-black` },
			...translucentColors.base.nearBlack,
			{ value: `${ classType }--${ positionalChar }base-light`, label: `${ classType }--${ positionalChar }base-light` },
			...translucentColors.base.light,
			{ value: `${ classType }--${ positionalChar }base-lighter`, label: `${ classType }--${ positionalChar }base-lighter` },
			...translucentColors.base.lighter,
			{ value: `${ classType }--${ positionalChar }base-lightest`, label: `${ classType }--${ positionalChar }base-lightest` },
			...translucentColors.base.lightest,
			{ value: `${ classType }--${ positionalChar }base-near-white`, label: `${ classType }--${ positionalChar }base-near-white` },
			...translucentColors.base.nearWhite,
			{ value: `${ classType }--${ positionalChar }base-complement`, label: `${ classType }--${ positionalChar }base-complement` },
			...translucentColors.base.complement,
			{ value: `${ classType }--${ positionalChar }base-inverted`, label: `${ classType }--${ positionalChar }base-inverted` },
			...translucentColors.base.inverted,
		],
		// Remaining Style Guide palette colours — one base class each (no ramp),
		// resolving through the `--color--{slug}` alias to each colour's preset var.
		semantic: [
			{ value: `${ classType }--${ positionalChar }accent`, label: `${ classType }--${ positionalChar }accent` },
			{ value: `${ classType }--${ positionalChar }heading`, label: `${ classType }--${ positionalChar }heading` },
			{ value: `${ classType }--${ positionalChar }body`, label: `${ classType }--${ positionalChar }body` },
			{ value: `${ classType }--${ positionalChar }background`, label: `${ classType }--${ positionalChar }background` },
			{ value: `${ classType }--${ positionalChar }surface`, label: `${ classType }--${ positionalChar }surface` },
			{ value: `${ classType }--${ positionalChar }outline`, label: `${ classType }--${ positionalChar }outline` },
			{ value: `${ classType }--${ positionalChar }foreground`, label: `${ classType }--${ positionalChar }foreground` },
			{ value: `${ classType }--${ positionalChar }success`, label: `${ classType }--${ positionalChar }success` },
			{ value: `${ classType }--${ positionalChar }error`, label: `${ classType }--${ positionalChar }error` },
			{ value: `${ classType }--${ positionalChar }info`, label: `${ classType }--${ positionalChar }info` },
			{ value: `${ classType }--${ positionalChar }warning`, label: `${ classType }--${ positionalChar }warning` },
		],
	}
};

/**
 * An object of arrays with the font size CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} fontSizeOptions The categorized font size options.
 */
const fontSizeOptions = {
	heading: [
		{ value: 'heading--1', label: 'heading--1' },
		{ value: 'heading--2', label: 'heading--2' },
		{ value: 'heading--3', label: 'heading--3' },
		{ value: 'heading--4', label: 'heading--4' },
		{ value: 'heading--5', label: 'heading--5' },
		{ value: 'heading--6', label: 'heading--6' },
	],
	text: [
		{ value: 'text--xs', label: 'text--xs' },
		{ value: 'text--sm', label: 'text--sm' },
		{ value: 'text--md', label: 'text--md' },
		{ value: 'text--lg', label: 'text--lg' },
		{ value: 'text--xl', label: 'text--xl' },
		{ value: 'text--xxl', label: 'text--xxl' },
	],
};

/**
 * An array of the line height CSS classes.
 *
 * @since x.x.x
 *
 * @member {Array} lineHeightOptions The line height options.
 */
const lineHeightOptions = [
	{ value: 'text--h-xs', label: 'text--h-xs' },
	{ value: 'text--h-sm', label: 'text--h-sm' },
	{ value: 'text--h-md', label: 'text--h-md' },
	{ value: 'text--h-lg', label: 'text--h-lg' },
];

/**
 * Generate display classes based on common display properties.
 *
 * @since x.x.x
 *
 * @return {Array} An array of display class options.
 */
const displayOptions = [
	{ value: 'display--block', label: 'display--block' },
	{ value: 'display--contents', label: 'display--contents' },
	{ value: 'display--flex', label: 'display--flex' },
	{ value: 'display--grid', label: 'display--grid' },
	{ value: 'display--inline', label: 'display--inline' },
	{ value: 'display--inline-block', label: 'display--inline-block' },
	{ value: 'display--inline-flex', label: 'display--inline-flex' },
	{ value: 'display--inline-grid', label: 'display--inline-grid' },
	{ value: 'display--none', label: 'display--none' },
];

/**
 * An object of arrays with the alignment CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} alignmentOptions The categorized alignment options.
 */
const alignmentOptions = {
	alignContent: [
		{ value: 'align-content--start', label: 'align-content--start' },
		{ value: 'align-content--end', label: 'align-content--end' },
		{ value: 'align-content--center', label: 'align-content--center' },
		{ value: 'align-content--between', label: 'align-content--between' },
		{ value: 'align-content--around', label: 'align-content--around' },
		{ value: 'align-content--evenly', label: 'align-content--evenly' },
		{ value: 'align-content--stretch', label: 'align-content--stretch' },
	],
	alignItems: [
		{ value: 'align-items--stretch', label: 'align-items--stretch' },
		{ value: 'align-items--start', label: 'align-items--start' },
		{ value: 'align-items--end', label: 'align-items--end' },
		{ value: 'align-items--center', label: 'align-items--center' },
		{ value: 'align-items--baseline', label: 'align-items--baseline' },
	],
	alignSelf: [
		{ value: 'align-self--start', label: 'align-self--start' },
		{ value: 'align-self--end', label: 'align-self--end' },
		{ value: 'align-self--center', label: 'align-self--center' },
		{ value: 'align-self--stretch', label: 'align-self--stretch' },
		{ value: 'align-self--baseline', label: 'align-self--baseline' },
	],
	justifyContent: [
		{ value: 'justify-content--start', label: 'justify-content--start' },
		{ value: 'justify-content--end', label: 'justify-content--end' },
		{ value: 'justify-content--center', label: 'justify-content--center' },
		{ value: 'justify-content--between', label: 'justify-content--between' },
		{ value: 'justify-content--around', label: 'justify-content--around' },
		{ value: 'justify-content--evenly', label: 'justify-content--evenly' },
	],
	justifyItems: [
		{ value: 'justify-items--start', label: 'justify-items--start' },
		{ value: 'justify-items--end', label: 'justify-items--end' },
		{ value: 'justify-items--center', label: 'justify-items--center' },
		{ value: 'justify-items--stretch', label: 'justify-items--stretch' },
	],
	justifySelf: [
		{ value: 'justify-self--start', label: 'justify-self--start' },
		{ value: 'justify-self--end', label: 'justify-self--end' },
		{ value: 'justify-self--center', label: 'justify-self--center' },
		{ value: 'justify-self--stretch', label: 'justify-self--stretch' },
	]
};

/**
 * An array of z-index CSS classes.
 *
 * @since x.x.x
 *
 * @member {Array} zIndexOptions The z-index options.
 */
const zIndexOptions = [
	{ value: 'z--0', label: 'z--0' },
	{ value: 'z--10', label: 'z--10' },
	{ value: 'z--20', label: 'z--20' },
	{ value: 'z--30', label: 'z--30' },
	{ value: 'z--40', label: 'z--40' },
	{ value: 'z--50', label: 'z--50' },
	{ value: 'z--60', label: 'z--60' },
	{ value: 'z--70', label: 'z--70' },
	{ value: 'z--80', label: 'z--80' },
	{ value: 'z--90', label: 'z--90' },
	{ value: 'z--back', label: 'z--back' },
	{ value: 'z--front', label: 'z--front' },
];

/**
 * An object of arrays with the size related CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} sizeOptions The categorized font size options.
 */
const sizeOptions = {
	width: [
		{ value: 'width--content', label: 'width--content' },
		{ value: 'width--20', label: 'width--20' },
		{ value: 'width--25', label: 'width--25' },
		{ value: 'width--30', label: 'width--30' },
		{ value: 'width--40', label: 'width--40' },
		{ value: 'width--50', label: 'width--50' },
		{ value: 'width--60', label: 'width--60' },
		{ value: 'width--70', label: 'width--70' },
		{ value: 'width--75', label: 'width--75' },
		{ value: 'width--80', label: 'width--80' },
		{ value: 'width--90', label: 'width--90' },
		{ value: 'width--100', label: 'width--100' },
		{ value: 'width--full', label: 'width--full' },
		{ value: 'max-width--20', label: 'max-width--20' },
		{ value: 'max-width--25', label: 'max-width--25' },
		{ value: 'max-width--30', label: 'max-width--30' },
		{ value: 'max-width--40', label: 'max-width--40' },
		{ value: 'max-width--50', label: 'max-width--50' },
		{ value: 'max-width--60', label: 'max-width--60' },
		{ value: 'max-width--70', label: 'max-width--70' },
		{ value: 'max-width--75', label: 'max-width--75' },
		{ value: 'max-width--80', label: 'max-width--80' },
		{ value: 'max-width--90', label: 'max-width--90' },
		{ value: 'max-width--100', label: 'max-width--100' },
		{ value: 'max-width--full', label: 'max-width--full' },
	],
	height: [
		{ value: 'height--20', label: 'height--20' },
		{ value: 'height--25', label: 'height--25' },
		{ value: 'height--30', label: 'height--30' },
		{ value: 'height--40', label: 'height--40' },
		{ value: 'height--50', label: 'height--50' },
		{ value: 'height--60', label: 'height--60' },
		{ value: 'height--70', label: 'height--70' },
		{ value: 'height--75', label: 'height--75' },
		{ value: 'height--80', label: 'height--80' },
		{ value: 'height--90', label: 'height--90' },
		{ value: 'height--100', label: 'height--100' },
		{ value: 'height--full', label: 'height--full' },
		{ value: 'max-height--20', label: 'max-height--20' },
		{ value: 'max-height--25', label: 'max-height--25' },
		{ value: 'max-height--30', label: 'max-height--30' },
		{ value: 'max-height--40', label: 'max-height--40' },
		{ value: 'max-height--50', label: 'max-height--50' },
		{ value: 'max-height--60', label: 'max-height--60' },
		{ value: 'max-height--70', label: 'max-height--70' },
		{ value: 'max-height--75', label: 'max-height--75' },
		{ value: 'max-height--80', label: 'max-height--80' },
		{ value: 'max-height--90', label: 'max-height--90' },
		{ value: 'max-height--100', label: 'max-height--100' },
		{ value: 'max-height--full', label: 'max-height--full' },
	],
	aspectRatio: [
		{ value: 'aspect-ratio--square', label: 'aspect-ratio--square' },
		{ value: 'aspect-ratio--1-2', label: 'aspect-ratio--1-2' },
		{ value: 'aspect-ratio--2-1', label: 'aspect-ratio--2-1' },
		{ value: 'aspect-ratio--2-3', label: 'aspect-ratio--2-3' },
		{ value: 'aspect-ratio--3-2', label: 'aspect-ratio--3-2' },
		{ value: 'aspect-ratio--3-4', label: 'aspect-ratio--3-4' },
		{ value: 'aspect-ratio--4-3', label: 'aspect-ratio--4-3' },
		{ value: 'aspect-ratio--16-9', label: 'aspect-ratio--16-9' },
		{ value: 'aspect-ratio--9-16', label: 'aspect-ratio--9-16' },
	],
};

/**
 * An object of arrays with the gap CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} gapOptions The categorized padding options.
 */
const gapOptions = {
	all: [
		{ value: 'gap--none', label: 'gap--none' },
		{ value: 'gap--pixel', label: 'gap--pixel' },
		{ value: 'gap--xs', label: 'gap--xs' },
		{ value: 'gap--sm', label: 'gap--sm' },
		{ value: 'gap--md', label: 'gap--md' },
		{ value: 'gap--lg', label: 'gap--lg' },
		{ value: 'gap--xl', label: 'gap--xl' },
		{ value: 'gap--xxl', label: 'gap--xxl' },
	],
	row: [
		{ value: 'row-gap--none', label: 'row-gap--none' },
		{ value: 'row-gap--pixel', label: 'row-gap--pixel' },
		{ value: 'row-gap--xs', label: 'row-gap--xs' },
		{ value: 'row-gap--sm', label: 'row-gap--sm' },
		{ value: 'row-gap--md', label: 'row-gap--md' },
		{ value: 'row-gap--lg', label: 'row-gap--lg' },
		{ value: 'row-gap--xl', label: 'row-gap--xl' },
		{ value: 'row-gap--xxl', label: 'row-gap--xxl' },
	],
	column: [
		{ value: 'col-gap--none', label: 'col-gap--none' },
		{ value: 'col-gap--pixel', label: 'col-gap--pixel' },
		{ value: 'col-gap--xs', label: 'col-gap--xs' },
		{ value: 'col-gap--sm', label: 'col-gap--sm' },
		{ value: 'col-gap--md', label: 'col-gap--md' },
		{ value: 'col-gap--lg', label: 'col-gap--lg' },
		{ value: 'col-gap--xl', label: 'col-gap--xl' },
		{ value: 'col-gap--xxl', label: 'col-gap--xxl' },
	],
};

/**
 * An object of arrays with the padding CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} paddingOptions The categorized padding options.
 */
const paddingOptions = {
	all: [
		{ value: 'padding--none', label: 'padding--none' },
		{ value: 'padding--xs', label: 'padding--xs' },
		{ value: 'padding--sm', label: 'padding--sm' },
		{ value: 'padding--md', label: 'padding--md' },
		{ value: 'padding--lg', label: 'padding--lg' },
		{ value: 'padding--xl', label: 'padding--xl' },
		{ value: 'padding--xxl', label: 'padding--xxl' },
	],
	vertical: [
		{ value: 'padding--y-none', label: 'padding--y-none' },
		{ value: 'padding--y-xs', label: 'padding--y-xs' },
		{ value: 'padding--y-sm', label: 'padding--y-sm' },
		{ value: 'padding--y-md', label: 'padding--y-md' },
		{ value: 'padding--y-lg', label: 'padding--y-lg' },
		{ value: 'padding--y-xl', label: 'padding--y-xl' },
		{ value: 'padding--y-xxl', label: 'padding--y-xxl' },
	],
	horizontal: [
		{ value: 'padding--x-none', label: 'padding--x-none' },
		{ value: 'padding--x-xs', label: 'padding--x-xs' },
		{ value: 'padding--x-sm', label: 'padding--x-sm' },
		{ value: 'padding--x-md', label: 'padding--x-md' },
		{ value: 'padding--x-lg', label: 'padding--x-lg' },
		{ value: 'padding--x-xl', label: 'padding--x-xl' },
		{ value: 'padding--x-xxl', label: 'padding--x-xxl' },
	],
	top: [
		{ value: 'padding--t-none', label: 'padding--t-none' },
		{ value: 'padding--t-xs', label: 'padding--t-xs' },
		{ value: 'padding--t-sm', label: 'padding--t-sm' },
		{ value: 'padding--t-md', label: 'padding--t-md' },
		{ value: 'padding--t-lg', label: 'padding--t-lg' },
		{ value: 'padding--t-xl', label: 'padding--t-xl' },
		{ value: 'padding--t-xxl', label: 'padding--t-xxl' },
	],
	right: [
		{ value: 'padding--r-none', label: 'padding--r-none' },
		{ value: 'padding--r-xs', label: 'padding--r-xs' },
		{ value: 'padding--r-sm', label: 'padding--r-sm' },
		{ value: 'padding--r-md', label: 'padding--r-md' },
		{ value: 'padding--r-lg', label: 'padding--r-lg' },
		{ value: 'padding--r-xl', label: 'padding--r-xl' },
		{ value: 'padding--r-xxl', label: 'padding--r-xxl' },
	],
	bottom: [
		{ value: 'padding--b-none', label: 'padding--b-none' },
		{ value: 'padding--b-xs', label: 'padding--b-xs' },
		{ value: 'padding--b-sm', label: 'padding--b-sm' },
		{ value: 'padding--b-md', label: 'padding--b-md' },
		{ value: 'padding--b-lg', label: 'padding--b-lg' },
		{ value: 'padding--b-xl', label: 'padding--b-xl' },
		{ value: 'padding--b-xxl', label: 'padding--b-xxl' },
	],
	left: [
		{ value: 'padding--l-none', label: 'padding--l-none' },
		{ value: 'padding--l-xs', label: 'padding--l-xs' },
		{ value: 'padding--l-sm', label: 'padding--l-sm' },
		{ value: 'padding--l-md', label: 'padding--l-md' },
		{ value: 'padding--l-lg', label: 'padding--l-lg' },
		{ value: 'padding--l-xl', label: 'padding--l-xl' },
		{ value: 'padding--l-xxl', label: 'padding--l-xxl' },
	],
};

/**
 * An object of arrays with the margin CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} marginOptions The categorized margin options.
 */
const marginOptions = {
	all: [
		{ value: 'margin--none', label: 'margin--none' },
		{ value: 'margin--xs', label: 'margin--xs' },
		{ value: 'margin--sm', label: 'margin--sm' },
		{ value: 'margin--md', label: 'margin--md' },
		{ value: 'margin--lg', label: 'margin--lg' },
		{ value: 'margin--xl', label: 'margin--xl' },
		{ value: 'margin--xxl', label: 'margin--xxl' },
	],
	vertical: [
		{ value: 'margin--y-none', label: 'margin--y-none' },
		{ value: 'margin--y-xs', label: 'margin--y-xs' },
		{ value: 'margin--y-sm', label: 'margin--y-sm' },
		{ value: 'margin--y-md', label: 'margin--y-md' },
		{ value: 'margin--y-lg', label: 'margin--y-lg' },
		{ value: 'margin--y-xl', label: 'margin--y-xl' },
		{ value: 'margin--y-xxl', label: 'margin--y-xxl' },
	],
	horizontal: [
		{ value: 'margin--x-none', label: 'margin--x-none' },
		{ value: 'margin--x-xs', label: 'margin--x-xs' },
		{ value: 'margin--x-sm', label: 'margin--x-sm' },
		{ value: 'margin--x-md', label: 'margin--x-md' },
		{ value: 'margin--x-lg', label: 'margin--x-lg' },
		{ value: 'margin--x-xl', label: 'margin--x-xl' },
		{ value: 'margin--x-xxl', label: 'margin--x-xxl' },
	],
	top: [
		{ value: 'margin--t-none', label: 'margin--t-none' },
		{ value: 'margin--t-xs', label: 'margin--t-xs' },
		{ value: 'margin--t-sm', label: 'margin--t-sm' },
		{ value: 'margin--t-md', label: 'margin--t-md' },
		{ value: 'margin--t-lg', label: 'margin--t-lg' },
		{ value: 'margin--t-xl', label: 'margin--t-xl' },
		{ value: 'margin--t-xxl', label: 'margin--t-xxl' },
	],
	right: [
		{ value: 'margin--r-none', label: 'margin--r-none' },
		{ value: 'margin--r-xs', label: 'margin--r-xs' },
		{ value: 'margin--r-sm', label: 'margin--r-sm' },
		{ value: 'margin--r-md', label: 'margin--r-md' },
		{ value: 'margin--r-lg', label: 'margin--r-lg' },
		{ value: 'margin--r-xl', label: 'margin--r-xl' },
		{ value: 'margin--r-xxl', label: 'margin--r-xxl' },
	],
	bottom: [
		{ value: 'margin--b-none', label: 'margin--b-none' },
		{ value: 'margin--b-xs', label: 'margin--b-xs' },
		{ value: 'margin--b-sm', label: 'margin--b-sm' },
		{ value: 'margin--b-md', label: 'margin--b-md' },
		{ value: 'margin--b-lg', label: 'margin--b-lg' },
		{ value: 'margin--b-xl', label: 'margin--b-xl' },
		{ value: 'margin--b-xxl', label: 'margin--b-xxl' },
	],
	left: [
		{ value: 'margin--l-none', label: 'margin--l-none' },
		{ value: 'margin--l-xs', label: 'margin--l-xs' },
		{ value: 'margin--l-sm', label: 'margin--l-sm' },
		{ value: 'margin--l-md', label: 'margin--l-md' },
		{ value: 'margin--l-lg', label: 'margin--l-lg' },
		{ value: 'margin--l-xl', label: 'margin--l-xl' },
		{ value: 'margin--l-xxl', label: 'margin--l-xxl' },
	],
};

/**
 * An object of arrays with the text and background color CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} colorOptions The categorized text and background color options.
 */
const colorOptions = {
	text: generateColorClasses( 'color' ),
	background: generateColorClasses( 'background', true ),
};

/**
 * An object of arrays with the overlay related CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} overlayOptions The categorized overlay options.
 */
const overlayOptions = {
	color: generateColorClasses( 'overlay', true ),
	blur: [
		{ value: 'overlay--blur-xs', label: 'overlay--blur-xs' },
		{ value: 'overlay--blur-sm', label: 'overlay--blur-sm' },
		{ value: 'overlay--blur-md', label: 'overlay--blur-md' },
		{ value: 'overlay--blur-lg', label: 'overlay--blur-lg' },
		{ value: 'overlay--blur-xl', label: 'overlay--blur-xl' },
		{ value: 'overlay--blur-xxl', label: 'overlay--blur-xxl' },
	],
};

/**
 * Generate corner radius classes for different positions.
 *
 * @since x.x.x
 *
 * @param {string} position The position for which to generate corner classes (e.g., 'tl', 'tr', etc.)
 * @return {Array} An array of corner class options for the specified position
 */
const generateCornerClass = ( position = '' ) => {
	const positionPrefix = position ? `--${ position }-` : '--';
	return [
		{ value: `corner${ positionPrefix }none`, label: `corner${ positionPrefix }none` },
		{ value: `corner${ positionPrefix }xs`, label: `corner${ positionPrefix }xs` },
		{ value: `corner${ positionPrefix }sm`, label: `corner${ positionPrefix }sm` },
		{ value: `corner${ positionPrefix }md`, label: `corner${ positionPrefix }md` },
		{ value: `corner${ positionPrefix }lg`, label: `corner${ positionPrefix }lg` },
		{ value: `corner${ positionPrefix }xl`, label: `corner${ positionPrefix }xl` },
		{ value: `corner${ positionPrefix }xxl`, label: `corner${ positionPrefix }xxl` },
		{ value: `corner${ positionPrefix }round`, label: `corner${ positionPrefix }round` },
	];
};

/**
 * Generate border style classes for different positions.
 *
 * @since x.x.x
 *
 * @param {string} position The position for which to generate border style classes (e.g., 't', 'r', etc.)
 * @return {Array} An array of border style class options for the specified position.
 */
const generateBorderStyleClass = ( position = '' ) => {
	const positionPrefix = position ? `--${ position }-` : '--';
	return [
		{ value: `border${ positionPrefix }none`, label: `border${ positionPrefix }none` },
		{ value: `border${ positionPrefix }solid`, label: `border${ positionPrefix }solid` },
		{ value: `border${ positionPrefix }dashed`, label: `border${ positionPrefix }dashed` },
		{ value: `border${ positionPrefix }dotted`, label: `border${ positionPrefix }dotted` },
		{ value: `border${ positionPrefix }double`, label: `border${ positionPrefix }double` },
		{ value: `border${ positionPrefix }groove`, label: `border${ positionPrefix }groove` },
		{ value: `border${ positionPrefix }ridge`, label: `border${ positionPrefix }ridge` },
		{ value: `border${ positionPrefix }inset`, label: `border${ positionPrefix }inset` },
		{ value: `border${ positionPrefix }outset`, label: `border${ positionPrefix }outset` },
	];
};

/**
 * An object of arrays with the border related CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} borderOptions The categorized border options.
 */
const borderOptions = {
	width: [
		{ value: 'border--none', label: 'border--none' },
		{ value: 'border--pixel', label: 'border--pixel' },
		{ value: 'border--xs', label: 'border--xs' },
		{ value: 'border--sm', label: 'border--sm' },
		{ value: 'border--md', label: 'border--md' },
		{ value: 'border--lg', label: 'border--lg' },
		{ value: 'border--xl', label: 'border--xl' },
		{ value: 'border--xxl', label: 'border--xxl' },
	],
	style: {
		all: generateBorderStyleClass(),
		top: generateBorderStyleClass( 't' ),
		right: generateBorderStyleClass( 'r' ),
		bottom: generateBorderStyleClass( 'b' ),
		left: generateBorderStyleClass( 'l' ),
	},
	radius: {
		all: generateCornerClass(),
		topLeft: generateCornerClass( 'tl' ),
		top: generateCornerClass( 't' ),
		topRight: generateCornerClass( 'tr' ),
		right: generateCornerClass( 'r' ),
		bottomRight: generateCornerClass( 'br' ),
		bottom: generateCornerClass( 'b' ),
		bottomLeft: generateCornerClass( 'bl' ),
		left: generateCornerClass( 'l' ),
	},
	color: {
		all: generateColorClasses( 'border', true ),
		top: generateColorClasses( 'border', true, 't-' ),
		right: generateColorClasses( 'border', true, 'r-' ),
		bottom: generateColorClasses( 'border', true, 'b-' ),
		left: generateColorClasses( 'border', true, 'l-' ),
		horizontal: generateColorClasses( 'border', true, 'y-' ),
		vertical: generateColorClasses( 'border', true, 'x-' ),
	},
};

/**
 * An object of arrays with the flex related CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} flexOptions The categorized flex options.
 */
const flexOptions = {
	direction: [
		{ value: 'flex--row', label: 'flex--row' },
		{ value: 'flex--row-reverse', label: 'flex--row-reverse' },
		{ value: 'flex--column', label: 'flex--column' },
		{ value: 'flex--column-reverse', label: 'flex--column-reverse' },
	],
	wrap: [
		{ value: 'flex--nowrap', label: 'flex--nowrap' },
		{ value: 'flex--wrap', label: 'flex--wrap' },
		{ value: 'flex--wrap-reverse', label: 'flex--wrap-reverse' },
	],
};

/**
 * An object of arrays with the object-fit and object-position CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} objectOptions The categorized object-fit and object-position options.
 */
const objectOptions = {
	fit: [
		{ value: 'object-fit--contain', label: 'object-fit--contain' },
		{ value: 'object-fit--cover', label: 'object-fit--cover' },
		{ value: 'object-fit--fill', label: 'object-fit--fill' },
		{ value: 'object-fit--none', label: 'object-fit--none' },
		{ value: 'object-fit--scaled', label: 'object-fit--scaled' },
	],
	position: [
		{ value: 'object-pos--tl', label: 'object-pos--tl' },
		{ value: 'object-pos--t', label: 'object-pos--t' },
		{ value: 'object-pos--tr', label: 'object-pos--tr' },
		{ value: 'object-pos--l', label: 'object-pos--l' },
		{ value: 'object-pos--center', label: 'object-pos--center' },
		{ value: 'object-pos--r', label: 'object-pos--r' },
		{ value: 'object-pos--bl', label: 'object-pos--bl' },
		{ value: 'object-pos--b', label: 'object-pos--b' },
		{ value: 'object-pos--br', label: 'object-pos--br' },
	],
};

/**
 * An object of arrays with the grid related CSS classes.
 *
 * @since x.x.x
 *
 * @member {Object} gridOptions The categorized grid options.
 */
const gridOptions = {
	structure: [
		{ value: 'grid--1', label: 'grid--1' },
		{ value: 'grid--1-2', label: 'grid--1-2' },
		{ value: 'grid--1-3', label: 'grid--1-3' },
		{ value: 'grid--2', label: 'grid--2' },
		{ value: 'grid--2-1', label: 'grid--2-1' },
		{ value: 'grid--2-3', label: 'grid--2-3' },
		{ value: 'grid--3', label: 'grid--3' },
		{ value: 'grid--3-1', label: 'grid--3-1' },
		{ value: 'grid--3-2', label: 'grid--3-2' },
		{ value: 'grid--4', label: 'grid--4' },
		{ value: 'grid--5', label: 'grid--5' },
		{ value: 'grid--6', label: 'grid--6' },
		{ value: 'grid--7', label: 'grid--7' },
		{ value: 'grid--8', label: 'grid--8' },
	],
	columns: [
		{ value: 'col-start--1', label: 'col-start--1' },
		{ value: 'col-start--2', label: 'col-start--2' },
		{ value: 'col-start--3', label: 'col-start--3' },
		{ value: 'col-start--4', label: 'col-start--4' },
		{ value: 'col-start--5', label: 'col-start--5' },
		{ value: 'col-start--6', label: 'col-start--6' },
		{ value: 'col-start--7', label: 'col-start--7' },
		{ value: 'col-start--8', label: 'col-start--8' },
		{ value: 'col-end--1', label: 'col-end--1' },
		{ value: 'col-end--2', label: 'col-end--2' },
		{ value: 'col-end--3', label: 'col-end--3' },
		{ value: 'col-end--4', label: 'col-end--4' },
		{ value: 'col-end--5', label: 'col-end--5' },
		{ value: 'col-end--6', label: 'col-end--6' },
		{ value: 'col-end--7', label: 'col-end--7' },
		{ value: 'col-end--8', label: 'col-end--8' },
		{ value: 'col-end--last', label: 'col-end--last' },
		{ value: 'col-span--2', label: 'col-span--2' },
		{ value: 'col-span--3', label: 'col-span--3' },
		{ value: 'col-span--4', label: 'col-span--4' },
		{ value: 'col-span--5', label: 'col-span--5' },
		{ value: 'col-span--6', label: 'col-span--6' },
		{ value: 'col-span--7', label: 'col-span--7' },
		{ value: 'col-span--8', label: 'col-span--8' },
	],
	rows: [
		{ value: 'row-start--1', label: 'row-start--1' },
		{ value: 'row-start--2', label: 'row-start--2' },
		{ value: 'row-start--3', label: 'row-start--3' },
		{ value: 'row-start--4', label: 'row-start--4' },
		{ value: 'row-start--5', label: 'row-start--5' },
		{ value: 'row-start--6', label: 'row-start--6' },
		{ value: 'row-start--7', label: 'row-start--7' },
		{ value: 'row-start--8', label: 'row-start--8' },
		{ value: 'row-end--1', label: 'row-end--1' },
		{ value: 'row-end--2', label: 'row-end--2' },
		{ value: 'row-end--3', label: 'row-end--3' },
		{ value: 'row-end--4', label: 'row-end--4' },
		{ value: 'row-end--5', label: 'row-end--5' },
		{ value: 'row-end--6', label: 'row-end--6' },
		{ value: 'row-end--7', label: 'row-end--7' },
		{ value: 'row-end--8', label: 'row-end--8' },
		{ value: 'row-end--last', label: 'row-end--last' },
		{ value: 'row-span--2', label: 'row-span--2' },
		{ value: 'row-span--3', label: 'row-span--3' },
		{ value: 'row-span--4', label: 'row-span--4' },
		{ value: 'row-span--5', label: 'row-span--5' },
		{ value: 'row-span--6', label: 'row-span--6' },
		{ value: 'row-span--7', label: 'row-span--7' },
		{ value: 'row-span--8', label: 'row-span--8' },
	],
};

/**
 * An array of class options objects, consisting of a group label and an array of the options.
 *
 * @since x.x.x
 *
 * @member {Array} classOptions The array of class options for the React Select dropdown.
 */
const classOptions = [
	{
		label: 'Font Size',
		options: [
			...fontSizeOptions.heading,
			...fontSizeOptions.text,
		],
	},
	{
		label: 'Object Fit',
		options: [
			...objectOptions.fit,
		],
	},
	{
		label: 'Object Position',
		options: [
			...objectOptions.position,
		],
	},
	{
		label: 'Line Height',
		options: [
			...lineHeightOptions,
		],
	},
	{
		label: 'Display',
		options: [
			...displayOptions,
		],
	},
	{
		label: 'Flex: Direction',
		options: [
			...flexOptions.direction,
		],
	},
	{
		label: 'Flex: Wrap',
		options: [
			...flexOptions.wrap,
		],
	},
	{
		label: 'Grid: Structure',
		options: [
			...gridOptions.structure,
		],
	},
	{
		label: 'Grid: Columns',
		options: [
			...gridOptions.columns,
		],
	},
	{
		label: 'Grid: Rows',
		options: [
			...gridOptions.rows,
		],
	},
	{
		label: 'Alignment: Content',
		options: [
			...alignmentOptions.alignContent,
		],
	},
	{
		label: 'Alignment: Items',
		options: [
			...alignmentOptions.alignItems,
		],
	},
	{
		label: 'Justification: Content',
		options: [
			...alignmentOptions.justifyContent,
		],
	},
	{
		label: 'Justification: Items',
		options: [
			...alignmentOptions.justifyItems,
		],
	},
	{
		label: 'Z-Index',
		options: [
			...zIndexOptions,
		],
	},
	{
		label: 'Gap',
		options: [
			...gapOptions.all,
			...gapOptions.row,
			...gapOptions.column,
		],
	},
	{
		label: 'Padding: All',
		options: [
			...paddingOptions.all,
		],
	},
	{
		label: 'Padding: Vertical',
		options: [
			...paddingOptions.vertical,
		],
	},
	{
		label: 'Padding: Horizontal',
		options: [
			...paddingOptions.horizontal,
		],
	},
	{
		label: 'Padding: Top',
		options: [
			...paddingOptions.top,
		],
	},
	{
		label: 'Padding: Right',
		options: [
			...paddingOptions.right,
		],
	},
	{
		label: 'Padding: Bottom',
		options: [
			...paddingOptions.bottom,
		],
	},
	{
		label: 'Padding: Left',
		options: [
			...paddingOptions.left,
		],
	},
	{
		label: 'Margin: All',
		options: [
			...marginOptions.all,
		],
	},
	{
		label: 'Margin: Vertical',
		options: [
			...marginOptions.vertical,
		],
	},
	{
		label: 'Margin: Horizontal',
		options: [
			...marginOptions.horizontal,
		],
	},
	{
		label: 'Margin: Top',
		options: [
			...marginOptions.top,
		],
	},
	{
		label: 'Margin: Right',
		options: [
			...marginOptions.right,
		],
	},
	{
		label: 'Margin: Bottom',
		options: [
			...marginOptions.bottom,
		],
	},
	{
		label: 'Margin: Left',
		options: [
			...marginOptions.left,
		],
	},
	{
		label: 'Width',
		options: [
			...sizeOptions.width,
		],
	},
	{
		label: 'Height',
		options: [
			...sizeOptions.height,
		],
	},
	{
		label: 'Aspect Ratio',
		options: [
			...sizeOptions.aspectRatio,
		],
	},
	{
		label: 'Color: Text',
		options: [
			...colorOptions.text.common,
			...colorOptions.text.primary,
			...colorOptions.text.secondary,
			...colorOptions.text.base,
			...colorOptions.text.semantic,
		],
	},
	{
		label: 'Color: Background',
		options: [
			...colorOptions.background.common,
			...colorOptions.background.primary,
			...colorOptions.background.secondary,
			...colorOptions.background.base,
			...colorOptions.background.semantic,
		],
	},
	{
		label: 'Overlay: Color',
		options: [
			...overlayOptions.color.common,
			...overlayOptions.color.primary,
			...overlayOptions.color.secondary,
			...overlayOptions.color.base,
		],
	},
	{
		label: 'Overlay: Blur',
		options: [
			...overlayOptions.blur,
		],
	},
	{
		label: 'Border: Width',
		options: [
			...borderOptions.width,
		],
	},
	{
		label: 'Border: Style',
		options: [
			...borderOptions.style.all,
			...borderOptions.style.top,
			...borderOptions.style.right,
			...borderOptions.style.bottom,
			...borderOptions.style.left,
		],
	},
	{
		label: 'Border: Radius',
		options: [
			...borderOptions.radius.all,
			...borderOptions.radius.topLeft,
			...borderOptions.radius.top,
			...borderOptions.radius.topRight,
			...borderOptions.radius.right,
			...borderOptions.radius.bottomRight,
			...borderOptions.radius.bottom,
			...borderOptions.radius.bottomLeft,
			...borderOptions.radius.left,
		],
	},
	{
		label: 'Border: Color',
		options: [
			...borderOptions.color.all.common,
			...borderOptions.color.all.primary,
			...borderOptions.color.all.secondary,
			...borderOptions.color.all.base,
		],
	},
	{
		label: 'Top Border: Color',
		options: [
			...borderOptions.color.top.common,
			...borderOptions.color.top.primary,
			...borderOptions.color.top.secondary,
			...borderOptions.color.top.base,
		],
	},
	{
		label: 'Right Border: Color',
		options: [
			...borderOptions.color.right.common,
			...borderOptions.color.right.primary,
			...borderOptions.color.right.secondary,
			...borderOptions.color.right.base,
		],
	},
	{
		label: 'Bottom Border: Color',
		options: [
			...borderOptions.color.bottom.common,
			...borderOptions.color.bottom.primary,
			...borderOptions.color.bottom.secondary,
			...borderOptions.color.bottom.base,
		],
	},
	{
		label: 'Left Border: Color',
		options: [
			...borderOptions.color.left.common,
			...borderOptions.color.left.primary,
			...borderOptions.color.left.secondary,
			...borderOptions.color.left.base,
		],
	},
	{
		label: 'Vertical Border: Color',
		options: [
			...borderOptions.color.vertical.common,
			...borderOptions.color.vertical.primary,
			...borderOptions.color.vertical.secondary,
			...borderOptions.color.vertical.base,
		],
	},
	{
		label: 'Horizontal Border: Color',
		options: [
			...borderOptions.color.horizontal.common,
			...borderOptions.color.horizontal.primary,
			...borderOptions.color.horizontal.secondary,
			...borderOptions.color.horizontal.base,
		],
	},
];

/**
 * Get the class options with custom options if available.
 *
 * @param {Object|null} liveClasses      Live user-defined custom classes to prepend.
 * @param {string[]}    customColorSlugs Saved custom-colour slugs to append as color--/background-- options.
 * @since x.x.x
 *
 * @return {Array} An array of class options for the React Select dropdown.
 */
const getClassOptions = ( liveClasses = null, customColorSlugs = [] ) => {
	const customOptions = addCustomOptions( liveClasses );

	// Inject the user's saved custom colours into the colour groups. These are
	// per-site (config.custom_colors), so they can't live in the static
	// classOptions — they're appended at call time as color--{slug} /
	// background--{slug}, matching the aliases + catalog on the server.
	let groups = classOptions;
	const slugs = Array.isArray( customColorSlugs ) ? customColorSlugs.filter( Boolean ) : [];
	if ( slugs.length ) {
		groups = classOptions.map( ( group ) => {
			if ( 'Color: Text' === group.label ) {
				return { ...group, options: [ ...group.options, ...slugs.map( ( s ) => ( { value: `color--${ s }`, label: `color--${ s }` } ) ) ] };
			}
			if ( 'Color: Background' === group.label ) {
				return { ...group, options: [ ...group.options, ...slugs.map( ( s ) => ( { value: `background--${ s }`, label: `background--${ s }` } ) ) ] };
			}
			return group;
		} );
	}

	// If there are no custom options, return the (possibly colour-augmented) groups.
	if ( Object.keys( customOptions ).length === 0 ) {
		return groups;
	}

	// If there are custom options, prepend them to the groups array.
	return [ customOptions, ...groups ];
};

// Export the getClassOptions function by default.
export default getClassOptions;