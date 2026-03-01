<?php
/**
 * View for rendering the counter child wrapper block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildWrapper
 */

defined( 'ABSPATH' ) || exit;
use Spectra\Helpers\HtmlSanitizer;
?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>

