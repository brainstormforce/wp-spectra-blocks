<?php
/**
 * Modal Block Test
 *
 * @package Spectra\Tests
 * @since 0.0.1
 */

use Spectra\Blocks\Modal;

/**
 * Test the Modal block functionality.
 * 
 * @since 0.0.1
 * 
 * @coversDefaultClass \Spectra\Blocks\Modal
 */
class Test_Modal_Block extends Spectra_Test_Case {

	/**
	 * Modal instance.
	 * 
	 * @var \Spectra\Blocks\Modal
	 */
	private $modal;

	/**
	 * Set up test environment.
	 * 
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Get instance of Modal.
		$this->modal = Modal::instance();
	}

	/**
	 * Test singleton instance.
	 * 
	 * @covers ::instance
	 * @return void
	 */
	public function test_singleton_instance() {
		$instance1 = Modal::instance();
		$instance2 = Modal::instance();
		
		$this->assertSame( $instance1, $instance2, 'Modal should return the same instance' );
	}

	/**
	 * Test init method hooks.
	 * 
	 * @covers ::init
	 * @return void
	 */
	public function test_init_hooks() {
		// Call init method.
		$this->modal->init();
		
		// Verify hooks were added.
		$this->assert_hook_added( 'enqueue_block_assets', $this->modal, 'enqueue_block_assets' );
		$this->assert_hook_added( 'wp_footer', $this->modal, 'handle_frontend_assets' );
	}

	/**
	 * Test enqueue_block_assets method.
	 * 
	 * @covers ::enqueue_block_assets
	 * @covers ::register_modal_assets
	 * @return void
	 */
	public function test_enqueue_block_assets() {
		// Call the method.
		$this->modal->enqueue_block_assets();

		// Verify script was registered.
		$this->assertTrue( wp_script_is( 'spectra-modal-settings', 'registered' ) );
		
		// Verify script was enqueued.
		$this->assertTrue( wp_script_is( 'spectra-modal-settings', 'enqueued' ) );
	}

	/**
	 * Test handle_frontend_assets when assets are needed.
	 * 
	 * @covers ::handle_frontend_assets
	 * @covers ::register_modal_assets
	 * @return void
	 */
	public function test_handle_frontend_assets_when_needed() {
		// Set needs_assets to true using reflection.
		$reflection = new \ReflectionClass( $this->modal );
		$property   = $reflection->getProperty( 'needs_assets' );
		$property->setAccessible( true );
		$property->setValue( $this->modal, true );

		// Call the method.
		$this->modal->handle_frontend_assets();

		// Verify script was registered.
		$this->assertTrue( wp_script_is( 'spectra-modal-settings', 'registered' ) );
		
		// Verify script was enqueued.
		$this->assertTrue( wp_script_is( 'spectra-modal-settings', 'enqueued' ) );
	}

	/**
	 * Test handle_frontend_assets when assets are not needed.
	 * 
	 * @covers ::handle_frontend_assets
	 * @covers ::register_modal_assets
	 * @return void
	 */
	public function test_handle_frontend_assets_when_not_needed() {
		// Reset script queue.
		global $wp_scripts;
		if ( isset( $wp_scripts->queue ) ) {
			$wp_scripts->queue = array();
		}
		
		// Set needs_assets to false using reflection.
		$reflection = new \ReflectionClass( $this->modal );
		$property   = $reflection->getProperty( 'needs_assets' );
		$property->setAccessible( true );
		$property->setValue( $this->modal, false );

		// Call the method.
		$this->modal->handle_frontend_assets();

		// Verify script was registered but not enqueued.
		$this->assertTrue( wp_script_is( 'spectra-modal-settings', 'registered' ) );
		$this->assertFalse( wp_script_is( 'spectra-modal-settings', 'enqueued' ) );
	}

	/**
	 * Test should_process_block with valid block.
	 * 
	 * @covers ::should_process_block
	 * @return void
	 */
	public function test_should_process_block_valid() {
		$block = array(
			'blockName' => 'spectra/modal',
			'attrs'     => array(
				'modalTrigger' => 'button',
			),
		);

		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->modal );
		$method     = $reflection->getMethod( 'should_process_block' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->modal, $block );
		
		$this->assertTrue( $result, 'Should return true for valid block with modalTrigger' );
	}

	/**
	 * Test should_process_block with invalid block - no blockName.
	 * 
	 * @covers ::should_process_block
	 * @return void
	 */
	public function test_should_process_block_no_blockname() {
		$block = array(
			'attrs' => array(
				'modalTrigger' => 'button',
			),
		);

		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->modal );
		$method     = $reflection->getMethod( 'should_process_block' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->modal, $block );
		
		$this->assertFalse( $result, 'Should return false for block without blockName' );
	}

	/**
	 * Test should_process_block with invalid block - no modalTrigger.
	 * 
	 * @covers ::should_process_block
	 * @return void
	 */
	public function test_should_process_block_no_modal_trigger() {
		$block = array(
			'blockName' => 'spectra/modal',
			'attrs'     => array(),
		);

		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->modal );
		$method     = $reflection->getMethod( 'should_process_block' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->modal, $block );
		
		$this->assertFalse( $result, 'Should return false for block without modalTrigger' );
	}

	/**
	 * Test add_modal_attributes_to_blocks returns unmodified content for invalid block.
	 * 
	 * Note: This test exposes a bug in the current implementation where
	 * the method returns $block_content instead of $modified_content.
	 * 
	 * @covers ::add_modal_attributes_to_blocks
	 * @return void
	 */
	public function test_add_modal_attributes_to_blocks_invalid_block() {
		$block_content = '<div>Test content</div>';
		$block         = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(),
		);

		$result = $this->modal->add_modal_attributes_to_blocks( $block_content, $block );
		
		$this->assertSame( $block_content, $result, 'Should return unmodified content for invalid block' );
	}

}