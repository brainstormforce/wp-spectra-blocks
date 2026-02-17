<?php
/**
 * View for rendering the SVG Animator block.
 *
 * @since x.x.x
 *
 * @package Spectra\Blocks\SvgAnimator
 */

use Spectra\Helpers\HtmlSanitizer;

// Build ARIA attributes based on accessibility mode.
$aria_attrs = '';
switch ( $accessibility_mode ) {
	case 'svg':
		$aria_attrs = ' role="graphics-symbol"';
		if ( ! empty( $accessibility_label ) ) {
			$aria_attrs .= sprintf( ' aria-label="%s"', esc_attr( $accessibility_label ) );
		}
		break;
	case 'image':
		$aria_attrs = ' role="img"';
		if ( ! empty( $accessibility_label ) ) {
			$aria_attrs .= sprintf( ' aria-label="%s"', esc_attr( $accessibility_label ) );
		}
		break;
	default:
		$aria_attrs = ' aria-hidden="true"';
}
?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo $aria_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr above. ?>
>
	<?php if ( ! empty( $inline_svg ) ) : ?>
		<?php HtmlSanitizer::render( $inline_svg ); ?>
	<?php endif; ?>
</div>
