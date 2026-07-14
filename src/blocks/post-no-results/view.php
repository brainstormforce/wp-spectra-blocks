<?php
/**
 * View for rendering the Post No Results block.
 *
 * @since 1.0.0
 * @package Spectra\Blocks\PostNoResults
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
