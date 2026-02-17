<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\ListChildIcon
 */

use Spectra\Helpers\Renderer;

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( 'ordered' === $list_type ) : ?>
		<span class="spectra-list-counter"><?php echo esc_html( $formatted_number ); ?></span>
	<?php else : ?>
		<?php Renderer::svg_html( $icon, $flip_for_rtl, $icon_props ); ?>
	<?php endif; ?>
</div>
