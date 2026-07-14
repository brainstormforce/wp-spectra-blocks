<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeaderIcon
 */

use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Helpers\Renderer;

?>
<span
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $accordion_icon_contexts, 'spectra/accordion' ) ); ?>
	data-wp-watch="spectra/accordion::callbacks.updateIconDisplay"
>
	<span
		data-wp-bind--hidden="spectra/accordion::context.isExpanded"
		data-wp-style--display="spectra/accordion::context.styleHide"
	>
		<?php Renderer::svg_html( $collapsed_icon, $attributes['flipForRTL'], $icon_props ); ?>
	</span>
	<span
		data-wp-bind--hidden="spectra/accordion::!context.isExpanded"
		data-wp-style--display="spectra/accordion::context.styleDisplay"
	>
		<?php Renderer::svg_html( $expanded_icon, $attributes['flipForRTLSecondary'], $icon_props ); ?>
	</span>
</span>
