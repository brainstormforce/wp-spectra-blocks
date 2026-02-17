<?php
/**
 * Mock functions for Spectra namespace.
 *
 * @package Spectra\Tests
 */

namespace Spectra;

if ( ! function_exists( 'Spectra\\spectra_log' ) ) {
    /**
     * Mock spectra_log function.
     *
     * @param string $message Log message.
     * @param string $level Log level.
     * @return void
     */
    function spectra_log( $message, $level = 'debug' ) {
        // Mock implementation for testing
        return;
    }
}

if ( ! function_exists( 'Spectra\\wp_raise_memory_limit' ) ) {
    /**
     * Mock wp_raise_memory_limit function.
     *
     * @param string $context Context for memory limit.
     * @return string
     */
    function wp_raise_memory_limit( $context = 'default' ) {
        return '256M';
    }
}

if ( ! function_exists( 'Spectra\\get_stylesheet_directory' ) ) {
    /**
     * Mock get_stylesheet_directory function.
     *
     * @return string
     */
    function get_stylesheet_directory() {
        return '/tmp/theme';
    }
}

if ( ! function_exists( 'Spectra\\file_get_contents' ) ) {
    /**
     * Mock file_get_contents function.
     *
     * @param string $filename Filename.
     * @return string|false
     */
    function file_get_contents( $filename ) {
        if ( strpos( $filename, 'theme.json' ) !== false ) {
            return json_encode( array( 
                'version' => 2,
                'settings' => array(
                    'typography' => array(
                        'fontFamilies' => array()
                    )
                )
            ) );
        }
        return \file_get_contents( $filename );
    }
}

namespace Spectra\Extensions;

if ( ! function_exists( 'Spectra\\Extensions\\wp_render_layout_support_flag' ) ) {
    /**
     * Mock wp_render_layout_support_flag function.
     *
     * @param string $block_content Block content.
     * @param array  $block Block data.
     * @return string
     */
    function wp_render_layout_support_flag( $block_content, $block ) {
        // Simple mock that just returns the content
        return $block_content;
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_generate_uuid4' ) ) {
    /**
     * Mock wp_generate_uuid4 function.
     *
     * @return string
     */
    function wp_generate_uuid4() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff )
        );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\remove_filter' ) ) {
    /**
     * Mock remove_filter function.
     *
     * @param string   $tag      The filter hook name.
     * @param callable $function The function to remove.
     * @param int      $priority The priority.
     * @return bool
     */
    function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
        return \remove_filter( $tag, $function_to_remove, $priority );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\add_filter' ) ) {
    /**
     * Mock add_filter function.
     *
     * @param string   $tag      The filter hook name.
     * @param callable $function The function to add.
     * @param int      $priority The priority.
     * @param int      $accepted_args Number of accepted arguments.
     * @return bool
     */
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        return \add_filter( $tag, $function_to_add, $priority, $accepted_args );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\add_action' ) ) {
    /**
     * Mock add_action function.
     *
     * @param string   $tag      The action hook name.
     * @param callable $function The function to add.
     * @param int      $priority The priority.
     * @param int      $accepted_args Number of accepted arguments.
     * @return bool
     */
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        return \add_action( $tag, $function_to_add, $priority, $accepted_args );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_doing_ajax' ) ) {
    /**
     * Mock wp_doing_ajax function.
     *
     * @return bool
     */
    function wp_doing_ajax() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_register_style' ) ) {
    /**
     * Mock wp_register_style function.
     *
     * @param string $handle Style handle.
     * @param string $src Style source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param string $media Media.
     * @return bool
     */
    function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
        return \wp_register_style( $handle, $src, $deps, $ver, $media );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_enqueue_style' ) ) {
    /**
     * Mock wp_enqueue_style function.
     *
     * @param string $handle Style handle.
     * @param string $src Style source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param string $media Media.
     * @return bool
     */
    function wp_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
        return \wp_enqueue_style( $handle, $src, $deps, $ver, $media );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\is_admin' ) ) {
    /**
     * Mock is_admin function.
     *
     * @return bool
     */
    function is_admin() {
        return \is_admin();
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\has_block' ) ) {
    /**
     * Mock has_block function.
     *
     * @param string $block_name Block name.
     * @param mixed  $post Post.
     * @return bool
     */
    function has_block( $block_name, $post = null ) {
        return \has_block( $block_name, $post );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_register_script' ) ) {
    /**
     * Mock wp_register_script function.
     *
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param bool   $in_footer Load in footer.
     * @return bool
     */
    function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
        return \wp_register_script( $handle, $src, $deps, $ver, $in_footer );
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\wp_enqueue_script' ) ) {
    /**
     * Mock wp_enqueue_script function.
     *
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param bool   $in_footer Load in footer.
     * @return bool
     */
    function wp_enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
        return \wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
    }
}

namespace Spectra\Extensions\ResponsiveControls;

if ( ! function_exists( 'Spectra\\Extensions\\ResponsiveControls\\wp_style_engine_get_stylesheet_from_css_rules' ) ) {
    /**
     * Mock wp_style_engine_get_stylesheet_from_css_rules function.
     *
     * @param array $css_rules CSS rules.
     * @param array $options Options.
     * @return string
     */
    function wp_style_engine_get_stylesheet_from_css_rules( $css_rules, $options = array() ) {
        $css = '';
        
        foreach ( $css_rules as $rule ) {
            if ( isset( $rule['selector'] ) && isset( $rule['declarations'] ) && ! empty( $rule['declarations'] ) ) {
                $css .= $rule['selector'] . '{';
                foreach ( $rule['declarations'] as $property => $value ) {
                    if ( ! empty( $value ) ) {
                        $css .= $property . ':' . $value . ';';
                    }
                }
                $css .= '}';
            }
        }
        
        return $css;
    }
}

if ( ! function_exists( 'Spectra\\Extensions\\ResponsiveControls\\esc_url' ) ) {
    /**
     * Mock esc_url function.
     *
     * @param string $url URL to escape.
     * @param array  $protocols Allowed protocols.
     * @param string $_context Context.
     * @return string
     */
    function esc_url( $url, $protocols = null, $_context = 'display' ) {
        return \esc_url( $url, $protocols, $_context );
    }
}

namespace Spectra\Blocks;

if ( ! function_exists( 'Spectra\\Blocks\\wp_script_is' ) ) {
    /**
     * Mock wp_script_is function.
     *
     * @param string $handle Script handle.
     * @param string $list List type.
     * @return bool
     */
    function wp_script_is( $handle, $list = 'enqueued' ) {
        return \wp_script_is( $handle, $list );
    }
}

if ( ! function_exists( 'Spectra\\Blocks\\wp_register_script' ) ) {
    /**
     * Mock wp_register_script function.
     *
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param bool   $in_footer Load in footer.
     * @return bool
     */
    function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
        return \wp_register_script( $handle, $src, $deps, $ver, $in_footer );
    }
}

if ( ! function_exists( 'Spectra\\Blocks\\wp_enqueue_script' ) ) {
    /**
     * Mock wp_enqueue_script function.
     *
     * @param string $handle Script handle.
     * @param string $src Script source.
     * @param array  $deps Dependencies.
     * @param string $ver Version.
     * @param bool   $in_footer Load in footer.
     * @return bool
     */
    function wp_enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
        return \wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
    }
}

if ( ! function_exists( 'Spectra\\Blocks\\is_admin' ) ) {
    /**
     * Mock is_admin function.
     *
     * @return bool
     */
    function is_admin() {
        return \is_admin();
    }
}