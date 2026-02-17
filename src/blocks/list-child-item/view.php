<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\ListChildItem
 */

use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

?>

<li <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</li>
