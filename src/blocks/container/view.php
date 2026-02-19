<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Button
 */

defined( 'ABSPATH' ) || exit;
use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

require_once __DIR__ . '/shapes.php';
use function Spectra\Blocks\Container\get_shape_svg;

?>
<<?php echo esc_attr( $html_tag ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( $link_attributes ); ?>
>
	<?php
		// Render the background video element if needed.
		Renderer::background_video( $background );

		// Define allowed SVG tags and attributes for shape dividers.
		$svg_kses_args = array(
			'svg'  => array(
				'xmlns'               => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
			),
			'path' => array(
				'class'   => true,
				'd'       => true,
				'opacity' => true,
			),
		);

		// Render top shape divider.
		if ( 'none' !== $top_type ) {
			$top_classes = array( 'spectra-container__shape', 'spectra-container__shape-top' );
			if ( $top_flip ) {
				$top_classes[] = 'spectra-container__shape-flip';
			}
			if ( $top_invert ) {
				$top_classes[] = 'spectra-container__invert';
			}
			if ( $top_content_above_shape ) {
				$top_classes[] = 'spectra-container__shape-above-content';
			}
			?>
			<div class="<?php echo esc_attr( implode( ' ', $top_classes ) ); ?>">
				<?php echo wp_kses( get_shape_svg( $top_type ), $svg_kses_args ); ?>
			</div>
			<?php
		}

		// Render bottom shape divider.
		if ( 'none' !== $bottom_type ) {
			$bottom_classes = array( 'spectra-container__shape', 'spectra-container__shape-bottom' );
			if ( $bottom_flip ) {
				$bottom_classes[] = 'spectra-container__shape-flip';
			}
			if ( $bottom_invert ) {
				$bottom_classes[] = 'spectra-container__invert';
			}
			if ( $bottom_content_above_shape ) {
				$bottom_classes[] = 'spectra-container__shape-above-content';
			}
			?>
			<div class="<?php echo esc_attr( implode( ' ', $bottom_classes ) ); ?>">
				<?php echo wp_kses( get_shape_svg( $bottom_type ), $svg_kses_args ); ?>
			</div>
			<?php
		}

		HtmlSanitizer::render( $content );
		?>
</<?php echo esc_attr( $html_tag ); ?>>
