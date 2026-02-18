<?php
/**
 * PHPUnit bootstrap file for Ultimate Addons for Gutenberg plugin - Spectra V3
 */

// First, try to load the WordPress tests environment
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Load test configuration if exists
$config_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/wp-tests-config.php';
if ( file_exists( $config_file ) ) {
    require_once $config_file;
}

// Give access to tests_add_filter() function.
if ( $_tests_dir && file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    require_once $_tests_dir . '/includes/functions.php';
} else {
    // For unit tests that don't need WordPress
    define( 'ABSPATH', dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) ) . '/' );
    define( 'WPINC', 'wp-includes' );
    
    // Initialize WordPress globals
    global $wp_filter, $wp_actions, $wp_current_filter;
    if ( ! isset( $wp_filter ) ) {
        $wp_filter = array();
    }
    if ( ! isset( $wp_actions ) ) {
        $wp_actions = array();
    }
    if ( ! isset( $wp_current_filter ) ) {
        $wp_current_filter = array();
    }
    
    // Mock WordPress functions that might be used
    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            global $wp_filter;
            if ( ! isset( $wp_filter[ $tag ] ) ) {
                $wp_filter[ $tag ] = array();
            }
            if ( ! isset( $wp_filter[ $tag ][ $priority ] ) ) {
                $wp_filter[ $tag ][ $priority ] = array();
            }
            $wp_filter[ $tag ][ $priority ][] = array(
                'function' => $function_to_add,
                'accepted_args' => $accepted_args
            );
        }
    }
    if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            global $wp_filter;
            if ( ! isset( $wp_filter[ $tag ] ) ) {
                $wp_filter[ $tag ] = array();
            }
            if ( ! isset( $wp_filter[ $tag ][ $priority ] ) ) {
                $wp_filter[ $tag ][ $priority ] = array();
            }
            $wp_filter[ $tag ][ $priority ][] = array(
                'function' => $function_to_add,
                'accepted_args' => $accepted_args
            );
            return true;
        }
    }
    if ( ! function_exists( 'do_action' ) ) {
        function do_action() {}
    }
    if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( $tag, $value ) {
            global $wp_filter;
            if ( ! isset( $wp_filter[ $tag ] ) ) {
                return $value;
            }
            
            $filtered = $value;
            foreach ( $wp_filter[ $tag ] as $priority => $functions ) {
                foreach ( $functions as $function ) {
                    if ( is_callable( $function['function'] ) ) {
                        $args = array( $filtered );
                        if ( $function['accepted_args'] > 1 ) {
                            $args = array_slice( func_get_args(), 1, $function['accepted_args'] );
                            $args[0] = $filtered;
                        }
                        $filtered = call_user_func_array( $function['function'], $args );
                    }
                }
            }
            return $filtered;
        }
    }
    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $str ) {
            return strip_tags( $str );
        }
    }
    if ( ! function_exists( 'trailingslashit' ) ) {
        function trailingslashit( $string ) {
            return rtrim( $string, '/' ) . '/';
        }
    }
    if ( ! function_exists( 'wp_enqueue_script' ) ) {
        function wp_enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
            global $wp_scripts;
            
            if ( ! isset( $wp_scripts ) ) {
                $wp_scripts = new stdClass();
                $wp_scripts->registered = array();
                $wp_scripts->queue = array();
            }
            
            // Register if src is provided
            if ( $src ) {
                wp_register_script( $handle, $src, $deps, $ver, $in_footer );
            }
            
            // Add to queue
            if ( ! in_array( $handle, $wp_scripts->queue ) ) {
                $wp_scripts->queue[] = $handle;
            }
            
            return true;
        }
    }
    if ( ! function_exists( 'wp_localize_script' ) ) {
        function wp_localize_script() {}
    }
    if ( ! function_exists( 'wp_register_style' ) ) {
        function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
            global $wp_styles;
            
            if ( ! isset( $wp_styles ) ) {
                $wp_styles = new stdClass();
                $wp_styles->registered = array();
                $wp_styles->queue = array();
            }
            
            $wp_styles->registered[ $handle ] = array(
                'src' => $src,
                'deps' => $deps,
                'ver' => $ver,
                'media' => $media
            );
            
            return true;
        }
    }
    if ( ! function_exists( 'wp_register_script' ) ) {
        function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
            global $wp_scripts;
            
            if ( ! isset( $wp_scripts ) ) {
                $wp_scripts = new stdClass();
                $wp_scripts->registered = array();
                $wp_scripts->queue = array();
            }
            
            $wp_scripts->registered[ $handle ] = array(
                'src' => $src,
                'deps' => $deps,
                'ver' => $ver,
                'in_footer' => $in_footer
            );
            
            return true;
        }
    }
    if ( ! function_exists( 'wp_enqueue_style' ) ) {
        function wp_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
            global $wp_styles;
            
            if ( ! isset( $wp_styles ) ) {
                $wp_styles = new stdClass();
                $wp_styles->registered = array();
                $wp_styles->queue = array();
            }
            
            // Register if src is provided
            if ( $src ) {
                wp_register_style( $handle, $src, $deps, $ver, $media );
            }
            
            // Add to queue
            if ( ! in_array( $handle, $wp_styles->queue ) ) {
                $wp_styles->queue[] = $handle;
            }
            
            return true;
        }
    }
    if ( ! function_exists( 'is_rtl' ) ) {
        function is_rtl() {
            global $test_is_rtl;
            return isset( $test_is_rtl ) ? $test_is_rtl : false;
        }
    }
    if ( ! function_exists( 'wp_style_add_data' ) ) {
        function wp_style_add_data() {}
    }
    if ( ! function_exists( 'wp_add_inline_style' ) ) {
        function wp_add_inline_style() {}
    }
    if ( ! function_exists( 'wp_get_theme' ) ) {
        function wp_get_theme() {
            return (object) array( 'get_stylesheet' => function() { return 'theme'; } );
        }
    }
    // Global storage for options in tests
    global $test_options;
    $test_options = array();
    
    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $option, $default = false ) {
            global $test_options;
            return isset( $test_options[ $option ] ) ? $test_options[ $option ] : $default;
        }
    }
    if ( ! function_exists( 'update_option' ) ) {
        function update_option( $option, $value ) {
            global $test_options;
            $test_options[ $option ] = $value;
            return true;
        }
    }
    if ( ! function_exists( 'delete_option' ) ) {
        function delete_option( $option ) {
            global $test_options;
            unset( $test_options[ $option ] );
            return true;
        }
    }
    if ( ! function_exists( 'esc_attr' ) ) {
        function esc_attr( $text ) {
            if ( is_array( $text ) || is_object( $text ) ) {
                return '';
            }
            return htmlspecialchars( (string) $text, ENT_QUOTES );
        }
    }
    if ( ! function_exists( 'esc_html' ) ) {
        function esc_html( $text ) {
            return htmlspecialchars( $text, ENT_NOQUOTES );
        }
    }
    if ( ! function_exists( 'wp_kses_post' ) ) {
        function wp_kses_post( $data ) {
            return strip_tags( $data );
        }
    }
    if ( ! function_exists( 'wp_parse_args' ) ) {
        function wp_parse_args( $args, $defaults = '' ) {
            if ( is_object( $args ) ) {
                $r = get_object_vars( $args );
            } elseif ( is_array( $args ) ) {
                $r =& $args;
            } else {
                wp_parse_str( $args, $r );
            }
            
            if ( is_array( $defaults ) ) {
                return array_merge( $defaults, $r );
            }
            return $r;
        }
    }
    if ( ! function_exists( 'wp_parse_str' ) ) {
        function wp_parse_str( $string, &$array ) {
            parse_str( $string, $array );
        }
    }
    if ( ! function_exists( 'has_block' ) ) {
        function has_block( $block_name, $post = null ) {
            global $post_content_for_testing;
            
            // If we have test content set, check it
            if ( isset( $post_content_for_testing ) ) {
                return strpos( $post_content_for_testing, 'wp:' . $block_name ) !== false;
            }
            
            // Check in post if provided
            if ( $post && is_object( $post ) && isset( $post->post_content ) ) {
                return strpos( $post->post_content, 'wp:' . $block_name ) !== false;
            }
            
            return false;
        }
    }
    if ( ! function_exists( 'plugin_dir_path' ) ) {
        function plugin_dir_path( $file ) {
            return dirname( $file ) . '/';
        }
    }
    if ( ! function_exists( 'plugin_dir_url' ) ) {
        function plugin_dir_url( $file ) {
            return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
        }
    }
    if ( ! function_exists( 'plugins_url' ) ) {
        function plugins_url( $path = '', $plugin = '' ) {
            return 'https://example.com/wp-content/plugins/' . trim( $path, '/' );
        }
    }
    if ( ! function_exists( 'wp_upload_dir' ) ) {
        function wp_upload_dir() {
            return array(
                'path' => '/tmp/uploads',
                'url' => 'https://example.com/wp-content/uploads',
                'subdir' => '',
                'basedir' => '/tmp/uploads',
                'baseurl' => 'https://example.com/wp-content/uploads',
                'error' => false,
            );
        }
    }
    if ( ! function_exists( 'get_bloginfo' ) ) {
        function get_bloginfo( $show = '' ) {
            return 'Test Blog';
        }
    }
    if ( ! function_exists( 'is_admin' ) ) {
        function is_admin() {
            global $current_screen;
            if ( isset( $current_screen ) && is_object( $current_screen ) ) {
                // Check if we're on an admin screen
                return in_array( $current_screen->base, array( 'edit-post', 'post', 'edit', 'upload', 'media', 'admin' ) );
            }
            return false;
        }
    }
    if ( ! function_exists( 'wp_json_encode' ) ) {
        function wp_json_encode( $data, $options = 0, $depth = 512 ) {
            return json_encode( $data, $options, $depth );
        }
    }
    if ( ! function_exists( 'wp_unslash' ) ) {
        function wp_unslash( $value ) {
            return is_string( $value ) ? stripslashes( $value ) : $value;
        }
    }
    if ( ! function_exists( 'current_user_can' ) ) {
        function current_user_can( $capability ) {
            return true; // For testing, assume user has all capabilities
        }
    }
    if ( ! function_exists( 'wp_verify_nonce' ) ) {
        function wp_verify_nonce( $nonce, $action = -1 ) {
            return true; // For testing, assume nonce is valid
        }
    }
    if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
        function get_block_wrapper_attributes( $extra_attributes = array() ) {
            $attributes = array();
            foreach ( $extra_attributes as $key => $value ) {
                if ( is_string( $value ) ) {
                    $attributes[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
                }
            }
            return implode( ' ', $attributes );
        }
    }
    if ( ! function_exists( 'wp_safe_remote_get' ) ) {
        function wp_safe_remote_get( $url, $args = array() ) {
            return array(
                'headers' => array(),
                'body' => '',
                'response' => array(
                    'code' => 200,
                    'message' => 'OK'
                ),
                'cookies' => array(),
                'filename' => null,
            );
        }
    }
    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ) {
            return ( $thing instanceof WP_Error );
        }
    }
    if ( ! class_exists( 'WP_Error' ) ) {
        class WP_Error {
            public function __construct( $code = '', $message = '', $data = '' ) {}
        }
    }
    if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
        function wp_remote_retrieve_body( $response ) {
            return isset( $response['body'] ) ? $response['body'] : '';
        }
    }
    if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ) {
            return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
        }
    }
    if ( ! function_exists( 'absint' ) ) {
        function absint( $value ) {
            return abs( intval( $value ) );
        }
    }
    if ( ! function_exists( 'filemtime' ) ) {
        // Override filemtime to avoid errors with file access
        function filemtime( $filename ) {
            // Return a fixed timestamp for testing
            return 1234567890;
        }
    }
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = 'spectra' ) {
            return $text;
        }
    }
    if ( ! function_exists( 'esc_url' ) ) {
        function esc_url( $url, $protocols = null, $_context = 'display' ) {
            return filter_var( $url, FILTER_SANITIZE_URL );
        }
    }
    if ( ! function_exists( 'wp_strip_all_tags' ) ) {
        function wp_strip_all_tags( $string, $remove_breaks = false ) {
            $string = strip_tags( $string );
            if ( $remove_breaks ) {
                $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
            }
            return trim( $string );
        }
    }
    // Global storage for transients in tests
    global $test_transients;
    $test_transients = array();
    
    if ( ! function_exists( 'delete_transient' ) ) {
        function delete_transient( $transient ) {
            global $test_transients;
            unset( $test_transients[ $transient ] );
            return true;
        }
    }
    if ( ! function_exists( 'get_transient' ) ) {
        function get_transient( $transient ) {
            global $test_transients;
            return isset( $test_transients[ $transient ] ) ? $test_transients[ $transient ] : false;
        }
    }
    if ( ! function_exists( 'set_transient' ) ) {
        function set_transient( $transient, $value, $expiration = 0 ) {
            global $test_transients;
            $test_transients[ $transient ] = $value;
            return true;
        }
    }
    if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
        define( 'HOUR_IN_SECONDS', 3600 );
    }
    if ( ! defined( 'DAY_IN_SECONDS' ) ) {
        define( 'DAY_IN_SECONDS', 86400 );
    }
    if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
        define( 'WEEK_IN_SECONDS', 604800 );
    }
    if ( ! function_exists( 'wp_kses' ) ) {
        function wp_kses( $string, $allowed_html, $allowed_protocols = array() ) {
            if ( is_null( $string ) ) {
                return '';
            }
            
            // Simple implementation that preserves allowed tags
            if ( empty( $allowed_html ) ) {
                return strip_tags( $string );
            }
            
            // For testing, implement a more realistic kses function
            // First, remove dangerous content completely
            $string = preg_replace( '/<script[^>]*>.*?<\/script>/is', '', $string );
            
            // Remove dangerous protocols and attributes more thoroughly
            $dangerous_protocols = array( 'javascript:', 'data:', 'vbscript:', 'mocha:', 'livescript:' );
            foreach ( $dangerous_protocols as $protocol ) {
                $string = str_ireplace( $protocol, '', $string );
            }
            
            // Remove dangerous event handlers more comprehensively
            $dangerous_events = array( 
                'onerror=', 'onclick=', 'onmouseover=', 'onload=', 'onfocus=', 'onblur=',
                'onmousedown=', 'onmouseup=', 'onkeydown=', 'onkeyup=', 'onchange='
            );
            foreach ( $dangerous_events as $event ) {
                $string = preg_replace( '/' . preg_quote( $event ) . '[^>]*/i', '', $string );
            }
            
            // Remove dangerous CSS
            $string = str_replace( 'expression(', '', $string );
            $string = str_replace( 'behavior:', '', $string );
            
            // Remove any remaining 'alert' function calls
            $string = preg_replace( '/alert\s*\([^)]*\)/i', '', $string );
            
            // Use a more realistic approach - allow specified tags and remove others
            $allowed_tags = array_keys( $allowed_html );
            if ( ! empty( $allowed_tags ) ) {
                $allowed_pattern = implode( '|', array_map( 'preg_quote', $allowed_tags ) );
                
                // Remove tags that are not in the allowed list (only valid HTML tags)
                $string = preg_replace( '/<(?!\/?(' . $allowed_pattern . ')(?:\s|>))[a-zA-Z][^>]*>/i', '', $string );
            }
            
            // Proper HTML entity encoding - split by valid tags and encode text content
            $parts = preg_split( '/(<\/?[a-zA-Z][a-zA-Z0-9]*(?:\s[^>]*)?>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE );
            $string = '';
            
            for ( $i = 0; $i < count( $parts ); $i++ ) {
                if ( $i % 2 === 0 ) {
                    // This is text content (not an HTML tag)
                    $text_content = $parts[ $i ];
                    // Encode special characters in text content only
                    $text_content = str_replace( '&', '&amp;', $text_content );
                    $text_content = str_replace( '<', '&lt;', $text_content );
                    $text_content = str_replace( '>', '&gt;', $text_content );
                    $string .= $text_content;
                } else {
                    // This is an HTML tag - keep it as is
                    $string .= $parts[ $i ];
                }
            }
            
            return $string;
        }
    }
    if ( ! function_exists( 'wp_allowed_protocols' ) ) {
        function wp_allowed_protocols() {
            return array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet' );
        }
    }
    if ( ! function_exists( 'register_block_type' ) ) {
        function register_block_type( $block_type, $args = array() ) {
            return new stdClass();
        }
    }
    if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
        function register_block_type_from_metadata( $file_or_folder, $args = array() ) {
            if ( file_exists( $file_or_folder . '/block.json' ) ) {
                $metadata = json_decode( file_get_contents( $file_or_folder . '/block.json' ), true );
                if ( isset( $metadata['name'] ) ) {
                    // Validate attributes must be an array
                    if ( isset( $metadata['attributes'] ) && ! is_array( $metadata['attributes'] ) ) {
                        return false;
                    }
                    $block = new stdClass();
                    $block->name = $metadata['name'];
                    $block->attributes = $metadata['attributes'] ?? array();
                    return $block;
                }
            }
            return false;
        }
    }
    if ( ! function_exists( 'wp_add_inline_script' ) ) {
        function wp_add_inline_script( $handle, $data, $position = 'after' ) {
            return true;
        }
    }
    if ( ! function_exists( 'get_post_meta' ) ) {
        function get_post_meta( $post_id, $key = '', $single = false ) {
            return '';
        }
    }
    if ( ! function_exists( 'get_post' ) ) {
        function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
            return null;
        }
    }
    if ( ! function_exists( 'wp_get_attachment_image_src' ) ) {
        function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail', $icon = false ) {
            return false;
        }
    }
    if ( ! function_exists( 'wp_get_attachment_url' ) ) {
        function wp_get_attachment_url( $attachment_id ) {
            return false;
        }
    }
    if ( ! function_exists( 'get_the_ID' ) ) {
        function get_the_ID() {
            return 1;
        }
    }
    if ( ! function_exists( 'wp_list_pluck' ) ) {
        function wp_list_pluck( $list, $field, $index_key = null ) {
            $newlist = array();
            foreach ( $list as $key => $value ) {
                if ( is_object( $value ) ) {
                    $newlist[ $key ] = $value->$field;
                } else {
                    $newlist[ $key ] = $value[ $field ];
                }
            }
            return $newlist;
        }
    }
    if ( ! function_exists( 'wp_normalize_path' ) ) {
        function wp_normalize_path( $path ) {
            $path = str_replace( '\\', '/', $path );
            $path = preg_replace( '|(?<=.)/+|', '/', $path );
            if ( ':' === substr( $path, 1, 1 ) ) {
                $path = ucfirst( $path );
            }
            return $path;
        }
    }
    if ( ! function_exists( 'get_stylesheet_directory' ) ) {
        function get_stylesheet_directory() {
            return '/tmp/theme';
        }
    }
    if ( ! function_exists( 'wp_kses_allowed_html' ) ) {
        function wp_kses_allowed_html( $context = '' ) {
            if ( $context === 'post' ) {
                return array(
                    'a' => array( 'href' => array(), 'title' => array(), 'class' => array(), 'id' => array() ),
                    'br' => array(),
                    'em' => array(),
                    'strong' => array(),
                    'div' => array( 'class' => array(), 'id' => array(), 'style' => array() ),
                    'span' => array( 'class' => array(), 'id' => array(), 'style' => array() ),
                    'p' => array( 'class' => array(), 'style' => array() ),
                    'h1' => array( 'class' => array(), 'id' => array() ),
                    'h2' => array( 'class' => array(), 'id' => array() ),
                    'h3' => array( 'class' => array(), 'id' => array() ),
                    'h4' => array( 'class' => array(), 'id' => array() ),
                    'h5' => array( 'class' => array(), 'id' => array() ),
                    'h6' => array( 'class' => array(), 'id' => array() ),
                    'img' => array( 'src' => array(), 'alt' => array(), 'class' => array(), 'id' => array(), 'width' => array(), 'height' => array() ),
                    'ul' => array( 'class' => array(), 'id' => array() ),
                    'ol' => array( 'class' => array(), 'id' => array() ),
                    'li' => array( 'class' => array(), 'id' => array() ),
                    'blockquote' => array( 'class' => array(), 'id' => array() ),
                    'cite' => array( 'class' => array(), 'id' => array() ),
                    'code' => array( 'class' => array(), 'id' => array() ),
                    'pre' => array( 'class' => array(), 'id' => array() ),
                    'table' => array( 'class' => array(), 'id' => array() ),
                    'thead' => array( 'class' => array(), 'id' => array() ),
                    'tbody' => array( 'class' => array(), 'id' => array() ),
                    'tr' => array( 'class' => array(), 'id' => array() ),
                    'th' => array( 'class' => array(), 'id' => array() ),
                    'td' => array( 'class' => array(), 'id' => array() ),
                    'svg' => array( 'class' => array(), 'viewBox' => array(), 'xmlns' => array(), 'width' => array(), 'height' => array() ),
                    'path' => array( 'd' => array(), 'fill' => array() ),
                    'circle' => array( 'cx' => array(), 'cy' => array(), 'r' => array(), 'fill' => array() ),
                    'article' => array( 'class' => array(), 'id' => array() ),
                    'section' => array( 'class' => array(), 'id' => array() ),
                    'header' => array( 'class' => array(), 'id' => array() ),
                    'footer' => array( 'class' => array(), 'id' => array() ),
                    'nav' => array( 'class' => array(), 'id' => array() ),
                    'aside' => array( 'class' => array(), 'id' => array() ),
                    'main' => array( 'class' => array(), 'id' => array() ),
                    'figure' => array( 'class' => array(), 'id' => array() ),
                    'figcaption' => array( 'class' => array(), 'id' => array() ),
                );
            }
            
            return array(
                'a' => array( 'href' => array(), 'title' => array() ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                'div' => array( 'class' => array(), 'id' => array() ),
                'span' => array( 'class' => array(), 'id' => array() ),
                'p' => array( 'class' => array() ),
                'svg' => array( 'class' => array(), 'viewBox' => array(), 'xmlns' => array() ),
                'path' => array( 'd' => array(), 'fill' => array() ),
            );
        }
    }
    if ( ! function_exists( 'remove_all_filters' ) ) {
        function remove_all_filters( $tag, $priority = false ) {
            global $wp_filter;
            if ( isset( $wp_filter[ $tag ] ) ) {
                if ( false === $priority ) {
                    $wp_filter[ $tag ] = array();
                } elseif ( isset( $wp_filter[ $tag ][ $priority ] ) ) {
                    $wp_filter[ $tag ][ $priority ] = array();
                }
            }
            return true;
        }
    }
    if ( ! function_exists( 'remove_filter' ) ) {
        function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
            global $wp_filter;
            if ( isset( $wp_filter[ $tag ][ $priority ] ) ) {
                foreach ( $wp_filter[ $tag ][ $priority ] as $key => $filter ) {
                    if ( $filter['function'] === $function_to_remove ) {
                        unset( $wp_filter[ $tag ][ $priority ][ $key ] );
                        if ( empty( $wp_filter[ $tag ][ $priority ] ) ) {
                            unset( $wp_filter[ $tag ][ $priority ] );
                        }
                        if ( empty( $wp_filter[ $tag ] ) ) {
                            unset( $wp_filter[ $tag ] );
                        }
                        return true;
                    }
                }
            }
            return false;
        }
    }
    if ( ! function_exists( 'wp_raise_memory_limit' ) ) {
        function wp_raise_memory_limit( $context = 'default' ) {
            return '256M';
        }
    }
    if ( ! function_exists( 'wp_allowed_protocols' ) ) {
        function wp_allowed_protocols() {
            return array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet' );
        }
    }
    if ( ! function_exists( 'admin_url' ) ) {
        function admin_url( $path = '', $scheme = 'admin' ) {
            return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
        }
    }
    if ( ! function_exists( 'wp_create_nonce' ) ) {
        function wp_create_nonce( $action = -1 ) {
            return substr( md5( $action ), 0, 10 );
        }
    }
    
    // Add missing functions for tests
    if ( ! function_exists( 'wp_script_is' ) ) {
        function wp_script_is( $handle, $list = 'enqueued' ) {
            global $wp_scripts;
            
            if ( ! isset( $wp_scripts ) ) {
                $wp_scripts = new stdClass();
                $wp_scripts->registered = array();
                $wp_scripts->queue = array();
            }
            
            if ( $list === 'registered' ) {
                return isset( $wp_scripts->registered[ $handle ] );
            } elseif ( $list === 'enqueued' ) {
                return in_array( $handle, (array) $wp_scripts->queue );
            }
            
            return false;
        }
    }

    if ( ! function_exists( 'wp_style_is' ) ) {
        function wp_style_is( $handle, $list = 'enqueued' ) {
            global $wp_styles;
            
            if ( ! isset( $wp_styles ) ) {
                $wp_styles = new stdClass();
                $wp_styles->registered = array();
                $wp_styles->queue = array();
            }
            
            if ( $list === 'registered' ) {
                return isset( $wp_styles->registered[ $handle ] );
            } elseif ( $list === 'enqueued' ) {
                return in_array( $handle, (array) $wp_styles->queue );
            }
            
            return false;
        }
    }

    if ( ! function_exists( 'set_current_screen' ) ) {
        function set_current_screen( $screen ) {
            global $current_screen;
            
            if ( is_string( $screen ) ) {
                $current_screen = new stdClass();
                $current_screen->id = $screen;
                $current_screen->base = $screen;
                $current_screen->is_block_editor = false;
            } else {
                $current_screen = $screen;
            }
        }
    }

    if ( ! function_exists( 'get_permalink' ) ) {
        function get_permalink( $post_id = 0 ) {
            return 'https://example.com/?p=' . $post_id;
        }
    }

    if ( ! function_exists( 'wp_create_post_autosave' ) ) {
        function wp_create_post_autosave( $post_data ) {
            return rand( 1000, 9999 );
        }
    }

    if ( ! function_exists( 'wp_is_post_autosave' ) ) {
        function wp_is_post_autosave( $post ) {
            return false;
        }
    }

    if ( ! function_exists( 'wp_is_post_revision' ) ) {
        function wp_is_post_revision( $post ) {
            return false;
        }
    }

    if ( ! function_exists( 'wp_doing_autosave' ) ) {
        function wp_doing_autosave() {
            return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
        }
    }
    
    if ( ! function_exists( 'wp_doing_ajax' ) ) {
        function wp_doing_ajax() {
            return defined( 'DOING_AJAX' ) && DOING_AJAX;
        }
    }

    // Add global script/style tracking
    global $wp_scripts, $wp_styles;
    
    if ( ! isset( $wp_scripts ) ) {
        $wp_scripts = new stdClass();
        $wp_scripts->registered = array();
        $wp_scripts->queue = array();
    }
    
    if ( ! isset( $wp_styles ) ) {
        $wp_styles = new stdClass();
        $wp_styles->registered = array();
        $wp_styles->queue = array();
    }
}

