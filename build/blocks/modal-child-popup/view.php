<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopup
 */


defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
