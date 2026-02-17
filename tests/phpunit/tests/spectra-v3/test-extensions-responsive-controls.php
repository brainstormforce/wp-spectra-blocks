<?php
/**
 * ResponsiveControls Extension Test
 *
 * @package Spectra\Tests
 * @since 0.0.1
 */

use Spectra\Extensions\ResponsiveControls;
use Spectra\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use Spectra\Helpers\Core;

/**
 * Test the ResponsiveControls extension functionality.
 * 
 * @since 0.0.1
 * 
 * @coversDefaultClass \Spectra\Extensions\ResponsiveControls
 */
class Test_Extensions_ResponsiveControls extends Spectra_Test_Case {

	/**
	 * ResponsiveControls instance.
	 * 
	 * @var \Spectra\Extensions\ResponsiveControls
	 */
	private $responsive_controls;

	/**
	 * Set up test environment.
	 * 
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Get instance of ResponsiveControls.
		$this->responsive_controls = ResponsiveControls::instance();
	}

	/**
	 * Test singleton instance.
	 * 
	 * @covers ::instance
	 * @return void
	 */
	public function test_singleton_instance() {
		$instance1 = ResponsiveControls::instance();
		$instance2 = ResponsiveControls::instance();
		
		$this->assertSame( $instance1, $instance2, 'ResponsiveControls should return the same instance' );
	}

	/**
	 * Test init method hooks.
	 * 
	 * @covers ::init
	 * @return void
	 */
	public function test_init_hooks() {
		// Call init method.
		$this->responsive_controls->init();
		
		// Verify hooks were added.
		$this->assert_hook_added( 'init', $this->responsive_controls, 'register_responsive_style' );
		$this->assert_hook_added( 'wp_enqueue_scripts', $this->responsive_controls, 'enqueue_responsive_videos_script' );
		$this->assert_hook_added( 'render_block_data', $this->responsive_controls, 'process_responsive_attributes', 10 );
		$this->assert_hook_added( 'render_block', $this->responsive_controls, 'process_block_and_add_responsive_styles', 10 );
		$this->assert_hook_added( 'render_block', $this->responsive_controls, 'maybe_skip_layout_support', 10 );
		$this->assert_hook_added( 'save_post', $this->responsive_controls, 'ensure_unique_spectra_ids', 20 );
	}

	/**
	 * Test register_responsive_style on frontend.
	 * 
	 * @covers ::register_responsive_style
	 * @return void
	 */
	public function test_register_responsive_style_frontend() {
		// Set up as frontend request.
		$this->go_to( '/' );

		// Call the method.
		$this->responsive_controls->register_responsive_style();

		// Verify style was registered.
		$this->assertTrue( wp_style_is( 'spectra-responsive-styles', 'registered' ) );
	}

	/**
	 * Test register_responsive_style skips on admin.
	 * 
	 * @covers ::register_responsive_style
	 * @return void
	 */
	public function test_register_responsive_style_skips_admin() {
		// Reset styles queue first.
		global $wp_styles;
		if ( isset( $wp_styles ) ) {
			$wp_styles->registered = array();
			$wp_styles->queue = array();
		}
		
		// Set current screen to admin.
		set_current_screen( 'edit-post' );

		// Call the method.
		$this->responsive_controls->register_responsive_style();

		// Verify style was not registered.
		$this->assertFalse( wp_style_is( 'spectra-responsive-styles', 'registered' ) );
		
		// Reset screen.
		set_current_screen( 'front' );
	}

	/**
	 * Test enqueue_responsive_videos_script when blocks are present.
	 * 
	 * @covers ::enqueue_responsive_videos_script
	 * @return void
	 */
	public function test_enqueue_responsive_videos_script_with_blocks() {
		global $post_content_for_testing;
		
		// Reset scripts queue first.
		global $wp_scripts;
		if ( isset( $wp_scripts ) ) {
			$wp_scripts->registered = array();
			$wp_scripts->queue = array();
		}
		
		// Set test content with spectra/container block.
		$post_content_for_testing = '<!-- wp:spectra/container --><!-- /wp:spectra/container -->';
		
		// Create a post with spectra/container block.
		$post_id = $this->factory->post->create( array(
			'post_content' => $post_content_for_testing,
		) );
		
		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Call the method.
		$this->responsive_controls->enqueue_responsive_videos_script();

		// Verify script was enqueued.
		$this->assertTrue( wp_script_is( 'spectra-responsive-videos', 'enqueued' ) );
		
		// Clean up.
		unset( $post_content_for_testing );
	}