/**
 * Manually load the plugin main file.
 */
function _manually_load_plugin() {
    require dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-blocks.php';
}

// If we have WordPress test environment, use it
if ( $_tests_dir && file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
    
    // Start up the WP testing environment.
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Define required constants for unit testing
    if ( ! defined( 'SPECTRA_PLUGIN_PATH' ) ) {
        define( 'SPECTRA_PLUGIN_PATH', dirname( dirname( dirname( __FILE__ ) ) ) . '/' );
    }
    if ( ! defined( 'SPECTRA_PLUGIN_URL' ) ) {
        define( 'SPECTRA_PLUGIN_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg' );
    }
    
    // Define Spectra V3 specific constants (only if not already defined)
    if ( ! defined( 'SPECTRA_3_FILE' ) ) {
        define( 'SPECTRA_3_FILE', dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-v3/index.php' );
    }
    if ( ! defined( 'SPECTRA_3_DIR' ) ) {
        define( 'SPECTRA_3_DIR', dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-v3/' );
    }
    if ( ! defined( 'SPECTRA_3_URL' ) ) {
        define( 'SPECTRA_3_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg/spectra-v3/' );
    }
    if ( ! defined( 'SPECTRA_DIR' ) ) {
        define( 'SPECTRA_DIR', dirname( dirname( dirname( __FILE__ ) ) ) . '/' );
    }
    if ( ! defined( 'SPECTRA_URL' ) ) {
        define( 'SPECTRA_URL', 'https://example.com/wp-content/plugins/ultimate-addons-for-gutenberg/' );
    }
    if ( ! defined( 'SPECTRA_VER' ) ) {
        define( 'SPECTRA_VER', '3.0.0' );
    }
    if ( ! defined( 'OBJECT' ) ) {
        define( 'OBJECT', 'OBJECT' );
    }
    if ( ! defined( 'ARRAY_A' ) ) {
        define( 'ARRAY_A', 'ARRAY_A' );
    }
    if ( ! defined( 'ARRAY_N' ) ) {
        define( 'ARRAY_N', 'ARRAY_N' );
    }
    
    // Load Spectra V3 autoloader if it exists
    $spectra_autoloader = dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-v3/includes/autoload.php';
    if ( file_exists( $spectra_autoloader ) ) {
        require_once $spectra_autoloader;
    }
    
    // Load spectra_blocks_init function (suppress warnings for redefined constants)
    $spectra_init_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-v3/index.php';
    if ( file_exists( $spectra_init_file ) ) {
        // Temporarily suppress warnings
        $old_error_reporting = error_reporting();
        error_reporting( $old_error_reporting & ~E_WARNING );
        require_once $spectra_init_file;
        error_reporting( $old_error_reporting );
    }
}

// Load Composer autoloader if it exists
if ( file_exists( dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/autoload.php' ) ) {
    require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/autoload.php';
}

// Mock WP_UnitTestCase if not available
if ( ! class_exists( 'WP_UnitTestCase' ) ) {
    class WP_Factory {
        public $user;
        public $post;
        
        public function __construct() {
            $this->user = new WP_Factory_For_User();
            $this->post = new WP_Factory_For_Post();
        }
    }
    
    class WP_Factory_For_User {
        public function create( $args = array() ) {
            return rand( 1, 1000 );
        }
    }
    
    class WP_Factory_For_Post {
        public function create( $args = array() ) {
            return rand( 1, 1000 );
        }
    }
    
    class WP_UnitTestCase extends PHPUnit\Framework\TestCase {
        public $factory;
        
        public function __construct() {
            parent::__construct();
            $this->factory = new WP_Factory();
        }
        
        public function setUp(): void {
            parent::setUp();
            if ( ! $this->factory ) {
                $this->factory = new WP_Factory();
            }
        }
        
        public function tearDown(): void {
            parent::tearDown();
        }
    }
}

// Include test helpers
$test_helpers_file = dirname( __FILE__ ) . '/includes/class-spectra-blocks-test-case.php';
if ( file_exists( $test_helpers_file ) ) {
    require_once $test_helpers_file;
}

// Include Spectra namespace functions
$spectra_functions_file = dirname( __FILE__ ) . '/includes/spectra-blocks-namespace-functions.php';
if ( file_exists( $spectra_functions_file ) ) {
    require_once $spectra_functions_file;
}

// Mock UAGB_Admin_Helper class
if ( ! class_exists( 'UAGB_Admin_Helper' ) ) {
    class Spectra_Admin_Helper {
        public static function get_admin_settings_option( $option, $default = array() ) {
            $spectra_fonts = get_option( 'spectra_fonts', array() );
            
            if ( isset( $spectra_fonts[ $option ] ) ) {
                return $spectra_fonts[ $option ];
            }
            
            if ( $option === 'spectra_font_families' ) {
                return $spectra_fonts;
            }
            
            return $default;
        }
    }
}

// Load HtmlSanitizer class early so we can override it if needed
$html_sanitizer_file = dirname( dirname( dirname( __FILE__ ) ) ) . '/spectra-v3/includes/Helpers/HtmlSanitizer.php';
if ( file_exists( $html_sanitizer_file ) && ! class_exists( 'Spectra\Helpers\HtmlSanitizer' ) ) {
    require_once $html_sanitizer_file;
}

// Mock WP_HTML_Tag_Processor for testing
if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
    class WP_HTML_Tag_Processor {
        private $html;
        private $current_tag;
        
        public function __construct( $html ) {
            $this->html = $html;
        }
        
        public function next_tag( $tag_name = null ) {
            if ( $tag_name ) {
                $this->current_tag = $tag_name;
                return strpos( $this->html, '<' . $tag_name ) !== false;
            }
            // If no specific tag, find the first tag
            if ( preg_match( '/<([a-zA-Z][a-zA-Z0-9]*)/', $this->html, $matches ) ) {
                $this->current_tag = $matches[1];
                return true;
            }
            return false;
        }
        
        public function get_attribute( $attribute_name ) {
            if ( $attribute_name === 'class' ) {
                if ( preg_match( '/class="([^"]*)"/', $this->html, $matches ) ) {
                    return $matches[1];
                }
            }
            if ( $attribute_name === 'style' ) {
                if ( preg_match( '/style="([^"]*)"/', $this->html, $matches ) ) {
                    return $matches[1];
                }
            }
            return null;
        }
        
        public function set_attribute( $attribute_name, $value ) {
            if ( ! $this->current_tag ) {
                return;
            }
            
            $escaped_value = esc_attr( $value );
            $tag_pattern = '<' . $this->current_tag;
            
            // Check if attribute already exists
            if ( preg_match( '/' . $attribute_name . '="([^"]*)"/', $this->html ) ) {
                // Update existing attribute
                $this->html = preg_replace( 
                    '/' . $attribute_name . '="([^"]*)"/', 
                    $attribute_name . '="' . $escaped_value . '"', 
                    $this->html 
                );
            } else {
                // Add new attribute to the tag
                $this->html = str_replace( 
                    $tag_pattern, 
                    $tag_pattern . ' ' . $attribute_name . '="' . $escaped_value . '"', 
                    $this->html 
                );
            }
        }
        
        public function get_updated_html() {
            return $this->html;
        }
    }
}