<?php
/**
 * View for rendering the counter block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Counter
 */

use SpectraBlocks\Helpers\Renderer;
use SpectraBlocks\Helpers\HtmlSanitizer;

// Add prefix, suffix, and separator data attributes to wrapper attributes before sanitization.
$additional_attributes = sprintf(
	' data-counter-start="%s" data-counter-end="%s" data-counter-prefix="%s" data-counter-suffix="%s" data-counter-separator="%s"',
	esc_attr( $counter_start_value ),
	esc_attr( $counter_end_value ),
	esc_attr( $counter_prefix_value ),
	esc_attr( $counter_suffix_value ),
	esc_attr( $counter_separator_value )
);
$wrapper_attributes   .= $additional_attributes;

?>
<?php
if ( 'circular' === $counter_style ) :
	// Parse progress size (apply default only when used, following icon pattern).
	$size_value = (int) str_replace( 'px', '', $progress_size ? $progress_size : '300px' );
	if ( $size_value <= 0 ) {
		$size_value = 300; // Fallback for invalid values.
	}
	// Calculate radius: half the size minus half the stroke width (stroke is centered on the path).
	$radius        = ( $size_value / 2 ) - ( ( $progress_stroke_width ? $progress_stroke_width : 8 ) / 2 );
	$circumference = 2 * M_PI * $radius;
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<div class="spectra-counter-circular-wrapper">
			<div class="spectra-counter-progress">
				<svg width="<?php echo esc_attr( $progress_size ? $progress_size : '300px' ); ?>" height="<?php echo esc_attr( $progress_size ? $progress_size : '300px' ); ?>">
					<circle 
						class="spectra-counter-progress-bg"
						cx="50%" 
						cy="50%" 
						r="<?php echo esc_attr( $radius ); ?>"
						stroke="<?php echo esc_attr( $progress_background_color ); ?>"
						stroke-width="<?php echo esc_attr( $progress_stroke_width ? $progress_stroke_width : 8 ); ?>"
						fill="none"
					/>
					<circle 
						class="spectra-counter-progress-circle"
						cx="50%" 
						cy="50%" 
						r="<?php echo esc_attr( $radius ); ?>"
						stroke="<?php echo esc_attr( $progress_color ); ?>"
						stroke-width="<?php echo esc_attr( $progress_stroke_width ? $progress_stroke_width : 8 ); ?>"
						fill="none"
						stroke-linecap="butt"
						stroke-dasharray="<?php echo esc_attr( $circumference ); ?>"
						stroke-dashoffset="<?php echo esc_attr( $circumference ); ?>"
					/>
				</svg>
			</div>
			<div class="spectra-counter-content">
				<?php HtmlSanitizer::render( $content ); ?>
			</div>
		</div>
	</div>
	<?php
else :
	// Simple and bar styles - render content directly (includes counter-child-wrapper).
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<?php HtmlSanitizer::render( $content ); ?>
	</div>
<?php endif; ?>
