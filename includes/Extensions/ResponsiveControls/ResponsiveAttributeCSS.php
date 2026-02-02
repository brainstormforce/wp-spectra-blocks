<?php
/**
 * Responsive Attribute CSS Generator.
 *
 * Handles the generation of CSS for block-specific responsive attributes.
 * Uses WordPress Style Engine for consistent, optimized CSS output.
 *
 * @package Spectra\Extensions\ResponsiveControls
 * @since 3.0.0
 */

namespace Spectra\Extensions\ResponsiveControls;

/**
 * Handles CSS generation for block-specific responsive attributes.
 *
 * This class provides:
 * - Attribute-to-CSS property mapping
 * - Mutually exclusive attribute handling
 * - CSS formatting and minification
 * - Integration with WordPress Style Engine
 *
 * @since 3.0.0
 */
class ResponsiveAttributeCSS {
	/**
	 * Attribute definitions per block.
	 *
	 * Maps block attributes to their CSS properties with optional:
	 * - CSS property name
	 * - Additional selector
	 * - State (like :hover)
	 * - Custom formatter callback
	 *
	 * @since 3.0.0
	 * @var array<string, array<string, array>>
	 */
	const ATTR_DEFINITIONS = array(
		'spectra/container'                    => array(
			'minWidth'                => array(
				'property' => 'min-width',
				'selector' => '.wp-block-spectra-container',
			),
			'orientationReverse'      => array(),
			'minHeight'               => array(
				'property' => 'min-height',
				'selector' => '.wp-block-spectra-container',
			),
			'maxWidth'                => array(
				'property' => 'max-width',
				'selector' => '.wp-block-spectra-container',
			),
			'maxHeight'               => array(
				'property' => 'max-height',
				'selector' => '.wp-block-spectra-container',
			),
			'width'                   => array(
				'property' => 'width',
				'selector' => '.wp-block-spectra-container',
			),
			'height'                  => array(
				'property' => 'height',
				'default'  => 'auto',
				'selector' => '.wp-block-spectra-container',
			),
			'background'              => array(
				'formatter' => 'format_background',
			),
			'overlayType'             => array(
				'formatter' => 'format_overlay_type',
			),
			'overlayImage'            => array(
				'formatter' => 'format_overlay_image',
			),
			'overlayPosition'         => array(
				'formatter' => 'format_overlay_position',
			),
			'overlayPositionMode'     => array(),
			'overlayPositionCentered' => array(),
			'overlayPositionX'        => array(),
			'overlayPositionY'        => array(),
			'overlayAttachment'       => array(
				'formatter' => 'format_overlay_attachment',
			),
			'overlayRepeat'           => array(
				'formatter' => 'format_overlay_repeat',
			),
			'overlaySize'             => array(
				'formatter' => 'format_overlay_size',
			),
			'overlayCustomWidth'      => array(),
			'overlayBlendMode'        => array(
				'formatter' => 'format_overlay_blend_mode',
			),
			'overlayOpacity'          => array(
				'formatter' => 'format_overlay_opacity',
			),
		),
		'spectra/content'                      => array(
			// Text shadow attributes - handled specially in generate_css method.
			'enableTextShadow'  => array(),
			'textShadowColor'   => array(),
			'textShadowBlur'    => array(),
			'textShadowOffsetX' => array(),
			'textShadowOffsetY' => array(),
		),
		'spectra/google-map'                   => array(
			'height' => array(
				'property' => 'height',
				'default'  => '400px',
				'selector' => '.wp-block-spectra-google-map',
			),
		),
		'spectra/button'                       => array(
			'size' => array( 
				'default'   => '16px',
				'selector'  => ' svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),

			'gap'  => array( 
				'default'  => '10px',
				'property' => 'gap',
				'selector' => '.wp-block-spectra-button.wp-block-button__link',
			),
		),
		'spectra/icon'                         => array(
			'size' => array( 
				'default'   => '48px',
				'selector'  => ' svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/accordion'                    => array(
			'size' => array(
				'default'   => '24px',
				'selector'  => ' span span svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/accordion-child-header-icon'  => array(
			'size' => array(
				'selector'  => '.wp-block-spectra-accordion-child-header-icon svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/tabs'                         => array(
			'size' => array(
				'default'   => '16px',
				'selector'  => ' .wp-block-spectra-tabs-child-tab-button > svg',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/tabs-child-tab-button'        => array(
			'size' => array(
				'selector'  => '.wp-block-spectra-tabs-child-tab-button > svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
			'gap'  => array(
				'default'  => '10px',
				'property' => 'gap',
			),
		),
		'spectra/countdown'                    => array(
			'width'     => array(
				'property' => 'width',
			),
			'height'    => array(
				'property' => 'height',
				'default'  => 'auto',
			),
			'minWidth'  => array(
				'property' => 'min-width',
			),
			'minHeight' => array(
				'property' => 'min-height',
			),
			'maxWidth'  => array(
				'property' => 'max-width',
			),
			'maxHeight' => array(
				'property' => 'max-height',
			),
		),
		'spectra/counter'                      => array(
			'prefixRightMargin' => array(
				'property'  => 'margin-right',
				'default'   => '0px',
				'selector'  => ' .wp-block-spectra-counter-child-number .spectra-counter-prefix, .spectra-counter-progress-label .spectra-counter-prefix',
				'formatter' => 'format_counter_margin',
			),
			'suffixLeftMargin'  => array(
				'property'  => 'margin-left',
				'default'   => '0px',
				'selector'  => ' .wp-block-spectra-counter-child-number .spectra-counter-suffix, .spectra-counter-progress-label .spectra-counter-suffix',
				'formatter' => 'format_counter_margin',
			),
		),
		'spectra/list'                         => array(
			'iconSize' => array(
				'default'   => '10px',
				'selector'  => ' :where(.wp-block-spectra-list-child-icon) > svg',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/list-child-icon'              => array(
			'iconSize' => array(
				'selector'  => ' > svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/slider'                       => array(
			'sliderHeight'        => array(
				'default'   => 'auto',
				'formatter' => 'format_slider_height',
			),
			'navigationSize'      => array(
				'default'  => '40px',
				'property' => array( 'width', 'height' ),
				'selector' => ' .swiper-button-prev, .swiper-button-next',
			),
			'navigationIconSize'  => array(
				'default'   => '20px',
				'selector'  => ' .swiper-button-prev svg, .swiper-button-next svg',
				'formatter' => 'format_svg_size',
			),
			'arrowDistance'       => array(
				'formatter' => 'format_slider_arrow_distance',
			),
			'paginationTopMargin' => array(
				'selector'  => ' .swiper-pagination, .swiper-horizontal > .swiper-pagination-bullets, .swiper-pagination-bullets.swiper-pagination-horizontal, .swiper-pagination-custom, .swiper-pagination-fraction',
				'formatter' => 'format_slider_pagination_top_margin',
			),
			'background'          => array(
				'formatter' => 'format_background',
			),
		),
		'spectra/slider-child'                 => array(
			'background' => array(
				'formatter' => 'format_background',
			),
		),
		'spectra/separator'                    => array(
			'separatorWidth'  => array(),
			'separatorHeight' => array(),
			'separatorSize'   => array(),
			'separatorStyle'  => array(),
			'separatorAlign'  => array(),
		),
		'spectra/modal-child-button'           => array(
			'size' => array(
				'default'   => '16px',
				'selector'  => ' svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
			'gap'  => array(
				'default'  => '10px',
				'property' => 'gap',
				'selector' => '.wp-block-spectra-modal-child-button',
			),
		),
		'spectra/modal-child-icon'             => array(
			'size' => array(
				'default'   => '30px',
				'selector'  => ' svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/modal-child-popup-close-icon' => array(
			'size' => array(
				'default'   => '25px',
				'selector'  => ' svg.spectra-icon',
				'formatter' => 'format_svg_size',
			),
		),
		'spectra/modal-popup-content'          => array(
			'contentHeight'      => array(),
			'containerWidth'     => array(),
			'containerHeight'    => array(),
			'maxContainerHeight' => array(),
			'background'         => array(
				'formatter' => 'format_background',
			),
		),
		'spectra/popup-builder'                => array(
			'width'      => array(
				'property' => 'width',
				'selector' => ' .spectra-popup-builder__wrapper',
			),
			'height'     => array(
				'property'  => 'height',
				'formatter' => 'format_popup_builder_height',
			),
			// Background attributes.
			'background' => array(
				'formatter' => 'format_background',
			),
		),
		'core/image'                           => array(
			'aspectRatio' => array(
				'property' => 'aspect-ratio',
				'selector' => ' img',
			),
			'width'       => array(
				'property' => 'width',
				'selector' => ' img',
			),
			'height'      => array(
				'property' => 'height',
				'selector' => ' img',
			),
			'scale'       => array(
				'property'  => 'object-fit',
				'selector'  => ' img',
				'formatter' => 'format_image_scale',
			),
		),
	);

	/**
	 * Get responsive attributes for a specific block.
	 *
	 * @since 3.0.0
	 * @param string $block_name The name of the block.
	 * @return array<string> List of responsive attribute names.
	 */
	public static function get_responsive_attributes( string $block_name ): array {
		// Get attribute definitions with filter applied.
		$attr_definitions = apply_filters( 'spectra_responsive_attr_definitions', self::ATTR_DEFINITIONS );
		
		return array_keys( $attr_definitions[ $block_name ] ?? array() );
	}

	/**
	 * Generate CSS for a block's attributes.
	 *
	 * @since 3.0.0
	 * @param string               $block_name The block name/type.
	 * @param array<string, mixed> $attrs The block attributes.
	 * @param string               $selector The base CSS selector for the block.
	 * @param string               $background_selector Optional. Low-specificity selector for background CSS. Defaults to empty string.
	 * @param array                $block_attrs Optional. The block attributes. Defaults to empty array.
	 * @return string Generated CSS rules.
	 */
	public static function generate_css(
		string $block_name,
		array $attrs,
		string $selector,
		string $background_selector = '',
		array $block_attrs = array()
	): string {
		// Get attribute definitions with filter applied.
		$attr_definitions = apply_filters( 'spectra_responsive_attr_definitions', self::ATTR_DEFINITIONS );

		// Return empty string if no definitions exist for this block.
		if ( ! isset( $attr_definitions[ $block_name ] ) ) {
			return '';
		}

		// Make attributes globally available for formatters.
		global $current_block_attrs;
		$current_block_attrs = $attrs;

		$css_rules = array();
		$defs      = $attr_definitions[ $block_name ];
		
		// Special handling for spectra/content text shadow.
		if ( 'spectra/content' === $block_name && isset( $attrs['enableTextShadow'] ) && $attrs['enableTextShadow'] ) {
			// Text shadow settings.
			$text_shadow_color    = $attrs['textShadowColor'] ?? '';
			$text_shadow_blur     = $attrs['textShadowBlur'] ?? 2;
			$text_shadow_offset_x = $attrs['textShadowOffsetX'] ?? 1;
			$text_shadow_offset_y = $attrs['textShadowOffsetY'] ?? 1;

			// Generate text shadow CSS.
			if ( ! empty( $text_shadow_color ) ) {
				$offset_x = $text_shadow_offset_x . 'px';
				$offset_y = $text_shadow_offset_y . 'px';
				$blur     = $text_shadow_blur . 'px';
				
				$text_shadow_css = "{$offset_x} {$offset_y} {$blur} {$text_shadow_color}";
				
				// Use style_attr to ensure text-shadow is not filtered out by WordPress Style Engine.
				$css_rules[] = array(
					'selector'   => '',
					'style_attr' => 'text-shadow: ' . $text_shadow_css . ';',
				);
			}
		}

		// Special handling for spectra/modal-popup-content conditional height logic.
		if ( 'spectra/modal-popup-content' === $block_name ) {
			$content_height       = $attrs['contentHeight'] ?? 'auto';
			$container_width      = $attrs['containerWidth'] ?? '600px';
			$container_height     = $attrs['containerHeight'] ?? 'auto';
			$max_container_height = $attrs['maxContainerHeight'];

			$declarations = array(
				'width'      => $container_width ? $container_width : '600px',
				'max-width'  => '100%',
				'height'     => 'custom' === $content_height ? $container_height : '',
				'max-height' => 'auto' === $content_height ? $max_container_height : '',
			);

			$css_rules[] = array(
				'selector'     => $selector,
				'declarations' => $declarations,
			);
		}

		// Special handling for spectra/separator responsive attributes.
		if ( 'spectra/separator' === $block_name ) {
			$separator_width  = $attrs['separatorWidth'] ?? '100%';
			$separator_height = $attrs['separatorHeight'] ?? '3px';
			$separator_size   = $attrs['separatorSize'] ?? '5px';
			$separator_style  = $attrs['separatorStyle'] ?? 'solid';
			$separator_align  = $attrs['separatorAlign'] ?? 'center';

			// Check if it's a custom SVG style.
			$is_custom_svg = in_array( $separator_style, array( 'rectangles', 'parallelogram', 'slash', 'leaves' ), true );

			// Wrapper alignment (justify-content) - matches controller.php logic.
			$css_rules[] = array(
				'selector'     => $selector,
				'declarations' => array(
					'justify-content' => 'left' === $separator_align ? 'flex-start' : ( 'right' === $separator_align ? 'flex-end' : 'center' ),
				),
			);

			// Separator line declarations - only responsive layout properties.
			$declarations = array(
				'width'                      => $separator_width,
				'--spectra-separator-size'   => $separator_size,
				'--spectra-separator-height' => $separator_height,
				'margin-left'                => 'left' === $separator_align ? '0' : 'auto',
				'margin-right'               => 'left' === $separator_align ? 'auto' : ( 'right' === $separator_align ? '0' : 'auto' ),
			);

			// Generate custom SVG pattern with black color for mask.
			$encoded_color       = rawurlencode( 'black' );
			$custom_svg_patterns = array(
				'parallelogram' => "url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6.4 0H16L9.6 16H0L6.4 0Z' fill='{$encoded_color}'/%3E%3C/svg%3E\")",
				'rectangles'    => "url(\"data:image/svg+xml,%3Csvg width='8' height='16' viewBox='0 0 8 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='8' height='16' fill='{$encoded_color}'/%3E%3C/svg%3E\")",
				'slash'         => "url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M6.29312 16.9999L17 6.29302M14.2931 16.9999L17 14.293M-0.707031 15.9999L16.0002 -0.707153M8.00017 -0.707153L-0.706882 7.9999' stroke='{$encoded_color}'/%3E%3C/svg%3E\")",
				'leaves'        => "url(\"data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cg clip-path='url(%23clip0_2356_5631)'%3E%3Cpath d='M15 1C10.5 1 9 2.5 9 7C13.5 7 15 5.5 15 1Z' stroke='{$encoded_color}'/%3E%3Cpath d='M1 1C5.5 1 7 2.5 7 7C2.5 7 1 5.5 1 1Z' stroke='{$encoded_color}'/%3E%3Cpath d='M15 15C10.5 15 9 13.5 9 9C13.5 9 15 10.5 15 15Z' stroke='{$encoded_color}'/%3E%3Cpath d='M1 15C5.5 15 7 13.5 7 9C2.5 9 1 10.5 1 15Z' stroke='{$encoded_color}'/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id='clip0_2356_5631'%3E%3Crect width='16' height='16' fill='white'/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E\")",
			);

			// Mask CSS string.
			$mask_css = '';

			// Color variable used by Spectra.
			$color_var = 'var(--spectra-separator-color, currentColor)';

			if ( $is_custom_svg ) {
				$declarations['height']           = $separator_height;
				$declarations['background-color'] = $color_var;
				$declarations['border-top']       = 'none';

				// Add masks via style_attr to bypass Style Engine filtering.
				$mask_url = isset( $custom_svg_patterns[ $separator_style ] ) ? $custom_svg_patterns[ $separator_style ] : '';
				if ( $mask_url ) {
					$mask_css .= "mask: {$mask_url};";
					$mask_css .= 'mask-repeat: repeat-x;';
					$mask_css .= 'mask-position: center;';
					$mask_css .= 'mask-size: var(--spectra-separator-size, 5px) 100%;';
					$mask_css .= "-webkit-mask: {$mask_url};";
					$mask_css .= '-webkit-mask-repeat: repeat-x;';
					$mask_css .= '-webkit-mask-position: center;';
					$mask_css .= '-webkit-mask-size: var(--spectra-separator-size, 5px) 100%;';
				}           
			} elseif ( 'solid' === $separator_style ) {
				$declarations['height']           = $separator_height;
				$declarations['background-color'] = $color_var;
				$declarations['border-top']       = 'none';

				$mask_css = 'mask: none; -webkit-mask: none;';
			} else {
				// For border styles, use CSS variable for height that will be set by responsive controls.
				$declarations['border-top']       = "var(--spectra-separator-height, 3px) {$separator_style} {$color_var}";
				$declarations['background-color'] = 'transparent';
				$declarations['height']           = '0px';

				$mask_css = 'mask: none; -webkit-mask: none;';
			}

			$css_rules[] = array(
				'selector'     => $selector . ' .spectra-separator-line',
				'declarations' => $declarations,
			);

			if ( ! empty( $mask_css ) ) {
				$css_rules[] = array(
					'selector'   => $selector . ' .spectra-separator-line',
					'style_attr' => $mask_css,
				);
			}
		}

		foreach ( $defs as $attr => $def ) {
			// Get attribute value or use default if not set.
			$value = isset( $attrs[ $attr ] ) ? $attrs[ $attr ] : ( $def['default'] ?? null );

			// Skip if value is null (no attribute and no default).
			if ( null === $value && ! isset( $def['formatter'] ) ) {
				continue;
			}

			// Handle formatted attributes (complex values that need processing).
			if ( isset( $def['formatter'] ) ) {
				$formatter = $def['formatter'];
				$formatted = null;

				// Convert string formatter to callable if it's a method in this class.
				if ( is_string( $formatter ) && method_exists( __CLASS__, $formatter ) ) {
					$formatter = array( __CLASS__, $formatter );
					// Pass special parameters for specific formatters.
					if ( 'format_background' === $formatter[1] && ! empty( $background_selector ) ) {
						$formatted = call_user_func( $formatter, $value, $def, $background_selector, $block_attrs );
					} elseif ( 'format_slider_height' === $formatter[1] ) {
						$formatted = call_user_func( $formatter, $value, $def, $selector );
					} elseif ( 'format_slider_arrow_distance' === $formatter[1] ) {
						$formatted = call_user_func( $formatter, $value, $def, $selector );
					} elseif ( 'format_popup_builder_height' === $formatter[1] ) {
						$formatted = call_user_func( $formatter, $value, $def, $selector, $block_attrs );
					} else {
						$formatted = call_user_func( $formatter, $value, $def, $block_attrs );
					}
				} elseif ( is_callable( $formatter ) ) {
					$formatted = call_user_func( $formatter, $value, $def );
				}

				// Skip if formatter was invalid or returned null.
				if ( null === $formatted ) {
					continue;
				}

				if ( is_array( $formatted ) ) {
					// Handle single rule or multiple rules returned by formatter.
					if ( isset( $formatted['selector'] ) && ( isset( $formatted['declarations'] ) || isset( $formatted['style_attr'] ) ) ) {
						$css_rules[] = $formatted;
					} elseif ( isset( $formatted[0] ) && is_array( $formatted[0] ) && isset( $formatted[0]['selector'] ) ) {
						// Multiple rules - add them all.
						foreach ( $formatted as $rule ) {
							$css_rules[] = $rule;
						}
					} else {
						$css_rules[] = array(
							'selector'     => self::build_full_selector( $selector, $def ),
							'declarations' => $formatted,
						);
					}
					continue;
				}

				// Single property formatters return a formatted value.
				$value = $formatted;
			}

			// Skip if no CSS property is defined for this attribute.
			if ( ! isset( $def['property'] ) ) {
				continue;
			}

			// Add standard CSS rule.
			if ( is_array( $def['property'] ) ) {
				$declarations = array();
				foreach ( $def['property'] as $property ) {
					$declarations[ $property ] = $value;
				}
				$css_rules[] = array(
					'selector'     => self::build_full_selector( $selector, $def ),
					'declarations' => $declarations,
				);
				continue;
			} else {
				$css_rules[] = array(
					'selector'     => self::build_full_selector( $selector, $def ),
					'declarations' => array(
						$def['property'] => $value,
					),
				);
			}       
		}

		// Separate rules with style_attr from regular rules.
		$style_attr_css = '';
		$regular_rules  = array();
		
		foreach ( $css_rules as $rule ) {
			if ( isset( $rule['style_attr'] ) ) {
				// Handle rules with style_attr directly.
				$full_selector   = $selector . ( $rule['selector'] ?? '' );
				$style_attr_css .= $full_selector . '{' . $rule['style_attr'] . '}';
			} else {
				$regular_rules[] = $rule;
			}
		}
		
		// Generate optimized CSS using WordPress Style Engine for regular rules.
		$style_engine_css = '';
		if ( ! empty( $regular_rules ) ) {
			$style_engine_css = wp_style_engine_get_stylesheet_from_css_rules(
				$regular_rules,
				array(
					'prettify' => false, // Output minified CSS.
				)
			);
		}
		
		// Combine both CSS outputs.
		return $style_engine_css . $style_attr_css;
	}

	/**
	 * Build full CSS selector with optional parts from definition.
	 *
	 * @param string $base_selector The base CSS selector.
	 * @param array  $def The attribute definition.
	 * @return string Complete CSS selector.
	 */
	private static function build_full_selector( string $base_selector, array $def ): string {
		$selector = $def['selector'] ?? '';
		$state    = $def['state'] ?? '';
		
		// Handle comma-separated selectors.
		if ( strpos( $selector, ',' ) !== false ) {
			// First, check if the selector starts with a space (descendant selector).
			$needs_space = strpos( $selector, ' ' ) === 0;
			
			$parts          = explode( ',', $selector );
			$combined_parts = array_map(
				function( $part ) use ( $base_selector, $state, $needs_space ) {
					// Trim the part but preserve the leading space logic.
					$part = trim( $part );
					
					// If original selector had leading space, ensure we add it back.
					if ( $needs_space && strpos( $part, ' ' ) !== 0 ) {
						$part = ' ' . $part;
					}
					
					return $base_selector . $part . $state;
				},
				$parts
			);
			return implode( ', ', $combined_parts );
		}
		
		// Single selector.
		return $base_selector . $selector . $state;
	}

	/**
	 * Format SVG size value for CSS.
	 * 
	 * Sets both width and height properties for SVG elements.
	 *
	 * @param mixed $val The size value.
	 * @return array CSS properties for SVG sizing.
	 */
	private static function format_svg_size( $val ): array {
		if ( is_null( $val ) ) {
			return array();
		}

		return array(
			'width'  => $val,
			'height' => $val,
		);
	}

	/**
	 * Format counter margin values with px unit.
	 * 
	 * Converts numeric margin values to CSS values with px unit.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed $val The margin value (numeric).
	 * @param array $def The attribute definition (unused).
	 * @param array $block_attrs The block attributes (unused).
	 * @return string The formatted value with px unit.
	 */
	private static function format_counter_margin( $val, $def = array(), $block_attrs = array() ): string {
		if ( is_null( $val ) ) {
			return '0px';
		}

		// If it's already a string with units, return as-is.
		if ( is_string( $val ) && preg_match( '/\d+(px|em|rem|%|vh|vw)$/', $val ) ) {
			return $val;
		}

		// Convert numeric value to px.
		return $val . 'px';
	}

	/**
	 * Format slider arrow distance for CSS.
	 * 
	 * Sets the positioning of slider navigation arrows based on the distance value.
	 * Uses the base selector to ensure CSS is scoped to the specific slider instance,
	 * preventing conflicts when multiple sliders are on the same page.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed  $val The distance value.
	 * @param array  $def The attribute definition.
	 * @param string $base_selector The scoped base selector for this block instance.
	 * @return array Array of CSS rule arrays.
	 */
	private static function format_slider_arrow_distance( $val, $def = array(), $base_selector = '' ): array {
		$val = is_null( $val ) ? '1px' : $val;
		return array(
			array(
				'selector'     => $base_selector . ' .swiper-button-prev',
				'declarations' => array(
					'left' => $val,
				),
			),
			array(
				'selector'     => $base_selector . ' .swiper-button-next',
				'declarations' => array(
					'right' => $val,
				),
			),
		);
	}

	/**
	 * Format slider pagination top margin for CSS.
	 *
	 * Sets CSS declarations based on the provided margin value.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed $val The margin value.
	 * @return array CSS declarations.
	 */
	private static function format_slider_pagination_top_margin( $val ): array {
		$bottom_value = ! is_null( $val ) ? $val : '0%';
		
		// Return single rule with bottom property to match frontend CSS.
		return array(
			array(
				'selector'     => ' .swiper-pagination, .swiper-horizontal > .swiper-pagination-bullets, .swiper-pagination-bullets.swiper-pagination-horizontal, .swiper-pagination-custom, .swiper-pagination-fraction',
				'declarations' => array(
					'bottom' => $bottom_value,
				),
				'style_attr'   => 'bottom: ' . $bottom_value . ';',
	
			),
		);
	}

		/**
		 * Format slider height for CSS.
		 *
		 * Sets height on slides and min-height on slide content when slider has responsive height.
		 * Generates multiple CSS rules to handle both the slide height and content min-height.
		 * Uses the base selector to ensure CSS is scoped to the specific slider instance.
		 * 
		 * @since 3.0.0
		 *
		 * @param mixed  $val The height value.
		 * @param array  $def The attribute definition.
		 * @param string $base_selector The scoped base selector for this block instance.
		 * @param array  $block_attrs The block attributes.
		 * @return array Array of CSS rule arrays.
		 */
	private static function format_popup_builder_height( $val, $def = array(), $base_selector = '', $block_attrs = array() ): array {
		$height_value = ! is_null( $val ) ? $val : 'auto';
		$rules        = array();
		
		$rules[] = array(
			'selector'     => $base_selector . ' .spectra-popup-builder__wrapper--banner',
			'declarations' => array(
				'height' => $height_value,
			),
		);

		if ( isset( $block_attrs['hasFixedHeight'] ) && $block_attrs['hasFixedHeight'] ) {
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__container.spectra-popup-builder__container--popup',
				'declarations' => array(
					'height' => $height_value,
				),
			);
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__container.spectra-popup-builder__container--banner',
				'declarations' => array(
					'height' => $height_value,
				),
			);
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__wrapper.spectra-popup-builder__wrapper--popup',
				'declarations' => array(
					'height' => $height_value,
				),
			);

		} else {
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__wrapper.spectra-popup-builder__wrapper--banner',
				'declarations' => array(
					'min-height' => $height_value,
					'height'     => 'auto',
				),
			);
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__container.spectra-popup-builder__container--popup',
				'declarations' => array(
					'max-height' => $height_value,
				),
			);
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__container.spectra-popup-builder__container--banner',
				'declarations' => array(
					'min-height' => $height_value,
				),
			);
			$rules[] = array(
				'selector'     => $base_selector . ' .spectra-popup-builder__wrapper.spectra-popup-builder__wrapper--popup',
				'declarations' => array(
					'max-height' => $height_value,
				),
			);
			
		}

		return $rules;
	}

	/**
	 * Format slider height for CSS.
	 *
	 * Sets height on the .swiper container and min-height on slider-child elements.
	 * Generates CSS rules that override default 250px min-height with responsive values.
	 * This ensures consistent slider height rendering between editor and frontend, including 100% values.
	 * Uses the base selector to ensure CSS is scoped to the specific slider instance.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed  $val The height value.
	 * @param array  $def The attribute definition.
	 * @param string $base_selector The scoped base selector for this block instance.
	 * @return array Array of CSS rule arrays.
	 */
	private static function format_slider_height( $val, $def = array(), $base_selector = '' ): array {
		$height_value = ! is_null( $val ) ? $val : 'auto';
		$rules        = array();
		
		// Set height on the .swiper container (matches editor's inline style on .swiper element).
		$rules[] = array(
			'selector'     => $base_selector . ' .swiper',
			'declarations' => array(
				'height' => $height_value,
			),
		);

		// Set min-height on slider-child to override default 250px.
		if ( 'auto' !== $height_value ) {
			$rules[] = array(
				'selector'     => $base_selector . ' .wp-block-spectra-slider-child',
				'declarations' => array(
					'min-height' => $height_value,
				),
			);
		}
		
		// Set min-height on slide-content to override default 250px including for 100% values.
		if ( 'auto' !== $height_value ) {
			$rules[] = array(
				'selector'     => $base_selector . ' .wp-block-spectra-slider-child .slide-content',
				'declarations' => array(
					'min-height' => $height_value,
				),
			);
		}

		return $rules;
	}

	/**
	 * Format background attribute for CSS.
	 *
	 * Generates actual background CSS properties for frontend, since style.scss no longer contains them.
	 * Handles complex background attributes including gradients, images, colors, and positioning.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed  $val The background attribute value.
	 * @param array  $def The attribute definition (contains selector info).
	 * @param string $background_selector Optional. Low-specificity selector for background CSS.
	 * @param array  $attrs The block attributes.
	 * @return array CSS declarations for background properties or multiple rules.
	 */
	private static function format_background( $val, $def = array(), $background_selector = '', $attrs = array() ): array {
		$rules     = array();
		$has_image = false;
		$has_video = false;
		if ( isset( $attrs['popupId'] ) && isset( $attrs['variantType'] ) && 'popup' === $attrs['variantType'] ) {
			$background_selector = ' .spectra-popup-builder__wrapper--popup';
			// If background type is null, we need to generate video wrapper CSS
			// We need to generate video wrapper CSS even if background is null.
			// This ensures proper visibility control across breakpoints.
			if ( is_null( $val ) ) {
				// For null backgrounds, explicitly hide the video wrapper with !important.
				$rules[] = array(
					'selector'   => '.has-video-background ' . $background_selector . '  .spectra-background-video__wrapper',
					'style_attr' => 'display: none !important;',
				);
				// For background type null and background set to some color. We need to add a opacity for it.
				$rules[] = array(
					'selector'   => $background_selector,
					'style_attr' => 'position: relative;',
				);
				
				// Create a new stacking context with z-index.
				$rules[] = array(
					'selector'   => $background_selector,
					'style_attr' => 'z-index: 0;',
				);
				
				$rules[] = array(
					'selector'   => $background_selector . '::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);

				$rules[] = array(
					'selector'   => '.spectra-background-color-hover ' . $background_selector . ':hover::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);
				return $rules;
			}

			// Return empty array if background is not an array.
			if ( ! is_array( $val ) ) {
				return array();
			}

			// Check for new background structure with type field.
			if ( isset( $val['type'] ) ) {
				if ( 'image' === $val['type'] ) {
					$has_image = true;
				} elseif ( 'video' === $val['type'] ) {
					$has_video = true;
				} elseif ( 'none' === $val['type'] ) {
					// For 'none' type, we still need to hide the video wrapper.
					$rules[] = array(
						'selector'   => '.has-video-background ' . $background_selector . '  .spectra-background-video__wrapper',
						'style_attr' => 'display: none !important;',
					);
					// For background type none and background set to some color. We need to add a opacity for it.
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => 'position: relative;',
					);
					
					// Create a new stacking context with z-index.
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => 'z-index: 0;',
					);

					$rules[] = array(
						'selector'   => $background_selector . '::before',
						'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
					);
					
					$rules[] = array(
						'selector'   => '.spectra-background-color-hover ' . $background_selector . ':hover::before',
						'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
					);
					return $rules;
				}
			} 

			// Also check for backgroundImage.
			if ( isset( $val['backgroundImage'] ) && ! empty( $val['backgroundImage'] ) ) {
				$has_image = true;
			}

			// Also check if media exists without type (another possible structure).
			if ( ! $has_image && ! $has_video && isset( $val['media'] ) && ! empty( $val['media'] ) ) {
				// Assume image if media exists but no type specified.
				$has_image = true;
			}
			// Add device-specific overlay control for responsive backgrounds.
			// For responsive controls, we create the overlay dynamically per breakpoint.
			if ( $has_image || $has_video ) {
				if ( $has_video ) {
					// For video backgrounds, create overlay directly on wrapper::after when needed.
					// Apply the background color/gradient to the overlay.
					$rules[] = array(
						'selector'   => $background_selector . ' > .spectra-background-video__wrapper::after',
						'style_attr' => 'content: ""; position: absolute; inset: 0; display: block; background: var(--spectra-background-gradient, var(--spectra-background-color)); z-index: 1;',
						'selector'   => $background_selector . '::before',
						'style_attr' => 'content: ""; position: absolute; display: block; inset: 0;',
					);

					$rules[] = array(
						'selector'   => $background_selector . ' > .spectra-background-video__wrapper',
						'style_attr' => 'z-index: -1',
					);
					
					// Add hover rules for video with overlay - the overlay itself changes on hover.
					// Use empty selector to maintain current element context.
					$rules[] = array(
						'selector'   => '.spectra-background-color-hover ' . $background_selector . ':hover > .spectra-background-video__wrapper::after',
						'style_attr' => 'background: var(--spectra-background-color-hover);',
					);
					
					$rules[] = array(
						'selector'   => '.spectra-background-gradient-hover ' . $background_selector . ':hover > .spectra-background-video__wrapper::after',
						'style_attr' => 'background: var(--spectra-background-gradient-hover);',
					);
					
				} else {
					// For image backgrounds, create the overlay without depending on classes.
					// This creates a ::before pseudo-element dynamically.
					// Use style_attr for properties that WordPress style engine might filter out.
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => 'position: relative;',
					);
					
					// Create a new stacking context with z-index.
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => 'z-index: 0;',
					);
					
					$rules[] = array(
						'selector'   => $background_selector . '::before',
						'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
					);
					
					
					// Add hover rules for overlay scenarios - override overlay background on hover.
					// Use :where() for low specificity to allow Global Styles override.
					$rules[] = array(
						'selector'   => ':where(.spectra-background-color-hover) ' . $background_selector . ':hover::before',
						'style_attr' => 'background: var(--spectra-background-color-hover);',
					);
					
					$rules[] = array(
						'selector'   => ':where(.spectra-background-gradient-hover) ' . $background_selector . ':hover::before',
						'style_attr' => 'background: var(--spectra-background-gradient-hover);',
					);
				}
			} else {
				// No overlay for this breakpoint - hide any existing overlay.
				// Use :where() for low specificity to allow Global Styles override.
				$rules[] = array(
					'selector'   => $background_selector . ':where()::before',
					'style_attr' => 'display: none;',
				);
				
				// Also hide video overlay if present.
				$rules[] = array(
					'selector'   => $background_selector . ':where() > .spectra-background-video__wrapper::after',
					'style_attr' => 'display: none;',
				);
				
				// For backgrounds without overlay, we still need hover functionality.
				if ( $has_video ) {
					// Add hover overlay for video backgrounds without overlay.
					$rules[] = array(
						'selector'   => $background_selector . '::before',
						'style_attr' => 'content: ""; position: absolute; display: block; inset: 0;',
					);

					$rules[] = array(
						'selector'   => $background_selector . ' > .spectra-background-video__wrapper',
						'style_attr' => 'z-index: -1',
					);
				} elseif ( $has_image ) {
					// For image backgrounds without overlay, create hover pseudo-element.
					// Ensure container is positioned for hover overlay.
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => 'position: relative;',
					);
					
					// Create ::before pseudo-element for hover only.
					// Use :where() for low specificity to allow Global Styles override.
					$rules[] = array(
						'selector'   => ':where(.spectra-background-color-hover) ' . $background_selector . ':hover::before',
						'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
					);
					
					$rules[] = array(
						'selector'   => ':where(.spectra-background-gradient-hover) ' . $background_selector . ':hover::before',
						'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
					);
					
				}
			}

			// Build the background CSS based on what's set.
			$declarations = array();

			// Control video wrapper visibility based on background type.
			// IMPORTANT: We must generate CSS for both video and non-video cases
			// to ensure proper override behavior across breakpoints.
			// Using style_attr only for the display property to preserve !important.
			if ( $has_video ) {
				// Show video wrapper for video breakpoints.
				$rules[] = array(
					'selector'   => $background_selector . ' > .spectra-background-video__wrapper',
					'style_attr' => 'display: block !important;',
				);
				
				// Position video wrapper to respect borders.
				// The video wrapper should be positioned inside the border area.
				$rules[] = array(
					'selector'   => $background_selector . ' > .spectra-background-video__wrapper',
					'style_attr' => 'top: 0; right: 0; bottom: 0; left: 0; box-sizing: border-box;',
				);

			} else {
				// Hide video wrapper for non-video breakpoints.
				// This is crucial - we MUST generate this CSS even if there's an image/color/gradient.
				$rules[] = array(
					'selector'   => '.has-video-background ' . $background_selector . ' .spectra-background-video__wrapper',
					'style_attr' => 'display: none !important;',
				);
			}

			// Always ensure the container maintains position relative for proper stacking.
			if ( $has_image || $has_video ) {
				$declarations['position'] = 'relative';
				
				// Position direct children above any overlays.
				$rules[] = array(
					'selector'     => $background_selector . ' > *:not(.spectra-background-video__wrapper)',
					'declarations' => array(
						'position' => 'relative',
						'z-index'  => '1',
					),
				);
			}

			// Continue with regular background processing for all breakpoints.
			
			// Handle background properties (size, position, repeat) even without an image.
			// These can be set independently in responsive controls.
			if ( isset( $val['backgroundSize'] ) || isset( $val['backgroundPosition'] ) || isset( $val['backgroundRepeat'] ) || isset( $val['positionMode'] ) || isset( $val['positionX'] ) || isset( $val['positionY'] ) ) {
				$css_vars = array();
				
				if ( isset( $val['backgroundSize'] ) ) {
					// Handle custom background size with width.
					if ( 'custom' === $val['backgroundSize'] ) {
						$width      = isset( $val['backgroundWidth'] ) ? $val['backgroundWidth'] : '100%';
						$css_vars[] = '--spectra-background-size: ' . $width . ' auto';
					} else {
						$css_vars[] = '--spectra-background-size: ' . $val['backgroundSize'];
					}
				}
				
				if ( isset( $val['backgroundRepeat'] ) ) {
					$css_vars[] = '--spectra-background-repeat: ' . $val['backgroundRepeat'];
				}
				
				if ( isset( $val['backgroundPosition'] ) ) {
					$bg_position = 'center center';
					if ( is_array( $val['backgroundPosition'] ) && isset( $val['backgroundPosition']['x'] ) && isset( $val['backgroundPosition']['y'] ) ) {
						$bg_position = ( $val['backgroundPosition']['x'] * 100 ) . '% ' . ( $val['backgroundPosition']['y'] * 100 ) . '%';
					} elseif ( is_string( $val['backgroundPosition'] ) ) {
						$bg_position = $val['backgroundPosition'];
					}
					$css_vars[] = '--spectra-background-position: ' . $bg_position;
				}
				
				if ( ! empty( $css_vars ) ) {
					// Add the CSS variables to the container.
					// Use background_selector if provided, otherwise use empty selector for current element.
					$bg_selector = ! empty( $background_selector ) ? $background_selector : '';
				
					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => implode( '; ', $css_vars ) . ';',
					);
				}
			}

			// Handle background image if set.
			if ( $has_image ) {
				$image_url = '';

					// Try multiple ways to extract image URL.
				if ( isset( $val['media']['url'] ) ) {
					// New structure with media.url.
					$image_url = 'url(' . esc_url( $val['media']['url'] ) . ')';
				} elseif ( isset( $val['media'] ) && is_string( $val['media'] ) ) {
					// Media as direct string URL.
					$image_url = 'url(' . esc_url( $val['media'] ) . ')';
				} elseif ( isset( $val['backgroundImage'] ) ) {
					// Legacy structure support.
					$bg_image = $val['backgroundImage'];

					if ( is_array( $bg_image ) ) {
						if ( isset( $bg_image['url'] ) ) {
							$image_url = 'url(' . esc_url( $bg_image['url'] ) . ')';
						}
					} elseif ( is_string( $bg_image ) ) {
						$image_url = 'url(' . esc_url( $bg_image ) . ')';
					}
				} elseif ( isset( $val['url'] ) ) {
					// Direct URL property.
					$image_url = 'url(' . esc_url( $val['url'] ) . ')';
				}

				if ( $image_url ) {
					// Get background properties.
					$bg_size = 'cover';
					if ( isset( $val['backgroundSize'] ) ) {
						if ( 'custom' === $val['backgroundSize'] ) {
							$width   = isset( $val['backgroundWidth'] ) ? $val['backgroundWidth'] : '100%';
							$bg_size = $width . ' auto';
						} else {
							$bg_size = $val['backgroundSize'];
						}
					}
					$bg_repeat   = isset( $val['backgroundRepeat'] ) ? $val['backgroundRepeat'] : 'no-repeat';
					$bg_position = 'center center';

					if ( isset( $val['backgroundPosition'] ) ) {
						if ( is_array( $val['backgroundPosition'] ) && isset( $val['backgroundPosition']['x'] ) && isset( $val['backgroundPosition']['y'] ) ) {
							$bg_position = ( $val['backgroundPosition']['x'] * 100 ) . '% ' . ( $val['backgroundPosition']['y'] * 100 ) . '%';
						} elseif ( is_string( $val['backgroundPosition'] ) ) {
							$bg_position = $val['backgroundPosition'];
						}
					}

					// Use style_attr to ensure background properties are preserved.
					// Use background_selector if provided, otherwise use empty selector for current element.

					// Get background attachment.
					$bg_attachment = isset( $val['backgroundAttachment'] ) ? $val['backgroundAttachment'] : 'scroll';

					$rules[] = array(
						'selector'   => $background_selector,
						'style_attr' => sprintf(
							'background-image: %s; background-size: %s; background-position: %s; background-repeat: %s; background-attachment: %s;',
							$image_url,
							$bg_size,
							$bg_position,
							$bg_repeat,
							$bg_attachment
						),
					);
				}
			}

			// If we have main declarations, add them as first rule.
			if ( ! empty( $declarations ) ) {
				array_unshift(
					$rules,
					array(
						'selector'     => '',
						'declarations' => $declarations,
					) 
				);
			}

			// Return rules if we have any, otherwise return declarations for backward compatibility.
			if ( ! empty( $rules ) ) {
				return $rules;
			}

			return ! empty( $declarations ) ? $declarations : array();
		}

		// If background type is null, we need to generate video wrapper CSS
		// We need to generate video wrapper CSS even if background is null.
		// This ensures proper visibility control across breakpoints.
		if ( is_null( $val ) ) {
			// For null backgrounds, explicitly hide the video wrapper with !important.
			$rules[] = array(
				'selector'   => '.has-video-background  .spectra-background-video__wrapper',
				'style_attr' => 'display: none !important;',
			);
			// For background type null and background set to some color. We need to add a opacity for it.
			$rules[] = array(
				'selector'   => '',
				'style_attr' => 'position: relative;',
			);
			
			$rules[] = array(
				'selector'   => '::before',
				'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; pointer-events: none; display: block;',
			);

			$rules[] = array(
				'selector'   => '.spectra-background-color-hover:hover::before',
				'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
			);
			return $rules;
		}

		// Return empty array if background is not an array.
		if ( ! is_array( $val ) ) {
			return array();
		}

		// Check for new background structure with type field.
		if ( isset( $val['type'] ) ) {
			if ( 'image' === $val['type'] ) {
				$has_image = true;
			} elseif ( 'video' === $val['type'] ) {
				$has_video = true;
			} elseif ( 'none' === $val['type'] ) {
				// For 'none' type, we still need to hide the video wrapper.
				$rules[] = array(
					'selector'   => '.has-video-background  .spectra-background-video__wrapper',
					'style_attr' => 'display: none !important;',
				);
				// For background type none and background set to some color. We need to add a opacity for it.
				$rules[] = array(
					'selector'   => '',
					'style_attr' => 'position: relative;',
				);
				
				$rules[] = array(
					'selector'   => '::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);
				
				$rules[] = array(
					'selector'   => '.spectra-background-color-hover:hover::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);
				return $rules;
			}
		} 

		// Also check for backgroundImage.
		if ( isset( $val['backgroundImage'] ) && ! empty( $val['backgroundImage'] ) ) {
			$has_image = true;
		}

		// Also check if media exists without type (another possible structure).
		if ( ! $has_image && ! $has_video && isset( $val['media'] ) && ! empty( $val['media'] ) ) {
			// Assume image if media exists but no type specified.
			$has_image = true;
		}

		// Add device-specific overlay control for responsive backgrounds.
		// For responsive controls, we create the overlay dynamically per breakpoint.
		if ( $has_image || $has_video ) {
			if ( $has_video ) {
				// For video backgrounds, create overlay directly on wrapper::after when needed.
				// Apply the background color/gradient to the overlay.
				$rules[] = array(
					'selector'   => ' > .spectra-background-video__wrapper::after',
					'style_attr' => 'content: ""; position: absolute; inset: 0; display: block; background: var(--spectra-background-gradient, var(--spectra-background-color)); z-index: 1;',
					'selector'   => '::before',
					'style_attr' => 'content: ""; position: absolute; display: block; inset: 0;',
				);

				$rules[] = array(
					'selector'   => ' > .spectra-background-video__wrapper',
					'style_attr' => 'z-index: -1',
				);
				
				// Add hover rules for video with overlay - the overlay itself changes on hover.
				// Use empty selector to maintain current element context.
				$rules[] = array(
					'selector'   => '.spectra-background-color-hover:hover > .spectra-background-video__wrapper::after',
					'style_attr' => 'background: var(--spectra-background-color-hover);',
				);
				
				$rules[] = array(
					'selector'   => '.spectra-background-gradient-hover:hover > .spectra-background-video__wrapper::after',
					'style_attr' => 'background: var(--spectra-background-gradient-hover);',
				);
				
			} else {
				// For image backgrounds, create the overlay without depending on classes.
				// This creates a ::before pseudo-element dynamically.
				// Use style_attr for properties that WordPress style engine might filter out.
				$rules[] = array(
					'selector'   => '',
					'style_attr' => 'position: relative;',
				);
				
				$rules[] = array(
					'selector'   => '::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; pointer-events: none; display: block;',
				);
				
				
				// Add hover rules for overlay scenarios - override overlay background on hover.
				// Use :where() for low specificity to allow Global Styles override.
				$rules[] = array(
					'selector'   => ':where(.spectra-background-color-hover):hover::before',
					'style_attr' => 'background: var(--spectra-background-color-hover);',
				);
				
				$rules[] = array(
					'selector'   => ':where(.spectra-background-gradient-hover):hover::before',
					'style_attr' => 'background: var(--spectra-background-gradient-hover);',
				);

				if ( $has_image && isset( $attrs['variantType'] ) && 'popup' === $attrs['variantType'] ) {
						// For image backgrounds, create the overlay without depending on classes.
						// This creates a ::before pseudo-element dynamically.
						// Use style_attr for properties that WordPress style engine might filter out.
						$rules[] = array(
							'selector'   => ' .spectra-popup-builder__wrapper--popup',
							'style_attr' => 'position: relative;',
						);
						
						// Create a new stacking context with z-index.
						$rules[] = array(
							'selector'   => ' .spectra-popup-builder__wrapper--popup',
							'style_attr' => 'z-index: 0;',
						);
						
						$rules[] = array(
							'selector'   => ' .spectra-popup-builder__wrapper--popup::before',
							'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
						);
						
						// Add hover rules for overlay scenarios - override overlay background on hover.
						// Use :where() for low specificity to allow Global Styles override.
						$rules[] = array(
							'selector'   => ':where(.spectra-background-color-hover) .spectra-popup-builder__wrapper--popup:hover::before',
							'style_attr' => 'background: var(--spectra-background-color-hover);',
						);
						
						$rules[] = array(
							'selector'   => ':where(.spectra-background-gradient-hover) .spectra-popup-builder__wrapper--popup:hover::before',
							'style_attr' => 'background: var(--spectra-background-gradient-hover);',
						);
				}
			}
		} else {
			// No overlay for this breakpoint - hide any existing overlay.
			// Use :where() for low specificity to allow Global Styles override.
			$rules[] = array(
				'selector'   => ':where()::before',
				'style_attr' => 'display: none;',
			);
			
			// Also hide video overlay if present.
			$rules[] = array(
				'selector'   => ':where() > .spectra-background-video__wrapper::after',
				'style_attr' => 'display: none;',
			);
			
			// For backgrounds without overlay, we still need hover functionality.
			if ( $has_video ) {
				// Add hover overlay for video backgrounds without overlay.
				$rules[] = array(
					'selector'   => '::before',
					'style_attr' => 'content: ""; position: absolute; display: block; inset: 0;',
				);

				$rules[] = array(
					'selector'   => ' > .spectra-background-video__wrapper',
					'style_attr' => 'z-index: -1',
				);
			} elseif ( $has_image ) {
				// For image backgrounds without overlay, create hover pseudo-element.
				// Ensure container is positioned for hover overlay.
				$rules[] = array(
					'selector'   => '',
					'style_attr' => 'position: relative;',
				);
				
				// Create ::before pseudo-element for hover only.
				// Use :where() for low specificity to allow Global Styles override.
				$rules[] = array(
					'selector'   => ':where(.spectra-background-color-hover):hover::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);
				
				$rules[] = array(
					'selector'   => ':where(.spectra-background-gradient-hover):hover::before',
					'style_attr' => 'content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: -1; pointer-events: none; display: block;',
				);
				
			}
		}

		// Build the background CSS based on what's set.
		$declarations = array();

		// Control video wrapper visibility based on background type.
		// IMPORTANT: We must generate CSS for both video and non-video cases
		// to ensure proper override behavior across breakpoints.
		// Using style_attr only for the display property to preserve !important.
		if ( $has_video ) {
			// Show video wrapper for video breakpoints.
			$rules[] = array(
				'selector'   => ' > .spectra-background-video__wrapper',
				'style_attr' => 'display: block !important;',
			);
			
			// Position video wrapper to respect borders.
			// The video wrapper should be positioned inside the border area.
			$rules[] = array(
				'selector'   => ' > .spectra-background-video__wrapper',
				'style_attr' => 'top: 0; right: 0; bottom: 0; left: 0; box-sizing: border-box;',
			);

		} else {
			// Hide video wrapper for non-video breakpoints.
			// This is crucial - we MUST generate this CSS even if there's an image/color/gradient.
			$rules[] = array(
				'selector'   => '.has-video-background .spectra-background-video__wrapper',
				'style_attr' => 'display: none !important;',
			);
		}

		// Always ensure the container maintains position relative for proper stacking.
		if ( $has_image || $has_video ) {
			$declarations['position'] = 'relative';
			
			// Position direct children above any overlays.
			$rules[] = array(
				'selector'     => ' > *:not(.spectra-background-video__wrapper)',
				'declarations' => array(
					'position' => 'relative',
					'z-index'  => '1',
				),
			);
		}

		// Continue with regular background processing for all breakpoints.
		
		// Handle background properties (size, position, repeat, attachment) even without an image.
		// These can be set independently in responsive controls.
		if ( isset( $val['backgroundSize'] ) || isset( $val['backgroundPosition'] ) || isset( $val['backgroundRepeat'] ) || isset( $val['backgroundAttachment'] ) || isset( $val['positionMode'] ) || isset( $val['positionX'] ) || isset( $val['positionY'] ) ) {
			$css_vars = array();

			if ( isset( $val['backgroundSize'] ) ) {
				// Handle custom background size with width.
				if ( 'custom' === $val['backgroundSize'] ) {
					$width      = isset( $val['backgroundWidth'] ) ? $val['backgroundWidth'] : '100%';
					$css_vars[] = '--spectra-background-size: ' . $width . ' auto';
				} else {
					$css_vars[] = '--spectra-background-size: ' . $val['backgroundSize'];
				}
			}

			if ( isset( $val['backgroundRepeat'] ) ) {
				$css_vars[] = '--spectra-background-repeat: ' . $val['backgroundRepeat'];
			}

			if ( isset( $val['backgroundAttachment'] ) ) {
				$css_vars[] = '--spectra-background-attachment: ' . esc_attr( $val['backgroundAttachment'] );
			}

			if ( isset( $val['backgroundPosition'] ) || isset( $val['positionMode'] ) || isset( $val['positionX'] ) || isset( $val['positionY'] ) ) {
				$bg_position = 'center center';

				// Handle custom positioning mode with any unit.
				if ( isset( $val['positionMode'] ) && 'custom' === $val['positionMode'] ) {
					// If centralized position is enabled, force both to 50%.
					if ( isset( $val['positionCentered'] ) && $val['positionCentered'] ) {
						$bg_position = '50% 50%';
					} else {
						$x_pos       = isset( $val['positionX'] ) ? $val['positionX'] : '0%';
						$y_pos       = isset( $val['positionY'] ) ? $val['positionY'] : '0%';
						$bg_position = $x_pos . ' ' . $y_pos;
					}
				} elseif ( isset( $val['backgroundPosition'] ) ) {
					// Handle default focal point mode.
					if ( is_array( $val['backgroundPosition'] ) && isset( $val['backgroundPosition']['x'] ) && isset( $val['backgroundPosition']['y'] ) ) {
						$bg_position = ( $val['backgroundPosition']['x'] * 100 ) . '% ' . ( $val['backgroundPosition']['y'] * 100 ) . '%';
					} elseif ( is_string( $val['backgroundPosition'] ) ) {
						$bg_position = $val['backgroundPosition'];
					}
				}
				$css_vars[] = '--spectra-background-position: ' . $bg_position;
			}
			
			if ( ! empty( $css_vars ) ) {
				// Add the CSS variables to the container.
				// Use background_selector if provided, otherwise use empty selector for current element.
				$bg_selector = ! empty( $background_selector ) ? $background_selector : '';
				if ( isset( $attrs['variantType'] ) && 'popup' === $attrs['variantType'] ) {
					$bg_selector = ' .spectra-popup-builder__wrapper--popup ';
				}
				$rules[] = array(
					'selector'   => $bg_selector,
					'style_attr' => implode( '; ', $css_vars ) . ';',
				);
			}
		}

		// Handle background image if set.
		if ( $has_image ) {
			$image_url = '';

				// Try multiple ways to extract image URL.
			if ( isset( $val['media']['url'] ) ) {
				// New structure with media.url.
				$image_url = 'url(' . esc_url( $val['media']['url'] ) . ')';
			} elseif ( isset( $val['media'] ) && is_string( $val['media'] ) ) {
				// Media as direct string URL.
				$image_url = 'url(' . esc_url( $val['media'] ) . ')';
			} elseif ( isset( $val['backgroundImage'] ) ) {
				// Legacy structure support.
				$bg_image = $val['backgroundImage'];

				if ( is_array( $bg_image ) ) {
					if ( isset( $bg_image['url'] ) ) {
						$image_url = 'url(' . esc_url( $bg_image['url'] ) . ')';
					}
				} elseif ( is_string( $bg_image ) ) {
					$image_url = 'url(' . esc_url( $bg_image ) . ')';
				}
			} elseif ( isset( $val['url'] ) ) {
				// Direct URL property.
				$image_url = 'url(' . esc_url( $val['url'] ) . ')';
			}

			if ( $image_url ) {
				// Get background properties.
				$bg_size = 'cover';
				if ( isset( $val['backgroundSize'] ) ) {
					if ( 'custom' === $val['backgroundSize'] ) {
						$width   = isset( $val['backgroundWidth'] ) ? $val['backgroundWidth'] : '100%';
						$bg_size = $width . ' auto';
					} else {
						$bg_size = $val['backgroundSize'];
					}
				}
				$bg_repeat   = isset( $val['backgroundRepeat'] ) ? $val['backgroundRepeat'] : 'no-repeat';
				$bg_position = 'center center';

				// Handle background position based on mode.
				if ( isset( $val['positionMode'] ) && 'custom' === $val['positionMode'] ) {
					// Custom positioning mode with any unit support.
					// If centralized position is enabled, force both to 50%.
					if ( isset( $val['positionCentered'] ) && $val['positionCentered'] ) {
						$bg_position = '50% 50%';
					} else {
						$x_pos       = isset( $val['positionX'] ) ? $val['positionX'] : '0%';
						$y_pos       = isset( $val['positionY'] ) ? $val['positionY'] : '0%';
						$bg_position = $x_pos . ' ' . $y_pos;
					}
				} elseif ( isset( $val['backgroundPosition'] ) ) {
					// Default focal point mode.
					if ( is_array( $val['backgroundPosition'] ) && isset( $val['backgroundPosition']['x'] ) && isset( $val['backgroundPosition']['y'] ) ) {
						$bg_position = ( $val['backgroundPosition']['x'] * 100 ) . '% ' . ( $val['backgroundPosition']['y'] * 100 ) . '%';
					} elseif ( is_string( $val['backgroundPosition'] ) ) {
						$bg_position = $val['backgroundPosition'];
					}
				}

				// Use style_attr to ensure background properties are preserved.
				// Use background_selector if provided, otherwise use empty selector for current element.
				$bg_selector = ! empty( $background_selector ) ? $background_selector : '';
				if ( isset( $attrs['variantType'] ) && 'popup' === $attrs['variantType'] ) {
					$bg_selector = ' .spectra-popup-builder__wrapper--popup ';
				}

				// Get background attachment.
				$bg_attachment = isset( $val['backgroundAttachment'] ) ? $val['backgroundAttachment'] : 'scroll';

				$rules[] = array(
					'selector'   => $bg_selector,
					'style_attr' => sprintf(
						'background-image: %s; background-size: %s; background-position: %s; background-repeat: %s; background-attachment: %s;',
						$image_url,
						$bg_size,
						$bg_position,
						$bg_repeat,
						$bg_attachment
					),
				);
			}
		}

		// If we have main declarations, add them as first rule.
		if ( ! empty( $declarations ) ) {
			array_unshift(
				$rules,
				array(
					'selector'     => '',
					'declarations' => $declarations,
				) 
			);
		}

		// Return rules if we have any, otherwise return declarations for backward compatibility.
		if ( ! empty( $rules ) ) {
			return $rules;
		}

		return ! empty( $declarations ) ? $declarations : array();
	}


	/**
	 * Format overlay position for CSS.
	 *
	 * Generates CSS custom properties for overlay background position.
	 * Supports string positions (legacy), focal point coordinates, and custom positioning mode.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay position attribute value (e.g., 'center', 'top left', or focal point object).
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_position( $val, $def = array() ): array {
		global $current_block_attrs;
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] ) {
			return array();
		}

		$position_value = '50% 50%'; // Default center position.

		// Handle custom positioning mode with any unit.
		if ( isset( $current_block_attrs['overlayPositionMode'] ) && 'custom' === $current_block_attrs['overlayPositionMode'] ) {
			// If centralized position is enabled, force both to 50%.
			if ( isset( $current_block_attrs['overlayPositionCentered'] ) && $current_block_attrs['overlayPositionCentered'] ) {
				$position_value = '50% 50%';
			} else {
				$x_pos          = isset( $current_block_attrs['overlayPositionX'] ) ? $current_block_attrs['overlayPositionX'] : '0%';
				$y_pos          = isset( $current_block_attrs['overlayPositionY'] ) ? $current_block_attrs['overlayPositionY'] : '0%';
				$position_value = $x_pos . ' ' . $y_pos;
			}
		} elseif ( is_array( $val ) && isset( $val['x'] ) && isset( $val['y'] ) ) {
			// Handle focal point coordinates (default mode).
			$focal_x        = (float) $val['x'];
			$focal_y        = (float) $val['y'];
			$position_value = ( $focal_x * 100 ) . '% ' . ( $focal_y * 100 ) . '%';
		} elseif ( is_string( $val ) && ! empty( $val ) ) {
			// Handle string positions (legacy format).
			$position_value = $val;
		}

		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-position: ' . $position_value . ';',
			),
		);
	}

	/**
	 * Format overlay attachment for CSS.
	 * 
	 * Generates CSS custom properties for overlay background attachment.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay attachment attribute value (e.g., 'scroll', 'fixed').
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_attachment( $val, $def = array() ): array {
		if ( is_null( $val ) || '' === $val ) {
			return array();
		}
		global $current_block_attrs;
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] ) {
			return array();
		}
		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-attachment: ' . esc_attr( $val ) . ';',
			),
		);
	}

	/**
	 * Format overlay repeat for CSS.
	 * 
	 * Generates CSS custom properties for overlay background repeat.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay repeat attribute value (e.g., 'no-repeat', 'repeat').
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_repeat( $val, $def = array() ): array {
		if ( is_null( $val ) || '' === $val ) {
			return array();
		}
		global $current_block_attrs;
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] ) {
			return array();
		}
		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-repeat: ' . esc_attr( $val ) . ';',
			),
		);
	}

	/**
	 * Format overlay size for CSS.
	 *
	 * Generates CSS custom properties for overlay background size.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay size attribute value (e.g., 'cover', 'contain', 'auto', 'custom').
	 * @param array $def The attribute definition.
	 * @param array $block_attrs The block attributes.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_size( $val, $def = array(), $block_attrs = array() ): array {
		if ( is_null( $val ) || '' === $val ) {
			return array();
		}
		global $current_block_attrs;
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] ) {
			return array();
		}

		// Handle custom size with width.
		if ( 'custom' === $val ) {
			$width = isset( $current_block_attrs['overlayCustomWidth'] ) ? $current_block_attrs['overlayCustomWidth'] : '100%';
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-size: ' . $width . ' auto;',
				),
			);
		}

		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-size: ' . $val . ';',
			),
		);
	}

	/**
	 * Format overlay blend mode for CSS.
	 *
	 * Generates CSS custom properties for overlay mix-blend-mode.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay blend mode attribute value (e.g., 'normal', 'multiply', 'overlay').
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_blend_mode( $val, $def = array() ): array {
		global $current_block_attrs;
		
		// Check background type - if video, don't apply overlay.
		$background_type = $current_block_attrs['background']['type'] ?? null;
		if ( 'video' === $background_type ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-blend-mode: normal;',
				),
			);
		}
		
		// If overlayType is 'none' or not 'image', clear the overlay blend mode.
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] || 'none' === $current_block_attrs['overlayType'] ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-blend-mode: normal;',
				),
			);
		}
		
		if ( is_null( $val ) || '' === $val ) {
			$val = 'normal';
		}
		
		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-blend-mode: ' . esc_attr( $val ) . ';',
			),
		);
	}

	/**
	 * Format overlay opacity for CSS.
	 * 
	 * Generates CSS custom properties for overlay opacity.
	 *
	 * @since 3.0.0
	 * @param mixed $val The overlay opacity attribute value (0-100).
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_opacity( $val, $def = array() ): array {
		global $current_block_attrs;
		
		// Check background type - if video, don't apply overlay.
		$background_type = $current_block_attrs['background']['type'] ?? null;
		if ( 'video' === $background_type ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-opacity-value: 0;',
				),
			);
		}
		
		// If overlayType is 'none' or not 'image', clear the overlay opacity.
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] || 'none' === $current_block_attrs['overlayType'] ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-opacity-value: 0;',
				),
			);
		}

		if ( is_null( $val ) || '' === $val || false === $val || ! is_numeric( $val ) ) {
			$val = 50;
		}

		$opacity_decimal = ( (float) $val ) / 100;
		
		// Ensure we don't generate NaN.
		if ( ! is_finite( $opacity_decimal ) ) {
			$opacity_decimal = 0.5; // Default to 50%.
		}
		
		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-opacity-value: ' . $opacity_decimal . ';',
			),
		);
	}

	/**
	 * Format overlay image for CSS.
	 * 
	 * Generates CSS custom properties for overlay images.
	 *
	 * @param mixed $val The overlay image attribute value.
	 * @param array $def The attribute definition.
	 * @return array CSS rules with CSS custom properties.
	 */
	private static function format_overlay_image( $val, $def = array() ): array {
		global $current_block_attrs;
		
		// Check background type - if video, don't apply overlay.
		$background_type = $current_block_attrs['background']['type'] ?? null;
		if ( 'video' === $background_type ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-image: none;',
				),
			);
		}
		
		// If overlayType is 'none' or not 'image', clear the overlay image.
		if ( ! isset( $current_block_attrs['overlayType'] ) || 'image' !== $current_block_attrs['overlayType'] || 'none' === $current_block_attrs['overlayType'] ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-image: none;',
				),
			);
		}

		if ( is_null( $val ) || empty( $val ) ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-image: none;',
				),
			);
		}

		$image_url = '';
		if ( is_array( $val ) && isset( $val['url'] ) ) {
			$image_url = $val['url'];
		} elseif ( is_string( $val ) ) {
			$image_url = $val;
		}
		
		if ( empty( $image_url ) ) {
			return array(
				array(
					'selector'   => '',
					'style_attr' => '--spectra-overlay-image: none;',
				),
			);
		}

		return array(
			array(
				'selector'   => '',
				'style_attr' => '--spectra-overlay-image: url("' . esc_url( $image_url ) . '");',
			),
		);
	}

	/**
	 * Format overlay type for CSS.
	 * 
	 * This formatter doesn't generate CSS directly but ensures overlayType is available 
	 * in the global context for other overlay formatters to reference.
	 *
	 * @param mixed $val The overlay type attribute value.
	 * @param array $def The attribute definition.
	 * @return array Empty array - no CSS rules generated.
	 */
	private static function format_overlay_type( $val, $def = array() ): array {
		// This formatter doesn't generate CSS but ensures overlayType is tracked
		// for use by other overlay formatters.
		return array();
	}

	/**
	 * Format image scale value for CSS object-fit property.
	 *
	 * Converts WordPress image scale values to CSS object-fit values.
	 * 
	 * @since 3.0.0
	 *
	 * @param mixed $val The scale value.
	 * @return string CSS object-fit value.
	 */
	private static function format_image_scale( $val ): string {
		if ( is_null( $val ) ) {
			return 'fill';
		}

		// Map WordPress scale values to CSS object-fit values.
		$scale_map = array(
			'cover'      => 'cover',
			'contain'    => 'contain',
			'fill'       => 'fill',
			'none'       => 'none',
			'scaleDown'  => 'scale-down',
			'scale-down' => 'scale-down',
		);

		return isset( $scale_map[ $val ] ) ? $scale_map[ $val ] : 'fill';
	}
}
