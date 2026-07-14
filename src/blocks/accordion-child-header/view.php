<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeader
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>
<<?php echo esc_html( $header_element ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-bind--id="spectra/accordion::context.headerId"
	data-wp-on--click="spectra/accordion::actions.toggleAnswer"
	data-wp-bind--aria-controls="spectra/accordion::context.detailsId"
	data-wp-bind--aria-expanded="spectra/accordion::context.isExpanded"
	data-wp-class--is-active="spectra/accordion::context.isExpanded"
	<?php if ( 'div' === $header_element ) : ?>
		role="button"
		data-wp-bind--tabindex="spectra/accordion::context.headerTabIndex"
		data-wp-on--keydown="spectra/accordion::actions.handleKeyDown"
		data-wp-bind--data-disabled="spectra/accordion::context.isDisabled"
		data-wp-bind--aria-disabled="spectra/accordion::context.isDisabled"
		data-wp-watch="spectra/accordion::callbacks.updateHeaderTabIndex"
	<?php else : ?>
		data-wp-bind--disabled="spectra/accordion::context.isDisabled"
		<?php if ( 'button' === $header_element ) : ?>
			type="button"
		<?php endif; ?>
	<?php endif; ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</<?php echo esc_html( $header_element ); ?>>
