<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Countdown
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-interactive="spectra/countdown"
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $countdown_context, 'spectra/countdown' ) ); ?>
	data-wp-init="spectra/countdown::callbacks.initialize"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
