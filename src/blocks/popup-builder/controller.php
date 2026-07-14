<?php
/**
 * Controller for rendering the Popup Builder block (V3)
 * Replicates V2 popup builder functionality with V3 architecture
 * Incorporates responsive CSS features from v2 frontend.css.php
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\PopupBuilder
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Set the attributes with v2 compatibility and responsive support.
$block_id           = $attributes['block_id'] ?? wp_unique_id( 'spectra-popup-' );
$variation_selected = $attributes['variationSelected'] ?? false;
$variant_type       = $attributes['variantType'] ?? 'popup';
$dim_ratio          = ( isset( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : 1 );
// Get attributes with fallbacks.
$popup_position_v          = $attributes['popupPositionV'] ?? 'top';
$popup_content_alignment_v = $attributes['popupContentAlignmentV'] ?? 'flex-start';
$popup_width               = $attributes['width'] ?? '';
$popup_height              = $attributes['height'] ?? '';

// Non-responsive attributes.
$has_fixed_height            = $attributes['hasFixedHeight'] ?? false;
$has_overlay                 = $attributes['hasOverlay'] ?? true;
$is_dismissable              = $attributes['isDismissable'] ?? true;
$halt_background_interaction = $attributes['haltBackgroundInteraction'] ?? true;
$will_push_content           = $attributes['willPushContent'] ?? true;
$close_icon                  = $attributes['closeIcon'] ?? 'xmark';
$close_icon_position         = $attributes['closeIconPosition'] ?? 'top-right';
$close_overlay_click         = $attributes['closeOverlayClick'] ?? true;
$close_escape_press          = $attributes['closeEscapePress'] ?? true;
$has_text_color              = ! empty( $attributes['textColor'] ?? '' );
// Overlay and styling attributes.
$close_icon_size     = $attributes['closeIconSize'] ?? '';
$popup_overlay_color = $attributes['popupOverlayColor'] ?? '';
$close_icon_rotation = $attributes['rotation'] ?? '';

// Get background attributes.
$background                = $attributes['background'] ?? array();
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';

// Background processing.
$responsive_controls  = $attributes['responsiveControls'] ?? array();
$background_type      = $background['type'] ?? '';
$has_image_background = 'image' === $background_type;
$background_styles    = Core::get_background_image_styles( $background, $background_gradient, $background_gradient_hover );
// Check if any breakpoint has video background, image background or overlay.
$has_video_background   = false;
$has_responsive_image   = false;
$video_background       = null;
$has_responsive_overlay = false;
foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
	if ( isset( $responsive_controls[ $device ]['background']['type'] ) ) {
		if ( 'video' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_video_background = true;
			// Store the first video found.
			if ( null === $video_background ) {
				$video_background = $responsive_controls[ $device ]['background'];
			}
		} elseif ( 'image' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_responsive_image = true;
		}
	}
}

// If we found a video background in any responsive breakpoint, ensure we render the video element.
// Even if desktop has no background, we need the video element for responsive switching.
if ( $has_video_background && null !== $video_background ) {
	// If background is not set or is 'none', use the video background to ensure video element renders.
	if ( ! $background || ( isset( $background['type'] ) && 'none' === $background['type'] ) ) {
		$background = $video_background;
	}
}

// Background specific values required for conditional classes.
$background_type = $background['type'] ?? '';

// Check if container has video background and border radius.
$has_border_radius = ! empty( $attributes['style']['border']['radius'] );

// Create CSS classes based on v2 structure.
$popup_classes = array(
	'spectra-popup-builder',
	'spectra-block-' . $block_id,
	'spectra-popup-builder--' . $variant_type,
	// Video background class is required for proper positioning (from common.scss).
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	$has_video_background ? 'has-video-background' : '',
	( $has_image_background || $has_responsive_image ) ? 'has-image-background' : '',
	// Add overlay class when overlay is used.
	$has_responsive_overlay ? 'spectra-background-overlay' : '',
	// Add background image class.
	( 'image' === $background_type || $has_responsive_image ) ? 'spectra-background-image' : '',
	$has_text_color ? 'has-text-color' : '',
);

// Add conditional classes matching v2 behavior.
if ( $variation_selected ) {
	$popup_classes[] = 'spectra-popup-builder--variation-selected';
}

if ( $has_overlay ) {
	$popup_classes[] = 'spectra-popup-builder--has-overlay';
}

if ( $is_dismissable ) {
	$popup_classes[] = 'spectra-popup-builder--dismissable';
}

if ( $halt_background_interaction ) {
	$popup_classes[] = 'spectra-popup-builder--halt-interaction';
}

if ( $will_push_content && 'banner' === $variant_type ) {
	$popup_classes[] = 'spectra-popup-builder--push-content';
}

if ( $has_fixed_height ) {
	$popup_classes[] = 'spectra-has-fixed-height';
} elseif ( ! isset( $has_fixed_height ) ) {
	$popup_classes[] = 'spectra-has-auto-height';
}

if ( $has_overlay ) {
	$popup_classes[] = 'spectra-popup-has-overlay';
}

// Position classes for banner variant.
if ( 'banner' === $variant_type && ! $will_push_content ) {
	$popup_classes[] = 'spectra-popup-builder--position-v-' . $popup_position_v;
}

if ( $has_fixed_height ) {
	$popup_classes[] = 'spectra-popup-builder--content-align-' . $popup_content_alignment_v;
}

// Close icon classes.
if ( $is_dismissable && $close_icon ) {
	$popup_classes[] = 'spectra-popup-builder--has-close';
	$popup_classes[] = 'spectra-popup-builder--close-' . $close_icon_position;
}

// Background type classes.
if ( ( 'video' === $background_type || $has_video_background ) ) {
	$popup_classes[] = 'has-video-background spectra-background-video';
}

if ( $has_image_background || $has_responsive_image ) {
	$popup_classes[] = 'has-image-background';
}

$background_styles = Core::get_background_image_styles( $background, $background_gradient, $background_gradient_hover );


// V3 styling configuration.
$style_configs = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradientHover' ),
	array(
		'key'        => 'dimRatio',
		'css_var'    => '--spectra-overlay-opacity',
		'class_name' => 'spectra-dim-ratio',
		'value'      => $dim_ratio,
	),
	array(
		'key'        => 'popupOverlayColor',
		'css_var'    => '--spectra-popup-overlay-color',
		'class_name' => 'spectra-popup-overlay-color',
		'value'      => $popup_overlay_color,
	),
	array(
		'key'        => 'closeIconColor',
		'css_var'    => '--spectra-close-icon-color',
		'class_name' => 'spectra-close-icon-color',
		'value'      => $attributes['closeIconColor'],
	),
	array(
		'key'        => 'closeIconColorHover',
		'css_var'    => '--spectra-close-icon-color-hover',
		'class_name' => 'spectra-close-icon-color-hover',
		'value'      => $attributes['closeIconColorHover'],
	),
	array(
		'key'        => 'closeIconSize',
		'css_var'    => '--spectra-close-icon-size',
		'class_name' => 'spectra-close-icon-size',
		'value'      => $close_icon_size . 'px',
	),
	array(
		'key'        => 'width',
		'css_var'    => '--spectra-popup-width',
		'class_name' => 'spectra-popup-width',
		'value'      => $popup_width,
	),
	array(
		'key'        => 'height',
		'css_var'    => '--spectra-popup-height',
		'class_name' => 'spectra-popup-height',
		'value'      => $popup_height,
	),
	array(
		'key'        => 'rotation',
		'css_var'    => '--spectra-rotate-close-icon',
		'class_name' => 'spectra-rotate-close-icon',
		'value'      => $close_icon_rotation . 'deg',
	),
);

// Add popup ID detection function.
if ( ! function_exists( 'spectra_get_popup_id' ) ) {
	/**
	 * Detect the actual popup post ID using multiple methods.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $block Block data (optional).
	 * @return int The popup post ID.
	 */
	function spectra_get_popup_id( $attributes = array(), $block = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Method 1: Check if we have popup ID in attributes (for shortcode/explicit rendering).
		if ( ! empty( $attributes['popupId'] ) ) {
			$popup_id        = (int) preg_replace( '/\D/', '', $attributes['popupId'] ); // \D matches any non-digit.
			$popup_post_type = get_post_type( $popup_id );
			// Accept both spectra-blocks-popup and spectra-popup (UAGB) to allow coexistence.
			if ( in_array( $popup_post_type, array( 'spectra-blocks-popup', 'spectra-popup' ), true ) ) {
				return $popup_id;
			}
		}

		// Fallback - use current page ID.
		return get_the_ID();
	}
}

