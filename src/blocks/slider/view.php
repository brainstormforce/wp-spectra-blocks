<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Slider
 */

use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Helpers\Renderer;
use SpectraBlocks\Helpers\HtmlSanitizer;
$icon_props = array(
	'focusable' => 'false',
);
?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
<?php
	// Render the background video element if needed.
	// When any responsive breakpoint uses video, render from that breakpoint's data
	// so the element exists in the DOM for CSS to show/hide per viewport.
	Renderer::background_video( ( $has_video_background && null !== $video_background ) ? $video_background : $background );
?>

	<div class="spectra-slider-container">
		<div class="swiper">
			<div class="swiper-wrapper" aria-live="polite">
				<?php HtmlSanitizer::render( $content ); ?>
			</div>
		</div>

		<?php if ( $navigation && $display_arrows ) : ?>
			<div class="spectra-slider-navigation" role="group" aria-label="<?php esc_attr_e( 'Slider navigation controls', 'spectra-blocks' ); ?>">
				<div 
					class="swiper-button-prev" 
					role="button"
					aria-label="<?php esc_attr_e( 'Previous slide', 'spectra-blocks' ); ?>"
					data-role="none"
					tabindex="0"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Previous slide', 'spectra-blocks' ); ?></span>
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
					aria-label="<?php esc_attr_e( 'Next slide', 'spectra-blocks' ); ?>"
					data-role="none"
					tabindex="0"
				>
					<span class="screen-reader-text"><?php esc_html_e( 'Next slide', 'spectra-blocks' ); ?></span>
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
				role="group" 
				aria-label="<?php esc_attr_e( 'Slider pagination', 'spectra-blocks' ); ?>"
				data-role="none"
			></div>
		<?php endif; ?>
	</div>
</div>
