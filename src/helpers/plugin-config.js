/**
 * Plugin configuration helpers.
 * Provides typed accessors for PHP-localized plugin data.
 *
 * @package SpectraBlocks
 */

const getConfig = () => window.spectra_blocks_info || {};

export const getPluginUrl = () => getConfig().plugin_url || '';
export const getSvgIcons = () => window.spectraBlocksSvgIcons || {};
export const getIconCategoryList = () => window.spectraBlocksIconCategoryList || {};
export const isRtl = () => getConfig().is_rtl || false;
export const getSpectraProStatus = () => getConfig().spectra_pro_status || '';
export const getCurrentPostId = () => getConfig().current_post_id || 0;
export const getFontAwesomePolyfill = () => getConfig().font_awesome_5_polyfill || {};
