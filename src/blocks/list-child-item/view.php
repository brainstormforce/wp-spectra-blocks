<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ListChildItem
 */

defined( 'ABSPATH' ) || exit;
use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

?>

<li <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</li>