// Get the actual popup ID using multiple detection methods.
$popup_id = spectra_get_popup_id( $attributes, $block ?? array() );

// Set global context to help other parts of the system.
$GLOBALS['spectra_current_popup_id'] = $popup_id;


// Get repetition meta values using the correct popup ID.
$repetition_meta = intval( get_post_meta( $popup_id, 'spectra-blocks-popup-repetition', true ) );
// Validate and sanitize repetition meta value.
$repetition_value = 1; // Default value.
if ( ! empty( $repetition_meta ) || 0 === $repetition_meta || '0' === $repetition_meta ) {
	$temp_value = intval( $repetition_meta );
	// Allow -1 (infinite) or positive integers only.
	if ( -1 === $temp_value || $temp_value > 0 ) {
		$repetition_value = $temp_value;
	}
}

$repeat_infinitely = -1 === $repetition_value;

$enable_cookies = $attributes['enableCookies'] ?? false;
$set_cookies_on = ! empty( $attributes['setCookiesOn'] ) ? $attributes['setCookiesOn'] : 'close-action';
$hide_for_days  = ! empty( $attributes['hideForDays'] ) ? $attributes['hideForDays'] : '2';

// Create popup context for JavaScript (matching v2 behavior).
$popup_context = array(
	'blockId'                   => $block_id,
	'variantType'               => $variant_type,
	'variationSelected'         => $variation_selected,
	'popupPositionV'            => $popup_position_v,
	'hasOverlay'                => $has_overlay,
	'isDismissable'             => $is_dismissable,
	'haltBackgroundInteraction' => $halt_background_interaction,
	'willPushContent'           => $will_push_content,
	'closeIcon'                 => $close_icon,
	'closeIconPosition'         => $close_icon_position,
	'closeOverlayClick'         => $close_overlay_click,
	'closeEscapePress'          => $close_escape_press,
	'hasFixedHeight'            => $has_fixed_height,
	'popupContentAlignmentV'    => $popup_content_alignment_v,
	'repeatInfinitely'          => $repeat_infinitely,
	'repetition'                => $repetition_value,
	'popupId'                   => $popup_id, // Actual popup ID for localStorage key compatibility.
	'isPushBanner'              => ( $will_push_content && 'banner' === $variant_type ),
	'enableCookies'             => $enable_cookies,
	'setCookiesOn'              => $set_cookies_on,
	'hideForDays'               => $hide_for_days,
);

