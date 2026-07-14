<?php
/**
 * View for rendering the counter progress bar block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildProgressBar
 */

// Only render progress bar for circular and bar styles.
if ( 'simple' === $counter_style ) {
	return;
}

?>
<?php
if ( 'circular' === $counter_style ) :
	// Parse progress size.
	$size_value = (int) str_replace( 'px', '', $progress_size ? $progress_size : '300px' );
	if ( $size_value <= 0 ) {
		$size_value = 300;
	}
	// Calculate radius: half the size minus half the stroke width.
	$radius        = ( $size_value / 2 ) - ( ( $progress_stroke_width ? $progress_stroke_width : 8 ) / 2 );
	$circumference = 2 * M_PI * $radius;

	// Calculate progress percentage.
	$safe_total_number = max( $total_number ? $total_number : 100, abs( $start_number ), abs( $end_number ) );
	$start_point       = max( 0, min( 100, ( $start_number / $safe_total_number ) * 100 ) );
	$start_offset      = ( ( 100 - $start_point ) / 100 ) * $circumference;
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<div class="spectra-counter-progress">
			<svg width="<?php echo esc_attr( $size_value ); ?>" height="<?php echo esc_attr( $size_value ); ?>">
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
					stroke-dashoffset="<?php echo esc_attr( $start_offset ); ?>"
				/>
			</svg>
		</div>
	</div>
	<?php
elseif ( 'bar' === $counter_style ) :
	// Calculate progress percentage for bar.
	$safe_total_number = max( $total_number ? $total_number : 100, abs( $start_number ), abs( $end_number ) );
	$initial_width     = max( 0, min( 100, ( $start_number / $safe_total_number ) * 100 ) );

	// Format the initial number display.
	$formatted_start = number_format( $start_number, $decimal_places, '.', $thousand_separator );
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<div class="spectra-counter-progress-track">
			<div
				class="spectra-counter-progress-bar"
				style="width: <?php echo esc_attr( $initial_width ); ?>%;"
			>
				<div class="spectra-counter-progress-label">
					<?php if ( ! empty( $prefix ) ) : ?>
						<span class="spectra-counter-prefix"><?php echo esc_html( $prefix ); ?></span>
					<?php endif; ?>
					<span class="spectra-counter-value"><?php echo esc_html( $formatted_start ); ?></span>
					<?php if ( ! empty( $suffix ) ) : ?>
						<span class="spectra-counter-suffix"><?php echo esc_html( $suffix ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
