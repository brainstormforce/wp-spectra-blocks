<?php
/**
 * Tests for AbstractAbility base class.
 *
 * Uses CreateSeparator as a minimal concrete implementation.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\CreateSeparator;

/**
 * AbstractAbility test case.
 */
class AbstractAbilityTest extends WP_UnitTestCase {

	/**
	 * Ability instance.
	 *
	 * @var CreateSeparator
	 */
	private $ability;

	/**
	 * Set up test.
	 */
	public function set_up() {
		parent::set_up();
		$this->ability = CreateSeparator::instance();
	}

	/**
	 * Test check_permission grants access to editors.
	 */
	public function test_check_permission_grants_access_to_editor() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( $this->ability->check_permission() );
	}

	/**
	 * Test check_permission denies access to subscribers.
	 */
	public function test_check_permission_denies_subscriber() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$result = $this->ability->check_permission();
		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test check_permission denies logged-out users.
	 */
	public function test_check_permission_denies_logged_out() {
		wp_set_current_user( 0 );

		$result = $this->ability->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test get_validated_post returns post for valid ID.
	 */
	public function test_get_validated_post_with_valid_post() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_title' => 'Test Post' ) );

		$method = new ReflectionMethod( $this->ability, 'get_validated_post' );
		$method->setAccessible( true );

		$post = $method->invoke( $this->ability, $post_id );
		$this->assertInstanceOf( WP_Post::class, $post );
		$this->assertSame( $post_id, $post->ID );
	}

	/**
	 * Test get_validated_post returns error for non-existent post.
	 */
	public function test_get_validated_post_with_nonexistent_post() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$method = new ReflectionMethod( $this->ability, 'get_validated_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, 999999 );
		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test get_validated_post returns error when user cannot edit.
	 */
	public function test_get_validated_post_without_edit_permission() {
		$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
		$other_id  = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$post_id = self::factory()->post->create( array( 'post_author' => $author_id ) );

		wp_set_current_user( $other_id );

		$method = new ReflectionMethod( $this->ability, 'get_validated_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, $post_id );
		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test insert_into_post appends content.
	 */
	public function test_insert_into_post_append() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_content' => 'Existing content.' ) );

		$method = new ReflectionMethod( $this->ability, 'insert_into_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, $post_id, '<!-- wp:spectra/separator /-->', 'append' );

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['post_id'] );
		$this->assertStringContainsString( 'Existing content.', $result['post_content'] );
		$this->assertStringContainsString( '<!-- wp:spectra/separator /-->', $result['post_content'] );
		// Appended content should come after existing.
		$this->assertGreaterThan(
			strpos( $result['post_content'], 'Existing content.' ),
			strpos( $result['post_content'], '<!-- wp:spectra/separator /-->' )
		);
	}

	/**
	 * Test insert_into_post prepends content.
	 */
	public function test_insert_into_post_prepend() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_content' => 'Existing content.' ) );

		$method = new ReflectionMethod( $this->ability, 'insert_into_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, $post_id, '<!-- wp:spectra/separator /-->', 'prepend' );

		$this->assertIsArray( $result );
		// Prepended content should come before existing.
		$this->assertLessThan(
			strpos( $result['post_content'], 'Existing content.' ),
			strpos( $result['post_content'], '<!-- wp:spectra/separator /-->' )
		);
	}

	/**
	 * Test insert_into_post replaces content.
	 */
	public function test_insert_into_post_replace() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_content' => 'Existing content.' ) );

		$method = new ReflectionMethod( $this->ability, 'insert_into_post' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, $post_id, '<!-- wp:spectra/separator /-->', 'replace' );

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'Existing content.', $result['post_content'] );
		$this->assertSame( '<!-- wp:spectra/separator /-->', $result['post_content'] );
	}

	/**
	 * Test maybe_insert_and_return without post_id returns markup only.
	 */
	public function test_maybe_insert_and_return_without_post_id() {
		$method = new ReflectionMethod( $this->ability, 'maybe_insert_and_return' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->ability, '<!-- wp:spectra/separator /-->', array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'block_markup', $result );
		$this->assertArrayNotHasKey( 'post_id', $result );
		$this->assertSame( '<!-- wp:spectra/separator /-->', $result['block_markup'] );
	}

	/**
	 * Test maybe_insert_and_return with post_id inserts and returns both.
	 */
	public function test_maybe_insert_and_return_with_post_id() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		$method = new ReflectionMethod( $this->ability, 'maybe_insert_and_return' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$this->ability,
			'<!-- wp:spectra/separator /-->',
			array(
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'block_markup', $result );
		$this->assertArrayHasKey( 'post_id', $result );
		$this->assertArrayHasKey( 'post_content', $result );
		$this->assertSame( $post_id, $result['post_id'] );
	}

	/**
	 * Test get_block_markup_output_schema returns valid structure.
	 */
	public function test_get_block_markup_output_schema() {
		$method = new ReflectionMethod( $this->ability, 'get_block_markup_output_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( $this->ability );

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'post_content', $schema['properties'] );
	}

	/**
	 * Test get_post_insertion_schema returns post_id and mode.
	 */
	public function test_get_post_insertion_schema() {
		$method = new ReflectionMethod( $this->ability, 'get_post_insertion_schema' );
		$method->setAccessible( true );

		$schema = $method->invoke( $this->ability );

		$this->assertArrayHasKey( 'post_id', $schema );
		$this->assertArrayHasKey( 'mode', $schema );
		$this->assertSame( 'integer', $schema['post_id']['type'] );
		$this->assertSame( array( 'replace', 'append', 'prepend' ), $schema['mode']['enum'] );
	}

	/**
	 * Test register calls wp_register_ability.
	 */
	public function test_register_calls_wp_register_ability() {
		if ( function_exists( 'wp_has_ability' ) ) {
			// WP 6.9+: ability is already registered during plugin boot via wp_abilities_api_init.
			$this->assertTrue( wp_has_ability( 'spectra-blocks/create-separator' ) );
		} else {
			// Older WP: verify via test stub global.
			global $_spectra_test_registered_abilities;
			$_spectra_test_registered_abilities = array();

			$this->ability->register();

			$this->assertArrayHasKey( 'spectra-blocks/create-separator', $_spectra_test_registered_abilities );
		}
	}
}
