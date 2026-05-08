import apiFetch from '@wordpress/api-fetch';

const setInitialState = ( store ) => {
    apiFetch( {
        path: '/spectra-blocks/v1/admin/commonsettings/',
    } ).then( ( data ) => {
        const initialState = {
            initialStateSetFlag : true,
			activeSettingsNavigationTab: 'license',
			settingsSavedNotification: '',
            blocksStatuses : data.blocks_activation_and_deactivation,
            enableTemplates : data.enable_templates_button,
            enableSelectedFontFamilies : data.load_select_font_globally,
            selectedFontFamilies :  data.select_font_globally,
            enableFSEFontFamilies : data.load_fse_font_globally,
            enableLoadFontsLocally : data.load_gfonts_locally,
            enablePreloadLocalFonts : data.preload_local_fonts,
            enableDynamicContentExtension: data.enable_dynamic_content,
			enableResponsiveConditions: data.enable_block_responsive,
			siteKeyV2: data.recaptcha_site_key_v2,
			secretKeyV2: data.recaptcha_secret_key_v2,
			siteKeyV3: data.recaptcha_site_key_v3,
			secretKeyV3: data.recaptcha_secret_key_v3,
			containerGlobalPadding: data.spectra_blocks_container_global_padding,
			containerGlobalElementsGap: data.spectra_blocks_container_global_elements_gap,
			disableCSSCache: data.spectra_blocks_disable_css_cache,
            coreBlocks: data.spectra_core_blocks,
            enableAnimationsExtension: data.spectra_blocks_enable_animations_extension,
            spectraFSEFonts: data.spectra_global_fse_fonts,
            spectraIsBlockTheme: data.wp_is_block_theme,
            themeFonts: data.theme_fonts,
            btnInheritFromTheme: data.spectra_blocks_btn_inherit_from_theme,
	        enableGBSExtension: data.spectra_blocks_enable_gbs_extension,
            zipAiModules: data.zip_ai_modules,
			enableBSFAnalyticsOption: data.enable_bsf_analytics_option,
        };

        store.dispatch( {type: 'UPDATE_INITIAL_STATE', payload: initialState} );

    } );
};

export default setInitialState;
