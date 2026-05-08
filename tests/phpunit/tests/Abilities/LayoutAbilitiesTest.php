<?php
/**
 * Tests for Layout abilities: CreateContainer, CreateModal, CreateSlider, GeneratePageLayout.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\CreateContainer;
use Spectra\Abilities\CreateModal;
use Spectra\Abilities\CreateSlider;
use Spectra\Abilities\GeneratePageLayout;

/**
 * Layout abilities test case.
 */
class LayoutAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user can edit.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// CreateContainer
	// -------------------------------------------------------------------------

	/**
	 * Test CreateContainer metadata.
	 */
	public function test_create_container_metadata() {
		$ability = CreateContainer::instance();

		$this->assertSame( 'spectra-blocks/create-container', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-layout', $ability->get_category() );
	}

	/**
	 * Test CreateContainer execute with defaults.
	 */
	public function test_create_container_execute_defaults() {
		$result = CreateContainer::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/container', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- /wp:spectra/container -->', $result['block_markup'] );
		$this->assertStringContainsString( '"variationSelected":true', $result['block_markup'] );
	}

	/**
	 * Test CreateContainer with custom HTML tag.
	 */
	public function test_create_container_with_custom_tag() {
		$result = CreateContainer::instance()->execute(
			array( 'htmlTag' => 'section' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"htmlTag":"section"', $result['block_markup'] );
	}

	/**
	 * Test CreateContainer ignores invalid HTML tags.
	 */
	public function test_create_container_invalid_tag_ignored() {
		$result = CreateContainer::instance()->execute(
			array( 'htmlTag' => 'script' )
		);

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( '"htmlTag":"script"', $result['block_markup'] );
	}

	/**
	 * Test CreateContainer with width and min height.
	 */
	public function test_create_container_with_dimensions() {
		$result = CreateContainer::instance()->execute(
			array(
				'width'     => '1200px',
				'minHeight' => '400px',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"width":"1200px"', $result['block_markup'] );
		$this->assertStringContainsString( '"minHeight":"400px"', $result['block_markup'] );
	}

	/**
	 * Test CreateContainer with inner content.
	 */
	public function test_create_container_with_content() {
		$inner = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
		$result = CreateContainer::instance()->execute(
			array( 'content' => $inner )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Hello', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateModal
	// -------------------------------------------------------------------------

	/**
	 * Test CreateModal metadata.
	 */
	public function test_create_modal_metadata() {
		$ability = CreateModal::instance();

		$this->assertSame( 'spectra-blocks/create-modal', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-layout', $ability->get_category() );
	}

	/**
	 * Test CreateModal execute with required params.
	 */
	public function test_create_modal_execute_success() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Open Modal',
				'popupContent' => 'Modal body content here.',
			)
		);

		$this->assertIsArray( $result );
		$markup = $result['block_markup'];
		$this->assertStringContainsString( '<!-- wp:spectra/modal', $markup );
		$this->assertStringContainsString( '<!-- wp:spectra/modal-child-trigger', $markup );
		$this->assertStringContainsString( '<!-- wp:spectra/modal-popup', $markup );
		$this->assertStringContainsString( 'Open Modal', $markup );
		$this->assertStringContainsString( 'Modal body content here.', $markup );
		$this->assertStringContainsString( '<!-- wp:spectra/modal-child-popup-close-icon', $markup );
	}

	/**
	 * Test CreateModal execute fails without triggerText.
	 */
	public function test_create_modal_execute_missing_trigger_text() {
		$result = CreateModal::instance()->execute(
			array( 'popupContent' => 'Content' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateModal execute fails without popupContent.
	 */
	public function test_create_modal_execute_missing_popup_content() {
		$result = CreateModal::instance()->execute(
			array( 'triggerText' => 'Open' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateModal with button trigger type (default).
	 */
	public function test_create_modal_button_trigger() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Click',
				'popupContent' => 'Content',
				'modalTrigger' => 'button',
			)
		);

		$this->assertStringContainsString( 'modal-child-button', $result['block_markup'] );
		$this->assertStringContainsString( '"modalTrigger":"button"', $result['block_markup'] );
	}

	/**
	 * Test CreateModal with icon trigger type.
	 */
	public function test_create_modal_icon_trigger() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Click',
				'popupContent' => 'Content',
				'modalTrigger' => 'icon',
			)
		);

		$this->assertStringContainsString( 'modal-child-icon', $result['block_markup'] );
		$this->assertStringContainsString( '"modalTrigger":"icon"', $result['block_markup'] );
	}

	/**
	 * Test CreateModal with text trigger type.
	 */
	public function test_create_modal_text_trigger() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Click here',
				'popupContent' => 'Content',
				'modalTrigger' => 'text',
			)
		);

		$this->assertStringContainsString( 'modal-child-content', $result['block_markup'] );
		$this->assertStringContainsString( '"modalTrigger":"text"', $result['block_markup'] );
	}

	/**
	 * Test CreateModal generates a UUID for modalId.
	 */
	public function test_create_modal_generates_uuid() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Open',
				'popupContent' => 'Content',
			)
		);

		$this->assertStringContainsString( '"modalId":', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateSlider
	// -------------------------------------------------------------------------

	/**
	 * Test CreateSlider metadata.
	 */
	public function test_create_slider_metadata() {
		$ability = CreateSlider::instance();

		$this->assertSame( 'spectra-blocks/create-slider', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-layout', $ability->get_category() );
	}

	/**
	 * Test CreateSlider execute with valid slides.
	 */
	public function test_create_slider_execute_success() {
		$result = CreateSlider::instance()->execute(
			array(
				'slides' => array(
					array( 'content' => 'Slide 1 content' ),
					array( 'content' => 'Slide 2 content' ),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/slider', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/slider-child', $result['block_markup'] );
		$this->assertStringContainsString( 'Slide 1 content', $result['block_markup'] );
		$this->assertStringContainsString( 'Slide 2 content', $result['block_markup'] );
		$this->assertStringContainsString( '"slideCount":2', $result['block_markup'] );
	}

	/**
	 * Test CreateSlider execute fails without slides.
	 */
	public function test_create_slider_execute_missing_slides() {
		$result = CreateSlider::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateSlider with autoplay and loop options.
	 */
	public function test_create_slider_with_options() {
		$result = CreateSlider::instance()->execute(
			array(
				'slides'        => array( array( 'content' => 'Slide' ) ),
				'slidesPerView' => 3,
				'loop'          => true,
				'autoplay'      => true,
				'navigation'    => false,
				'pagination'    => false,
			)
		);

		$this->assertIsArray( $result );
		$markup = $result['block_markup'];
		$this->assertStringContainsString( '"slidesPerView":3', $markup );
		$this->assertStringContainsString( '"loop":true', $markup );
		$this->assertStringContainsString( '"autoplay":true', $markup );
		$this->assertStringContainsString( '"displayArrows":false', $markup );
		$this->assertStringContainsString( '"displayDots":false', $markup );
	}

	/**
	 * Test CreateSlider skips empty slides.
	 */
	public function test_create_slider_skips_empty_slides() {
		$result = CreateSlider::instance()->execute(
			array(
				'slides' => array(
					array( 'content' => '' ),
					array( 'content' => 'Valid slide' ),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"slideCount":1', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// GeneratePageLayout
	// -------------------------------------------------------------------------

	/**
	 * Test GeneratePageLayout metadata.
	 */
	public function test_generate_page_layout_metadata() {
		$ability = GeneratePageLayout::instance();

		$this->assertSame( 'spectra-blocks/generate-page-layout', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-layout', $ability->get_category() );
	}

	/**
	 * Test GeneratePageLayout with simple block list.
	 */
	public function test_generate_page_layout_execute_success() {
		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks' => array(
					array(
						'blockName' => 'core/paragraph',
						'innerHTML' => '<p>Hello World</p>',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:core/paragraph -->', $result['block_markup'] );
		$this->assertStringContainsString( '<p>Hello World</p>', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- /wp:core/paragraph -->', $result['block_markup'] );
	}

	/**
	 * Test GeneratePageLayout execute fails without blocks.
	 */
	public function test_generate_page_layout_execute_missing_blocks() {
		$result = GeneratePageLayout::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test GeneratePageLayout with nested blocks.
	 */
	public function test_generate_page_layout_with_nested_blocks() {
		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks' => array(
					array(
						'blockName'   => 'spectra/container',
						'attrs'       => array( 'variationSelected' => true ),
						'innerBlocks' => array(
							array(
								'blockName' => 'core/paragraph',
								'innerHTML' => '<p>Nested paragraph</p>',
							),
						),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$markup = $result['block_markup'];
		$this->assertStringContainsString( '<!-- wp:spectra/container', $markup );
		$this->assertStringContainsString( '<!-- wp:core/paragraph -->', $markup );
		$this->assertStringContainsString( 'Nested paragraph', $markup );
		$this->assertStringContainsString( '<!-- /wp:spectra/container -->', $markup );
	}

	/**
	 * Test GeneratePageLayout with self-closing block (no content).
	 */
	public function test_generate_page_layout_self_closing_block() {
		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks' => array(
					array(
						'blockName' => 'spectra/separator',
						'attrs'     => array( 'separatorStyle' => 'dashed' ),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/separator', $result['block_markup'] );
		$this->assertStringContainsString( '/-->', $result['block_markup'] );
	}

	/**
	 * Test GeneratePageLayout with block attributes.
	 */
	public function test_generate_page_layout_with_attrs() {
		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks' => array(
					array(
						'blockName' => 'spectra/separator',
						'attrs'     => array(
							'separatorStyle' => 'dotted',
							'separatorWidth' => 50,
						),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"separatorStyle":"dotted"', $result['block_markup'] );
		$this->assertStringContainsString( '"separatorWidth":50', $result['block_markup'] );
	}

	/**
	 * Test GeneratePageLayout enforces max depth of 10.
	 */
	public function test_generate_page_layout_max_depth_enforcement() {
		// Build 12-level nesting.
		$block = array(
			'blockName' => 'core/group',
			'innerHTML' => '<div>Deep</div>',
		);

		for ( $i = 0; $i < 12; $i++ ) {
			$block = array(
				'blockName'   => 'core/group',
				'innerBlocks' => array( $block ),
			);
		}

		$result = GeneratePageLayout::instance()->execute(
			array( 'blocks' => array( $block ) )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_depth_exceeded', $result->get_error_code() );
	}

	/**
	 * Test CreateContainer denies subscribers.
	 */
	public function test_create_container_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateContainer::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test CreateContainer input schema.
	 */
	public function test_create_container_input_schema() {
		$schema = CreateContainer::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'htmlTag', $schema['properties'] );
	}

	/**
	 * Test CreateContainer output schema.
	 */
	public function test_create_container_output_schema() {
		$schema = CreateContainer::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateModal input schema.
	 */
	public function test_create_modal_input_schema() {
		$schema = CreateModal::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'triggerText', $schema['required'] );
		$this->assertContains( 'popupContent', $schema['required'] );
	}

	/**
	 * Test CreateSlider input schema.
	 */
	public function test_create_slider_input_schema() {
		$schema = CreateSlider::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'slides', $schema['required'] );
	}

	/**
	 * Test GeneratePageLayout input schema.
	 */
	public function test_generate_page_layout_input_schema() {
		$schema = GeneratePageLayout::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'blocks', $schema['required'] );
	}

	/**
	 * Test GeneratePageLayout output schema.
	 */
	public function test_generate_page_layout_output_schema() {
		$schema = GeneratePageLayout::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test GeneratePageLayout skips blocks without blockName.
	 */
	public function test_generate_page_layout_skips_blocks_without_name() {
		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks' => array(
					array( 'innerHTML' => '<p>No name</p>' ),
					array(
						'blockName' => 'core/paragraph',
						'innerHTML' => '<p>Has name</p>',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Has name', $result['block_markup'] );
		$this->assertStringNotContainsString( 'No name', $result['block_markup'] );
	}

	/**
	 * Test GeneratePageLayout with post insertion.
	 */
	public function test_generate_page_layout_with_post_insertion() {
		$post_id = self::factory()->post->create();

		$result = GeneratePageLayout::instance()->execute(
			array(
				'blocks'  => array(
					array(
						'blockName' => 'core/paragraph',
						'innerHTML' => '<p>Page content</p>',
					),
				),
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['post_id'] );
		$this->assertStringContainsString( 'Page content', $result['post_content'] );
	}

	// =========================================================================
	// Missing Output Schema Tests
	// =========================================================================

	/**
	 * Test CreateModal output schema.
	 */
	public function test_create_modal_output_schema() {
		$schema = CreateModal::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateSlider output schema.
	 */
	public function test_create_slider_output_schema() {
		$schema = CreateSlider::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	// =========================================================================
	// Missing Permission Tests
	// =========================================================================

	/**
	 * Test CreateModal denies subscribers.
	 */
	public function test_create_modal_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateModal::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test CreateSlider denies subscribers.
	 */
	public function test_create_slider_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateSlider::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GeneratePageLayout denies subscribers.
	 */
	public function test_generate_page_layout_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GeneratePageLayout::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GeneratePageLayout grants access to editors.
	 */
	public function test_generate_page_layout_permission_editor() {
		$this->assertTrue( GeneratePageLayout::instance()->check_permission() );
	}

	/**
	 * Test CreateModal with invalid trigger type defaults to button.
	 */
	public function test_create_modal_invalid_trigger_defaults_to_button() {
		$result = CreateModal::instance()->execute(
			array(
				'triggerText'  => 'Click',
				'popupContent' => 'Content',
				'modalTrigger' => 'invalid',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'modal-child-button', $result['block_markup'] );
	}
}
