<?php
/**
 * A `<template>` must survive `HtmlSanitizer::render()` with its attributes.
 *
 * WooCommerce's store notices (and any Interactivity API `data-wp-each` list)
 * print their markup inside a `<template>` a script clones. kses does not allow
 * the tag, so inside a Spectra container it was deleted and the notices region
 * rendered as an empty box.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Helpers;

use SpectraBlocks\Helpers\HtmlSanitizer;
use WP_UnitTestCase;

/**
 * HtmlSanitizerTemplateTest test case.
 *
 * @since 1.0.13
 */
class HtmlSanitizerTemplateTest extends WP_UnitTestCase {

	/**
	 * WooCommerce's notices template comes back with its directives.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_store_notices_template_survives() {
		$markup = '<div class="wc-block-components-notices" data-wp-interactive="woocommerce/store-notices">'
			. '<template data-wp-each--notice="state.notices" data-wp-each-key="context.notice.id">'
			. '<div class="wc-block-components-notice-banner"><span data-wp-text="context.notice.message"></span></div>'
			. '</template></div>';

		$rendered = HtmlSanitizer::render( $markup, null, false );

		$this->assertStringContainsString( '<template data-wp-each--notice="state.notices" data-wp-each-key="context.notice.id">', $rendered );
		$this->assertStringContainsString( '<span data-wp-text="context.notice.message"></span></div></template>', $rendered );
	}

	/**
	 * Inside a template, kses still strips what it strips anywhere.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_template_content_is_still_sanitized() {
		$rendered = HtmlSanitizer::render( '<template><img src="x" onerror="alert(1)"></template>', null, false );

		$this->assertStringNotContainsString( 'onerror', $rendered );
	}
}
