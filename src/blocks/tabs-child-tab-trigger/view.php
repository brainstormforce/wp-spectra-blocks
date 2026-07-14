<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabTrigger
 */

use SpectraBlocks\Helpers\Renderer;
use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $tab_contexts, 'spectra/tabs' ) ); ?>
	role="tab"
	aria-selected="<?php echo esc_attr( $aria_attributes['aria-selected'] ); ?>"
	tabindex="<?php echo esc_attr( $aria_attributes['tabindex'] ); ?>"
	data-wp-init="spectra/tabs::callbacks.initializeTabs"
	data-wp-watch--accessibility="spectra/tabs::callbacks.updateTabAttributes"
	data-wp-watch--active="spectra/tabs::callbacks.isActiveTab"
	data-wp-bind--id="spectra/tabs::context.tabId"
	data-wp-bind--aria-controls="spectra/tabs::context.tabPanelId"
	data-wp-bind--aria-selected="spectra/tabs::context.ariaSelected"
	data-wp-bind--tabindex="spectra/tabs::context.tabIndex"
	data-wp-class--spectra-block-is-active="spectra/tabs::context.isActive"
	data-wp-on--click="spectra/tabs::actions.updateActiveTab"
	data-wp-on--keydown="spectra/tabs::actions.switchTabs"
>
	<?php
		// Render the inner content.
		HtmlSanitizer::render( $content );
	?>
</div>
