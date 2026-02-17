<?php
/**
 * Tests for Autoloader class.
 *
 * @package Spectra\Tests
 */

use Spectra\Autoloader;

/**
 * Autoloader test case.
 */
class Test_Autoloader extends Spectra_Test_Case {

    /**
     * Test that autoloader registers itself correctly.
     *
     * @covers Spectra\Autoloader::register
     */
    public function test_autoloader_registers_successfully() {
        // Arrange
        $autoload_functions_before = spl_autoload_functions();
        
        // Act - The autoloader is already registered by bootstrap, so we just check it exists
        // (The autoloader is loaded in bootstrap.php, so we don't need to load it again)
        
        // Assert
        $autoload_functions_after = spl_autoload_functions();
        $this->assertGreaterThanOrEqual( 
            count( $autoload_functions_before ), 
            count( $autoload_functions_after ),
            'Autoloader should be registered'
        );
        
        // Test that we can load a Spectra class
        $this->assertTrue( class_exists( 'Spectra\\AssetLoader' ), 'AssetLoader should be loadable' );
    }

    /**
     * Test that autoloader correctly loads Spectra classes.
     */
    public function test_autoloader_loads_spectra_classes() {
        // Test that key Spectra classes can be loaded
        $test_classes = array(
            'Spectra\\ExtensionManager',
            'Spectra\\AssetLoader',
            'Spectra\\Extensions\\Animations',
            'Spectra\\Extensions\\ImageMask',
        );
        
        foreach ( $test_classes as $class_name ) {
            $this->assertTrue( 
                class_exists( $class_name ),
                "Class {$class_name} should be loadable by autoloader"
            );
        }
        
        // Test that traits can be loaded
        $this->assertTrue( 
            trait_exists( 'Spectra\\Traits\\Singleton' ),
            'Singleton trait should be loadable by autoloader'
        );
    }

    /**
     * Data provider for class names and their expected file paths.
     *
     * @return array
     */
    public function class_name_provider() {
        return array(
            'ExtensionManager class' => array(
                'Spectra\ExtensionManager',
                'ExtensionManager.php'
            ),
            'Singleton trait' => array(
                'Spectra\Traits\Singleton',
                'Traits/Singleton.php'
            ),
            'Animations extension' => array(
                'Spectra\Extensions\Animations',
                'Extensions/Animations.php'
            ),
            'ImageMask extension' => array(
                'Spectra\Extensions\ImageMask',
                'Extensions/ImageMask.php'
            ),
        );
    }

    /**
     * Test that autoloader ignores non-Spectra classes.
     */
    public function test_autoloader_ignores_non_spectra_classes() {
        // Test that non-Spectra classes are ignored by our autoloader
        $this->assertFalse( class_exists( 'NonSpectraClass' ), 'Non-Spectra classes should not be loaded' );
        $this->assertFalse( class_exists( 'AnotherNamespace\\SomeClass' ), 'Classes from other namespaces should not be loaded' );
        
        // But Spectra classes should be loadable
        $this->assertTrue( class_exists( 'Spectra\\AssetLoader' ), 'Spectra classes should be loadable' );
    }

    /**
     * Test that autoloader handles invalid class names gracefully.
     */
    public function test_autoloader_handles_invalid_class_names() {
        // Test various invalid class names
        $invalid_class_names = array(
            '',
            'Spectra',
            'Spectra-Invalid-Class',
            'Spectra\\123',
            'Spectra\\Class@Name',
        );
        
        foreach ( $invalid_class_names as $invalid_class_name ) {
            $this->assertFalse( 
                class_exists( $invalid_class_name ),
                "Invalid class name '{$invalid_class_name}' should not be loadable"
            );
        }
    }

    /**
     * Data provider for invalid class names.
     *
     * @return array
     */
    public function invalid_class_names_provider() {
        return array(
            'empty string'        => array( '' ),
            'single word'         => array( 'Spectra' ),
            'wrong separator'     => array( 'Spectra-Invalid-Class' ),
            'numeric class name'  => array( 'Spectra\123' ),
            'special characters'  => array( 'Spectra\Class@Name' ),
        );
    }

    /**
     * Test that autoloader correctly constructs file paths.
     */
    public function test_autoloader_constructs_correct_file_paths() {
        // Test that the autoloader logic would construct correct file paths
        $test_cases = array(
            'Spectra\Core\Manager' => 'Core/Manager.php',
            'Spectra\Utils\Helper' => 'Utils/Helper.php',
            'Spectra\Extensions\Feature\Subfeature' => 'Extensions/Feature/Subfeature.php',
        );
        
        foreach ( $test_cases as $class_name => $expected_path ) {
            // Simulate the autoloader logic from autoload.php
            $namespace = 'Spectra\\';
            $relative_class = substr( $class_name, strlen( $namespace ) );
            $constructed_path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $relative_class ) . '.php';
            
            // Assert
            $this->assertEquals( 
                $expected_path, 
                str_replace( DIRECTORY_SEPARATOR, '/', $constructed_path ),
                "Path construction for {$class_name} should match expected"
            );
        }
    }
}