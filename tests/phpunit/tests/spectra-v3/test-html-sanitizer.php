
<?php
/**
 * Tests for HtmlSanitizer class.
 *
 * @package Spectra\Tests
 */

use Spectra\Helpers\HtmlSanitizer;

/**
 * HtmlSanitizer test case.
 */
class Test_Html_Sanitizer extends Spectra_Test_Case {

    /**
     * Test basic HTML sanitization.
     */
    public function test_render_sanitizes_basic_html() {
        // Arrange
        $input = '<p>Hello <strong>World</strong></p>';
        $expected = '<p>Hello <strong>World</strong></p>';
        
        // Act - pass false to get return value instead of echo
        $result = HtmlSanitizer::render( $input, null, false );
        
        // Assert
        $this->assertEquals( $expected, $result );
    }

    /**
     * Test script tag removal.
     */
    public function test_render_removes_script_tags() {
        // Arrange
        $malicious_html = '<p>Hello</p><script>alert("XSS")</script><p>World</p>';
        $expected = '<p>Hello</p><p>World</p>';
        
        // Act - pass false to get return value instead of echo
        $result = HtmlSanitizer::render( $malicious_html, null, false );
        
        // Assert
        $this->assertEquals( $expected, $result );
        $this->assertStringNotContainsString( '<script', $result );
    }

    /**
     * Test various malicious inputs.
     */
    public function test_render_handles_malicious_input() {
        $test_cases = $this->malicious_html_provider();
        
        foreach ( $test_cases as $name => $case ) {
            list( $input, $should_not_contain ) = $case;
            // Act - pass false to get return value instead of echo
            $result = HtmlSanitizer::render( $input, null, false );
            
            // Assert
            foreach ( $should_not_contain as $dangerous_string ) {
                $this->assertStringNotContainsString( 
                    $dangerous_string, 
                    $result,
                    "Output should not contain: {$dangerous_string} for case: {$name}"
                );
            }
        }
    }

    /**
     * Data provider for malicious HTML tests.
     *
     * @return array
     */
    public function malicious_html_provider() {
        return array(
            'javascript protocol' => array(
                '<a href="javascript:alert(\'XSS\')">Click</a>',
                array( 'javascript:', 'alert' ),
            ),
            'onerror attribute' => array(
                '<img src="x" onerror="alert(\'XSS\')" />',
                array( 'onerror', 'alert' ),
            ),
            'onclick attribute' => array(
                '<button onclick="maliciousCode()">Click</button>',
                array( 'onclick', 'maliciousCode' ),
            ),
            'data protocol' => array(
                '<a href="data:text/html,<script>alert(\'XSS\')</script>">Link</a>',
                array( 'data:', '<script>' ),
            ),
        );
    }

    /**
     * Test allowed HTML5 elements.
     */
    public function test_render_allows_html5_elements() {
        // Arrange
        $html5_elements = array(
            '<article>Content</article>',
            '<section>Content</section>',
            '<nav>Content</nav>',
            '<aside>Content</aside>',
            '<header>Content</header>',
            '<footer>Content</footer>',
            '<figure><figcaption>Caption</figcaption></figure>',
        );
        
        foreach ( $html5_elements as $element ) {
            // Act - pass false to get return value instead of echo
            $result = HtmlSanitizer::render( $element, null, false );
            
            // Assert
            $this->assertEquals( 
                $element, 
                $result,
                "HTML5 element should be preserved: {$element}"
            );
        }
    }

    /**
     * Test SVG sanitization.
     */
    public function test_render_sanitizes_svg() {
        // Arrange
        $safe_svg = '<svg width="100" height="100"><circle cx="50" cy="50" r="40" /></svg>';
        $malicious_svg = '<svg><script>alert("XSS")</script><circle cx="50" cy="50" r="40" /></svg>';
        
        // Act - pass false to get return value instead of echo
        $safe_result = HtmlSanitizer::render( $safe_svg, null, false );
        $malicious_result = HtmlSanitizer::render( $malicious_svg, null, false );
        
        // Assert
        $this->assertStringContainsString( '<svg', $safe_result );
        $this->assertStringContainsString( '<circle', $safe_result );
        $this->assertStringNotContainsString( '<script', $malicious_result );
    }

