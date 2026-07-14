<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabpanel
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $tabpanel_contexts, 'spectra/tabs' ) ); ?>
	role="tabpanel"
	tabindex="0"
	data-wp-init="spectra/tabs::callbacks.initializeTabs"
	data-wp-watch="spectra/tabs::callbacks.isActiveTab"
	data-wp-bind--id="spectra/tabs::context.tabPanelId"
	data-wp-bind--aria-labelledby="spectra/tabs::context.tabId"
	data-wp-bind--hidden="spectra/tabs::!context.isActive"
	data-wp-class--spectra-block-is-hidden="spectra/tabs::!context.isActive"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