// Wrapper attributes for V3 interactivity.
$wrapper_config = array(
	'id'                       => 'spectra-popup-builder-' . $popup_id,
	'data-block-id'            => $block_id,
	'data-variant-type'        => $variant_type,
	'data-has-overlay'         => $has_overlay ? 'true' : 'false',
	'data-dismissable'         => $is_dismissable ? 'true' : 'false',
	'data-close-overlay'       => $close_overlay_click ? 'true' : 'false',
	'data-close-escape'        => $close_escape_press ? 'true' : 'false',
	'data-halt-interaction'    => $halt_background_interaction ? 'true' : 'false',
	'data-push-content'        => $will_push_content ? 'true' : 'false',
	'data-has-fixed-height'    => $has_fixed_height ? 'true' : 'false',
	'data-popup-context'       => wp_json_encode( $popup_context ),
	'data-responsive-controls' => wp_json_encode( $responsive_controls ),
	'aria-modal'               => 'popup' === $variant_type ? 'true' : 'false',
	'role'                     => 'popup' === $variant_type ? 'dialog' : 'banner',
	'aria-hidden'              => 'true',
	'data-repetition'          => $repetition_value,
	'data-repeat-infinitely'   => $repeat_infinitely ? 'true' : 'false',
	'data-popup-id'            => $popup_id,
);

// Add responsive video data as data attribute for JavaScript.
$responsive_video_data = array();
if ( ! empty( $responsive_controls ) ) {
	foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
		if ( isset( $responsive_controls[ $device ]['background'], $responsive_controls[ $device ]['background']['type'] ) &&
		'video' === $responsive_controls[ $device ]['background']['type'] &&
		! empty( $responsive_controls[ $device ]['background']['media']['url'] ) ) {
			$responsive_video_data[ $device ] = $responsive_controls[ $device ]['background']['media']['url'];
		}
	}
}

