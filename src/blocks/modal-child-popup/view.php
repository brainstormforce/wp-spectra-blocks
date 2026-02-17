<?php
/**
 * View for rendering the block.
 *
 * @since 0.0.1
 *
 * @package Spectra\Blocks\ModalChildPopup
 */

use Spectra\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $needs_wrapper ) : ?>
		<div
		<?php echo wp_kses_data( $content_wrap_attributes ); ?>>
			<?php HtmlSanitizer::render( $content ); ?>
		</div>
	<?php else : ?>
		<?php HtmlSanitizer::render( $content ); ?>
	<?php endif; ?>
</div>
