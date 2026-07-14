<?php
/**
 * Spectra Blocks Abilities Manager.
 *
 * Registers Spectra block capabilities as WordPress Abilities,
 * making them discoverable and executable by AI agents.
 *
 * @package Spectra_Blocks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Abilities_Manager' ) ) {

	/**
	 * Class Spectra_Blocks_Abilities_Manager.
	 *
	 * @since 1.0.0
	 */
	final class Spectra_Blocks_Abilities_Manager {

		/**
		 * Instance.
		 *
		 * @var Spectra_Blocks_Abilities_Manager|null
		 */
		private static $instance;

		/**
		 * Get instance.
		 *
		 * @since 1.0.0
		 * @return Spectra_Blocks_Abilities_Manager
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Requires WordPress Abilities API (6.9+).
			if ( ! function_exists( 'wp_register_ability' ) ) {
				return;
			}

			// Bail if master toggle is off.
			if ( 'enabled' !== \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_abilities', 'disabled' ) ) {
				return;
			}

			$this->load_ability_classes();

			add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
			add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

			// Register dedicated Spectra MCP server when enabled and MCP Adapter is active.
			if (
				'enabled' === \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_mcp_server', 'disabled' )
				&& class_exists( 'WP\\MCP\\Plugin' )
			) {
				add_action( 'mcp_adapter_init', array( $this, 'register_mcp_server' ) );
			}
		}

		/**
		 * Load ability class files.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function load_ability_classes() {
			$dir = SPECTRA_BLOCKS_DIR . 'classes/abilities/';

			require_once $dir . 'class-spectra-blocks-abstract-ability.php';
			require_once $dir . 'trait-spectra-blocks-insert-into-post.php';
			require_once $dir . 'class-spectra-blocks-ability-create-container.php';
			require_once $dir . 'class-spectra-blocks-ability-create-content.php';
		}

		/**
		 * Register ability categories.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function register_categories() {
			wp_register_ability_category(
				'spectra-layout',
				array(
					'label'       => __( 'Spectra - Layout', 'spectra-blocks' ),
					'description' => __( 'Create and configure Spectra layout blocks like containers.', 'spectra-blocks' ),
				)
			);

			wp_register_ability_category(
				'spectra-content',
				array(
					'label'       => __( 'Spectra - Content', 'spectra-blocks' ),
					'description' => __( 'Create and manage Spectra content blocks like headings and paragraphs.', 'spectra-blocks' ),
				)
			);
		}

		/**
		 * Register all abilities.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function register_abilities() {
			$abilities = array(
				new Spectra_Blocks_Ability_Create_Container(),
				new Spectra_Blocks_Ability_Create_Content(),
			);

			foreach ( $abilities as $ability ) {
				if ( $ability->is_enabled() ) {
					$ability->register();
				}
			}
		}

		/**
		 * Register a dedicated Spectra MCP server.
		 *
		 * Creates an MCP server at spectra-blocks/v1/mcp exposing only spectra/ abilities.
		 *
		 * @since 1.0.0
		 * @param object $adapter The MCP adapter instance.
		 * @return void
		 */
		public function register_mcp_server( $adapter ) {
			if ( ! function_exists( 'wp_get_abilities' ) ) {
				return;
			}

			$abilities = wp_get_abilities();
			$tools     = array();

			foreach ( $abilities as $ability ) {
				if ( 0 === strpos( $ability->get_name(), 'spectra/' ) ) {
					$tools[] = $ability->get_name();
				}
			}

			$transport_class = class_exists( '\WP\MCP\Transport\HttpTransport' )
				? \WP\MCP\Transport\HttpTransport::class
				: \WP\MCP\Transport\Http\RestTransport::class;

			$adapter->create_server(
				'spectra-blocks',
				'spectra-blocks/v1',
				'mcp',
				__( 'Spectra Blocks MCP Server', 'spectra-blocks' ),
				__( 'Spectra Blocks MCP server for AI-assisted block creation and content generation.', 'spectra-blocks' ),
				defined( 'SPECTRA_BLOCKS_VER' ) ? SPECTRA_BLOCKS_VER : '1.0.0',
				array( $transport_class ),
				\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
				\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
				$tools,
				array(),
				array()
			);
		}
	}

	Spectra_Blocks_Abilities_Manager::get_instance();
}
