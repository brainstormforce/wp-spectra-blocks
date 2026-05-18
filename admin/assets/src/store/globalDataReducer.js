const globalDataReducer = ( state = {}, action ) => {
    switch ( action.type ) {
        case 'UPDATE_INITIAL_STATE':
            return {
                ...action.payload,
            };
        case 'UPDATE_INITIAL_STATE_FLAG':
            return {
                ...state,
                initialStateSetFlag: action.payload,
            };
        case 'UPDATE_BLOCK_STATUSES':
            return {
                ...state,
                blocksStatuses: action.payload
            };
        case 'UPDATE_SETTINGS_ACTIVE_NAVIGATION_TAB':
            return {
                ...state,
                activeSettingsNavigationTab: action.payload
            };
        case 'UPDATE_TEMPLATES_BUTTON':
            return {
                ...state,
                enableTemplates: action.payload,
            };
        case 'UPDATE_ENABLE_SELECTED_FONT_FAMILIES':
            return {
                ...state,
                enableSelectedFontFamilies: action.payload,
            };
        case 'UPDATE_SELECTED_FONT_FAMILIES':
            return {
                ...state,
                selectedFontFamilies: action.payload,
            };
        case 'UPDATE_ENABLE_FSE_FONT_FAMILIES':
            return {
                ...state,
                enableFSEFontFamilies: action.payload,
        };
        case 'UPDATE_ENABLE_LOAD_FONTS_LOCALLY':
            return {
                ...state,
                enableLoadFontsLocally: action.payload,
            };
        case 'UPDATE_ENABLE_PRELOAD_LOCAL_FONTS':
            return {
                ...state,
                enablePreloadLocalFonts: action.payload,
            };
        case 'UPDATE_ENABLE_ANIMATIONS_EXTENSION':
            return {
                ...state,
                enableAnimationsExtension: action.payload,
            };
        case 'UPDATE_ENABLE_DYNAMIC_CONTENT_EXTENSION':
            return {
                ...state,
                enableDynamicContentExtension: action.payload,
            };
		case 'UPDATE_ENABLE_RESPONSIVE_CONDITIONS':
			return {
				...state,
				enableResponsiveConditions: action.payload,
			};
		case 'UPDATE_RECAPTCHA_SITE_KEY_V2':
			return {
					...state,
					siteKeyV2: action.payload,
				};
		case 'UPDATE_RECAPTCHA_SITE_KEY_V3':
			return {
				...state,
				siteKeyV3: action.payload,
			};
		case 'UPDATE_RECAPTCHA_SECRET_KEY_V2':
			return {
				...state,
				secretKeyV2: action.payload,
			};
		case 'UPDATE_RECAPTCHA_SECRET_KEY_V3':
			return {
				...state,
				secretKeyV3: action.payload,
			};
		case 'UPDATE_SETTINGS_SAVED_NOTIFICATION':
			return {
				...state,
				settingsSavedNotification: action.payload,
			};
        case 'UPDATE_DISABLE_CSS_CACHE':
            return {
                ...state,
                disableCSSCache: action.payload,
            };
		case 'UPDATE_CONTAINER_GLOBAL_PADDING':
			return {
				...state,
				containerGlobalPadding: action.payload,
				};
		case 'UPDATE_CONTAINER_GLOBAL_ELEMENTS_GAP':
			return {
				...state,
				containerGlobalElementsGap: action.payload,
				};
        case 'UPDATE_BTN_INHERIT_FROM_THEME':
            return {
                ...state,
                btnInheritFromTheme: action.payload,
                };
        case 'UPDATE_GBS_EXTENSION':
            return {
                ...state,
                enableGBSExtension: action.payload,
                };
		case 'UPDATE_ZIP_AI_MODULES':
			return {
				...state,
				zipAiModules: action.payload,
				};
		case 'UPDATE_ALL_EXTENSIONS':
			const enableAll = 'enabled' === action.payload ? 'enabled' : 'disabled';
			return {
				...state,
				enableGBSExtension: enableAll,
				enableAnimationsExtension: enableAll,
				enableDynamicContentExtension: enableAll,
				enableResponsiveConditions: enableAll
			}; 
		case 'UPDATE_ENABLE_BSF_ANALYTICS_OPTION':
			return {
				...state,
				enableBSFAnalyticsOption: action.payload,
			};
        default:
            return state;
    }
}

export default globalDataReducer;
