<?php
/**
 * View for the Popup Builder block (V3)
 * Replicates V2 popup builder structure with V3 architecture
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\PopupBuilder
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
use SpectraBlocks\Helpers\Renderer;
// Bail out if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set global context for V3 popup rendering.
$GLOBALS['spectra_v3_popup_context'] = true;
$icon_props                          = array(
	'focusable' => 'false',
	'style'     => array(
		'fill'      => 'currentColor',
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
	),
);

// Add the accessibility details based on the attributes.
switch ( $attributes['accessibilityMode'] ?? '' ) {
	case 'svg':
		// SVG based accessibility attributes.
		$icon_props['role']        = 'graphics-symbol';
		$icon_props['aria-hidden'] = 'false';
		$icon_props['aria-label']  = ! empty( $attributes['accessibilityLabel'] )
			? $attributes['accessibilityLabel']
			: sprintf(
				/* translators: %s: The name of the SVG icon. */
				__( 'An icon named %s', 'spectra-blocks' ),
				Renderer::get_icon_name( $icon )
			);
		break;
	case 'image':
		// Image based accessibility attributes.
		$icon_props['role']        = 'img';
		$icon_props['aria-hidden'] = 'false';
		$icon_props['aria-label']  = ! empty( $attributes['accessibilityLabel'] )
			? $attributes['accessibilityLabel']
			: sprintf(
				/* translators: %s: The name of the SVG image. */
				__( 'An image named %s', 'spectra-blocks' ),
				Renderer::get_icon_name( $icon )
			);
		break;
	default:
		// In any other case, the SVG should be hidden from the accessibility tree.
		$icon_props['aria-hidden'] = 'true';
}

// Generate close button aria label.
$close_aria_label = ! empty( $attributes['accessibilityLabel'] ) ? $attributes['accessibilityLabel'] : sprintf(
				/* translators: %s: The name of the SVG image. */
	__( 'An image named %s', 'spectra-blocks' ),
	Renderer::get_icon_name( $close_icon )
);

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
	if ( 'popup' !== $variant_type ) :
		Renderer::background_video( $background );
	endif;
	?>
	<?php if ( $has_overlay && 'popup' === $variant_type ) : ?>
		<div 
			class="spectra-popup-builder__overlay"
			data-overlay="true"
			role="presentation"
			aria-hidden="true"
		></div>
	<?php endif; ?>

	<div 
		class="spectra-popup-builder__wrapper spectra-popup-builder__wrapper--<?php echo esc_attr( $variant_type ); ?>"
		<?php if ( 'popup' === $variant_type ) : ?>
			role="dialog"
			aria-modal="true"
			aria-label="<?php echo esc_attr__( 'Popup content', 'spectra-blocks' ); ?>"
		<?php else : ?>
			role="region"
			aria-label="<?php echo esc_attr__( 'Information banner', 'spectra-blocks' ); ?>"
		<?php endif; ?>
		aria-hidden="true"
		tabindex="-1"
	>
	<?php
	if ( 'banner' !== $variant_type ) :
		Renderer::background_video( $background );
	endif;
	?>
		<div class="spectra-popup-builder__container spectra-popup-builder__container--<?php echo esc_attr( $variant_type ); ?>">

			<?php if ( $is_dismissable && $close_icon ) : ?>
				<button 
					class="spectra-popup-builder__close spectra-popup-builder__close--<?php echo esc_attr( $close_icon_position ); ?>"
					data-close-popup="true"
					aria-label="<?php echo esc_attr( $close_aria_label ); ?>"
					type="button"
					title="<?php echo esc_attr( $close_aria_label ); ?>"
				>
					<?php
					Renderer::svg_html(
						$close_icon,
						$attributes['flipForRTL'] ?? false,
						$icon_props
					);
					?>
				</button>
			<?php endif; ?>

			<div class="spectra-popup-builder__content">
				<?php HtmlSanitizer::render( $content ); ?>
			</div>

		</div>
	</div>

</div>
<script><?php echo $js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JavaScript output is controlled and generated in controller.php ?></script>
<?php
// Clean up global context.
unset( $GLOBALS['spectra_v3_popup_context'] );
?>
