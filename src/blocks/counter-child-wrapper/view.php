<?php
/**
 * View for rendering the counter child wrapper block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildWrapper
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is sanitized via HtmlSanitizer::render() which uses wp_kses() internally.
	echo HtmlSanitizer::render( $content );
	?>
</div>

