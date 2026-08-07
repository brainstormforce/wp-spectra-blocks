<?php
/**
 * Spectra Blocks Rollback.
 *
 * @package Spectra_Blocks
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Spectra_Blocks_Rollback.
 *
 * @since 1.0.1
 */
class Spectra_Blocks_Rollback {

	/**
	 * Package URL.
	 *
	 * @since 1.0.1
	 * @var string
	 */
	protected $package_url;

	/**
	 * Version.
	 *
	 * @since 1.0.1
	 * @var string
	 */
	protected $version;

	/**
	 * Plugin name (basename).
	 *
	 * @since 1.0.1
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.1
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Constructor.
	 *
	 * @since 1.0.1
	 * @param array $args Rollback arguments.
	 */
	public function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Register admin-post action.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_spectra_blocks_rollback', array( __CLASS__, 'handle_rollback' ) );
	}

	/**
	 * Handle the rollback admin-post request.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public static function handle_rollback() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'spectra-blocks' ),
				esc_html__( 'Rollback to Previous Version', 'spectra-blocks' ),
				array( 'response' => 200 )
			);
		}

		check_admin_referer( 'spectra_blocks_rollback' );

		$rollback_versions = Spectra_Blocks_Admin_Helper::get_rollback_versions();
		$update_version    = isset( $_GET['version'] ) ? sanitize_text_field( wp_unslash( $_GET['version'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified via check_admin_referer above.

		if ( empty( $update_version ) || ! in_array( $update_version, $rollback_versions, true ) ) {
			wp_die( esc_html__( 'Error occurred. The version selected is invalid. Try selecting a different version.', 'spectra-blocks' ) );
		}

		$plugin_slug = basename( SPECTRA_BLOCKS_FILE, '.php' );

		$rollback = new self(
			array(
				'version'     => $update_version,
				'plugin_name' => SPECTRA_BLOCKS_BASE,
				'plugin_slug' => $plugin_slug,
				'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, $update_version ),
			)
		);

		$rollback->run();

		wp_die(
			'',
			esc_html__( 'Rollback to Previous Version', 'spectra-blocks' ),
			array( 'response' => 200 )
		);
	}

	/**
	 * Modify update_plugins transient to inject the specific rollback package URL.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	protected function apply_package() {
		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! is_object( $update_plugins ) ) {
			$update_plugins = new \stdClass();
		}

		$plugin_info              = new \stdClass();
		$plugin_info->new_version = $this->version;
		$plugin_info->slug        = $this->plugin_slug;
		$plugin_info->package     = $this->package_url;
		$plugin_info->url         = 'https://wpspectra.com/';

		$update_plugins->response[ $this->plugin_name ] = $plugin_info;

		set_site_transient( 'update_plugins', $update_plugins );
	}

	/**
	 * Print inline styles for the upgrader progress page.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	private function print_inline_style() {
		?>
		<style>
			.wrap {
				overflow: hidden;
				max-width: 850px;
				margin: auto;
				font-family: Courier, monospace;
			}

			h1 {
				background: #6828f3;
				text-align: center;
				color: #fff !important;
				padding: 70px !important;
				text-transform: uppercase;
				letter-spacing: 1px;
			}

			h1 img {
				max-width: 300px;
				display: block;
				margin: auto auto 50px;
			}
		</style>
		<?php
	}

	/**
	 * Run the WordPress plugin upgrader for the rollback.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	protected function upgrade() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$this->print_inline_style();

		$upgrader_args = array(
			'url'    => 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $this->plugin_name ),
			'plugin' => $this->plugin_name,
			'nonce'  => 'upgrade-plugin_' . $this->plugin_name,
			'title'  => __( 'Spectra Blocks <p>Rollback to Previous Version</p>', 'spectra-blocks' ),
		);

		$upgrader = new \Plugin_Upgrader( new \Plugin_Upgrader_Skin( $upgrader_args ) );
		$upgrader->upgrade( $this->plugin_name );
	}

	/**
	 * Execute the rollback.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function run() {
		$this->apply_package();
		$this->upgrade();
	}
}
