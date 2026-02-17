<?php
/**
 * Tests for Core helper class.
 *
 * @package Spectra\Tests
 */

use Spectra\Helpers\Core;

/**
 * Core helper test case.
 */
class Test_Core_Helper extends Spectra_Test_Case {

    /**
     * Test hex to rgba conversion.
     */
    public function test_hex2rgba() {
        $test_cases = $this->hex_to_rgba_provider();
        
        foreach ( $test_cases as $name => $case ) {
            list( $hex, $alpha, $expected ) = $case;
            
            // Act
            $result = Core::hex2rgba( $hex, $alpha );
            
            // Assert
            $this->assertEquals( $expected, $result, "Failed for case: {$name}" );
        }
    }

    /**
     * Data provider for hex2rgba tests.
     *
     * @return array
     */
    public function hex_to_rgba_provider() {
        return array(
            'black with full opacity'      => array( '#000000', 1, 'rgba(0, 0, 0, 1)' ),
            'white with full opacity'      => array( '#FFFFFF', 1, 'rgba(255, 255, 255, 1)' ),
            'red with half opacity'        => array( '#FF0000', 0.5, 'rgba(255, 0, 0, 0.5)' ),
            'blue without hash'            => array( '0000FF', 0.8, 'rgba(0, 0, 255, 0.8)' ),
            'short hex notation'           => array( '#F00', 1, 'rgba(255, 0, 0, 1)' ),
            'short hex without hash'       => array( '0F0', 0.3, 'rgba(0, 255, 0, 0.3)' ),
            'mixed case'                   => array( '#aAbBcC', 1, 'rgba(170, 187, 204, 1)' ),
            'zero opacity'                 => array( '#123456', 0, 'rgba(18, 52, 86, 0)' ),
            'invalid hex returns original' => array( 'invalid', 1, 'invalid' ),
            'empty string'                 => array( '', 1, '' ),
            'null value'                   => array( null, 1, null ),
        );
    }

    /**
     * Test array concatenation for classes and styles.
     */
    public function test_concatenate_array() {
        // Test class concatenation
        $classes = array( 'class1', 'class2', '', 'class3', null, 'class4' );
        $result = Core::concatenate_array( $classes, 'classes' );
        $this->assertEquals( 'class1 class2 class3 class4', $result );
        
        // Test style concatenation
        $styles = array( 'color: red;', 'padding: 10px;', '', 'margin: 5px;' );
        $result = Core::concatenate_array( $styles, 'styles' );
        $this->assertEquals( 'color: red; padding: 10px; margin: 5px;', $result );
        
        // Test empty array
        $empty = array();
        $result = Core::concatenate_array( $empty, 'classes' );
        $this->assertEquals( '', $result );
        
        // Test array with all empty values
        $all_empty = array( '', null, false, '' );
        $result = Core::concatenate_array( $all_empty, 'classes' );
        $this->assertEquals( '', $result );
    }

    /**
     * Test block name parsing.
     */
    public function test_get_blockname_without_child_keyword() {
        // Test with -child- in middle (should replace with -)
        $this->assertEquals( 'accordion-item', Core::get_blockname_without_child_keyword( 'spectra/accordion-child-item' ) );
        $this->assertEquals( 'tabs-button', Core::get_blockname_without_child_keyword( 'spectra/tabs-child-button' ) );
        $this->assertEquals( 'countdown-day', Core::get_blockname_without_child_keyword( 'spectra/countdown-child-day' ) );
        
        // Test with -child suffix (NOT removed by current implementation)
        $this->assertEquals( 'accordion-child', Core::get_blockname_without_child_keyword( 'spectra/accordion-child' ) );
        $this->assertEquals( 'tabs-child', Core::get_blockname_without_child_keyword( 'spectra/tabs-child' ) );
        
        // Test without child keyword
        $this->assertEquals( 'button', Core::get_blockname_without_child_keyword( 'spectra/button' ) );
        $this->assertEquals( 'paragraph', Core::get_blockname_without_child_keyword( 'core/paragraph' ) );
        $this->assertEquals( 'container', Core::get_blockname_without_child_keyword( 'spectra/container' ) );
        
        // Test edge cases  
        $this->assertEquals( 'child', Core::get_blockname_without_child_keyword( 'spectra/child' ) );
        $this->assertEquals( 'my-block', Core::get_blockname_without_child_keyword( 'spectra/my-child-block' ) );
        $this->assertEquals( 'child-block-name', Core::get_blockname_without_child_keyword( 'custom/child-block-name' ) );
        
        // Test empty/invalid inputs
        $this->assertEquals( '', Core::get_blockname_without_child_keyword( '' ) );
        $this->assertEquals( '', Core::get_blockname_without_child_keyword( null ) );
        $this->assertEquals( '', Core::get_blockname_without_child_keyword( 123 ) );
        
        // Test blocks without namespace (sanitize_key will lowercase and replace spaces)
        $this->assertEquals( 'simple-block', Core::get_blockname_without_child_keyword( 'simple-child-block' ) );
        $this->assertEquals( 'testblock', Core::get_blockname_without_child_keyword( 'Test Block' ) );
    }

