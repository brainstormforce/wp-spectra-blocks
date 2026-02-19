<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeaderContent
 */


defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<<?php echo esc_html( $tag_name ); ?> <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php echo wp_kses_post( $text ); ?>
</<?php echo esc_html( $tag_name ); ?>>
