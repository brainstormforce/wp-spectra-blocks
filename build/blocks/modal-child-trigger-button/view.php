<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerButton
 */


defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
use Spectra\Helpers\Renderer;

?>

<button
<?php
	echo wp_kses_data( $wrapper_attributes );
?>
	data-wp-on--click="spectra/modal::actions.toggle"
<?php if ( ! empty( $aria_label ) ) : ?>
	aria-label="<?php echo esc_attr( $aria_label ); ?>"
<?php endif; ?>
>
<?php
if ( isset( $icon ) && 'before' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}

if ( $show_text ) {
	echo '<span class="spectra-button__link">' . wp_kses_post( $text ) . '</span>';
}

if ( isset( $icon ) && 'after' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}
?>
</button>
