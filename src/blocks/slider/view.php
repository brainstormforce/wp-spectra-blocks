<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\Slider
 */

use Spectra\Helpers\Core;
use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;
$icon_props = array(
	'focusable' => 'false',
);
?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
<?php
	// Render the background video element if needed.
	Renderer::background_video( $background );
?>

	<div class="spectra-slider-container">
		<div class="swiper">
			<div class="swiper-wrapper" aria-live="polite">
				<?php HtmlSanitizer::render( $content ); ?>
			</div>
		</div>

		<?php if ( $navigation ) : ?>
			<div class="spectra-slider-navigation" role="group" aria-label="<?php esc_attr_e( 'Slider navigation controls', 'spectra' ); ?>">
				<div 
					class="swiper-button-prev" 
					role="button"
					aria-label="<?php esc_attr_e( 'Previous slide', 'spectra' ); ?>"
					data-role="none"
					tabindex="0"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Previous slide', 'spectra' ); ?></span>
					<?php 
					Renderer::svg_html(
						$attributes['navigationPrevIcon'] ?? 'arrow-left',
						false,
						array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
					); 
					?>
				</div>
				<div 
					class="swiper-button-next" 
					role="button"
					aria-label="<?php esc_attr_e( 'Next slide', 'spectra' ); ?>"
					data-role="none"
					tabindex="0"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Next slide', 'spectra' ); ?></span>
					<?php 
					Renderer::svg_html(
						$attributes['navigationNextIcon'] ?? 'arrow-right',
						false,
						array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
					); 
					?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $pagination ) : ?>
			<div 
				class="swiper-pagination" 
				role="tablist" 
				aria-label="<?php esc_attr_e( 'Slider pagination', 'spectra' ); ?>"
				data-role="none"
			></div>
		<?php endif; ?>
	</div>
</div>
