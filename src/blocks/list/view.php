<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\List
 */

use Spectra\Helpers\HtmlSanitizer;

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
