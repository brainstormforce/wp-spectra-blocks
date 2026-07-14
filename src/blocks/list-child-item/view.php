<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ListChildItem
 */

use SpectraBlocks\Helpers\Renderer;
use SpectraBlocks\Helpers\HtmlSanitizer;

?>

<li <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</li>
