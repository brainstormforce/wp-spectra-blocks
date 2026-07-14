<?php
/**
 * Modal Block
 *
 * @package Spectra\Extensions
 */

namespace SpectraBlocks\Blocks;

use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Modal class.
 *
 * @since 3.0.0
 */
class Modal {

	use Singleton;

	/**
	 * Flag indicating if modal assets are needed.
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
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		add_action( 'wp_footer', array( $this, 'handle_frontend_assets' ) );
	}

	/**
	 * Enqueue modal JS assets for editor.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		$this->register_modal_assets();

		// Enqueue only in editor.
		if ( wp_script_is( 'spectra-blocks-modal-settings', 'registered' ) ) {
			wp_enqueue_script( 'spectra-blocks-modal-settings' );
		}
	}

	/**
	 * Handle frontend asset registration and enqueueing
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function handle_frontend_assets() {

		$this->register_modal_assets();

		if ( $this->needs_assets && wp_script_is( 'spectra-blocks-modal-settings', 'registered' ) ) {
			wp_enqueue_script( 'spectra-blocks-modal-settings' );
		}
	}

	/**
	 * Register modal assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_modal_assets() {
		wp_register_script(
			'spectra-blocks-modal-settings',
			SPECTRA_BLOCKS_URL . 'assets/js/modal-script.js',
			array( 'wp-hooks' ),
			SPECTRA_BLOCKS_VER,
			true
		);
	}

	/**
	 * Determine whether the block should be processed for modal.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block Block data.
	 * @return bool
	 */
	private function should_process_block( $block ) {
		return ! empty( $block['blockName'] )
			&& ! empty( $block['attrs']['modalTrigger'] );
	}

	/**
	 * Add modal attributes to the output of supported blocks.
	 *
	 * The modal attributes into the block's wrapper tag using WP_HTML_Tag_Processor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block instance.
	 * @return string The block content with modal attributes added.
	 */
	public function add_modal_attributes_to_blocks( $block_content, $block ) {
		if ( ! $this->should_process_block( $block ) ) {
			return $block_content;
		}

		$attributes       = $this->get_modal_attributes( $block['attrs'] );
		$modified_content = $this->apply_attributes( $block_content, $attributes );

		$this->needs_assets = true;

		return $block_content;
	}
}
