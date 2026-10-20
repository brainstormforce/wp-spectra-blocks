<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabWrapper
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>
	<?php echo spectra_blocks_esc_attributes( wp_interactivity_data_wp_context( $tab_button_wrapper_contexts, 'spectra/tabs' ) ); ?>
	role="tablist"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
