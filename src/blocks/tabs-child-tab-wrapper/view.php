<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 * 
 * @package Spectra\Blocks\TabsChildTabWrapper
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $tab_button_wrapper_contexts, 'spectra/tabs' ) ); ?>
	role="tablist"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
