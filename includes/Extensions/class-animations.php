<?php
/**
 * Animations Extension
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Animations class.
 *
 * @since 3.0.0
 */
class Animations {

	use Singleton;

	/**
	 * Flag indicating if animation assets are needed.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private $needs_assets = false;

	/**
	 * Initialize the class.
	 *
	 * Hooks into render_block, asset registration, and conditional asset enqueue.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'render_block', array( $this, 'add_animation_attributes_to_blocks' ), 10, 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		add_action( 'wp_footer', array( $this, 'handle_frontend_assets' ) );
	}

	/**
	 * Add animation attributes to the output of supported blocks.
	 *
	 * Ensures the block has the 'spectraAnimationType' attribute defined and injects
	 * the animation attributes into the block's wrapper tag using WP_HTML_Tag_Processor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content with animation attributes added.
	 */
	public function add_animation_attributes_to_blocks( $block_content, $block ) {
		// If the block should not be processed, return the original content.
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$attributes = $this->get_animation_attributes( $block['attrs'] );

		// If the block does not have the 'spectraAnimationType' attribute, return the original content.
		if ( empty( $attributes['type'] ) ) {
			return $block_content;
		}

		// Apply animation attributes to the block content.
		$modified_content = $this->apply_attributes( $block_content, $attributes );

		// If the block content was modified, enqueue AOS assets.
		if ( false !== $modified_content ) {
			$this->needs_assets = true;

			return $modified_content;
		}

		return $block_content;
	}

	/**
	 * Enqueue AOS CSS and JS assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {

		// AOS is a front-end-only scroll library, and authored scroll-reveals
		// can't reliably re-fire across Gutenberg's block re-renders
		// (select/deselect/edit re-mounts the DOM, and the reveal JS doesn't
		// re-scan the fresh nodes) — so reveal content gets stuck in its hidden
		// start state. We do NOT run the animation here; we inject a static reset
		// that shows every reveal element in its FINAL state. Plain CSS keeps
		// applying to re-rendered nodes with no JS re-binding; the front-end
		// animation is unchanged (this reset is editor-only).
		//
		// Targets, in order of reliability:
		// - `data-aos` / `data-animate` / `data-reveal` — attribute conventions.
		// - `-spectra-anim` — the reserved convention SUFFIX authored reveals carry
		// (SSOT); matched explicitly so the convention always wins.
		// - `-anim` — DASH-anchored, matches any `-anim`/`-animate` compound class.
		// - `-reveal` / `-fade` — common reveal suffixes as a safety net for
		// imports that predate / don't follow the convention.
		// All class tokens are DASH-anchored (`-token`) so they only fire on
		// compound reveal classes (`hero-fade`, `mng-reveal`) and skip unrelated
		// words (`faded`, `animation-wrapper`). NOTE: the reset also clears
		// `transform` (the entrance OFFSET for a reveal — correct here), so tokens
		// matching LAYOUT-transform elements (`slide`/`carousel`/`zoom`, bare
		// `-in`) are deliberately EXCLUDED to avoid flattening slider positioning.
		if ( is_admin() ) {
			$reset_handle    = 'spectra-blocks-aos-editor-reset';
			$reset_selectors = implode(
				',',
				array(
					'[data-aos]',
					'[data-animate]',
					'[data-reveal]',
					'[class*="-spectra-anim"]',
					'[class*="-anim"]',
					'[class*="-reveal"]',
					'[class*="-fade"]',
				)
			);
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Inline-only stylesheet; no src/version needed.
			wp_register_style( $reset_handle, false, array(), null );
			wp_enqueue_style( $reset_handle );
			wp_add_inline_style(
				$reset_handle,
				$reset_selectors . '{opacity:1 !important;transform:none !important;}'
			);

			return;
		}

		if ( ! wp_script_is( 'spectra-blocks-aos-js', 'registered' ) ) {
			wp_register_script( 'spectra-blocks-aos-js', SPECTRA_BLOCKS_URL . 'assets/js/aos.min.js', array(), SPECTRA_BLOCKS_VER, true );
		}

		if ( ! wp_style_is( 'spectra-blocks-aos-css', 'registered' ) ) {
			wp_register_style( 'spectra-blocks-aos-css', SPECTRA_BLOCKS_URL . 'assets/css/aos.min.css', array(), SPECTRA_BLOCKS_VER );
		}

		wp_enqueue_style( 'spectra-blocks-aos-css' );
		wp_enqueue_script( 'spectra-blocks-aos-js' );
	}

	/**
	 * Handle frontend asset registration and enqueueing
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function handle_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		$this->register_animation_assets();

		// Enqueue AOS assets. if needed.
		if ( $this->needs_assets ) {
			wp_enqueue_style( 'spectra-blocks-aos-css' );
			wp_enqueue_script( 'spectra-blocks-aos-js' );
			wp_enqueue_script( 'spectra-aos-init' );
		}
	}

	/**
	 * Determine whether the block should be processed for animations.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& ! empty( $block['attrs']['spectraAnimationType'] )
			&& $this->is_allowed_block( $block['blockName'] );
	}

	/**
	 * Retrieve sanitized animation attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attrs Block attributes.
	 * @return array Sanitized attributes.
	 */
	private function get_animation_attributes( $attrs ) {
		return array(
			'type'   => $attrs['spectraAnimationType'] ?? '',
			'time'   => $attrs['spectraAnimationTime'] ?? 400,
			'delay'  => $attrs['spectraAnimationDelay'] ?? 0,
			'easing' => $attrs['spectraAnimationEasing'] ?? 'ease',
			'once'   => $attrs['spectraAnimationOnce'] ?? false, // Play repeatedly on scroll.
		);
	}

	/**
	 * Apply animation attributes to block content.
	 *
	 * Uses WP_HTML_Tag_Processor to safely inject data attributes into the first tag.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content    Block content.
	 * @param array  $attributes Animation attributes.
	 * @return string|false Modified content or false on failure.
	 */
	private function apply_attributes( $content, $attributes ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$processor = new WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		$processor->set_attribute( 'data-aos', $attributes['type'] );
		$processor->set_attribute( 'data-aos-duration', $attributes['time'] );
		$processor->set_attribute( 'data-aos-delay', $attributes['delay'] );
		$processor->set_attribute( 'data-aos-easing', $attributes['easing'] );
		$processor->set_attribute( 'data-aos-once', $attributes['once'] ? 'false' : 'true' ); // If `Play Repeatedly on Scroll`(spectraAnimationOnce) is enabled, set `data-aos-once` to false otherwise true.

		return $processor->get_updated_html();
	}

	/**
	 * Check if a block is allowed for animations.
	 *
	 * Uses allowed prefixes to determine if a block should receive AOS attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	private function is_allowed_block( $block_name ) {
		return preg_match( '/^(spectra\/|spectra-pro\/|uagb\/|core\/)/', $block_name );
	}

	/**
	 * Register animation assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_animation_assets() {
		// Register AOS init JS.
		wp_register_script(
			'spectra-aos-init',
			SPECTRA_BLOCKS_URL . 'assets/js/spectra-animations.js',
			array( 'spectra-blocks-aos-js' ),
			SPECTRA_BLOCKS_VER,
			true
		);
	}
}