	/**
	 * Test enqueue_responsive_videos_script skips when no blocks.
	 * 
	 * @covers ::enqueue_responsive_videos_script
	 * @return void
	 */
	public function test_enqueue_responsive_videos_script_no_blocks() {
		global $post_content_for_testing;
		
		// Set test content without spectra blocks.
		$post_content_for_testing = '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->';
		
		// Create a post without spectra blocks.
		$post_id = $this->factory->post->create( array(
			'post_content' => $post_content_for_testing,
		) );
		
		// Go to the post.
		$this->go_to( get_permalink( $post_id ) );

		// Call the method.
		$this->responsive_controls->enqueue_responsive_videos_script();

		// Verify script was not enqueued.
		$this->assertFalse( wp_script_is( 'spectra-responsive-videos', 'enqueued' ) );
		
		// Clean up.
		unset( $post_content_for_testing );
	}

	/**
	 * Test process_responsive_attributes skips non-Spectra blocks.
	 * 
	 * @covers ::process_responsive_attributes
	 * @covers ::should_apply_responsive_controls
	 * @return void
	 */
	public function test_process_responsive_attributes_skips_non_spectra() {
		$block = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(),
		);

		$result = $this->responsive_controls->process_responsive_attributes( $block );

		$this->assertSame( $block, $result, 'Should return unmodified block for non-Spectra blocks' );
	}

	/**
	 * Test process_responsive_attributes with Spectra block.
	 * 
	 * @covers ::process_responsive_attributes
	 * @covers ::should_apply_responsive_controls
	 * @covers ::remove_conflicting_core_attributes
	 * @return void
	 */
	public function test_process_responsive_attributes_spectra_block() {
		$block = array(
			'blockName' => 'spectra/container',
			'attrs'     => array(
				'fontSize' => 'large',
				'style'    => array(
					'spacing' => array(
						'padding' => '20px',
					),
				),
			),
		);

		$result = $this->responsive_controls->process_responsive_attributes( $block );

		// Check that responsive controls were added.
		$this->assertArrayHasKey( 'responsiveControls', $result['attrs'] );
		$this->assertArrayHasKey( 'lg', $result['attrs']['responsiveControls'] );
		
		// Check that fontSize was migrated to responsive controls.
		$this->assertEquals( 'large', $result['attrs']['responsiveControls']['lg']['fontSize'] );
		
		// Check that default layout was applied.
		$this->assertArrayHasKey( 'layout', $result['attrs']['responsiveControls']['lg'] );
	}

	/**
	 * Test maybe_skip_layout_support for Spectra blocks.
	 * 
	 * @covers ::maybe_skip_layout_support
	 * @return void
	 */
	public function test_maybe_skip_layout_support_spectra_block() {
		$block_content = '<div>Test content</div>';
		$block         = array(
			'blockName' => 'spectra/container',
		);

		$result = $this->responsive_controls->maybe_skip_layout_support( $block_content, $block );

		$this->assertSame( $block_content, $result, 'Should return unmodified content for Spectra blocks' );
	}

	/**
	 * Test maybe_skip_layout_support for non-Spectra blocks.
	 * 
	 * @covers ::maybe_skip_layout_support
	 * @return void
	 */
	public function test_maybe_skip_layout_support_non_spectra_block() {
		$block_content = '<div>Test content</div>';
		$block         = array(
			'blockName' => 'core/group',
		);

		$result = $this->responsive_controls->maybe_skip_layout_support( $block_content, $block );

		// For non-Spectra blocks, it should apply layout support.
		// The actual modification depends on WordPress core implementation.
		$this->assertIsString( $result );
	}

	/**
	 * Test ensure_unique_spectra_ids skips autosaves.
	 * 
	 * @covers ::ensure_unique_spectra_ids
	 * @return void
	 */
	public function test_ensure_unique_spectra_ids_skips_autosave() {
		// Create a post.
		$post_id = $this->factory->post->create();
		$post    = get_post( $post_id );
		
		// Create an autosave.
		$autosave_id = wp_create_post_autosave( array(
			'post_ID'      => $post_id,
			'post_content' => 'Autosave content',
		) );

		// Call the method with autosave ID.
		$this->responsive_controls->ensure_unique_spectra_ids( $autosave_id, $post );

		// If we get here without errors, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test has_actual_value method using reflection.
	 * 
	 * @covers ::has_actual_value
	 * @return void
	 */
	public function test_has_actual_value() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->responsive_controls );
		$method     = $reflection->getMethod( 'has_actual_value' );
		$method->setAccessible( true );

		// Test null value.
		$this->assertFalse( $method->invoke( $this->responsive_controls, null ) );

		// Test empty string.
		$this->assertFalse( $method->invoke( $this->responsive_controls, '' ) );

		// Test zero values.
		$this->assertTrue( $method->invoke( $this->responsive_controls, 0 ) );
		$this->assertTrue( $method->invoke( $this->responsive_controls, '0' ) );
		$this->assertTrue( $method->invoke( $this->responsive_controls, '0px' ) );

		// Test regular values.
		$this->assertTrue( $method->invoke( $this->responsive_controls, 'test' ) );
		$this->assertTrue( $method->invoke( $this->responsive_controls, 123 ) );

		// Test arrays.
		$this->assertFalse( $method->invoke( $this->responsive_controls, array() ) );
		$this->assertFalse( $method->invoke( $this->responsive_controls, array( '', null ) ) );
		$this->assertTrue( $method->invoke( $this->responsive_controls, array( 'test' ) ) );
		$this->assertTrue( $method->invoke( $this->responsive_controls, array( '', 'test' ) ) );
	}

	/**
	 * Test generate_unique_spectra_id method.
	 * 
	 * @covers ::generate_unique_spectra_id
	 * @return void
	 */
	public function test_generate_unique_spectra_id() {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->responsive_controls );
		$method     = $reflection->getMethod( 'generate_unique_spectra_id' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->responsive_controls );

		// Should start with 'spectra-' and have a UUID.
		$this->assertStringStartsWith( 'spectra-', $result );
		$this->assertGreaterThan( 8, strlen( $result ) );
	}

	/**
	 * Test device fallback order property.
	 * 
	 * @return void
	 */
	public function test_device_fallback_order() {
		// Use reflection to access private property.
		$reflection = new \ReflectionClass( $this->responsive_controls );
		$property   = $reflection->getProperty( 'device_fallback_order' );
		$property->setAccessible( true );

		$fallback_order = $property->getValue( $this->responsive_controls );

		// Test mobile fallback order.
		$this->assertEquals( array( 'sm', 'md', 'lg' ), $fallback_order['sm'] );

		// Test tablet fallback order.
		$this->assertEquals( array( 'md', 'lg' ), $fallback_order['md'] );

		// Test desktop fallback order.
		$this->assertEquals( array( 'lg' ), $fallback_order['lg'] );
	}

	/**
	 * Test media queries property.
	 * 
	 * @return void
	 */
	public function test_media_queries() {
		// Use reflection to access private property.
		$reflection = new \ReflectionClass( $this->responsive_controls );
		$property   = $reflection->getProperty( 'media_queries' );
		$property->setAccessible( true );

		$media_queries = $property->getValue( $this->responsive_controls );

		// Test mobile media query.
		$this->assertEquals( '(max-width: 767.98px)', $media_queries['sm'] );

		// Test tablet media query.
		$this->assertEquals( '(min-width: 768px) and (max-width: 1023.98px)', $media_queries['md'] );

		// Test desktop media query.
		$this->assertEquals( '(min-width: 1024px)', $media_queries['lg'] );
	}
}