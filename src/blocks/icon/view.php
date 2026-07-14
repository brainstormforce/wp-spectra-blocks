<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Icon
 */

use SpectraBlocks\Helpers\Renderer;

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php if ( isset( $icon ) && '' !== $icon ) : // empty() treats "0" as empty; use explicit check instead. ?>
		<?php Renderer::svg_html( $icon, $attributes['flipForRTL'] ?? false, $icon_props ); ?>
	<?php endif; ?>
</<?php echo esc_attr( $tag_name ); ?>>
