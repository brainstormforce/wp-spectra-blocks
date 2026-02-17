<?php
/**
 * View for rendering the buttons block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\Buttons
 */

use Spectra\Helpers\HtmlSanitizer;

/**
 * Render the buttons container with its child button blocks.
 */
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
