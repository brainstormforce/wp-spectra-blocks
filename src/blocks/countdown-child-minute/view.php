<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildMinute
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
