<?php
/**
 * Font Library activation across a theme switch.
 *
 * Each theme has its own user `wp_global_styles` post. Families active under
 * one theme are unknown to the next, and the install is already done, so no
 * `save_post_wp_font_*` hook fires to fix it. On `after_switch_theme` the bridge
 * carries what the previous theme had active (and nothing it had turned off);
 * on `zipai_font_library_ready` it activates the families the importer names.
 *
 * @package Spectra\Tests\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\GlobalStylesBridge;
use WP_UnitTestCase;

/**
 * FontLibraryThemeSwitchTest test case.
 *
 * @since 1.0.10
 */
class FontLibraryThemeSwitchTest extends WP_UnitTestCase {

	const THEME_A       = 'sb-font-switch-a';
	const THEME_B       = 'sb-font-switch-b';
	const THEME_CLASSIC = 'sb-font-switch-classic';

	/**
	 * Themes root registered for the fixtures.
	 *
	 * @var string
	 */
	private $root = '';

	/**
	 * The bridge whose hooks this test registered.
	 *
	 * @var GlobalStylesBridge
	 */
	private $bridge;

	/**
	 * Two throwaway block themes and a classic one; start on block theme A.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// The bridge's hooks, whether or not the plugin bootstrap already added them
		// (the sync is idempotent; the hooks are dropped again at tear-down).
		$this->bridge = new GlobalStylesBridge( Engine::get_instance() );
		$this->bridge->init();

		$this->root = untrailingslashit( get_temp_dir() ) . '/spectra-font-switch-themes';
		foreach ( array( self::THEME_A, self::THEME_B, self::THEME_CLASSIC ) as $slug ) {
			$dir = $this->root . '/' . $slug;
			wp_mkdir_p( $dir . '/templates' );
			file_put_contents( $dir . '/style.css', "/*\nTheme Name: {$slug}\nVersion: 1.0.0\nText Domain: {$slug}\n*/\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
			if ( self::THEME_CLASSIC === $slug ) {
				file_put_contents( $dir . '/index.php', "<?php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
				continue;
			}
			file_put_contents( $dir . '/theme.json', (string) wp_json_encode( array( 'version' => 3 ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
			file_put_contents( $dir . '/templates/index.html', "<!-- wp:post-content /-->\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
		}
		register_theme_directory( $this->root );
		wp_clean_themes_cache();

		$this->activate( self::THEME_A );
	}

	/**
	 * Drop the fixture theme root and the resolver's cached theme data.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wp_theme_directories;
		$wp_theme_directories = array_values( array_diff( (array) $wp_theme_directories, array( $this->root ) ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- undo register_theme_directory().
		wp_clean_themes_cache();
		if ( class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
		parent::tear_down();
	}

	/**
	 * A family active under theme A is active in theme B right after the switch.
	 *
	 * @return void
	 */
	public function test_after_switch_theme_carries_the_active_families(): void {
		$this->install_family( 'plus-jakarta-sans' );
		$this->assertContains( 'plus-jakarta-sans', $this->active_slugs( self::THEME_A ), 'Precondition: the install activates the family in theme A.' );

		$this->activate( self::THEME_B );
		$this->assertNotContains( 'plus-jakarta-sans', $this->active_slugs( self::THEME_B ), 'Precondition: theme B starts without the family.' );

		do_action( 'after_switch_theme', 'Theme A', wp_get_theme( self::THEME_A ) );

		$this->assertContains( 'plus-jakarta-sans', $this->active_slugs( self::THEME_B ) );
	}

	/**
	 * A family the user turned off under theme A stays off in theme B.
	 *
	 * @return void
	 */
	public function test_after_switch_theme_leaves_a_family_turned_off_off(): void {
		$this->install_family( 'plus-jakarta-sans' );
		$this->install_family( 'lora' );
		$this->deactivate( self::THEME_A, 'lora' );

		$this->activate( self::THEME_B );
		do_action( 'after_switch_theme', 'Theme A', wp_get_theme( self::THEME_A ) );

		$active = $this->active_slugs( self::THEME_B );
		$this->assertContains( 'plus-jakarta-sans', $active );
		$this->assertNotContains( 'lora', $active );
	}

	/**
	 * A classic theme without imported chrome gets nothing on the switch.
	 *
	 * @return void
	 */
	public function test_after_switch_theme_skips_a_classic_theme_without_imported_chrome(): void {
		$this->install_family( 'plus-jakarta-sans' );
		$this->activate( self::THEME_CLASSIC );

		do_action( 'after_switch_theme', 'Theme A', wp_get_theme( self::THEME_A ) );

		$this->assertNull( $this->styles_post( self::THEME_CLASSIC ) );
	}

	/**
	 * The first request after a switch can be a visitor's: no untagged post is made.
	 *
	 * @return void
	 */
	public function test_after_switch_theme_as_a_visitor_creates_no_post(): void {
		$this->install_family( 'plus-jakarta-sans' );
		$this->activate( self::THEME_B );
		$before = $this->styles_post_count();

		wp_set_current_user( 0 );
		do_action( 'after_switch_theme', 'Theme A', wp_get_theme( self::THEME_A ) );

		$this->assertSame( $before, $this->styles_post_count() );
	}

	/**
	 * The carry a visitor's request could not write runs on the next admin request.
	 *
	 * @return void
	 */
	public function test_a_visitor_switch_is_carried_on_the_next_admin_request(): void {
		$admin = get_current_user_id();
		$this->install_family( 'plus-jakarta-sans' );
		$this->activate( self::THEME_B );

		wp_set_current_user( 0 );
		do_action( 'after_switch_theme', 'Theme A', wp_get_theme( self::THEME_A ) );
		$this->assertSame( self::THEME_A, get_option( GlobalStylesBridge::FONT_CARRY_PENDING_OPTION ) );

		// Core's own admin_init callbacks send headers, so the listener runs alone.
		$this->assertSame( 20, has_action( 'admin_init', array( $this->bridge, 'carry_pending_font_activation' ) ) );
		wp_set_current_user( $admin );
		$this->bridge->carry_pending_font_activation();

		$this->assertContains( 'plus-jakarta-sans', $this->active_slugs( self::THEME_B ) );
		$this->assertFalse( get_option( GlobalStylesBridge::FONT_CARRY_PENDING_OPTION ) );
	}

	/**
	 * The importer's ready signal activates exactly the families it names.
	 *
	 * @return void
	 */
	public function test_font_library_ready_activates_the_named_families_only(): void {
		$this->install_family( 'plus-jakarta-sans' );
		$this->install_family( 'lora' );
		$this->activate( self::THEME_B );

		do_action( 'zipai_font_library_ready', array( 'plus-jakarta-sans' ) );

		$active = $this->active_slugs( self::THEME_B );
		$this->assertContains( 'plus-jakarta-sans', $active );
		$this->assertNotContains( 'lora', $active );
	}

	/**
	 * Switch theme and drop the resolver's cached user post.
	 *
	 * @param string $slug Theme slug.
	 * @return void
	 */
	private function activate( string $slug ): void {
		switch_theme( $slug );
		if ( get_stylesheet() !== $slug ) {
			$this->markTestSkipped( 'Fixture theme could not be activated.' );
		}
		if ( class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
	}

	/**
	 * Insert a Font Library family with one face (fires the bridge's save hooks).
	 *
	 * @param string $slug Family slug.
	 * @return void
	 */
	private function install_family( string $slug ): void {
		$name      = ucwords( str_replace( '-', ' ', $slug ) );
		$family_id = self::factory()->post->create(
			array(
				'post_type'    => 'wp_font_family',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_name'    => $slug,
				'post_content' => (string) wp_json_encode( array( 'fontFamily' => "\"{$name}\", sans-serif" ) ),
			)
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'wp_font_face',
				'post_status'  => 'publish',
				'post_parent'  => $family_id,
				'post_content' => (string) wp_json_encode(
					array(
						'fontFamily' => $name,
						'fontWeight' => '400',
						'fontStyle'  => 'normal',
						'src'        => "https://example.test/fonts/{$slug}-400.woff2",
					)
				),
			)
		);
	}

	/**
	 * Remove a family from a theme's active list, as the Font Library's "deactivate" does.
	 *
	 * @param string $stylesheet Theme stylesheet.
	 * @param string $slug       Family slug.
	 * @return void
	 */
	private function deactivate( string $stylesheet, string $slug ): void {
		global $wpdb;
		$post = $this->styles_post( $stylesheet );
		$this->assertNotNull( $post, 'Precondition: the theme has a user global-styles post.' );
		$content = json_decode( $post->post_content, true );
		$custom  = $content['settings']['typography']['fontFamilies']['custom'] ?? array();
		$content['settings']['typography']['fontFamilies']['custom'] = array_values(
			array_filter( $custom, static fn ( $f ) => ( $f['slug'] ?? '' ) !== $slug )
		);
		$wpdb->update( $wpdb->posts, array( 'post_content' => (string) wp_json_encode( $content ) ), array( 'ID' => $post->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- the bridge's own write path.
		clean_post_cache( $post->ID );
	}

	/**
	 * A theme's user global-styles post, found by its wp_theme term (never created here).
	 *
	 * @param string $stylesheet Theme stylesheet.
	 * @return \WP_Post|null
	 */
	private function styles_post( string $stylesheet ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'   => 'wp_global_styles',
				'post_status' => array( 'publish', 'auto-draft' ),
				'numberposts' => 1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query'   => array(
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => $stylesheet,
					),
				),
			)
		);
		return isset( $posts[0] ) && $posts[0] instanceof \WP_Post ? $posts[0] : null;
	}

	/**
	 * Every wp_global_styles post, tagged or not.
	 *
	 * @return int
	 */
	private function styles_post_count(): int {
		return count(
			get_posts(
				array(
					'post_type'   => 'wp_global_styles',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * Slugs in a theme's user global-styles `fontFamilies.custom`.
	 *
	 * @param string $stylesheet Theme stylesheet.
	 * @return array<int, string>
	 */
	private function active_slugs( string $stylesheet ): array {
		$post    = $this->styles_post( $stylesheet );
		$content = null !== $post ? json_decode( $post->post_content, true ) : array();
		$custom  = is_array( $content ) ? ( $content['settings']['typography']['fontFamilies']['custom'] ?? array() ) : array();
		return array_values( array_filter( array_map( static fn ( $f ) => is_array( $f ) ? (string) ( $f['slug'] ?? '' ) : '', (array) $custom ) ) );
	}
}
