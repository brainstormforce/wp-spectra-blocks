<?php
/**
 * View template for the Before After Slider block.
 *
 * @since x.x.x
 * @package Spectra\Blocks\BeforeAfterSlider
 */

// Bail if no images are set.
if ( empty( $before_image_url ) || empty( $after_image_url ) ) {
	return;
}

$clip_value = $is_horizontal
	? 'inset(0 ' . esc_attr( 100 - $initial_offset ) . '% 0 0)'
	: 'inset(0 0 ' . esc_attr( 100 - $initial_offset ) . '% 0)';

$handle_pos = $is_horizontal
	? 'left:' . esc_attr( $initial_offset ) . '%'
	: 'top:' . esc_attr( $initial_offset ) . '%';
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<div class="spectra-ba-slider__container">
		<?php // After image (base layer). ?>
		<div class="spectra-ba-slider__after">
			<img
				src="<?php echo esc_url( $after_image_url ); ?>"
				alt="<?php echo esc_attr( $after_image_alt ); ?>"
				class="spectra-ba-slider__image"
			/>
			<div class="spectra-ba-slider__overlay"></div>
			<?php if ( 'none' !== $show_labels && ! empty( $after_label ) ) : ?>
				<span class="spectra-ba-slider__label spectra-ba-slider__label--after">
					<?php echo esc_html( $after_label ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php // Before image (clipped layer). ?>
		<div class="spectra-ba-slider__before" style="clip-path:<?php echo esc_attr( $clip_value ); ?>">
			<img
				src="<?php echo esc_url( $before_image_url ); ?>"
				alt="<?php echo esc_attr( $before_image_alt ); ?>"
				class="spectra-ba-slider__image"
			/>
			<div class="spectra-ba-slider__overlay"></div>
			<?php if ( 'none' !== $show_labels && ! empty( $before_label ) ) : ?>
				<span class="spectra-ba-slider__label spectra-ba-slider__label--before">
					<?php echo esc_html( $before_label ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php // Handle. ?>
		<div class="spectra-ba-slider__handle" style="<?php echo esc_attr( $handle_pos ); ?>">
			<div class="spectra-ba-slider__handle-line"></div>
			<div class="spectra-ba-slider__handle-circle">
				<?php if ( $is_horizontal ) : ?>
					<span class="spectra-ba-slider__arrow spectra-ba-slider__arrow--left"></span>
					<span class="spectra-ba-slider__arrow spectra-ba-slider__arrow--right"></span>
				<?php else : ?>
					<span class="spectra-ba-slider__arrow spectra-ba-slider__arrow--up"></span>
					<span class="spectra-ba-slider__arrow spectra-ba-slider__arrow--down"></span>
				<?php endif; ?>
			</div>
			<div class="spectra-ba-slider__handle-line"></div>
		</div>
	</div>
</div>
