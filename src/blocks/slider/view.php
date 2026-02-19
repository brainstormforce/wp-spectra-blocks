<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Slider
 */

defined( 'ABSPATH' ) || exit;
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
			<div class="spectra-slider-navigation" role="group" aria-label="<?php esc_attr_e( 'Slider navigation controls', 'spectra-blocks' ); ?>">
				<button
					type="button"
					class="swiper-button-prev"
					aria-label="<?php esc_attr_e( 'Previous slide', 'spectra-blocks' ); ?>"
					data-role="none"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Previous slide', 'spectra-blocks' ); ?></span>
					<?php
					Renderer::svg_html(
						$attributes['navigationPrevIcon'] ?? 'arrow-left',
						false,
						array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
					);
					?>
				</button>
				<button
					type="button"
					class="swiper-button-next"
					aria-label="<?php esc_attr_e( 'Next slide', 'spectra-blocks' ); ?>"
					data-role="none"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Next slide', 'spectra-blocks' ); ?></span>
					<?php
					Renderer::svg_html(
						$attributes['navigationNextIcon'] ?? 'arrow-right',
						false,
						array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
					);
					?>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( $pagination ) : ?>
			<div 
				class="swiper-pagination" 
				role="tablist" 
				aria-label="<?php esc_attr_e( 'Slider pagination', 'spectra-blocks' ); ?>"
				data-role="none"
			></div>
		<?php endif; ?>
	</div>
</div>