if ( ! empty( $responsive_video_data ) ) {
	$wrapper_config['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}

// Get the block wrapper attributes using V3 system.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$style_configs,
	$wrapper_config,
	$popup_classes
);

$js             = '';
$is_push_banner = ( 'banner' === $attributes['variantType'] && $attributes['willPushContent'] );
$popup_timer    = $is_push_banner ? 500 : 100;

// Render the JS Script to handle this popup on the current page.
ob_start();
?>
	window.addEventListener( 'DOMContentLoaded', function() {
		const blockScope = document.getElementById('spectra-popup-builder-<?php echo esc_attr( strval( $popup_id ) ); ?>');
		if ( ! blockScope ) {
			return;
		}

		<?php
			// Either check if the localStorage has been set before - If not, create it.
			// Or if this popup has an updated repetition number, reset the localStorage.
		?>
		let popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		<?php
		// Use the already-sanitized `$repetition_value` (defaults to 1, honors
		// -1 for infinite) instead of re-reading the raw meta. The raw read
		// returns empty (→ intval 0) when the popup's repetition meta is not
		// explicitly set — the JS below then treats popupSesh[0]===0 as
		// "already shown enough" and calls blockScope.remove(), silently
		// deleting the popup wrapper from the DOM. This mirrors what every
		// other consumer of the value (data-repetition attribute, popup_context
		// JS config, $repeat_infinitely flag) already does.
		?>
		const repetition = <?php echo intval( $repetition_value ); ?>;
		if ( null === popupSesh || repetition !== popupSesh[1] ) {
			<?php // [0] is the updating repetition number, [1] is the original repetition number. ?>		
			const repetitionArray = [
				repetition,
				repetition,
			];
			localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( repetitionArray ) );
			popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		}

		if ( 0 === popupSesh[0] ) {
			blockScope.remove();
			return;
		}

		const theBody = document.querySelector( 'body' );

		blockScope.style.display = 'flex';
		setTimeout( () => {
			<?php
			// If this is a banner with push, render the max height instead of opacity on timeout.
			if ( $is_push_banner ) {
				?>
					blockScope.style.maxHeight = '100vh';
				<?php
			} else {
				// If this is a popup which prevent background interaction, hide the scrollbar.
				if ( 'popup' === $attributes['variantType'] && $attributes['haltBackgroundInteraction'] ) :
					?>
						theBody.classList.add( 'spectra-popup-builder__body--overflow-hidden' );
						blockScope.classList.add( 'spectra-popup--open' );
						<?php // Focus management for accessibility. ?>
						const closeButton = blockScope.querySelector( '.spectra-popup-builder__close' );
						if ( closeButton ) {
							closeButton.focus();
						} else {
							<?php // Fallback: create a focusable element to add focus onto the popup and then remove it. ?>
							const focusElement = document.createElement( 'button' );
							focusElement.style.position = 'absolute';
							focusElement.style.opacity = '0';
							const popupFocus = blockScope.insertBefore( focusElement, blockScope.firstChild );
							popupFocus.focus();
							popupFocus.remove();
						}
					<?php endif; ?>
					blockScope.style.opacity = 1;
				<?php
			}
			?>
		}, 100 );

		<?php
			// If this is a banner with push, Add the unset bezier curve after animating.
		if ( $is_push_banner ) :
			?>
			setTimeout( () => {
				blockScope.style.transition = 'max-height 0.5s cubic-bezier(0, 1, 0, 1)';
			}, 600 );
		<?php endif; ?>

		const closePopup = ( event = null ) => {
			if ( event && blockScope !== event?.target ) {
				return;
			}
			if ( popupSesh[0] > 0 ) {
				popupSesh[0] -= 1;
				localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( popupSesh ) );
			}
			<?php
				// If this is a banner with push, render the required animation instead of opacity.
			if ( $is_push_banner ) :
				?>
				blockScope.style.maxHeight = '';
			<?php else : ?>
				blockScope.style.opacity = 0;
			<?php endif; ?>
			setTimeout( () => {
				<?php
					// If this is a banner with push, remove the unset bezier curve.
				if ( $is_push_banner ) :
					?>
					blockScope.style.transition = '';
				<?php endif; ?>
				blockScope.remove();
				const allActivePopups = document.querySelectorAll( '.spectra-popup-builder.spectra-popup--open' );
				if ( 0 === allActivePopups.length ) {
					theBody.classList.remove( 'spectra-popup-builder__body--overflow-hidden' );
				}
			}, <?php echo intval( $popup_timer ); ?> );
		};

		<?php
		if ( $attributes['isDismissable'] ) :
			if ( $attributes['hasOverlay'] && $attributes['closeOverlayClick'] ) :
				?>
				blockScope.addEventListener( 'click', ( event ) => closePopup( event ) );
				<?php
				endif;
			if ( $attributes['closeIcon'] ) :
				?>
				const closeButton = blockScope.querySelector( '.spectra-popup-builder__close' );
				closeButton.style.cursor = 'pointer';
				closeButton.addEventListener( 'click', () => closePopup() );
				closeButton.addEventListener( 'keydown', ( event ) => {
					if ( 13 === event.keyCode || 32 === event.keyCode ) {
						event.preventDefault();
						closePopup();
					}
				} );
				<?php
				endif;
			if ( $attributes['closeEscapePress'] && 'popup' === $attributes['variantType'] && $attributes['haltBackgroundInteraction'] ) :
				?>
				document.addEventListener( 'keyup', ( event ) => {
					if ( 27 === event.keyCode && blockScope.classList.contains( 'spectra-popup--open' ) ) {
						return closePopup();
					}
				} );
				<?php
				endif;
			endif;
		?>

		const closingElements = blockScope.querySelectorAll( '.spectra-popup-close-<?php echo esc_attr( strval( $popup_id ) ); ?>' );
		for ( let i = 0; i < closingElements.length; i++ ) {
			closingElements[ i ].style.cursor = 'pointer';
			closingElements[ i ].addEventListener( 'click', () => closePopup() );
		}
	} );
<?php
$js = ob_get_clean();
$js = apply_filters( 'spectra_pro_popup_frontend_js_v3', $js, $popup_id, $attributes, $is_push_banner, $popup_timer );
// Return the view file.
return 'file:./view.php';
