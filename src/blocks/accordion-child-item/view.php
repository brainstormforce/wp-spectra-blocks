<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildItem
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $accordion_item_contexts, 'spectra/accordion' ) ); ?>
	<?php if ( $open_by_default ) : ?>
		data-wp-init="spectra/accordion::callbacks.initOpenByDefault"
	<?php endif; ?>
	data-wp-class--is-active="spectra/accordion::context.isExpanded"
	data-wp-watch--toggle="spectra/accordion::callbacks.isToggled"
	data-wp-watch--animate="spectra/accordion::callbacks.isAnimated"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
