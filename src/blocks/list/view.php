<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\List
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>

<?php if ( 'ordered' === $list_type ) : ?>
<ol <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</ol>
<?php else : ?>
<ul <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</ul>
<?php endif; ?>
