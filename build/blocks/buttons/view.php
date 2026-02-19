<?php
/**
 * View for rendering the buttons block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Buttons
 */


defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
use Spectra\Helpers\HtmlSanitizer;

/**
 * Render the buttons container with its child button blocks.
 */
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
