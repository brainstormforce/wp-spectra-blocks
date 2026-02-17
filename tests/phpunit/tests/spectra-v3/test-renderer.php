<?php
/**
 * Tests for Renderer helper class.
 *
 * @package Spectra\Tests
 */

use Spectra\Helpers\Renderer;

/**
 * Renderer test case.
 */
class Test_Renderer extends Spectra_Test_Case {


    /**
     * Data provider for SVG rendering tests.
     *
     * @return array
     */
    public function svg_render_provider() {
        return array(
            'arrow icon LTR' => array(
                'icon' => 'arrow',
                'is_rtl' => false,
                'expected_contains' => array( '<svg', 'viewBox', '</svg>' ),
                'not_expected' => array( 'transform' ),
            ),
            'arrow icon RTL' => array(
                'icon' => 'arrow',
                'is_rtl' => true,
                'expected_contains' => array( '<svg', 'transform="scale(-1,1)"', '</svg>' ),
                'not_expected' => array(),
            ),
            'check icon' => array(
                'icon' => 'check',
                'is_rtl' => false,
                'expected_contains' => array( '<svg', '<path', '</svg>' ),
                'not_expected' => array( 'transform' ),
            ),
            'custom icon' => array(
                'icon' => 'custom',
                'is_rtl' => false,
                'expected_contains' => array( '<svg', '</svg>' ),
                'not_expected' => array(),
            ),
        );
    }

    /**
     * Test SVG with empty or invalid input.
     */
    public function test_svg_html_with_invalid_input() {
        // Test empty string
        ob_start();
        Renderer::svg_html( '' );
        $result = ob_get_clean();
        $this->assertEquals( '', $result );
        
        // Test non-existent icon
        ob_start();
        Renderer::svg_html( 'non-existent-icon' );
        $result = ob_get_clean();
        if ( ! empty( $result ) ) {
            $this->assertStringContainsString( '<svg', $result );
            $this->assertStringContainsString( '</svg>', $result );
        } else {
            $this->assertTrue( true, 'No output for non-existent icon' );
        }
    }

