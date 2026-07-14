<?php
/**
 * REST controller for Global Styles CRUD.
 *
 * Exposes three resources under the `spectra-blocks/v1/global-styles`
 * namespace: `custom-classes`, `keyframes`, and the idempotent `bulk`
 * writer that accepts classes + keyframes in one call. Replaces the
 * legacy Pro AJAX handlers — ERA writes via these endpoints and Pro's
 * admin UI swaps its fetches to call the same routes.
 *
 * All routes require the `edit_theme_options` capability. Payloads are
 * sanitized via {@see Sanitizer}.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestController.
 *
 * @since 1.0.0
 */
class RestController {

	/**
	 * Register all routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		$namespace = Engine::REST_NAMESPACE;

		register_rest_route(
			$namespace,
			'/global-styles/custom-classes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_classes' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'post_id' => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_custom_class' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'class_name'     => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
						'styles'         => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
						'is_destructive' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'post_id'        => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/keyframes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_keyframes' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_keyframe' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'keyframe_name'   => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
						'keyframe_data'   => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
						'is_destructive'  => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'check_duplicate' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/custom-vars',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_vars' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_custom_vars' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'variables' => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/metadata',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_metadata' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/bulk',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_bulk' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'classes'   => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
						'keyframes' => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
					),
				),
			)
		);

		// Region-keyed V2 (parallel/shadow): site-wide payload store. Holds the
		// header/footer + global/common CSS as a schema-v1 payload, separate from
		// the user-editable `spectra_blocks_pro_gs_user_css`. See
		// STYLE-PREPARATION-ROADMAP.md (Phase 1).
		register_rest_route(
			$namespace,
			'/global-styles/sitewide',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_sitewide' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'payload' => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/global-styles/jit-compile',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'jit_compile' ),
					'permission_callback' => array( $this, 'check_jit_compile_permission' ),
					'args'                => array(
						'class_strings' => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array( 'type' => 'string' ),
						),
					),
				),
			)
		);

		// Live-paint render (Vibe Editing): render a schema-v1 payload to a CSS
		// string for the editor canvas. Persists NOTHING — the page/global WRITE
		// happens via editPost (session) / /sitewide (option). `scope` picks the
		// GenCssRenderer scope (page vs sitewide). Reuses the SSOT renderer so the
		// emitted selector specificity is the canonical GBS tier (no string hacks).
		register_rest_route(
			$namespace,
			'/global-styles/render',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'render_payload' ),
					'permission_callback' => array( $this, 'check_jit_compile_permission' ),
					'args'                => array(
						'payload' => array(
							'type'     => array( 'object', 'array' ),
							'required' => true,
						),
						'post_id' => array(
							'type'    => 'integer',
							'default' => 0,
						),
						'scope'   => array(
							'type'    => 'string',
							'enum'    => array( 'page', 'global' ),
							'default' => 'page',
						),
					),
				),
			)
		);

		// Full user-CSS option read (Vibe Editing get_styles{global}): the WHOLE
		// schema-v1 payload (every bucket), unlike /custom-classes which returns
		// only `classes`. The editor reads this, modifies, and writes back via
		// /sitewide (read-modify-write so importer chrome/user classes survive).
		register_rest_route(
			$namespace,
			'/global-styles/user-css',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_css_full' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Unified DASHBOARD / import-pipeline GBS write (server-side, immediate) —
		// ONE endpoint for BOTH scopes. `scope:'global'` targets the site-wide
		// option (same as /sitewide); `scope:'page'` + `post_id` targets the
		// per-page postmeta. The editor keeps its own session-scoped path
		// (editor__set_styles via editPost), so the two stay separate by design.
		register_rest_route(
			$namespace,
			'/global-styles/save',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_save' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'scope'   => array(
							'type'     => 'string',
							'required' => true,
							'enum'     => array( 'page', 'global' ),
						),
						'post_id' => array(
							'type' => 'integer',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_save' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'scope'   => array(
							'type'     => 'string',
							'required' => true,
							'enum'     => array( 'page', 'global' ),
						),
						'post_id' => array(
							'type' => 'integer',
						),
						'payload' => array(
							'type'     => array( 'object', 'array' ),
							'required' => true,
						),
						'replace' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Keyframe names reserved by the registry for built-in animations.
	 *
	 * Submitted keyframes using any of these names are rejected so the
	 * palette of `animate-{spin|pulse|bounce|ping}` utilities keeps its
	 * canonical behaviour.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const RESERVED_KEYFRAME_NAMES = array(
		'spectra-spin',
		'spectra-pulse',
		'spectra-bounce',
		'spectra-ping',
		// Preset animation library (class-class-registry.php).
		// Reserved so user-authored keyframes can't shadow or override the
		// builtin entrance/ambient animations.
		'spectra-fade-up',
		'spectra-fade-in',
		'spectra-fade-down',
		'spectra-slide-left',
		'spectra-slide-right',
		'spectra-scale-in',
		'spectra-ring-in',
		'spectra-ring-dash',
		'spectra-drift',
		'spectra-pulse-dot',
		'spectra-wiggle',
		'spectra-shake',
		'spectra-reveal',
		'spectra-fade-up-m',
	);

	/**
	 * Regex matching a syntactically valid custom-class name.
	 *
	 * Validate-by-syntax, not allowlist-by-prefix: a name is acceptable when
	 * it is a valid CSS class identifier (starts with a letter or underscore,
	 * then letters / digits / `-` / `_`). This matches the UI write path
	 * (`update_custom_class`), which has never enforced a prefix.
	 *
	 * History: this was previously a `gs-|animate-` prefix allowlist with a
	 * letter-only first char after the prefix. That silently dropped (a)
	 * `gs-{6-hex-hash}-{base}` names whose hash starts with a digit — ~62% of
	 * SaaS-imported classes (`gs-69f07e-ap-footer`) — and (b) bare source
	 * author classes routed site-wide by the import (`ap-btn-signal`).
	 * Digit-leading hashes are valid CSS (the identifier starts with `g`);
	 * bare names are validated by syntax + the reserved-prefix denylist below.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CLASS_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]*$/';

	/**
	 * Class-name prefixes the import endpoints must never register.
	 *
	 * The old prefix-allowlist existed to keep legacy/theme class families out
	 * of the registry; this denylist preserves that guard now that arbitrary
	 * (syntactically valid) names are accepted. Registering one of these
	 * site-wide would restyle theme / core / plugin elements that already
	 * carry the class:
	 *  - `gc-spectra-` — legacy generated-class family (orphan-scrubbed elsewhere).
	 *  - `ast-` / `astra-` — Astra theme classes.
	 *  - `swt-` — legacy Spectra website templates family.
	 *  - `wp-` — WordPress core block/element classes (`wp-block-*`, `wp-element-*`).
	 *  - `uagb-` — legacy UAG/Spectra-one block classes.
	 *  - `spectra-` — this plugin's own block classes.
	 *  - `is-` / `has-` — core state/preset classes (`is-style-*`, `has-*-color`).
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const RESERVED_CLASS_PREFIXES = array(
		'gc-spectra-',
		'ast-',
		'astra-',
		'swt-',
		'wp-',
		'uagb-',
		'spectra-',
		'is-',
		'has-',
	);

	/**
	 * Whether a class name may be written by the import endpoints
	 * (`/bulk`, `/sitewide`): syntactically valid AND not reserved.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $name Candidate class name.
	 * @return bool
	 */
	private static function is_allowed_class_name( $name ): bool {
		if ( ! is_string( $name ) || '' === $name || ! preg_match( self::CLASS_NAME_PATTERN, $name ) ) {
			return false;
		}
		foreach ( self::RESERVED_CLASS_PREFIXES as $prefix ) {
			if ( 0 === strpos( $name, $prefix ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Regex matching valid keyframe names.
	 *
	 * Allows lowerCamelCase or kebab-case identifiers the SaaS emits for
	 * bespoke keyframes (e.g. `softFadeUp`, `soft-fade-up`).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const KEYFRAME_NAME_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_-]*$/';

	/**
	 * Capability check for Global Styles CRUD.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	/**
	 * Capability check for the pure JIT compile route.
	 *
	 * Deliberately WEAKER than the CRUD routes: compiling utility tokens to
	 * CSS persists nothing and exposes nothing site-private — it is needed by
	 * anyone who can edit content in the block editor, where live-inserted
	 * blocks can carry utility classes the canvas stylesheet has not seen yet
	 * (the per-post JIT compiles SAVED content only at enqueue time).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_jit_compile_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Compile className strings into utility CSS — pure, no persistence.
	 *
	 * Closes the live-canvas gap: blocks inserted programmatically after
	 * editor load (e.g. ERA Vibe Editing section inserts) can introduce
	 * utility tokens with no rule in the enqueued per-post JIT stylesheet,
	 * so they render unstyled until the next save + reload. The editor
	 * client calls this route with the new className strings and injects
	 * the returned CSS — compiled by the SAME {@see JitCompiler} that runs
	 * at save time, so the live canvas converges to saved-page truth.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request with `class_strings`.
	 * @return WP_REST_Response
	 */
	public function jit_compile( WP_REST_Request $request ): WP_REST_Response {
		$raw = $request->get_param( 'class_strings' );
		$raw = is_array( $raw ) ? $raw : array();

		// Bound the work: a single apply touches at most a few dozen blocks;
		// 500 strings × 500 chars is far beyond any legitimate plan.
		$class_strings = array();
		foreach ( array_slice( $raw, 0, 500 ) as $entry ) {
			if ( ! is_string( $entry ) || '' === trim( $entry ) ) {
				continue;
			}
			$class_strings[] = substr( wp_strip_all_tags( $entry ), 0, 500 );
		}

		$css = empty( $class_strings ) ? '' : JitCompiler::compile( $class_strings );

		return new WP_REST_Response(
			array(
				'success' => true,
				'css'     => $css,
			),
			200
		);
	}

	/**
	 * GET the utility catalogue + bracket-prefix metadata.
	 *
	 * Mirrors {@see ClassRegistry::export_metadata()} verbatim so the SaaS
	 * mirror can refresh its utility catalogue at build time. The response
	 * carries an ETag derived from the `spectra_blocks_gs_jit_version`
	 * option; callers sending a matching `If-None-Match` header receive a
	 * 304 with an empty body. `Cache-Control: private, max-age=60` keeps
	 * the mirror responsive to Style Guide token changes.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_metadata( WP_REST_Request $request ): WP_REST_Response {
		$version = (string) get_option( JitCache::VERSION_OPTION, '' );
		// CONTENT-ADDRESSED contract caching: the grammar is now DERIVED
		// from the constants the compiler/generator consume, so its content
		// changes with code changes — without anyone bumping a number. A
		// version-only ETag silently served 304s across such changes
		// (observed live 2026-06-12: palette channels 7 → 16 with an
		// unchanged ETag). Hash the actual contract payload instead;
		// CONTRACT_VERSION still rides inside the payload as the semantic
		// marker consumers branch on.
		$contract_hash = md5( (string) wp_json_encode( JitCompiler::export_contract() ) . (string) wp_json_encode( ClassRegistry::palette_channels() ) );
		$etag          = '"' . md5( $version . '|contract:' . $contract_hash ) . '"';
		$if_none_match = $request->get_header( 'If-None-Match' );

		// RFC 7232 §3.2: If-None-Match uses WEAK comparison. Proxies (nginx
		// gzip) legitimately weaken our strong ETag to `W/"…"` on the wire,
		// so a client echoing exactly what it received must still match —
		// strict string equality silently disabled every 304 (verified live
		// 2026-06-11: weak echo → 200, strong echo → 304).
		$strip_weak = static function ( string $tag ): string {
			return trim( (string) preg_replace( '/^\s*W\//i', '', trim( $tag ) ) );
		};

		if ( is_string( $if_none_match ) && '' !== $if_none_match && $strip_weak( $if_none_match ) === $strip_weak( $etag ) ) {
			$not_modified = new WP_REST_Response( null, 304 );
			$not_modified->header( 'ETag', $etag );
			$not_modified->header( 'Cache-Control', 'private, max-age=60' );
			return $not_modified;
		}

		$response = rest_ensure_response( ClassRegistry::export_metadata() );
		$response->header( 'ETag', $etag );
		$response->header( 'Cache-Control', 'private, max-age=60' );

		return $response;
	}

	/**
	 * GET custom classes.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function get_custom_classes( WP_REST_Request $request ): WP_REST_Response {
		$user_css = $this->get_user_css();
		$classes  = isset( $user_css['classes'] ) && is_array( $user_css['classes'] ) ? $user_css['classes'] : array();

		// When a post_id is supplied, merge page-scoped classes (from post-meta) on
		// top of global classes. Page classes take precedence: same class name in
		// both scopes → the page version is what renders on that page.
		$post_id = (int) $request->get_param( 'post_id' );
		if ( $post_id > 0 ) {
			$page_payload = $this->read_page_payload( $post_id );
			if ( ! empty( $page_payload['classes'] ) && is_array( $page_payload['classes'] ) ) {
				$classes = array_merge( $classes, $page_payload['classes'] );
			}
		}

		return rest_ensure_response(
			array(
				'classes' => $classes,
			)
		);
	}

	/**
	 * POST a custom class (create/update/delete).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_custom_class( WP_REST_Request $request ) {
		$class_name     = $request->get_param( 'class_name' );
		$is_destructive = (bool) $request->get_param( 'is_destructive' );
		$styles_input   = $request->get_param( 'styles' );
		$post_id        = (int) $request->get_param( 'post_id' );

		if ( '' === $class_name || null === $class_name ) {
			return new WP_Error(
				'spectra_gs_invalid_class',
				__( 'Class name is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		// Class styles arrive unwrapped one level (`{bucket:{prop:value}}`), so
		// the value sits at depth 1. Pass base_depth 1 so the CSS-aware value
		// rules (var rejection, char whitelist, length cap) — which apply at
		// depth >= 2 — actually reach the declaration values.
		$styles = $is_destructive ? array() : Sanitizer::sanitize_json( $styles_input, true, 1 );
		if ( ! $is_destructive ) {
			$styles = self::normalize_class_styles_to_flat( $styles );
		}

		// A missing `styles` param is invalid, but an explicitly-provided empty
		// payload is a valid placeholder class (ClassFlyout pattern) — the user
		// lands in the CSS editor and adds rules in a follow-up save.
		if ( ! $is_destructive && null === $styles_input ) {
			return new WP_Error(
				'spectra_gs_invalid_styles',
				__( 'Styles payload is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		if ( $post_id > 0 ) {
			// Page-scoped write — update the per-page post meta directly.
			$read_result  = $this->read_page_payload( $post_id );
			$page_payload = $read_result ? $read_result : array();
			if ( empty( $page_payload['classes'] ) || ! is_array( $page_payload['classes'] ) ) {
				$page_payload['classes'] = array();
			}
			if ( $is_destructive ) {
				unset( $page_payload['classes'][ $class_name ] );
			} else {
				$page_payload['classes'][ $class_name ] = $styles;
			}
			update_post_meta( $post_id, GenCssOrphanStripper::META_KEY, $page_payload );
		} else {
			// Global write — update the site-wide option.
			$user_css = $this->get_user_css();
			if ( empty( $user_css['classes'] ) || ! is_array( $user_css['classes'] ) ) {
				$user_css['classes'] = array();
			}
			if ( $is_destructive ) {
				unset( $user_css['classes'][ $class_name ] );
			} else {
				$user_css['classes'][ $class_name ] = $styles;
			}
			$this->save_user_css( $user_css );
		}

		return rest_ensure_response(
			array(
				'saved_class' => array(
					'name'   => $class_name,
					'styles' => $styles,
				),
				'destroyed'   => $is_destructive,
			)
		);
	}

	/**
	 * GET keyframes.
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public function get_keyframes(): WP_REST_Response {
		$user_css  = $this->get_user_css();
		$keyframes = isset( $user_css['keyframes'] ) && is_array( $user_css['keyframes'] ) ? $user_css['keyframes'] : array();

		return rest_ensure_response(
			array(
				'keyframes' => $keyframes,
			)
		);
	}

	/**
	 * POST a keyframe (create/update/delete).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_keyframe( WP_REST_Request $request ) {
		$keyframe_name   = $request->get_param( 'keyframe_name' );
		$is_destructive  = (bool) $request->get_param( 'is_destructive' );
		$check_duplicate = (bool) $request->get_param( 'check_duplicate' );
		$data_input      = $request->get_param( 'keyframe_data' );

		if ( '' === $keyframe_name || null === $keyframe_name ) {
			return new WP_Error(
				'spectra_gs_invalid_keyframe',
				__( 'Keyframe name is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$keyframe_data = $is_destructive ? array() : Sanitizer::sanitize_keyframe_data( $data_input );

		if ( ! $is_destructive && empty( $keyframe_data['css'] ) ) {
			return new WP_Error(
				'spectra_gs_invalid_keyframe_data',
				__( 'Keyframe data is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$user_css = $this->get_user_css();
		if ( empty( $user_css['keyframes'] ) || ! is_array( $user_css['keyframes'] ) ) {
			$user_css['keyframes'] = array();
		}

		$existing = isset( $user_css['keyframes'][ $keyframe_name ] ) ? $user_css['keyframes'][ $keyframe_name ] : null;

		if ( ! $is_destructive && null !== $existing ) {
			$existing_css = is_array( $existing ) && isset( $existing['css'] ) && is_string( $existing['css'] )
				? $existing['css']
				: '';
			$incoming_css = isset( $keyframe_data['css'] ) && is_string( $keyframe_data['css'] )
				? $keyframe_data['css']
				: '';

			if ( '' !== $existing_css && $existing_css === $incoming_css ) {
				// Idempotent no-op: same name + same body already persisted.
				return rest_ensure_response(
					array(
						'saved_keyframe' => array(
							'name' => $keyframe_name,
							'data' => $existing,
						),
						'destroyed'      => false,
						'unchanged'      => true,
					)
				);
			}

			if ( $check_duplicate ) {
				return new WP_Error(
					'spectra_gs_keyframe_exists',
					sprintf(
						/* translators: %s: Keyframe name. */
						__( 'A keyframe named "%s" already exists with different contents.', 'spectra-blocks' ),
						$keyframe_name
					),
					array( 'status' => 409 )
				);
			}
		}

		if ( $is_destructive ) {
			unset( $user_css['keyframes'][ $keyframe_name ] );
		} else {
			$user_css['keyframes'][ $keyframe_name ] = $keyframe_data;
		}

		$this->save_user_css( $user_css );

		return rest_ensure_response(
			array(
				'saved_keyframe' => array(
					'name' => $keyframe_name,
					'data' => $keyframe_data,
				),
				'destroyed'      => $is_destructive,
				'unchanged'      => false,
			)
		);
	}

	/**
	 * GET all custom CSS variables.
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public function get_custom_vars(): WP_REST_Response {
		$user_css  = $this->get_user_css();
		$variables = isset( $user_css['variables'] ) && is_array( $user_css['variables'] ) ? $user_css['variables'] : array();

		return rest_ensure_response( array( 'variables' => $variables ) );
	}

	/**
	 * POST (replace) the full custom CSS variables map.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_custom_vars( WP_REST_Request $request ) {
		$input = $request->get_param( 'variables' );
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$sanitized = array();
		foreach ( $input as $name => $value ) {
			$name = (string) $name;
			if ( ! preg_match( '/^--[a-zA-Z0-9_-]+$/', $name ) ) {
				continue;
			}
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				$sanitized[ $name ] = $value;
			}
		}

		$user_css              = $this->get_user_css();
		$user_css['variables'] = $sanitized;
		$this->save_user_css( $user_css );

		return rest_ensure_response( array( 'variables' => $sanitized ) );
	}

	/**
	 * POST classes + keyframes in a single idempotent write.
	 *
	 * Accepts `{classes: {name => bucket}, keyframes: {name => data}}` and
	 * returns a structured audit of what landed versus what was skipped.
	 * Invalid names or reserved keyframe names are reported per-entry so
	 * the SaaS caller can surface reasons rather than silently drop work.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_bulk( WP_REST_Request $request ): WP_REST_Response {
		$classes_input   = $request->get_param( 'classes' );
		$keyframes_input = $request->get_param( 'keyframes' );

		$classes_input   = is_array( $classes_input ) ? $classes_input : array();
		$keyframes_input = is_array( $keyframes_input ) ? $keyframes_input : array();

		$user_css = $this->get_user_css();
		if ( empty( $user_css['classes'] ) || ! is_array( $user_css['classes'] ) ) {
			$user_css['classes'] = array();
		}
		if ( empty( $user_css['keyframes'] ) || ! is_array( $user_css['keyframes'] ) ) {
			$user_css['keyframes'] = array();
		}

		$written_classes = array();
		$skipped_classes = array();

		foreach ( $classes_input as $name => $styles ) {
			if ( ! is_string( $name ) || '' === $name ) {
				$skipped_classes[] = array(
					'name'   => is_string( $name ) ? $name : '',
					'reason' => 'invalid_name',
				);
				continue;
			}

			if ( ! self::is_allowed_class_name( $name ) ) {
				$skipped_classes[] = array(
					'name'   => $name,
					'reason' => 'invalid_name',
				);
				continue;
			}

			if ( ! is_array( $styles ) || empty( $styles ) ) {
				$skipped_classes[] = array(
					'name'   => $name,
					'reason' => 'empty_styles',
				);
				continue;
			}

			$sanitized = Sanitizer::sanitize_json( $styles );
			$sanitized = self::normalize_class_styles_to_flat( $sanitized );
			if ( empty( $sanitized ) ) {
				$skipped_classes[] = array(
					'name'   => $name,
					'reason' => 'empty_styles',
				);
				continue;
			}

			$user_css['classes'][ $name ] = $sanitized;
			$written_classes[]            = $name;
		}

		$written_keyframes = array();
		$skipped_keyframes = array();

		foreach ( $keyframes_input as $name => $data ) {
			if ( ! is_string( $name ) || '' === $name ) {
				$skipped_keyframes[] = array(
					'name'   => is_string( $name ) ? $name : '',
					'reason' => 'invalid_name',
				);
				continue;
			}

			if ( in_array( $name, self::RESERVED_KEYFRAME_NAMES, true ) ) {
				$skipped_keyframes[] = array(
					'name'   => $name,
					'reason' => 'reserved_name',
				);
				continue;
			}

			if ( ! preg_match( self::KEYFRAME_NAME_PATTERN, $name ) ) {
				$skipped_keyframes[] = array(
					'name'   => $name,
					'reason' => 'invalid_name',
				);
				continue;
			}

			$sanitized = Sanitizer::sanitize_keyframe_data( $data );
			if ( '' === $sanitized['css'] ) {
				$skipped_keyframes[] = array(
					'name'   => $name,
					'reason' => 'empty_css',
				);
				continue;
			}

			$user_css['keyframes'][ $name ] = $sanitized;
			$written_keyframes[]            = $name;
		}

		if ( ! empty( $written_classes ) || ! empty( $written_keyframes ) ) {
			$this->save_user_css( $user_css );
		}

		return rest_ensure_response(
			array(
				'written_classes'   => $written_classes,
				'skipped_classes'   => $skipped_classes,
				'written_keyframes' => $written_keyframes,
				'skipped_keyframes' => $skipped_keyframes,
			)
		);
	}

	/**
	 * Region-keyed V2: MERGE the import's site-wide style payload into the GBS
	 * option `spectra_blocks_pro_gs_user_css`.
	 *
	 * Accepts a schema-v1 payload `{ v, classes?, keyframes?, wrapperStyles?,
	 * rootStyles?, scopeVars?, presetLock?, imports?, mediaQuery? }` carrying the
	 * header/footer + global/common CSS. `classes`/`keyframes` are sanitized (same
	 * as `/global-styles/bulk`) and merged per entry (latest import wins on a name
	 * collision); the non-class buckets are import-owned and replaced wholesale.
	 * User-authored classes are preserved. An empty payload is a NO-OP (this is the
	 * shared, user-editable option — never deleted here).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_sitewide( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_param( 'payload' );

		// Empty payload: NO-OP. This store is the shared, user-editable GBS
		// registry (`spectra_blocks_pro_gs_user_css`) — never delete it here.
		if ( ! is_array( $payload ) || array() === $payload ) {
			return rest_ensure_response(
				array(
					'option' => Engine::OPTION_KEY_USER_CSS,
					'noop'   => true,
				)
			);
		}

		if ( ! isset( $payload['v'] ) ) {
			return new WP_REST_Response(
				array( 'error' => 'payload must be a schema-versioned object (missing "v").' ),
				400
			);
		}

		// 1 MB hard cap — mirrors the per-page SetPageCustomCss ability.
		if ( strlen( (string) wp_json_encode( $payload ) ) > 1048576 ) {
			return new WP_REST_Response(
				array( 'error' => 'site-wide payload exceeds 1 MB cap.' ),
				400
			);
		}

		// MERGE the incoming site-wide payload INTO the existing GBS option —
		// never overwrite a bucket the caller didn't touch, so user-authored
		// styles AND earlier pages of the same multi-page build survive.
		// - classes / keyframes: merged per entry (sanitized the same way as the
		// global-styles/bulk path); on a name collision the latest write wins.
		// - rootStyles / wrapperStyles / scopeVars / presetLock / mediaQuery:
		// merged per entry (null entry deletes; null bucket deletes the bucket).
		// - imports: union + dedup.
		// This is the SSOT never-clobber rule — import, website-build and the
		// editor all POST here and inherit it (the editor's client-side
		// mergePayload becomes redundant once it posts deltas).
		$user_css = $this->merge_user_css( $this->get_user_css(), $payload );

		$this->save_user_css( $user_css );

		return rest_ensure_response(
			array(
				'option'  => Engine::OPTION_KEY_USER_CSS,
				'buckets' => array_keys( $user_css ),
				'updated' => true,
			)
		);
	}

	/**
	 * Read the user CSS option, coerced to an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	private function get_user_css(): array {
		$user_css = get_option( Engine::OPTION_KEY_USER_CSS, array() );
		return is_array( $user_css ) ? $user_css : array();
	}

	/**
	 * Persist the user CSS option.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $user_css User CSS payload.
	 * @return void
	 */
	private function save_user_css( array $user_css ): void {
		update_option( Engine::OPTION_KEY_USER_CSS, $user_css );
	}

	/**
	 * Merge a schema-v1 payload INTO an existing GBS payload — the SSOT
	 * never-clobber contract shared by /sitewide and /save (option AND postmeta):
	 *  - classes / keyframes: sanitized (same as /bulk) and merged per entry.
	 *  - rootStyles / wrapperStyles / scopeVars / presetLock / mediaQuery:
	 *    merged per entry (null entry deletes; null whole bucket deletes it).
	 *  - imports: union + dedup.
	 *
	 * Merging into an empty base yields a sanitized full payload — that is how
	 * /save handles a per-page `replace` (full overwrite).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $user_css Existing payload (the merge base).
	 * @param array<string, mixed> $payload  Incoming schema-v1 payload.
	 * @return array<string, mixed> Merged payload.
	 */
	private function merge_user_css( array $user_css, array $payload ): array {
		$user_css['v'] = isset( $payload['v'] ) ? $payload['v'] : ( $user_css['v'] ?? '1' );

		if ( isset( $payload['classes'] ) && is_array( $payload['classes'] ) ) {
			if ( empty( $user_css['classes'] ) || ! is_array( $user_css['classes'] ) ) {
				$user_css['classes'] = array();
			}
			foreach ( $payload['classes'] as $name => $styles ) {
				if ( ! self::is_allowed_class_name( $name ) || ! is_array( $styles ) ) {
					continue;
				}
				$sanitized = self::normalize_class_styles_to_flat( Sanitizer::sanitize_json( $styles ) );
				if ( ! empty( $sanitized ) ) {
					$user_css['classes'][ $name ] = $sanitized;
				}
			}
		}

		if ( isset( $payload['keyframes'] ) && is_array( $payload['keyframes'] ) ) {
			if ( empty( $user_css['keyframes'] ) || ! is_array( $user_css['keyframes'] ) ) {
				$user_css['keyframes'] = array();
			}
			foreach ( $payload['keyframes'] as $name => $data ) {
				if ( ! is_string( $name ) || '' === $name || in_array( $name, self::RESERVED_KEYFRAME_NAMES, true ) || ! preg_match( self::KEYFRAME_NAME_PATTERN, $name ) ) {
					continue;
				}
				$sanitized = Sanitizer::sanitize_keyframe_data( $data );
				if ( '' !== $sanitized['css'] ) {
					$user_css['keyframes'][ $name ] = $sanitized;
				}
			}
		}

		// Import-owned non-class buckets (rendered by GenCssRenderer), MERGED per
		// entry — never-clobber, so a multi-page build / batch accumulates instead
		// of last-write-wins. A null entry deletes that key; a null whole bucket
		// deletes the bucket.
		foreach ( array( 'rootStyles', 'wrapperStyles', 'scopeVars', 'presetLock', 'mediaQuery' ) as $bucket ) {
			if ( ! array_key_exists( $bucket, $payload ) ) {
				continue;
			}
			$incoming = $payload[ $bucket ];
			if ( null === $incoming ) {
				unset( $user_css[ $bucket ] );
				continue;
			}
			if ( ! is_array( $incoming ) ) {
				continue;
			}
			if ( empty( $user_css[ $bucket ] ) || ! is_array( $user_css[ $bucket ] ) ) {
				$user_css[ $bucket ] = array();
			}
			foreach ( $incoming as $key => $value ) {
				if ( null === $value ) {
					unset( $user_css[ $bucket ][ $key ] );
				} else {
					$user_css[ $bucket ][ $key ] = $value;
				}
			}
		}

		// `imports` is a flat list (font stylesheet URLs) — union + dedup.
		if ( isset( $payload['imports'] ) && is_array( $payload['imports'] ) ) {
			$existing_imports    = ( isset( $user_css['imports'] ) && is_array( $user_css['imports'] ) ) ? $user_css['imports'] : array();
			$user_css['imports'] = array_values( array_unique( array_merge( $existing_imports, $payload['imports'] ) ) );
		}

		return $user_css;
	}

	/**
	 * Unified GBS write for the DASHBOARD / import pipeline (server-side, immediate).
	 * ONE endpoint, two scopes — the editor keeps its own session-scoped path:
	 *  - scope `global`         → the site-wide OPTION (merge, never-clobber; empty = no-op).
	 *  - scope `page` + post_id  → the per-page POSTMETA. `replace` true overwrites the
	 *    page payload (a re-import clears stale classes); false merges per entry (partial
	 *    writes, e.g. a palette change); an empty payload deletes the meta.
	 * Both scopes reuse {@see merge_user_css()}, so the SSOT contract is identical.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_save( WP_REST_Request $request ): WP_REST_Response {
		// `scope` is guaranteed page|global by the route's required+enum arg
		// schema (WP rejects anything else with rest_invalid_param before this
		// callback runs) — no need to re-validate it here.
		$scope   = $request->get_param( 'scope' );
		$payload = $request->get_param( 'payload' );

		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'error' => 'payload must be a schema-v1 object.' ), 400 );
		}

		// ── global → the shared, user-editable OPTION (never deleted here). ──
		if ( 'global' === $scope ) {
			if ( array() === $payload ) {
				return rest_ensure_response(
					array(
						'option' => Engine::OPTION_KEY_USER_CSS,
						'noop'   => true,
					)
				);
			}
			$error = $this->validate_payload_envelope( $payload );
			if ( null !== $error ) {
				return $error;
			}
			// replace=true RESETS the import-owned non-class buckets (presetLock /
			// rootStyles / wrapperStyles / scopeVars / mediaQuery) so a fresh
			// replace_site whole-site build cannot inherit a PRIOR build's body-level
			// palette / token overrides — the cross-build "stale presetLock" leak
			// where a previous build's `body { --wp--preset--color--*: … }` survived
			// the per-entry merge (an omitted bucket is preserved) and beat the new
			// build's :root palette. User-authored `classes`, document-global
			// `keyframes` and `imports` are PRESERVED (an empty-base reset would wipe
			// them, and the editor + other imports own them). replace=false → plain
			// merge (match_site siblings / partial writes), unchanged.
			$replace = (bool) $request->get_param( 'replace' );
			$base    = $this->get_user_css();
			if ( $replace ) {
				$base = array_intersect_key(
					$base,
					array_flip( array( 'v', 'classes', 'keyframes', 'imports' ) )
				);
			}
			$merged = $this->merge_user_css( $base, $payload );
			$this->save_user_css( $merged );
			return rest_ensure_response(
				array(
					'option'   => Engine::OPTION_KEY_USER_CSS,
					'buckets'  => array_keys( $merged ),
					'replaced' => $replace,
					'updated'  => true,
				)
			);
		}

		// ── page → the per-page POSTMETA (per-page isolation; NEVER the option). ──
		$post_id = (int) $request->get_param( 'post_id' );
		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return new WP_REST_Response( array( 'error' => 'a valid post_id is required for scope "page".' ), 400 );
		}

		// Empty payload clears the per-page CSS (stale styles removed on re-import).
		if ( array() === $payload ) {
			delete_post_meta( $post_id, Engine::OPTION_KEY_USER_CSS );
			return rest_ensure_response(
				array(
					'post_id' => $post_id,
					'cleared' => true,
				)
			);
		}

		$error = $this->validate_payload_envelope( $payload );
		if ( null !== $error ) {
			return $error;
		}

		// replace=true → full overwrite (merge into an empty base = a sanitized full
		// payload; clears stale gs-* classes on a re-import). replace=false → merge
		// onto the existing per-page payload (partial writes).
		$replace  = (bool) $request->get_param( 'replace' );
		$existing = $replace ? array() : $this->read_page_payload( $post_id );
		$merged   = $this->merge_user_css( $existing, $payload );

		// The post meta carries the orphan-stripper sanitize filter, so
		// update_post_meta re-sanitizes on write.
		update_post_meta( $post_id, Engine::OPTION_KEY_USER_CSS, $merged );

		return rest_ensure_response(
			array(
				'post_id'  => $post_id,
				'buckets'  => array_keys( $merged ),
				'replaced' => $replace,
				'updated'  => true,
			)
		);
	}

	/**
	 * Read the GBS payload for a scope — used by the dashboard / palette
	 * read-modify-write round-trips.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_save( WP_REST_Request $request ): WP_REST_Response {
		$scope = $request->get_param( 'scope' );

		if ( 'global' === $scope ) {
			return rest_ensure_response(
				array(
					'scope'   => 'global',
					'payload' => $this->get_user_css(),
				)
			);
		}

		// scope is 'page' — the route's required+enum arg schema guarantees page|global.
		$post_id = (int) $request->get_param( 'post_id' );
		if ( $post_id <= 0 ) {
			return new WP_REST_Response( array( 'error' => 'post_id is required for scope "page".' ), 400 );
		}
		return rest_ensure_response(
			array(
				'scope'   => 'page',
				'post_id' => $post_id,
				'payload' => $this->read_page_payload( $post_id ),
			)
		);
	}

	/**
	 * Read the per-page GBS postmeta payload, coerced to an array.
	 *
	 * Delegates to the canonical per-page reader (the ONE reader the enqueue path
	 * + asset-loader presence check go through) so the controller can't diverge
	 * from what actually gets enqueued; `?? array()` bridges its null-when-empty.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	private function read_page_payload( int $post_id ): array {
		return GenCssOrphanStripper::read_page_payload( $post_id ) ?? array();
	}

	/**
	 * Validate the schema-v1 envelope (version marker + 1 MB cap) shared by the
	 * write routes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $payload Payload to validate.
	 * @return WP_REST_Response|null Error response, or null when valid.
	 */
	private function validate_payload_envelope( array $payload ): ?WP_REST_Response {
		if ( ! isset( $payload['v'] ) ) {
			return new WP_REST_Response( array( 'error' => 'payload must be a schema-versioned object (missing "v").' ), 400 );
		}
		if ( strlen( (string) wp_json_encode( $payload ) ) > 1048576 ) {
			return new WP_REST_Response( array( 'error' => 'payload exceeds 1 MB cap.' ), 400 );
		}
		return null;
	}

	/**
	 * Render a schema-v1 payload to a CSS string for live editor paint
	 * (Vibe Editing editor__set_styles). Persists NOTHING — pure reuse of the
	 * SSOT GenCssRenderer, so the emitted selectors carry the canonical GBS-tier
	 * specificity (block attributes still win, block defaults still lose).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function render_payload( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_param( 'payload' );
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'error' => 'payload must be a schema-v1 object.' ), 400 );
		}
		$post_id  = (int) $request->get_param( 'post_id' );
		$sitewide = ( 'global' === (string) $request->get_param( 'scope' ) );
		$css      = GenCssRenderer::render( $payload, $post_id, true, $sitewide );

		return rest_ensure_response(
			array(
				'success' => true,
				'css'     => (string) $css,
			)
		);
	}

	/**
	 * Return the FULL user-CSS option (every schema-v1 bucket) for the editor's
	 * get_styles{global} read — distinct from get_custom_classes (classes only).
	 * The editor read-modify-writes this back via /sitewide so importer chrome
	 * and user-authored classes survive an edit.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_user_css_full(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => true,
				'payload' => $this->get_user_css(),
			)
		);
	}

	/**
	 * Validate and clean per-class declaration buckets into a flat
	 * property→value map before persisting.
	 *
	 *   [ 'default' => [ 'color' => 'red' ] ]
	 *
	 * Malformed declarations (non-array buckets, non-string property/value,
	 * empty entries) are silently dropped — sibling declarations in the same
	 * state are preserved.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $styles Per-class bucket map (`state => declarations`).
	 * @return array<string, array<string, string>>
	 */
	private static function normalize_class_styles_to_flat( array $styles ): array {
		$normalized = array();
		foreach ( $styles as $bucket => $declarations ) {
			if ( ! is_string( $bucket ) || ! is_array( $declarations ) ) {
				continue;
			}

			$clean = array();
			foreach ( $declarations as $property => $value ) {
				if ( ! is_string( $property ) || ! is_string( $value ) ) {
					continue;
				}
				$property = trim( $property );
				$value    = trim( $value );
				if ( '' === $property || '' === $value ) {
					continue;
				}
				$clean[ $property ] = $value;
			}

			if ( ! empty( $clean ) ) {
				$normalized[ $bucket ] = $clean;
			}
		}

		return $normalized;
	}
}
