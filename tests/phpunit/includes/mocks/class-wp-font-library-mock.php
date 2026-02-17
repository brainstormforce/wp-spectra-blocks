<?php
/**
 * Mock WP_Font_Library class for testing.
 *
 * @package Spectra\Tests\Mocks
 */

if ( ! class_exists( 'WP_Font_Library' ) ) {
    /**
     * Mock WP_Font_Library class.
     */
    class WP_Font_Library {
        /**
         * Instance of the class.
         *
         * @var WP_Font_Library
         */
        private static $instance = null;
        
        /**
         * Get instance.
         *
         * @return WP_Font_Library
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Get font collection.
         *
         * @param string $collection_id Collection ID.
         * @return mixed
         */
        public function get_font_collection( $collection_id ) {
            if ( 'google-fonts' === $collection_id ) {
                return new WP_Font_Collection();
            }
            return null;
        }
    }
}

if ( ! class_exists( 'WP_Font_Collection' ) ) {
    /**
     * Mock WP_Font_Collection class.
     */
    class WP_Font_Collection {
        /**
         * Get data.
         *
         * @return array
         */
        public function get_data() {
            return array(
                'font_families' => array()
            );
        }
    }
}

if ( ! class_exists( 'WP_Theme_JSON_Data' ) ) {
    /**
     * Mock WP_Theme_JSON_Data class.
     */
    class WP_Theme_JSON_Data {
        /**
         * Theme JSON data.
         *
         * @var array
         */
        private $data;

        /**
         * Constructor.
         *
         * @param array $data Theme JSON data.
         */
        public function __construct( $data = array() ) {
            $this->data = $data;
        }

        /**
         * Get data.
         *
         * @return array
         */
        public function get_data() {
            return $this->data;
        }

        /**
         * Update with.
         *
         * @param array $data New data.
         * @return void
         */
        public function update_with( $data ) {
            $this->data = array_merge_recursive( $this->data, $data );
        }
    }
}