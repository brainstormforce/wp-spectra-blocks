<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerIcon
 */

use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Helpers\Renderer;

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-context
	data-wp-on--click="spectra/modal::actions.toggle"
	role="button"
	tabindex="0"
	<?php if ( ! empty( $wrapper_aria_label ) ) : ?>
		aria-label="<?php echo esc_attr( $wrapper_aria_label ); ?>"
	<?php endif; ?>>
<?php
// After this condition, just render the icon.
Renderer::svg_html( $icon, $attributes['flipForRTL'], $icon_props );
?>
</div>
