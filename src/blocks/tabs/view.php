<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Tabs
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-interactive="spectra/tabs"
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $tabs_context, 'spectra/tabs' ) ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