    /**
     * Test style attribute handling.
     */
    public function test_render_handles_style_attributes() {
        // Arrange
        $valid_styles = '<div style="color: red; background-color: #fff; margin: 10px;">Content</div>';
        $invalid_styles = '<div style="behavior: url(xss.htc); expression(alert(\'XSS\'));">Content</div>';
        
        // Act - pass false to get return value instead of echo
        $valid_result = HtmlSanitizer::render( $valid_styles, null, false );
        $invalid_result = HtmlSanitizer::render( $invalid_styles, null, false );
        
        // Assert
        $this->assertStringContainsString( 'color: red', $valid_result );
        $this->assertStringNotContainsString( 'behavior:', $invalid_result );
        $this->assertStringNotContainsString( 'expression', $invalid_result );
    }

    /**
     * Test CSS transform functions.
     */
    public function test_allow_css_transform_functions() {
        // Arrange
        $transform_styles = array(
            'transform: rotate(45deg)',
            'transform: scale(1.5)',
            'transform: translate(10px, 20px)',
            'transform: skew(10deg)',
            'transform: matrix(1, 0, 0, 1, 0, 0)',
        );
        
        foreach ( $transform_styles as $style ) {
            $html = '<div style="' . $style . '">Content</div>';
            
            // Act - pass false to get return value instead of echo
            $result = HtmlSanitizer::render( $html, null, false );
            
            // Assert
            $this->assertStringContainsString( 
                $style, 
                $result,
                "Transform function should be allowed: {$style}"
            );
        }
    }

    /**
     * Test data attributes.
     */
    public function test_render_preserves_data_attributes() {
        // Arrange
        $html = '<div data-id="123" data-type="button" data-spectra-animation="fade">Content</div>';
        
        // Act - pass false to get return value instead of echo
        $result = HtmlSanitizer::render( $html, null, false );
        
        // Assert
        $this->assertStringContainsString( 'data-id="123"', $result );
        $this->assertStringContainsString( 'data-type="button"', $result );
        $this->assertStringContainsString( 'data-spectra-animation="fade"', $result );
    }

    /**
     * Test nested HTML structures.
     */
    public function test_render_handles_nested_html() {
        // Arrange
        $nested_html = '
            <div class="container">
                <header>
                    <h1>Title</h1>
                    <nav>
                        <ul>
                            <li><a href="#home">Home</a></li>
                            <li><a href="#about">About</a></li>
                        </ul>
                    </nav>
                </header>
                <main>
                    <article>
                        <h2>Article Title</h2>
                        <p>Content with <strong>emphasis</strong> and <em>italics</em>.</p>
                    </article>
                </main>
            </div>
        ';
        
        // Act - pass false to get return value instead of echo
        $result = HtmlSanitizer::render( $nested_html, null, false );
        
        // Assert
        $this->assertStringContainsString( '<div class="container">', $result );
        $this->assertStringContainsString( '<nav>', $result );
        $this->assertStringContainsString( '<article>', $result );
        $this->assertStringContainsString( '<strong>emphasis</strong>', $result );
    }

    /**
     * Test empty and null inputs.
     */
    public function test_render_handles_empty_inputs() {
        // Test empty string - pass false to get return value instead of echo
        $this->assertEquals( '', HtmlSanitizer::render( '', null, false ) );
        
        // Test null - skip since render expects string
        // HtmlSanitizer::render() requires string parameter
        
        // Test whitespace - pass false to get return value instead of echo
        $this->assertEquals( '   ', HtmlSanitizer::render( '   ', null, false ) );
    }

    /**
     * Test special characters.
     */
    public function test_render_handles_special_characters() {
        // Arrange
        $special_chars = '<p>Price: $100 & shipping > $10 < $20</p>';
        $expected = '<p>Price: $100 &amp; shipping &gt; $10 &lt; $20</p>';
        
        // Act - pass false to get return value instead of echo
        $result = HtmlSanitizer::render( $special_chars, null, false );
        
        // Assert
        $this->assertEquals( $expected, $result );
    }


    /**
     * Test CSS property extension.
     */
    public function test_extend_safe_style_css_adds_properties() {
        // Arrange
        $default_properties = array( 'color', 'background' );
        
        // Act
        $extended = HtmlSanitizer::extend_safe_style_css( $default_properties );
        
        // Assert
        $this->assertContains( 'color', $extended );
        $this->assertContains( 'background', $extended );
        $this->assertContains( 'transform', $extended );
        $this->assertContains( 'box-shadow', $extended );
        $this->assertContains( 'clip-path', $extended );
    }
}