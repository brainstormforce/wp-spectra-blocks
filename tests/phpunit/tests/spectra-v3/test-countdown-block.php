<?php
/**
 * Tests for Countdown block class.
 *
 * @package Spectra\Tests
 */

use Spectra\Blocks\Countdown;

/**
 * Countdown block test case.
 */
class Test_Countdown_Block extends Spectra_Test_Case {

    /**
     * The Countdown instance.
     *
     * @var Countdown
     */
    private $countdown;

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Get Countdown instance
        $this->countdown = Countdown::instance();
    }

    /**
     * Test singleton implementation.
     */
    public function test_singleton_instance() {
        $instance1 = Countdown::instance();
        $instance2 = Countdown::instance();
        
        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test separator modification for countdown block.
     */
    public function test_modify_separator_with_show_separator_true() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(
                'showSeparator' => true,
                'separatorType' => ':',
                'block_id' => 'test-123',
            ),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertEquals( $parsed_block['blockName'], $result['blockName'] );
        $this->assertEquals( $parsed_block['attrs'], $result['attrs'] );
        $this->assertTrue( $result['attrs']['showSeparator'] );
    }

    /**
     * Test separator modification when showSeparator is false.
     */
    public function test_modify_separator_with_show_separator_false() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(
                'showSeparator' => false,
                'separatorType' => ':',
                'block_id' => 'test-456',
            ),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertFalse( $result['attrs']['showSeparator'] );
        $this->assertEquals( '', $result['attrs']['separatorType'] );
    }

    /**
     * Test separator modification with missing showSeparator attribute.
     */
    public function test_modify_separator_with_missing_show_separator() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(
                'separatorType' => ':',
                'block_id' => 'test-789',
            ),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertEquals( $parsed_block, $result );
        $this->assertEquals( ':', $result['attrs']['separatorType'] );
    }

    /**
     * Test non-countdown block passes through unchanged.
     */
    public function test_modify_separator_with_different_block() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/button',
            'attrs' => array(
                'showSeparator' => false,
                'separatorType' => ':',
            ),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertEquals( $parsed_block, $result );
        $this->assertEquals( ':', $result['attrs']['separatorType'] ); // Unchanged
    }

    /**
     * Test various separator types.
     */
    public function test_different_separator_types() {
        $test_cases = $this->separator_types_provider();
        
        foreach ( $test_cases as $name => $case ) {
            list( $separator_type, $show_separator, $expected_separator ) = $case;
            
            // Arrange
            $parsed_block = array(
                'blockName' => 'spectra/countdown',
                'attrs' => array(
                    'showSeparator' => $show_separator,
                    'separatorType' => $separator_type,
                    'block_id' => 'test-separator',
                ),
            );
            
            // Act
            $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
            
            // Assert
            $this->assertEquals( $expected_separator, $result['attrs']['separatorType'], "Failed for case: {$name}" );
        }
    }

    /**
     * Data provider for separator types.
     *
     * @return array
     */
    public function separator_types_provider() {
        return array(
            'colon with show true'     => array( ':', true, ':' ),
            'colon with show false'    => array( ':', false, '' ),
            'slash with show true'     => array( '/', true, '/' ),
            'slash with show false'    => array( '/', false, '' ),
            'dash with show true'      => array( '-', true, '-' ),
            'dash with show false'     => array( '-', false, '' ),
            'empty with show true'     => array( '', true, '' ),
            'empty with show false'    => array( '', false, '' ),
            'custom with show true'    => array( ' | ', true, ' | ' ),
            'custom with show false'   => array( ' | ', false, '' ),
        );
    }

    /**
     * Test filter hook registration.
     */
    public function test_init_registers_filter() {
        // Reset instance and init
        $this->reset_singleton( '\Spectra\Blocks\Countdown' );
        $countdown = Countdown::instance();
        $countdown->init();
        
        // Check filter is registered
        $this->assert_hook_added( 
            'render_block_data', 
            $countdown, 
            'modify_the_separator_countdown_block_data',
            10
        );
    }

    /**
     * Test block with empty attributes array.
     */
    public function test_modify_separator_with_empty_attrs() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertEquals( $parsed_block, $result );
        $this->assertIsArray( $result['attrs'] );
    }

    /**
     * Test block with null attributes.
     */
    public function test_modify_separator_with_null_attrs() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => null,
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert
        $this->assertEquals( $parsed_block, $result );
    }

    /**
     * Test edge cases for separator values.
     */
    public function test_separator_edge_cases() {
        $edge_cases = array(
            'null separator' => null,
            'boolean true' => true,
            'boolean false' => false,
            'number' => 123,
            'array' => array( ':' ),
            'object' => (object) array( 'sep' => ':' ),
        );
        
        foreach ( $edge_cases as $case_name => $separator_value ) {
            $parsed_block = array(
                'blockName' => 'spectra/countdown',
                'attrs' => array(
                    'showSeparator' => false,
                    'separatorType' => $separator_value,
                ),
            );
            
            $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
            
            // When showSeparator is false, separatorType should be empty string
            $this->assertEquals( 
                '', 
                $result['attrs']['separatorType'],
                "Failed for case: {$case_name}"
            );
        }
    }

    /**
     * Test performance with multiple calls.
     */
    public function test_performance_multiple_modifications() {
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(
                'showSeparator' => false,
                'separatorType' => ':',
                'block_id' => 'perf-test',
            ),
        );
        
        $iterations = 1000;
        $start_time = microtime( true );
        
        for ( $i = 0; $i < $iterations; $i++ ) {
            $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        }
        
        $end_time = microtime( true );
        $total_time = $end_time - $start_time;
        
        // Should process 1000 blocks in less than 0.1 seconds
        $this->assertLessThan( 
            0.1, 
            $total_time,
            'Processing should be fast'
        );
    }

    /**
     * Test nested block structure.
     */
    public function test_nested_countdown_blocks() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'core/group',
            'innerBlocks' => array(
                array(
                    'blockName' => 'spectra/countdown',
                    'attrs' => array(
                        'showSeparator' => false,
                        'separatorType' => ':',
                    ),
                ),
            ),
        );
        
        // Act - The filter would process inner blocks separately
        $inner_result = $this->countdown->modify_the_separator_countdown_block_data( 
            $parsed_block['innerBlocks'][0] 
        );
        
        // Assert
        $this->assertEquals( '', $inner_result['attrs']['separatorType'] );
    }

    /**
     * Test preservation of other attributes.
     */
    public function test_other_attributes_preserved() {
        // Arrange
        $parsed_block = array(
            'blockName' => 'spectra/countdown',
            'attrs' => array(
                'showSeparator' => false,
                'separatorType' => ':',
                'block_id' => 'test-preserve',
                'endDate' => '2024-12-31',
                'textColor' => '#000000',
                'backgroundColor' => '#ffffff',
                'fontSize' => 18,
                'customAttribute' => 'custom-value',
            ),
        );
        
        // Act
        $result = $this->countdown->modify_the_separator_countdown_block_data( $parsed_block );
        
        // Assert - All attributes except separatorType should be preserved
        $this->assertEquals( '', $result['attrs']['separatorType'] );
        $this->assertEquals( 'test-preserve', $result['attrs']['block_id'] );
        $this->assertEquals( '2024-12-31', $result['attrs']['endDate'] );
        $this->assertEquals( '#000000', $result['attrs']['textColor'] );
        $this->assertEquals( '#ffffff', $result['attrs']['backgroundColor'] );
        $this->assertEquals( 18, $result['attrs']['fontSize'] );
        $this->assertEquals( 'custom-value', $result['attrs']['customAttribute'] );
    }
}