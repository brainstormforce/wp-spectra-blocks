<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ListChildIcon
 */

use SpectraBlocks\Helpers\Renderer;

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( 'ordered' === $list_type ) : ?>
		<span class="spectra-list-counter"><?php echo esc_html( $formatted_number ); ?></span>
	<?php else : ?>
		<?php Renderer::svg_html( $icon, $flip_for_rtl, $icon_props ); ?>
	<?php endif; ?>
</div>