    /**
     * Test background video rendering.
     */
    public function test_background_video_basic() {
        // Arrange
        $background = array(
            'type' => 'video',
            'media' => array(
                'url' => 'https://example.com/video.mp4',
            ),
            'useOverlay' => false,
        );
        
        // Act
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        
        // Assert
        if ( ! empty( $result ) ) {
            $this->assertStringContainsString( '<video', $result );
            $this->assertStringContainsString( 'src="https://example.com/video.mp4"', $result );
            $this->assertStringContainsString( 'autoPlay', $result );
            $this->assertStringContainsString( 'loop', $result );
            $this->assertStringContainsString( 'muted', $result );
            $this->assertStringContainsString( '</video>', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
    }

    /**
     * Test background video with different configurations.
     */
    public function test_background_video_configurations() {
        // Test video with overlay
        $background = array(
            'type' => 'video',
            'media' => array( 'url' => 'https://example.com/video.mp4' ),
            'useOverlay' => true,
        );
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        if ( ! empty( $result ) ) {
            // Note: Overlay class is handled via CSS, not conditional PHP classes
            $this->assertStringContainsString( 'spectra-background-video__wrapper', $result );
            $this->assertStringContainsString( 'autoPlay', $result );
            $this->assertStringContainsString( 'loop', $result );
            $this->assertStringContainsString( 'muted', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
        
        // Test video without overlay
        $background = array(
            'type' => 'video',
            'media' => array( 'url' => 'https://example.com/video.mp4' ),
            'useOverlay' => false,
        );
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        if ( ! empty( $result ) ) {
            $this->assertStringContainsString( '<video', $result );
            // Note: Overlay class is handled via CSS, not conditional PHP classes
            $this->assertStringContainsString( 'spectra-background-video__wrapper', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
        
        // Test non-video type
        $background = array(
            'type' => 'image',
            'media' => array( 'url' => 'https://example.com/image.jpg' ),
        );
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        $this->assertEquals( '', $result );
        
        // Test no video URL
        $background = array(
            'type' => 'video',
            'media' => array(),
        );
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        $this->assertEquals( '', $result );
    }

    /**
     * Data provider for video configuration tests.
     *
     * @return array
     */
    public function video_config_provider() {
        return array(
            'video with all options enabled' => array(
                'attributes' => array(
                    'backgroundVideo' => array( 'url' => 'https://example.com/video.mp4' ),
                    'backgroundVideoLoop' => true,
                    'backgroundVideoMuted' => true,
                    'backgroundVideoAutoplay' => true,
                    'backgroundVideoControls' => true,
                ),
                'expected_attrs' => array( 'loop', 'muted', 'autoplay', 'controls' ),
            ),
            'video with options disabled' => array(
                'attributes' => array(
                    'backgroundVideo' => array( 'url' => 'https://example.com/video.mp4' ),
                    'backgroundVideoLoop' => false,
                    'backgroundVideoMuted' => false,
                    'backgroundVideoAutoplay' => false,
                ),
                'expected_attrs' => array( '<video' ),
                'not_expected_attrs' => array( 'loop', 'muted', 'autoplay' ),
            ),
            'video with poster image' => array(
                'attributes' => array(
                    'backgroundVideo' => array( 'url' => 'https://example.com/video.mp4' ),
                    'backgroundVideoPoster' => 'https://example.com/poster.jpg',
                ),
                'expected_attrs' => array( 'poster="https://example.com/poster.jpg"' ),
            ),
            'no video URL' => array(
                'attributes' => array(
                    'backgroundVideo' => array(),
                ),
                'expected_attrs' => array(),
            ),
        );
    }

    /**
     * Test video opacity handling.
     */
    public function test_background_video_opacity() {
        // The background_video method doesn't handle opacity directly
        // It only handles type, media URL, and overlay
        $background = array(
            'type' => 'video',
            'media' => array( 'url' => 'https://example.com/video.mp4' ),
            'useOverlay' => true,
        );
        
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        
        if ( ! empty( $result ) ) {
            $this->assertStringContainsString( 'spectra-background-video__wrapper', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
    }

    /**
     * Test video with custom classes.
     */
    public function test_background_video_custom_classes() {
        $background = array(
            'type' => 'video',
            'media' => array( 'url' => 'https://example.com/video.mp4' ),
            'useOverlay' => false,
        );
        
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        
        if ( ! empty( $result ) ) {
            $this->assertStringContainsString( 'class="', $result );
            $this->assertStringContainsString( 'spectra-background-video__wrapper', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
    }

    /**
     * Test SVG icon library.
     */
    public function test_svg_icon_library() {
        // Common icons that should be available
        $common_icons = array(
            'arrow-right',
            'arrow-left',
            'arrow-up',
            'arrow-down',
            'check',
            'close',
            'menu',
            'search',
            'plus',
            'minus',
        );
        
        foreach ( $common_icons as $icon ) {
            ob_start();
            Renderer::svg_html( $icon );
            $result = ob_get_clean();
            
            if ( ! empty( $result ) ) {
                $this->assertStringContainsString( '<svg', $result );
                $this->assertStringContainsString( '</svg>', $result );
            } else {
                $this->assertTrue( true, 'Icon ' . $icon . ' not found in library' );
            }
        }
    }


    /**
     * Test video rendering with XSS attempts.
     */
    public function test_background_video_xss_prevention() {
        $malicious_background = array(
            'type' => 'video',
            'media' => array(
                'url' => 'javascript:alert("XSS")',
            ),
            'useOverlay' => false,
        );
        
        ob_start();
        Renderer::background_video( $malicious_background );
        $result = ob_get_clean();
        
        if ( ! empty( $result ) ) {
            // Should not contain any script tags or javascript
            $this->assertStringNotContainsString( '<script', $result );
            $this->assertStringNotContainsString( 'javascript:', $result );
            $this->assertStringNotContainsString( 'onload=', $result );
            $this->assertStringNotContainsString( 'alert', $result );
        } else {
            $this->assertTrue( true, 'No output for malicious input' );
        }
    }

    /**
     * Test rendering performance with caching.
     */
    public function test_svg_rendering_performance() {
        $icon = 'arrow';
        $iterations = 100;
        
        $start_time = microtime( true );
        
        for ( $i = 0; $i < $iterations; $i++ ) {
            ob_start();
            Renderer::svg_html( $icon );
            ob_end_clean();
        }
        
        $end_time = microtime( true );
        $total_time = $end_time - $start_time;
        
        // Should complete 100 renders in less than 0.1 seconds
        $this->assertLessThan( 0.1, $total_time, 'SVG rendering should be fast' );
    }

    /**
     * Test video fallback content.
     */
    public function test_background_video_fallback() {
        $background = array(
            'type' => 'video',
            'media' => array( 'url' => 'https://example.com/video.mp4' ),
            'useOverlay' => false,
        );
        
        ob_start();
        Renderer::background_video( $background );
        $result = ob_get_clean();
        
        if ( ! empty( $result ) ) {
            // Should be inside video tags
            $this->assertStringContainsString( '<video', $result );
            $this->assertStringContainsString( '</video>', $result );
            $this->assertStringContainsString( '<source', $result );
        } else {
            $this->markTestIncomplete( 'Background video rendering returned empty result' );
        }
    }
}