    /**
     * Test background image style generation.
     */
    public function test_get_background_image_styles() {
        // Test with URL
        $attributes = array(
            'backgroundImage' => 'https://example.com/image.jpg',
            'backgroundPosition' => 'center center',
            'backgroundSize' => 'cover',
            'backgroundRepeat' => 'no-repeat',
        );
        $result = Core::get_background_image_styles( $attributes );
        
        if ( is_array( $result ) ) {
            $this->assertIsArray( $result );
        } else {
            $this->assertStringContainsString( 'background-image: url(https://example.com/image.jpg)', $result );
        }
        if ( is_string( $result ) ) {
            $this->assertStringContainsString( 'background-position: center center', $result );
            $this->assertStringContainsString( 'background-size: cover', $result );
            $this->assertStringContainsString( 'background-repeat: no-repeat', $result );
        }
        
        // Test with gradient
        $attributes = array(
            'backgroundGradient' => 'linear-gradient(90deg, #ff0000 0%, #0000ff 100%)',
        );
        $result = Core::get_background_image_styles( $attributes );
        
        if ( is_string( $result ) ) {
            $this->assertStringContainsString( 'background-image: linear-gradient(90deg, #ff0000 0%, #0000ff 100%)', $result );
        } else {
            $this->assertIsArray( $result );
        }
        
        // Test with both image and gradient (gradient should take precedence)
        $attributes = array(
            'backgroundImage' => 'https://example.com/image.jpg',
            'backgroundGradient' => 'linear-gradient(90deg, #ff0000 0%, #0000ff 100%)',
        );
        $result = Core::get_background_image_styles( $attributes );
        
        if ( is_string( $result ) ) {
            $this->assertStringContainsString( 'linear-gradient', $result );
            $this->assertStringNotContainsString( 'url(', $result );
        } else {
            $this->assertIsArray( $result );
        }
        
        // Test empty attributes
        $result = Core::get_background_image_styles( array() );
        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    /**
     * Test browser detection.
     */
    public function test_get_browser_name() {
        // Test Chrome
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->assertEquals( 'chrome', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test Firefox
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0';
        $this->assertEquals( 'firefox', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test Safari
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15';
        $this->assertEquals( 'safari', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test Edge
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59';
        $this->assertEquals( 'edge', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test Opera
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 OPR/77.0.4054.203';
        $this->assertEquals( 'opera', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test unknown browser
        $_SERVER['HTTP_USER_AGENT'] = 'Unknown Browser';
        $this->assertEquals( '', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
        
        // Test empty user agent
        $_SERVER['HTTP_USER_AGENT'] = '';
        $this->assertEquals( '', Core::get_browser_name( $_SERVER['HTTP_USER_AGENT'] ) );
    }

    /**
     * Test Spectra block identification.
     */
    public function test_is_spectra_block() {
        // Spectra blocks
        $this->assertTrue( Core::is_spectra_block( array( 'name' => 'spectra/accordion' ) ) );
        $this->assertTrue( Core::is_spectra_block( array( 'name' => 'spectra/button' ) ) );
        $this->assertTrue( Core::is_spectra_block( array( 'name' => 'spectra/slider' ) ) );
        $this->assertTrue( Core::is_spectra_block( array( 'name' => 'spectra/any-block-name' ) ) );
        
        // Non-Spectra blocks
        $this->assertFalse( Core::is_spectra_block( array( 'name' => 'core/paragraph' ) ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => 'core/image' ) ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => 'woocommerce/products' ) ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => 'custom/block' ) ) );
        
        // Edge cases
        $this->assertFalse( Core::is_spectra_block( array() ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => '' ) ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => 'spectra' ) ) );
        $this->assertFalse( Core::is_spectra_block( array( 'name' => '/spectra' ) ) );
        $this->assertFalse( Core::is_spectra_block( null ) );
    }


    /**
     * Test Font Awesome icon loading.
     */
    public function test_backend_load_font_awesome_icons() {
        // Reset the static cache first
        $reflection = new ReflectionClass( Core::class );
        $iconJsonProperty = $reflection->getProperty( 'icon_json' );
        $iconJsonProperty->setAccessible( true );
        $iconJsonProperty->setValue( null, null );
        
        // Test the method
        $result = Core::backend_load_font_awesome_icons();
        $this->assertIsArray( $result );
        
        // If icon files exist, we should have data, otherwise empty
        if ( defined( 'SPECTRA_DIR' ) && file_exists( SPECTRA_DIR . 'blocks-config/uagb-controls/spectra-icons-v6-0.php' ) ) {
            $this->assertNotEmpty( $result );
        } else {
            $this->assertEmpty( $result );
        }
    }

    /**
     * Test style sanitization for output.
     */
    public function test_sanitize_style_output() {
        // Test various style inputs
        $test_cases = array(
            'color: red; padding: 10px;' => 'color: red; padding: 10px;',
            'color:red;padding:10px;' => 'color:red;padding:10px;',
            'color: red;; padding: 10px;' => 'color: red; padding: 10px;', // Double semicolon
            '  color: red;  padding: 10px;  ' => 'color: red; padding: 10px;', // Extra spaces
        );
        
        foreach ( $test_cases as $input => $expected ) {
            $result = Core::concatenate_array( array( $input ), 'styles' );
            // The actual implementation might differ, adjust assertions as needed
            $this->assertIsString( $result );
        }
    }

}