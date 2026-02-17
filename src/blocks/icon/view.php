<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\Icon
 */

use Spectra\Helpers\Renderer;

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php if ( ! empty( $icon ) ) : ?>
		<?php Renderer::svg_html( $icon, $attributes['flipForRTL'] ?? false, $icon_props ); ?>
	<?php endif; ?>
</<?php echo esc_attr( $tag_name ); ?>>
