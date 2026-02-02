<?php
/**
 * Utility for re-using WP Kses-based sanitization rules.
 * 
 * @package Spectra\Helpers
 */

namespace Spectra\Helpers;

/**
 * Utility for re-using WP Kses-based sanitization rules.
 * 
 * @since 3.0.0
 */
class HtmlSanitizer {
	/**
	 * Comprehensive list of allowed HTML tags and attributes for all block types.
	 * 
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private static function get_allowed_tags(): array {
		// Basic tags.
		$allowed_tags = wp_kses_allowed_html( 'post' );

		// Custom tags.
		$custom_tags = array(
			/**
			 * Video tag with comprehensive attributes.
			 */
			'video'         => array(
				'src'      => true,
				'poster'   => true,
				'preload'  => true,
				'autoplay' => true,
				'loop'     => true,
				'muted'    => true,
				'controls' => true,
				'width'    => true,
				'height'   => true,
				'style'    => true,
				'class'    => true,
			),
			/**
			 * IFrame tag for embedded content like Google Maps.
			 */
			'iframe'        => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'style'           => true,
				'class'           => true,
				'title'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'referrerpolicy'  => true,
				'frameborder'     => true,
				'scrolling'       => true,
				'sandbox'         => true,
			),
			/**
			 * Embed tag for embedded content like Google Maps.
			 */
			'embed'         => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'style'           => true,
				'class'           => true,
				'title'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'referrerpolicy'  => true,
				'type'            => true,
			),
			/**
			 * Source tag for video/audio elements.
			 */
			'source'        => array(
				'src'   => true,
				'type'  => true,
				'media' => true,
				'sizes' => true,
				'class' => true,
			),
			'svg'           => array(
				'aria-controls'     => true,
				'aria-current'      => true,
				'aria-describedby'  => true,
				'aria-details'      => true,
				'aria-expanded'     => true,
				'aria-hidden'       => true,
				'aria-label'        => true,
				'aria-labelledby'   => true,
				'aria-live'         => true,
				'class'             => true,
				'role'              => true,
				'xmlns'             => true,
				'width'             => true,
				'height'            => true,
				'viewBox'           => true,
				'viewbox'           => true,
				'fill'              => true,
				'focusable'         => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'fill-rule'         => true,
				'stroke-linecap'    => true,
				'stroke-linejoin'   => true,
				'stroke-miterlimit' => true,
				'style'             => true, // Inline styles.
			),
			'path'          => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'transform'       => true,
				'style'           => true,
				'class'           => true,
			),
			'g'             => array(
				'transform' => true,
				'style'     => true,
				'class'     => true,
				'fill'      => true,
				'stroke'    => true,
			),
			'circle'        => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'rect'          => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
				'rx'           => true,
				'ry'           => true,
			),
			'line'          => array(
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'polygon'       => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'polyline'      => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'ellipse'       => array(
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'text'          => array(
				'x'           => true,
				'y'           => true,
				'fill'        => true,
				'stroke'      => true,
				'font-family' => true,
				'font-size'   => true,
				'text-anchor' => true,
				'style'       => true,
				'class'       => true,
				'transform'   => true,
			),
			'defs'          => array(
				'class' => true,
			),
			'clippath'      => array(
				'id'    => true,
				'class' => true,
			),
			'clipPath'      => array(
				'id'    => true,
				'class' => true,
			),
			'mask'          => array(
				'id'    => true,
				'class' => true,
			),
			/**
			 * Use tag with comprehensive attributes.
			 */
			'use'           => array(
				'xlink:href' => true,
				'href'       => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,    
			),
			/**
			 * Symbol tag with comprehensive attributes.
			 */
			'symbol'        => array(
				'id'      => true,
				'viewBox' => true,
				'viewbox' => true,  
			),
			/**
			 * Polyline tag with comprehensive attributes.
			 */
			'polyline'      => array(
				'points' => true,
			),

			/**
			 * Select tags with comprehensive attributes.
			 */
			'select'        => array(
				'name'                  => true,
				'id'                    => true,
				'class'                 => true,
				'multiple'              => true,
				'required'              => true,
				'disabled'              => true,
				'size'                  => true,
				'data-*'                => true,
				'style'                 => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
				'aria-atomic'           => true,
				'aria-autocomplete'     => true,
				'aria-busy'             => true,
				'aria-checked'          => true,
				'aria-colcount'         => true,
				'aria-colindex'         => true,
				'aria-colspan'          => true,
				'aria-controls'         => true,
				'aria-current'          => true,
				'aria-describedby'      => true,
				'aria-details'          => true,
				'aria-disabled'         => true,
				'aria-dropeffect'       => true,
				'aria-errormessage'     => true,
				'aria-expanded'         => true,
				'aria-flowto'           => true,
				'aria-grabbed'          => true,
				'aria-haspopup'         => true,
				'aria-hidden'           => true,
				'aria-invalid'          => true,
				'aria-keyshortcuts'     => true,
				'aria-label'            => true,
				'aria-labelledby'       => true,
				'aria-level'            => true,
				'aria-live'             => true,
				'aria-modal'            => true,
				'aria-multiline'        => true,
				'aria-multiselectable'  => true,
				'aria-orientation'      => true,
				'aria-owns'             => true,
				'aria-placeholder'      => true,
				'aria-posinset'         => true,
				'aria-pressed'          => true,
				'aria-readonly'         => true,
				'aria-relevant'         => true,
				'aria-required'         => true,
				'aria-roledescription'  => true,
				'aria-rowcount'         => true,
				'aria-rowindex'         => true,
				'aria-rowspan'          => true,
				'aria-selected'         => true,
				'aria-setsize'          => true,
				'aria-sort'             => true,
				'aria-valuemax'         => true,
				'aria-valuemin'         => true,
				'aria-valuenow'         => true,
				'aria-valuetext'        => true,
			),
			'option'        => array(
				'value'        => true,
				'selected'     => true,
				'disabled'     => true,
				'label'        => true,
				'option-value' => true,
				'data-*'       => true,
			),
			'optgroup'      => array(
				'label'    => true,
				'disabled' => true,
			),

			/**
			 * Input tag with comprehensive attributes.
			 */
			'input'         => array(
				'type'                  => array( 'search', 'checkbox', 'text', 'email', 'url', 'tel', 'number', 'hidden', 'file', 'password', 'date', 'datetime-local', 'time', 'month', 'week', 'radio' ),
				'name'                  => true,
				'value'                 => true,
				'id'                    => true,
				'class'                 => true,
				'placeholder'           => true,
				'required'              => true,
				'checked'               => true,
				'data-*'                => true,
				'style'                 => true,
				'min'                   => true,
				'max'                   => true,
				'step'                  => true,
				'pattern'               => true,
				'accept'                => true,
				'autocomplete'          => true,
				'disabled'              => true,
				'readonly'              => true,
				'form'                  => true,
				'formaction'            => true,
				'formenctype'           => true,
				'formmethod'            => true,
				'formnovalidate'        => true,
				'formtarget'            => true,
				'list'                  => true,
				'maxlength'             => true,
				'minlength'             => true,
				'multiple'              => true,
				'size'                  => true,
				'src'                   => true,
				'alt'                   => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
				'aria-atomic'           => true,
				'aria-autocomplete'     => true,
				'aria-busy'             => true,
				'aria-checked'          => true,
				'aria-colcount'         => true,
				'aria-colindex'         => true,
				'aria-colspan'          => true,
				'aria-controls'         => true,
				'aria-current'          => true,
				'aria-describedby'      => true,
				'aria-details'          => true,
				'aria-disabled'         => true,
				'aria-dropeffect'       => true,
				'aria-errormessage'     => true,
				'aria-expanded'         => true,
				'aria-flowto'           => true,
				'aria-grabbed'          => true,
				'aria-haspopup'         => true,
				'aria-hidden'           => true,
				'aria-invalid'          => true,
				'aria-keyshortcuts'     => true,
				'aria-label'            => true,
				'aria-labelledby'       => true,
				'aria-level'            => true,
				'aria-live'             => true,
				'aria-modal'            => true,
				'aria-multiline'        => true,
				'aria-multiselectable'  => true,
				'aria-orientation'      => true,
				'aria-owns'             => true,
				'aria-placeholder'      => true,
				'aria-posinset'         => true,
				'aria-pressed'          => true,
				'aria-readonly'         => true,
				'aria-relevant'         => true,
				'aria-required'         => true,
				'aria-roledescription'  => true,
				'aria-rowcount'         => true,
				'aria-rowindex'         => true,
				'aria-rowspan'          => true,
				'aria-selected'         => true,
				'aria-setsize'          => true,
				'aria-sort'             => true,
				'aria-valuemax'         => true,
				'aria-valuemin'         => true,
				'aria-valuenow'         => true,
				'aria-valuetext'        => true,
			),

			/**
			 * Form tag with comprehensive attributes.
			 */
			'form'          => array(
				'action'                => true,
				'method'                => true,
				'enctype'               => true,
				'id'                    => true,
				'class'                 => true,
				'name'                  => true,
				'target'                => true,
				'novalidate'            => true,
				'data-*'                => true,
				'style'                 => true,
				'autocomplete'          => true,
				'accept-charset'        => true,
				'form-id'               => true,
				'after-submission'      => true,
				'message-type'          => true,
				'success-url'           => true,
				'ajaxurl'               => true,
				'data-nonce'            => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
				'aria-atomic'           => true,
				'aria-autocomplete'     => true,
				'aria-busy'             => true,
				'aria-checked'          => true,
				'aria-colcount'         => true,
				'aria-colindex'         => true,
				'aria-colspan'          => true,
				'aria-controls'         => true,
				'aria-current'          => true,
				'aria-describedby'      => true,
				'aria-details'          => true,
				'aria-disabled'         => true,
				'aria-dropeffect'       => true,
				'aria-errormessage'     => true,
				'aria-expanded'         => true,
				'aria-flowto'           => true,
				'aria-grabbed'          => true,
				'aria-haspopup'         => true,
				'aria-hidden'           => true,
				'aria-invalid'          => true,
				'aria-keyshortcuts'     => true,
				'aria-label'            => true,
				'aria-labelledby'       => true,
				'aria-level'            => true,
				'aria-live'             => true,
				'aria-modal'            => true,
				'aria-multiline'        => true,
				'aria-multiselectable'  => true,
				'aria-orientation'      => true,
				'aria-owns'             => true,
				'aria-placeholder'      => true,
				'aria-posinset'         => true,
				'aria-pressed'          => true,
				'aria-readonly'         => true,
				'aria-relevant'         => true,
				'aria-required'         => true,
				'aria-roledescription'  => true,
				'aria-rowcount'         => true,
				'aria-rowindex'         => true,
				'aria-rowspan'          => true,
				'aria-selected'         => true,
				'aria-setsize'          => true,
				'aria-sort'             => true,
				'aria-valuemax'         => true,
				'aria-valuemin'         => true,
				'aria-valuenow'         => true,
				'aria-valuetext'        => true,
			),

			/**
			 * Textarea tag with comprehensive attributes.
			 */
			'textarea'      => array(
				'name'                  => true,
				'id'                    => true,
				'class'                 => true,
				'placeholder'           => true,
				'required'              => true,
				'data-*'                => true,
				'style'                 => true,
				'rows'                  => true,
				'cols'                  => true,
				'maxlength'             => true,
				'minlength'             => true,
				'wrap'                  => true,
				'readonly'              => true,
				'disabled'              => true,
				'form'                  => true,
				'autocomplete'          => true,
				'spellcheck'            => true,
				'autofocus'             => true,
				'dir'                   => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
				'aria-atomic'           => true,
				'aria-autocomplete'     => true,
				'aria-busy'             => true,
				'aria-checked'          => true,
				'aria-colcount'         => true,
				'aria-colindex'         => true,
				'aria-colspan'          => true,
				'aria-controls'         => true,
				'aria-current'          => true,
				'aria-describedby'      => true,
				'aria-details'          => true,
				'aria-disabled'         => true,
				'aria-dropeffect'       => true,
				'aria-errormessage'     => true,
				'aria-expanded'         => true,
				'aria-flowto'           => true,
				'aria-grabbed'          => true,
				'aria-haspopup'         => true,
				'aria-hidden'           => true,
				'aria-invalid'          => true,
				'aria-keyshortcuts'     => true,
				'aria-label'            => true,
				'aria-labelledby'       => true,
				'aria-level'            => true,
				'aria-live'             => true,
				'aria-modal'            => true,
				'aria-multiline'        => true,
				'aria-multiselectable'  => true,
				'aria-orientation'      => true,
				'aria-owns'             => true,
				'aria-placeholder'      => true,
				'aria-posinset'         => true,
				'aria-pressed'          => true,
				'aria-readonly'         => true,
				'aria-relevant'         => true,
				'aria-required'         => true,
				'aria-roledescription'  => true,
				'aria-rowcount'         => true,
				'aria-rowindex'         => true,
				'aria-rowspan'          => true,
				'aria-selected'         => true,
				'aria-setsize'          => true,
				'aria-sort'             => true,
				'aria-valuemax'         => true,
				'aria-valuemin'         => true,
				'aria-valuenow'         => true,
				'aria-valuetext'        => true,
			),

			/**
			 * Button tag with comprehensive attributes.
			 */
			'button'        => array_merge(
				$allowed_tags['button'] ?? array(),
				array(
					'form'                  => true,
					'formaction'            => true,
					'formenctype'           => true,
					'formmethod'            => true,
					'formnovalidate'        => true,
					'formtarget'            => true,
					'autofocus'             => true,
					'recaptcha-type'        => true,
					'data-sitekey'          => true,
					'data-callback'         => true,
					'data-error-callback'   => true,
					'data-size'             => true,
					// Explicit ARIA attributes.
					'aria-activedescendant' => true,
					'aria-atomic'           => true,
					'aria-autocomplete'     => true,
					'aria-busy'             => true,
					'aria-checked'          => true,
					'aria-colcount'         => true,
					'aria-colindex'         => true,
					'aria-colspan'          => true,
					'aria-controls'         => true,
					'aria-current'          => true,
					'aria-describedby'      => true,
					'aria-details'          => true,
					'aria-disabled'         => true,
					'aria-dropeffect'       => true,
					'aria-errormessage'     => true,
					'aria-expanded'         => true,
					'aria-flowto'           => true,
					'aria-grabbed'          => true,
					'aria-haspopup'         => true,
					'aria-hidden'           => true,
					'aria-invalid'          => true,
					'aria-keyshortcuts'     => true,
					'aria-label'            => true,
					'aria-labelledby'       => true,
					'aria-level'            => true,
					'aria-live'             => true,
					'aria-modal'            => true,
					'aria-multiline'        => true,
					'aria-multiselectable'  => true,
					'aria-orientation'      => true,
					'aria-owns'             => true,
					'aria-placeholder'      => true,
					'aria-posinset'         => true,
					'aria-pressed'          => true,
					'aria-readonly'         => true,
					'aria-relevant'         => true,
					'aria-required'         => true,
					'aria-roledescription'  => true,
					'aria-rowcount'         => true,
					'aria-rowindex'         => true,
					'aria-rowspan'          => true,
					'aria-selected'         => true,
					'aria-setsize'          => true,
					'aria-sort'             => true,
					'aria-valuemax'         => true,
					'aria-valuemin'         => true,
					'aria-valuenow'         => true,
					'aria-valuetext'        => true,
				)
			),

			/**
			 * Extending divs to allow for required accessibility attributes.
			 */
			'div'           => array_merge(
				$allowed_tags['div'] ?? array(),
				array(
					'focusable'             => true,
					'hidden'                => true,
					'tabindex'              => true,
					'class'                 => true,
					'id'                    => true,
					'style'                 => true,
					'data-*'                => true,
					'form-id'               => true,
					'after-submission'      => true,
					'message-type'          => true,
					'success-url'           => true,
					'ajaxurl'               => true,
					'data-nonce'            => true,
					'data-callback'         => true,
					'data-error-callback'   => true,
					'data-theme'            => true,
					'data-sitekey'          => true,
					'recaptcha-type'        => true,
					// Explicit ARIA attributes.
					'aria-activedescendant' => true,
					'aria-atomic'           => true,
					'aria-autocomplete'     => true,
					'aria-busy'             => true,
					'aria-checked'          => true,
					'aria-colcount'         => true,
					'aria-colindex'         => true,
					'aria-colspan'          => true,
					'aria-controls'         => true,
					'aria-current'          => true,
					'aria-describedby'      => true,
					'aria-details'          => true,
					'aria-disabled'         => true,
					'aria-dropeffect'       => true,
					'aria-errormessage'     => true,
					'aria-expanded'         => true,
					'aria-flowto'           => true,
					'aria-grabbed'          => true,
					'aria-haspopup'         => true,
					'aria-hidden'           => true,
					'aria-invalid'          => true,
					'aria-keyshortcuts'     => true,
					'aria-label'            => true,
					'aria-labelledby'       => true,
					'aria-level'            => true,
					'aria-live'             => true,
					'aria-modal'            => true,
					'aria-multiline'        => true,
					'aria-multiselectable'  => true,
					'aria-orientation'      => true,
					'aria-owns'             => true,
					'aria-placeholder'      => true,
					'aria-posinset'         => true,
					'aria-pressed'          => true,
					'aria-readonly'         => true,
					'aria-relevant'         => true,
					'aria-required'         => true,
					'aria-roledescription'  => true,
					'aria-rowcount'         => true,
					'aria-rowindex'         => true,
					'aria-rowspan'          => true,
					'aria-selected'         => true,
					'aria-setsize'          => true,
					'aria-sort'             => true,
					'aria-valuemax'         => true,
					'aria-valuemin'         => true,
					'aria-valuenow'         => true,
					'aria-valuetext'        => true,
				)
			),

			/**
			 * Label tag with comprehensive attributes.
			 */
			'label'         => array(
				'for'                   => true,
				'id'                    => true,
				'class'                 => true,
				'data-*'                => true,
				'style'                 => true,
				'form'                  => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
				'aria-atomic'           => true,
				'aria-autocomplete'     => true,
				'aria-busy'             => true,
				'aria-checked'          => true,
				'aria-colcount'         => true,
				'aria-colindex'         => true,
				'aria-colspan'          => true,
				'aria-controls'         => true,
				'aria-current'          => true,
				'aria-describedby'      => true,
				'aria-details'          => true,
				'aria-disabled'         => true,
				'aria-dropeffect'       => true,
				'aria-errormessage'     => true,
				'aria-expanded'         => true,
				'aria-flowto'           => true,
				'aria-grabbed'          => true,
				'aria-haspopup'         => true,
				'aria-hidden'           => true,
				'aria-invalid'          => true,
				'aria-keyshortcuts'     => true,
				'aria-label'            => true,
				'aria-labelledby'       => true,
				'aria-level'            => true,
				'aria-live'             => true,
				'aria-modal'            => true,
				'aria-multiline'        => true,
				'aria-multiselectable'  => true,
				'aria-orientation'      => true,
				'aria-owns'             => true,
				'aria-placeholder'      => true,
				'aria-posinset'         => true,
				'aria-pressed'          => true,
				'aria-readonly'         => true,
				'aria-relevant'         => true,
				'aria-required'         => true,
				'aria-roledescription'  => true,
				'aria-rowcount'         => true,
				'aria-rowindex'         => true,
				'aria-rowspan'          => true,
				'aria-selected'         => true,
				'aria-setsize'          => true,
				'aria-sort'             => true,
				'aria-valuemax'         => true,
				'aria-valuemin'         => true,
				'aria-valuenow'         => true,
				'aria-valuetext'        => true,
			),

			/**
			 * Canvas tag with comprehensive attributes.
			 */
			'canvas'        => array(
				'id'                    => true,
				'class'                 => true,
				'style'                 => true,
				'width'                 => true,
				'height'                => true,
				'data-*'                => true,
				// Explicit ARIA attributes.
				'aria-activedescendant' => true,
			),
			/**
			 * Script tag for structured data (JSON-LD only).
			 */
			'script'        => array(
				'type' => array( 'application/ld+json' ),
			),

			/**
			 * Style tag for structured data (text/css only).
			 */
			'style'         => array(
				'type' => array( 'text/css' ),
			),

			/**
			 * Custom element: presto-player for video player plugin.
			 */
			'presto-player' => array(
				'id'                => true,
				'class'             => true,
				'style'             => true,
				'preset'            => true,
				'branding'          => true,
				'chapters'          => true,
				'overlays'          => true,
				'tracks'            => true,
				'block-attributes'  => true,
				'analytics'         => true,
				'automations'       => true,
				'provider'          => true,
				'src'               => true,
				'media-title'       => true,
				'css'               => true,
				'skin'              => true,
				'icon-url'          => true,
				'preload'           => true,
				'poster'            => true,
				'youtube'           => true,
				'provider-video-id' => true,
				'video-id'          => true,
				'playsinline'       => true,
				'data-*'            => true,
			),
		);

		return array_merge( $allowed_tags, $custom_tags );
	}
	
	/**
	 * Extend allowed CSS properties for style attributes.
	 * 
	 * @since 3.0.0
	 * 
	 * @return void
	 */
	private static function allow_svg_css_properties() : void {
		// Add filter to extend safe CSS properties for SVG elements.
		add_filter( 'safe_style_css', array( __CLASS__, 'extend_safe_style_css' ) );
		
		// Add filter to allow CSS transform functions in style attributes.
		add_filter( 'safecss_filter_attr_allow_css', array( __CLASS__, 'allow_css_transform_functions' ), 10, 2 );
	}

	/**
	 * Extend safe CSS properties for style attributes.
	 * 
	 * @since 3.0.0
	 * 
	 * @param array $properties Existing safe CSS properties.
	 * @return array Extended safe CSS properties.
	 */
	public static function extend_safe_style_css( $properties ) {
		// Define additional CSS properties we want to allow for SVG elements and CSS variables.
		$new_properties = array(
			'fill',
			'fill-rule',
			'fill-opacity',
			'stroke',
			'stroke-width',
			'stroke-linecap',
			'stroke-linejoin',
			'stroke-miterlimit',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-opacity',
			'clip-rule',
			'clip-path',
			'transform',
			'transform-origin',
			'display',
			'visibility',
			'position',
			'box-shadow',
			'--*',  // Allow all CSS custom properties (variables).
		);

		// Merge with existing allowed properties.
		return array_merge( $properties, $new_properties );
	}

	/**
	 * Allows CSS transform properties with functions and CSS variables.
	 *
	 * This filter allows 'transform' properties with functions (e.g. 'transform: rotate(45deg)') 
	 * and CSS variables with modern syntax in style attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $allow_css Whether to allow the CSS property.
	 * @param string $css_test_string CSS property to test.
	 * @return bool Whether to allow the CSS property.
	 */
	public static function allow_css_transform_functions( $allow_css, $css_test_string ) {
		// Specifically allow transform properties with functions.
		if ( false !== strpos( $css_test_string, 'transform:' ) ) {
			return true;
		}
		
		// Allow CSS custom properties (variables).
		if ( 0 === strpos( trim( $css_test_string ), '--' ) ) {
			return true;
		}
		
		// Allow HSL color functions with modern syntax.
		if ( false !== strpos( $css_test_string, 'hsl(' ) ) {
			return true;
		}

		return $allow_css;
	}


	/**
	 * Sanitizes and outputs or returns HTML content.
	 * 
	 * When $echo is true, outputs the sanitized HTML directly. When false, returns the sanitized string.
	 * If `$echo_output` is true, the sanitized content is echoed directly.
	 * Otherwise, it is returned as a string.
	 * 
	 * @since 3.0.0
	 *
	 * @param string     $content      HTML content to sanitize.
	 * @param array|null $allowed_tags Optional. Custom allowed tags. Default null (uses default allowed tags).
	 * @param bool       $should_echo Optional. Whether to echo the sanitized content. Default true.
	 * @return string|void Sanitized HTML string if not echoed, otherwise nothing.
	 */
	public static function render( string $content, ?array $allowed_tags = null, bool $should_echo = true ) {
		// Use default full list if no custom tags provided.
		$allowed_tags = $allowed_tags ?? self::get_allowed_tags();
		
		// Special handling for SureForms CSS variables.
		if ( strpos( $content, 'srfm-form-container' ) !== false && strpos( $content, '<style>' ) !== false ) {
			// Extract style tag content.
			preg_match( '/<style>(.*?)<\/style>/s', $content, $style_matches );
			if ( ! empty( $style_matches[1] ) ) {
				$style_content = $style_matches[1];
				// Replace the style tag in content with a placeholder.
				$content = str_replace( $style_matches[0], '<!--STYLE_PLACEHOLDER-->', $content );
			}
		}
		
		// Temporarily allow SVG CSS properties during processing.
		self::allow_svg_css_properties();
		$sanitized = wp_kses( $content, $allowed_tags );
		
		// Clean up by removing our filters.
		remove_all_filters( 'safe_style_css' );
		remove_all_filters( 'safecss_filter_attr_allow_css' );
		
		// Restore style tag if it was extracted.
		if ( isset( $style_content ) && strpos( $sanitized, '<!--STYLE_PLACEHOLDER-->' ) !== false ) {
			$sanitized = str_replace( '<!--STYLE_PLACEHOLDER-->', '<style>' . $style_content . '</style>', $sanitized );
		}
		
		if ( $should_echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaping not required because it's already sanitized using wp_kses.
			echo $sanitized;
			return;
		}
				
		return $sanitized;
	}

	/**
	 * Validates SVG content structure and security.
	 * 
	 * @since 3.0.0
	 *
	 * @param string $svg_content Raw SVG content to validate.
	 * @return array Validation result with success status and error message.
	 */
	private static function validate_svg_structure( string $svg_content ) : array {
		// Check if content exists.
		if ( empty( $svg_content ) || ! is_string( $svg_content ) ) {
			return array(
				'success' => false,
				'error'   => 'SVG content is required and must be a string',
			);
		}

		// Check file size (1MB limit).
		$max_size = 1024 * 1024;
		if ( strlen( $svg_content ) > $max_size ) {
			return array(
				'success' => false,
				'error'   => 'SVG content exceeds maximum size limit of 1MB',
			);
		}

		// Check for basic SVG structure.
		if ( ! preg_match( '/<svg[^>]*>/i', $svg_content ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid SVG: Missing opening <svg> tag',
			);
		}

		if ( ! preg_match( '/<\/svg>/i', $svg_content ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid SVG: Missing closing </svg> tag',
			);
		}

		// Check for ONLY critical forbidden patterns in the raw content.
		// Note: We don't check for url() or other CSS patterns here because they're legitimate in SVG.
		// The element-based sanitization via wp_kses will handle attribute-level security.
		$critical_patterns = array(
			'script'     => '/<script[\s\S]*?<\/script>/i',
			'javascript' => '/javascript\s*:/i',
			'vbscript'   => '/vbscript\s*:/i',
			'event'      => '/\son\w+\s*=/i', // Only match event handlers with space before (on<event>=).
		);

		foreach ( $critical_patterns as $name => $pattern ) {
			if ( preg_match( $pattern, $svg_content ) ) {
				return array(
					'success' => false,
					'error'   => 'SVG contains potentially dangerous content',
				);
			}
		}

		// Basic XML validation.
		libxml_use_internal_errors( true );
		$doc    = new \DOMDocument();
		$result = $doc->loadXML( $svg_content );
		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ( ! $result || ! empty( $errors ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid SVG: XML parsing error',
			);
		}

		return array( 'success' => true );
	}

	/**
	 * Sanitizes SVG content specifically for icon blocks.
	 * 
	 * @since 3.0.0
	 *
	 * @param string $svg_content Raw SVG content to sanitize.
	 * @return string Sanitized SVG content, or empty string if validation fails.
	 * @throws \Exception If SVG content is completely removed during sanitization or corrupted.
	 */
	public static function sanitize_svg( string $svg_content ) : string {
		// First validate the structure.
		$validation = self::validate_svg_structure( $svg_content );
		if ( ! $validation['success'] ) {
			// Return empty string for invalid SVG instead of throwing exception.
			return '';
		}
		// Define allowed SVG tags and attributes.
		$allowed_svg_tags = array(
			'svg'      => array(
				'aria-controls'     => true,
				'aria-current'      => true,
				'aria-describedby'  => true,
				'aria-details'      => true,
				'aria-expanded'     => true,
				'aria-hidden'       => true,
				'aria-label'        => true,
				'aria-labelledby'   => true,
				'aria-live'         => true,
				'class'             => true,
				'role'              => true,
				'xmlns'             => true,
				'width'             => true,
				'height'            => true,
				'viewBox'           => true,
				'viewbox'           => true,
				'fill'              => true,
				'focusable'         => true,
				'stroke'            => true,
				'stroke-width'      => true,
				'fill-rule'         => true,
				'stroke-linecap'    => true,
				'stroke-linejoin'   => true,
				'stroke-miterlimit' => true,
				'style'             => true,
			),
			'g'        => array(
				'transform' => true,
				'style'     => true,
				'class'     => true,
				'fill'      => true,
				'stroke'    => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'transform'       => true,
				'style'           => true,
				'class'           => true,
			),
			'circle'   => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'rect'     => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
				'rx'           => true,
				'ry'           => true,
			),
			'line'     => array(
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'polygon'  => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'polyline' => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'ellipse'  => array(
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'style'        => true,
				'class'        => true,
				'transform'    => true,
			),
			'text'     => array(
				'x'           => true,
				'y'           => true,
				'fill'        => true,
				'stroke'      => true,
				'font-family' => true,
				'font-size'   => true,
				'text-anchor' => true,
				'style'       => true,
				'class'       => true,
				'transform'   => true,
			),
			'defs'     => array(
				'class' => true,
			),
			'clippath' => array(
				'id'    => true,
				'class' => true,
			),
			'clipPath' => array(
				'id'    => true,
				'class' => true,
			),
			'mask'     => array(
				'id'    => true,
				'class' => true,
			),
			'use'      => array(
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
				'transform'  => true,
				'style'      => true,
				'class'      => true,
			),
			'symbol'   => array(
				'id'      => true,
				'viewBox' => true,
				'viewbox' => true,
				'class'   => true,
			),
			'title'    => array(
				'id'    => true,
				'class' => true,
			),
			'desc'     => array(
				'id'    => true,
				'class' => true,
			),
		);

		// Preserve viewBox before sanitization as wp_kses sometimes strips it.
		$viewbox_value = '';
		if ( preg_match( '/viewBox\s*=\s*["\']([^"\']+)["\']/i', $svg_content, $matches ) ) {
			$viewbox_value = $matches[1];
		}
		
		// Temporarily allow SVG CSS properties during processing.
		self::allow_svg_css_properties();
		$sanitized = wp_kses( $svg_content, $allowed_svg_tags );
		
		// Clean up by removing our filters.
		remove_all_filters( 'safe_style_css' );
		remove_all_filters( 'safecss_filter_attr_allow_css' );
		
		// Re-add viewBox if it was stripped but we had one originally.
		if ( ! empty( $viewbox_value ) && ! preg_match( '/viewBox\s*=/i', $sanitized ) ) {
			$sanitized = preg_replace( '/(<svg[^>]*?)(\s*>)/i', '$1 viewBox="' . esc_attr( $viewbox_value ) . '"$2', $sanitized );
		}
		
		// Additional post-processing security checks.
		if ( empty( $sanitized ) ) {
			throw new \Exception( 'SVG content was completely removed during sanitization' );
		}

		// Ensure we still have a valid SVG after sanitization.
		if ( ! preg_match( '/<svg[^>]*>/i', $sanitized ) ) {
			throw new \Exception( 'SVG structure was corrupted during sanitization' );
		}

		// Remove any remaining dangerous patterns that might have slipped through.
		$dangerous_patterns = array(
			'/<script[\s\S]*?<\/script>/i',
			'/javascript:/i',
			'/vbscript:/i',
			'/on\w+\s*=/i',
		);

		foreach ( $dangerous_patterns as $pattern ) {
			$sanitized = preg_replace( $pattern, '', $sanitized );
		}
		
		return $sanitized;
	}
}
