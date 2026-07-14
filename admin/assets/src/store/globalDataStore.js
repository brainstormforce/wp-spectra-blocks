import { createStore } from 'redux';
import globalDataReducer from './globalDataReducer';

const initialState = {
	initialStateSetFlag : false,
	activeSettingsNavigationTab : '',
	blocksStatuses : [],
	enableTemplates : '',
	enableSelectedFontFamilies : '',
	selectedFontFamilies : '',
	enableFSEFontFamilies : '',
	enableLoadFontsLocally : '',
	enablePreloadLocalFonts : '',
	enableAnimationsExtension : '',
	enableDynamicContentExtension: '',
	enableResponsiveConditions : '',
	siteKeyV2: '',
	siteKeyV3: '',
	secretKeyV2: '',
	secretKeyV3: '',
	settingsSavedNotification: '',
	coreBlocks: [
		'container',
		'advanced-heading',
		'image',
		'icon',
		'buttons',
		'info-box',
		'call-to-action',
		'countdown',
		'popup-builder'
	],
	spectraFSEFonts: [],
	themeFonts: [],
	spectraIsBlockTheme: false,
	enableGBSExtension: '',
	zipAiModules: [],
	enableBSFAnalyticsOption: 'no',
};

const globalDataStore = createStore(
	globalDataReducer,
	initialState,
	window.__REDUX_DEVTOOLS_EXTENSION__ &&
	window.__REDUX_DEVTOOLS_EXTENSION__()
);

export default globalDataStore;
