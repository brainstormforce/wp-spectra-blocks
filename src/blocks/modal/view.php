<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Modal
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-interactive="spectra/modal"
	data-wp-init="spectra/modal::callbacks.initialize"
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $modal_context, 'spectra/modal' ) ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
