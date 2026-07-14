<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabButton
 */

use SpectraBlocks\Helpers\Renderer;

?>
<button
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $tab_button_contexts, 'spectra/tabs' ) ); ?>
	role="tab"
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
// Render the icon before the text if required.
if ( isset( $icon ) && 'before' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}

// Render the text if required.
if ( $show_text ) {
	echo '<div class="spectra-button__link">' . wp_kses_post( $text ) . '</div>';
}

// Render the icon after the text if required.
if ( isset( $icon ) && 'after' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}
?>
</button>
