<?php
/**
 * Tests for Extension abilities: ApplyAnimation, RemoveAnimation, ApplySticky, RemoveSticky,
 * ApplyResponsiveConditions, RemoveResponsiveConditions, ApplyZIndex, ApplyImageMask.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\ApplyAnimation;
use Spectra\Abilities\RemoveAnimation;
use Spectra\Abilities\ApplySticky;
use Spectra\Abilities\RemoveSticky;
use Spectra\Abilities\ApplyResponsiveConditions;
use Spectra\Abilities\RemoveResponsiveConditions;
use Spectra\Abilities\ApplyZIndex;
use Spectra\Abilities\ApplyImageMask;
use Spectra\Abilities\GetPostContent;

/**
 * Extension abilities test case.
 */
class ExtensionAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is editor.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Helper: Create a post with a spectra container block.
	 *
	 * @return int Post ID.
	 */
	private function create_post_with_block(): int {
		$content = '<!-- wp:spectra/container {"block_id":"test123"} --><div class="wp-block-spectra-container"><p>Content</p></div><!-- /wp:spectra/container -->';

		return self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * Helper: Create a post with a core/image block.
	 *
	 * @return int Post ID.
	 */
	private function create_post_with_image(): int {
		$content = '<!-- wp:core/image {"id":1} --><figure class="wp-block-image"><img src="test.jpg" /></figure><!-- /wp:core/image -->';

		return self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * Helper: Get block attributes at index from a post.
	 *
	 * @param int $post_id    Post ID.
	 * @param int $block_index Block index.
	 * @return array Block attributes.
	 */
	private function get_block_attrs( int $post_id, int $block_index = 0 ): array {
		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		return $result['blocks'][ $block_index ]['attributes'] ?? array();
	}

	// -------------------------------------------------------------------------
	// ApplyAnimation
	// -------------------------------------------------------------------------

	/**
	 * Test ApplyAnimation metadata.
	 */
	public function test_apply_animation_metadata() {
		$ability = ApplyAnimation::instance();

		$this->assertSame( 'spectra-blocks/apply-animation', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test ApplyAnimation execute success.
	 */
	public function test_apply_animation_execute_success() {
		$post_id = $this->create_post_with_block();

		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'fadeIn',
				'duration'    => 1500,
				'delay'       => 200,
				'easing'      => 'ease-in-out',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'fadeIn', $result['animation_settings']['UAGAnimationType'] );
		$this->assertSame( 1500, $result['animation_settings']['UAGAnimationTime'] );
		$this->assertSame( 200, $result['animation_settings']['UAGAnimationDelay'] );
		$this->assertSame( 'ease-in-out', $result['animation_settings']['UAGAnimationEasing'] );

		// Verify attributes were saved to post.
		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 'fadeIn', $attrs['UAGAnimationType'] );
	}

	/**
	 * Test ApplyAnimation fails without required params.
	 */
	public function test_apply_animation_missing_params() {
		$result = ApplyAnimation::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test ApplyAnimation input schema.
	 */
	public function test_apply_animation_input_schema() {
		$schema = ApplyAnimation::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
		$this->assertContains( 'type', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// RemoveAnimation
	// -------------------------------------------------------------------------

	/**
	 * Test RemoveAnimation metadata.
	 */
	public function test_remove_animation_metadata() {
		$ability = RemoveAnimation::instance();

		$this->assertSame( 'spectra-blocks/remove-animation', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test RemoveAnimation execute success.
	 */
	public function test_remove_animation_execute_success() {
		$post_id = $this->create_post_with_block();

		// First apply animation.
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'fadeIn',
				'duration'    => 1000,
			)
		);

		// Then remove it.
		$result = RemoveAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertContains( 'UAGAnimationType', $result['removed'] );
		$this->assertContains( 'UAGAnimationTime', $result['removed'] );

		// Verify attributes were removed.
		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGAnimationType', $attrs );
	}

	/**
	 * Test RemoveAnimation fails without params.
	 */
	public function test_remove_animation_missing_params() {
		$result = RemoveAnimation::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// ApplySticky
	// -------------------------------------------------------------------------

	/**
	 * Test ApplySticky metadata.
	 */
	public function test_apply_sticky_metadata() {
		$ability = ApplySticky::instance();

		$this->assertSame( 'spectra-blocks/apply-sticky', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test ApplySticky execute success.
	 */
	public function test_apply_sticky_execute_success() {
		$post_id = $this->create_post_with_block();

		$result = ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 'sticky', $attrs['UAGPosition'] );
	}

	/**
	 * Test ApplySticky fails without params.
	 */
	public function test_apply_sticky_missing_params() {
		$result = ApplySticky::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// RemoveSticky
	// -------------------------------------------------------------------------

	/**
	 * Test RemoveSticky metadata.
	 */
	public function test_remove_sticky_metadata() {
		$ability = RemoveSticky::instance();

		$this->assertSame( 'spectra-blocks/remove-sticky', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test RemoveSticky execute success.
	 */
	public function test_remove_sticky_execute_success() {
		$post_id = $this->create_post_with_block();

		// Apply sticky first.
		ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Then remove.
		$result = RemoveSticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGPosition', $attrs );
	}

	/**
	 * Test RemoveSticky fails without params.
	 */
	public function test_remove_sticky_missing_params() {
		$result = RemoveSticky::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// ApplyResponsiveConditions
	// -------------------------------------------------------------------------

	/**
	 * Test ApplyResponsiveConditions metadata.
	 */
	public function test_apply_responsive_conditions_metadata() {
		$ability = ApplyResponsiveConditions::instance();

		$this->assertSame( 'spectra-blocks/apply-responsive-conditions', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test ApplyResponsiveConditions execute success.
	 */
	public function test_apply_responsive_conditions_execute_success() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'      => $post_id,
				'block_index'  => 0,
				'hide_desktop' => false,
				'hide_tablet'  => true,
				'hide_mobile'  => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'conditions_applied', $result );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGResponsiveConditions'] );
		$this->assertTrue( $attrs['UAGHideTab'] );
		$this->assertTrue( $attrs['UAGHideMob'] );
	}

	/**
	 * Test ApplyResponsiveConditions with user role conditions.
	 */
	public function test_apply_responsive_conditions_user_role() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'logged_in'   => true,
				'user_role'   => array( 'administrator', 'editor' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGLoggedIn'] );
		$this->assertSame( array( 'administrator', 'editor' ), $attrs['UAGUserRole'] );
	}

	/**
	 * Test ApplyResponsiveConditions fails without params.
	 */
	public function test_apply_responsive_conditions_missing_params() {
		$result = ApplyResponsiveConditions::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// RemoveResponsiveConditions
	// -------------------------------------------------------------------------

	/**
	 * Test RemoveResponsiveConditions metadata.
	 */
	public function test_remove_responsive_conditions_metadata() {
		$ability = RemoveResponsiveConditions::instance();

		$this->assertSame( 'spectra-blocks/remove-responsive-conditions', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test RemoveResponsiveConditions execute success.
	 */
	public function test_remove_responsive_conditions_execute_success() {
		$post_id = $this->create_post_with_block();

		// Apply conditions first.
		ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'      => $post_id,
				'block_index'  => 0,
				'hide_desktop' => true,
				'hide_mobile'  => true,
			)
		);

		// Then remove.
		$result = RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertContains( 'UAGHideDesktop', $result['removed'] );
		$this->assertContains( 'UAGHideMob', $result['removed'] );
		$this->assertContains( 'UAGResponsiveConditions', $result['removed'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGHideDesktop', $attrs );
		$this->assertArrayNotHasKey( 'UAGResponsiveConditions', $attrs );
	}

	/**
	 * Test RemoveResponsiveConditions fails without params.
	 */
	public function test_remove_responsive_conditions_missing_params() {
		$result = RemoveResponsiveConditions::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// ApplyZIndex
	// -------------------------------------------------------------------------

	/**
	 * Test ApplyZIndex metadata.
	 */
	public function test_apply_zindex_metadata() {
		$ability = ApplyZIndex::instance();

		$this->assertSame( 'spectra-blocks/apply-zindex', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test ApplyZIndex execute success.
	 */
	public function test_apply_zindex_execute_success() {
		$post_id = $this->create_post_with_block();

		$result = ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 99,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 99, $result['zindex'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 99, $attrs['spectraZIndex'] );
	}

	/**
	 * Test ApplyZIndex fails without params.
	 */
	public function test_apply_zindex_missing_params() {
		$result = ApplyZIndex::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test ApplyZIndex input schema.
	 */
	public function test_apply_zindex_input_schema() {
		$schema = ApplyZIndex::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'zindex', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// ApplyImageMask
	// -------------------------------------------------------------------------

	/**
	 * Test ApplyImageMask metadata.
	 */
	public function test_apply_image_mask_metadata() {
		$ability = ApplyImageMask::instance();

		$this->assertSame( 'spectra-blocks/apply-image-mask', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-extensions', $ability->get_category() );
	}

	/**
	 * Test ApplyImageMask execute success with predefined shape.
	 */
	public function test_apply_image_mask_execute_success() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'circle',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'circle', $result['mask_settings']['shape'] );
		$this->assertSame( 'contain', $result['mask_settings']['size'] );
	}

	/**
	 * Test ApplyImageMask execute with custom shape requires url.
	 */
	public function test_apply_image_mask_custom_requires_url() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'custom',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test ApplyImageMask execute with custom shape and url.
	 */
	public function test_apply_image_mask_custom_with_url() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'custom',
				'custom_url'  => 'https://example.com/mask.svg',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'custom', $result['mask_settings']['shape'] );
		$this->assertSame( 'https://example.com/mask.svg', $result['mask_settings']['image']['url'] );
	}

	/**
	 * Test ApplyImageMask fails with invalid shape.
	 */
	public function test_apply_image_mask_invalid_shape() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'invalid-shape',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_param', $result->get_error_code() );
	}

	/**
	 * Test ApplyImageMask fails without params.
	 */
	public function test_apply_image_mask_missing_params() {
		$result = ApplyImageMask::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// =========================================================================
	// Additional ApplyAnimation tests
	// =========================================================================

	/**
	 * Test ApplyAnimation output schema.
	 */
	public function test_apply_animation_output_schema() {
		$schema = ApplyAnimation::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'animation_settings', $schema['properties'] );
	}

	/**
	 * Test ApplyAnimation fails for non-existent post.
	 */
	public function test_apply_animation_nonexistent_post() {
		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
				'type'        => 'fadeIn',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test ApplyAnimation fails for invalid block index.
	 */
	public function test_apply_animation_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
				'type'        => 'fadeIn',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test ApplyAnimation with only required params omits optional keys.
	 */
	public function test_apply_animation_optional_params_omitted() {
		$post_id = $this->create_post_with_block();

		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'slideInUp',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'slideInUp', $result['animation_settings']['UAGAnimationType'] );
		$this->assertArrayNotHasKey( 'UAGAnimationTime', $result['animation_settings'] );
		$this->assertArrayNotHasKey( 'UAGAnimationDelay', $result['animation_settings'] );
		$this->assertArrayNotHasKey( 'UAGAnimationEasing', $result['animation_settings'] );
	}

	/**
	 * Test ApplyAnimation with do_not_apply_to_container param.
	 */
	public function test_apply_animation_do_not_apply_to_container() {
		$post_id = $this->create_post_with_block();

		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'                   => $post_id,
				'block_index'               => 0,
				'type'                      => 'zoomIn',
				'do_not_apply_to_container' => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['animation_settings']['UAGAnimationDoNotApplyToContainer'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGAnimationDoNotApplyToContainer'] );
	}

	// =========================================================================
	// Additional RemoveAnimation tests
	// =========================================================================

	/**
	 * Test RemoveAnimation output schema.
	 */
	public function test_remove_animation_output_schema() {
		$schema = RemoveAnimation::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'removed', $schema['properties'] );
	}

	/**
	 * Test RemoveAnimation on a block with no animation attributes.
	 */
	public function test_remove_animation_when_no_animation_present() {
		$post_id = $this->create_post_with_block();

		$result = RemoveAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertEmpty( $result['removed'] );
	}

	/**
	 * Test RemoveAnimation fails for invalid block index.
	 */
	public function test_remove_animation_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = RemoveAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	// =========================================================================
	// Additional ApplySticky tests
	// =========================================================================

	/**
	 * Test ApplySticky output schema.
	 */
	public function test_apply_sticky_output_schema() {
		$schema = ApplySticky::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
	}

	/**
	 * Test ApplySticky fails for invalid block index.
	 */
	public function test_apply_sticky_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test ApplySticky fails for non-existent post.
	 */
	public function test_apply_sticky_nonexistent_post() {
		$result = ApplySticky::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test ApplySticky overwrites existing UAGPosition value.
	 */
	public function test_apply_sticky_overwrites_existing_position() {
		$content = '<!-- wp:spectra/container {"block_id":"test","UAGPosition":"fixed"} --><div></div><!-- /wp:spectra/container -->';
		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 'sticky', $attrs['UAGPosition'] );
	}

	// =========================================================================
	// Additional RemoveSticky tests
	// =========================================================================

	/**
	 * Test RemoveSticky output schema.
	 */
	public function test_remove_sticky_output_schema() {
		$schema = RemoveSticky::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
	}

	/**
	 * Test RemoveSticky on a block that is not sticky.
	 */
	public function test_remove_sticky_when_not_sticky() {
		$post_id = $this->create_post_with_block();

		$result = RemoveSticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test RemoveSticky fails for invalid block index.
	 */
	public function test_remove_sticky_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = RemoveSticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	// =========================================================================
	// Additional ApplyResponsiveConditions tests
	// =========================================================================

	/**
	 * Test ApplyResponsiveConditions output schema.
	 */
	public function test_apply_responsive_conditions_output_schema() {
		$schema = ApplyResponsiveConditions::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'conditions_applied', $schema['properties'] );
	}

	/**
	 * Test ApplyResponsiveConditions with browser and OS targeting.
	 */
	public function test_apply_responsive_conditions_browser_and_os() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'browser'     => array( 'chrome', 'firefox' ),
				'os'          => array( 'windows', 'macos' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( array( 'chrome', 'firefox' ), $attrs['UAGBrowser'] );
		$this->assertSame( array( 'windows', 'macos' ), $attrs['UAGSystem'] );
	}

	/**
	 * Test ApplyResponsiveConditions with day-of-week targeting.
	 */
	public function test_apply_responsive_conditions_day() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'day'         => array( 'monday', 'wednesday', 'friday' ),
			)
		);

		$this->assertIsArray( $result );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( array( 'monday', 'wednesday', 'friday' ), $attrs['UAGDay'] );
	}

	/**
	 * Test ApplyResponsiveConditions fails for invalid block index.
	 */
	public function test_apply_responsive_conditions_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test ApplyResponsiveConditions fails for non-existent post.
	 */
	public function test_apply_responsive_conditions_nonexistent_post() {
		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	// =========================================================================
	// Additional RemoveResponsiveConditions tests
	// =========================================================================

	/**
	 * Test RemoveResponsiveConditions output schema.
	 */
	public function test_remove_responsive_conditions_output_schema() {
		$schema = RemoveResponsiveConditions::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'removed', $schema['properties'] );
	}

	/**
	 * Test RemoveResponsiveConditions on block with no conditions.
	 */
	public function test_remove_responsive_conditions_when_no_conditions() {
		$post_id = $this->create_post_with_block();

		$result = RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertEmpty( $result['removed'] );
	}

	/**
	 * Test RemoveResponsiveConditions fails for invalid block index.
	 */
	public function test_remove_responsive_conditions_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	// =========================================================================
	// Additional ApplyZIndex tests
	// =========================================================================

	/**
	 * Test ApplyZIndex output schema.
	 */
	public function test_apply_zindex_output_schema() {
		$schema = ApplyZIndex::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'zindex', $schema['properties'] );
	}

	/**
	 * Test ApplyZIndex with zero value.
	 */
	public function test_apply_zindex_zero_value() {
		$post_id = $this->create_post_with_block();

		$result = ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['zindex'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 0, $attrs['spectraZIndex'] );
	}

	/**
	 * Test ApplyZIndex with negative value.
	 */
	public function test_apply_zindex_negative_value() {
		$post_id = $this->create_post_with_block();

		$result = ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => -1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( -1, $result['zindex'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( -1, $attrs['spectraZIndex'] );
	}

	/**
	 * Test ApplyZIndex fails for non-existent post.
	 */
	public function test_apply_zindex_nonexistent_post() {
		$result = ApplyZIndex::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
				'zindex'      => 10,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test ApplyZIndex fails for invalid block index.
	 */
	public function test_apply_zindex_invalid_block_index() {
		$post_id = $this->create_post_with_block();

		$result = ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
				'zindex'      => 10,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test ApplyZIndex overwrites previous value.
	 */
	public function test_apply_zindex_overwrites_previous() {
		$post_id = $this->create_post_with_block();

		ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 99,
			)
		);

		ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 10,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 10, $attrs['spectraZIndex'] );
	}

	// =========================================================================
	// Additional ApplyImageMask tests
	// =========================================================================

	/**
	 * Test ApplyImageMask output schema.
	 */
	public function test_apply_image_mask_output_schema() {
		$schema = ApplyImageMask::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'mask_settings', $schema['properties'] );
	}

	/**
	 * Test ApplyImageMask with all position/size/repeat options.
	 */
	public function test_apply_image_mask_all_options() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'hexagon',
				'size'        => 'cover',
				'position_x'  => 0.25,
				'position_y'  => 0.75,
				'repeat'      => 'repeat',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'hexagon', $result['mask_settings']['shape'] );
		$this->assertSame( 'cover', $result['mask_settings']['size'] );
		$this->assertEquals( 0.25, $result['mask_settings']['position']['x'] );
		$this->assertEquals( 0.75, $result['mask_settings']['position']['y'] );
		$this->assertSame( 'repeat', $result['mask_settings']['repeat'] );
	}

	/**
	 * Test ApplyImageMask fails for non-existent post.
	 */
	public function test_apply_image_mask_nonexistent_post() {
		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
				'shape'       => 'circle',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test ApplyImageMask fails for invalid block index.
	 */
	public function test_apply_image_mask_invalid_block_index() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
				'shape'       => 'circle',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test ApplyImageMask with 'none' shape stores correctly.
	 */
	public function test_apply_image_mask_none_shape() {
		$post_id = $this->create_post_with_image();

		$result = ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'none',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'none', $result['mask_settings']['shape'] );
	}

	/**
	 * Test ApplyImageMask verifies saved attributes via GetPostContent.
	 */
	public function test_apply_image_mask_verify_via_read() {
		$post_id = $this->create_post_with_image();

		ApplyImageMask::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'shape'       => 'diamond',
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayHasKey( 'spectraMask', $attrs );
		$this->assertSame( 'diamond', $attrs['spectraMask']['shape'] );
	}

	// =========================================================================
	// Extension Integration Cycles
	// =========================================================================

	/**
	 * Test animation apply → read → remove → verify clean state.
	 */
	public function test_animation_apply_read_remove_cycle() {
		$post_id = $this->create_post_with_block();

		// Apply.
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'fadeIn',
				'duration'    => 500,
			)
		);

		// Read and verify.
		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 'fadeIn', $attrs['UAGAnimationType'] );
		$this->assertSame( 500, $attrs['UAGAnimationTime'] );

		// Remove.
		RemoveAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Verify clean.
		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGAnimationType', $attrs );
		$this->assertArrayNotHasKey( 'UAGAnimationTime', $attrs );
	}

	/**
	 * Test sticky apply → read → remove → verify clean state.
	 */
	public function test_sticky_apply_read_remove_cycle() {
		$post_id = $this->create_post_with_block();

		ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertSame( 'sticky', $attrs['UAGPosition'] );

		RemoveSticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGPosition', $attrs );
	}

	/**
	 * Test responsive conditions apply → read → remove → verify clean state.
	 */
	public function test_responsive_conditions_apply_read_remove_cycle() {
		$post_id = $this->create_post_with_block();

		ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'      => $post_id,
				'block_index'  => 0,
				'hide_desktop' => true,
				'hide_mobile'  => true,
				'logged_in'    => true,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGHideDesktop'] );
		$this->assertTrue( $attrs['UAGHideMob'] );
		$this->assertTrue( $attrs['UAGLoggedIn'] );
		$this->assertTrue( $attrs['UAGResponsiveConditions'] );

		RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertArrayNotHasKey( 'UAGHideDesktop', $attrs );
		$this->assertArrayNotHasKey( 'UAGHideMob', $attrs );
		$this->assertArrayNotHasKey( 'UAGLoggedIn', $attrs );
		$this->assertArrayNotHasKey( 'UAGResponsiveConditions', $attrs );
	}

	// =========================================================================
	// Permission Tests for All Extension Abilities
	// =========================================================================

	/**
	 * Test ApplyAnimation denies subscribers.
	 */
	public function test_apply_animation_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ApplyAnimation::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ApplyAnimation grants access to editors.
	 */
	public function test_apply_animation_permission_editor() {
		$this->assertTrue( ApplyAnimation::instance()->check_permission() );
	}

	/**
	 * Test RemoveAnimation denies subscribers.
	 */
	public function test_remove_animation_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = RemoveAnimation::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ApplySticky denies subscribers.
	 */
	public function test_apply_sticky_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ApplySticky::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test RemoveSticky denies subscribers.
	 */
	public function test_remove_sticky_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = RemoveSticky::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ApplyResponsiveConditions denies subscribers.
	 */
	public function test_apply_responsive_conditions_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ApplyResponsiveConditions::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test RemoveResponsiveConditions denies subscribers.
	 */
	public function test_remove_responsive_conditions_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = RemoveResponsiveConditions::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ApplyZIndex denies subscribers.
	 */
	public function test_apply_zindex_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ApplyZIndex::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ApplyImageMask denies subscribers.
	 */
	public function test_apply_image_mask_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ApplyImageMask::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// =========================================================================
	// Missing Input Schema Tests
	// =========================================================================

	/**
	 * Test RemoveAnimation input schema.
	 */
	public function test_remove_animation_input_schema() {
		$schema = RemoveAnimation::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test ApplySticky input schema.
	 */
	public function test_apply_sticky_input_schema() {
		$schema = ApplySticky::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test RemoveSticky input schema.
	 */
	public function test_remove_sticky_input_schema() {
		$schema = RemoveSticky::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test ApplyResponsiveConditions input schema.
	 */
	public function test_apply_responsive_conditions_input_schema() {
		$schema = ApplyResponsiveConditions::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test RemoveResponsiveConditions input schema.
	 */
	public function test_remove_responsive_conditions_input_schema() {
		$schema = RemoveResponsiveConditions::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test ApplyImageMask input schema.
	 */
	public function test_apply_image_mask_input_schema() {
		$schema = ApplyImageMask::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
		$this->assertContains( 'shape', $schema['required'] );
	}

	// =========================================================================
	// Missing Edge Cases
	// =========================================================================

	/**
	 * Test ApplyAnimation with repeat and delay_interval params.
	 */
	public function test_apply_animation_repeat_and_delay_interval() {
		$post_id = $this->create_post_with_block();

		$result = ApplyAnimation::instance()->execute(
			array(
				'post_id'        => $post_id,
				'block_index'    => 0,
				'type'           => 'fadeIn',
				'repeat'         => true,
				'delay_interval' => 500,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertNotEmpty( $attrs['UAGAnimationRepeat'] );
		$this->assertSame( 500, $attrs['UAGAnimationDelayInterval'] );
	}

	/**
	 * Test ApplyResponsiveConditions with logged_out param.
	 */
	public function test_apply_responsive_conditions_logged_out() {
		$post_id = $this->create_post_with_block();

		$result = ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'logged_out'  => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGLoggedOut'] );
		$this->assertTrue( $attrs['UAGResponsiveConditions'] );
	}

	/**
	 * Test RemoveSticky fails for non-existent post.
	 */
	public function test_remove_sticky_nonexistent_post() {
		$result = RemoveSticky::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test RemoveResponsiveConditions fails for non-existent post.
	 */
	public function test_remove_responsive_conditions_nonexistent_post() {
		$result = RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test RemoveAnimation fails for non-existent post.
	 */
	public function test_remove_animation_nonexistent_post() {
		$result = RemoveAnimation::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test RemoveResponsiveConditions removes all 11 condition keys when all are set.
	 */
	public function test_remove_responsive_conditions_removes_all_keys() {
		$post_id = $this->create_post_with_block();

		// Apply all possible conditions.
		ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'      => $post_id,
				'block_index'  => 0,
				'hide_desktop' => true,
				'hide_tablet'  => true,
				'hide_mobile'  => true,
				'logged_in'    => true,
				'logged_out'   => true,
				'user_role'    => array( 'administrator' ),
				'browser'      => array( 'chrome' ),
				'os'           => array( 'windows' ),
				'day'          => array( 'monday' ),
			)
		);

		// Verify all keys are set.
		$attrs = $this->get_block_attrs( $post_id );
		$this->assertTrue( $attrs['UAGHideDesktop'] );
		$this->assertTrue( $attrs['UAGHideTab'] );
		$this->assertTrue( $attrs['UAGHideMob'] );
		$this->assertTrue( $attrs['UAGLoggedIn'] );
		$this->assertTrue( $attrs['UAGLoggedOut'] );

		// Remove all.
		$result = RemoveResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		// Verify all condition keys are gone.
		$attrs          = $this->get_block_attrs( $post_id );
		$condition_keys = array(
			'UAGHideDesktop',
			'UAGHideMob',
			'UAGHideTab',
			'UAGLoggedIn',
			'UAGLoggedOut',
			'UAGUserRole',
			'UAGBrowser',
			'UAGSystem',
			'UAGDay',
			'UAGDisplayConditions',
			'UAGResponsiveConditions',
		);
		foreach ( $condition_keys as $key ) {
			$this->assertArrayNotHasKey( $key, $attrs, "Key '$key' should be removed." );
		}
	}
}
