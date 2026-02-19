<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopupCloseIcon
 */

defined( 'ABSPATH' ) || exit;
use Spectra\Helpers\Core;
use Spectra\Helpers\Renderer;

?>
<button <?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-on--click="spectra/modal::actions.close"
	<?php if ( ! empty( $wrapper_aria_label ) ) : ?>
		aria-label="<?php echo esc_attr( $wrapper_aria_label ); ?>"
	<?php endif; ?>
>
<?php
// After this condition, just render the icon.
Renderer::svg_html( $icon, $attributes['flipForRTL'], $icon_props );
?>
</button>
