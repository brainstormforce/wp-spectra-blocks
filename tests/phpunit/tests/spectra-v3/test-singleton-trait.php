<?php
/**
 * Tests for Singleton trait.
 *
 * @package Spectra\Tests
 */

use Spectra\Traits\Singleton;

/**
 * Mock class for testing Singleton trait.
 */
class Mock_Singleton_Class {
    use Singleton;
    
    public $test_property = 'initial_value';
    
    public function set_property( $value ) {
        $this->test_property = $value;
    }
}

/**
 * Another mock class for testing Singleton isolation.
 */
class Another_Mock_Singleton_Class {
    use Singleton;
    
    public $another_property = 'another_value';
}

/**
 * Singleton trait test case.
 */
class Test_Singleton_Trait extends Spectra_Test_Case {

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Reset singleton instances
        $this->reset_singleton( 'Mock_Singleton_Class' );
        $this->reset_singleton( 'Another_Mock_Singleton_Class' );
    }

    /**
     * Test that instance() returns the same object.
     */
    public function test_instance_returns_same_object() {
        $instance1 = Mock_Singleton_Class::instance();
        $instance2 = Mock_Singleton_Class::instance();
        
        $this->assertSame( $instance1, $instance2, 'instance() should return the same object' );
    }

    /**
     * Test that singleton maintains state.
     */
    public function test_singleton_maintains_state() {
        $instance1 = Mock_Singleton_Class::instance();
        $instance1->set_property( 'modified_value' );
        
        $instance2 = Mock_Singleton_Class::instance();
        
        $this->assertEquals( 'modified_value', $instance2->test_property, 'Singleton should maintain state across calls' );
    }

    /**
     * Test that different classes have different instances.
     */
    public function test_different_classes_have_different_instances() {
        $mock_instance = Mock_Singleton_Class::instance();
        $another_instance = Another_Mock_Singleton_Class::instance();
        
        $this->assertNotSame( $mock_instance, $another_instance, 'Different classes should have different singleton instances' );
        $this->assertInstanceOf( 'Mock_Singleton_Class', $mock_instance );
        $this->assertInstanceOf( 'Another_Mock_Singleton_Class', $another_instance );
    }

    /**
     * Test that constructor is protected.
     */
    public function test_constructor_is_protected() {
        $reflection = new ReflectionClass( 'Mock_Singleton_Class' );
        $constructor = $reflection->getConstructor();
        
        $this->assertTrue( $constructor->isProtected(), 'Constructor should be protected' );
    }

    /**
     * Test that cloning is prevented.
     */
    public function test_clone_is_prevented() {
        $instance = Mock_Singleton_Class::instance();
        
        $this->expectException( Error::class );
        $this->expectExceptionMessage( 'Cannot clone singleton' );
        $clone = clone $instance;
    }

    /**
     * Test that unserialization is prevented.
     */
    public function test_wakeup_is_prevented() {
        $instance = Mock_Singleton_Class::instance();
        $serialized = serialize( $instance );
        
        $this->expectException( Error::class );
        $this->expectExceptionMessage( 'Cannot unserialize singleton' );
        $unserialized = unserialize( $serialized );
    }

    /**
     * Test reset functionality.
     */
    public function test_reset_singleton() {
        // Get instance and modify it
        $instance1 = Mock_Singleton_Class::instance();
        $instance1->set_property( 'modified_value' );
        
        // Reset the singleton
        $this->reset_singleton( 'Mock_Singleton_Class' );
        
        // Get new instance
        $instance2 = Mock_Singleton_Class::instance();
        
        // Check that it's a new instance with initial values
        $this->assertNotSame( $instance1, $instance2, 'After reset, instance() should return a new object' );
        $this->assertEquals( 'initial_value', $instance2->test_property, 'New instance should have initial values' );
    }
}