<?php

use TT\injector;

require_once('TestClass.php');

class InjectorTest extends \PHPUnit_Framework_TestCase {
    private function factory($function, $dependencies) {
        return new injector\Factory($function, $dependencies);
    }

    protected function injector($parent=null) {
        return new injector\Injector([
            'value1' => $this->factory(function() { return 1; }, []),
            'value2' => $this->factory(function() { return 'some string'; }, []),
            'factory1' => $this->factory(function() { return 'factory 1 result'; }, []),
            'factory2' => $this->factory(
                function($val1) { return "value 1 is $val1"; },
                ['value1']
            ),
        ], $parent);
    }

    public function test_list_provided_dependencies() {
        $parent_injector = new injector\Injector([
            'a' => $this->factory(function() { return 123; }, []),
            'value1' => $this->factory(function($a) { return 2 * $a; }, ['a']),
        ]);
        $injector = $this->injector($parent_injector);
        $provided = $injector->list_provided_dependencies();
        $expected = ['a', 'value1', 'value2', 'factory1', 'factory2', 'injector'];
        sort($expected);
        sort($provided);
        $this->assertEquals($expected, $provided);
    }

    public function test_has_dependency() {
        $this->assertTrue($this->injector()->has_dependency('value1'));
        $this->assertFalse($this->injector()->has_dependency('something else'));
    }

    /**
     * @expectedException TT\injector\MissingDependencyError
     */
    public function test_throws_exception_for_missing() {
        $this->injector()->get_dependency('something else');
    }

    public function test_get_value() {
        $this->assertEquals(1, $this->injector()->get_dependency('value1'));
        $this->assertEquals('some string', $this->injector()->get_dependency('value2'));
    }

    public function test_get_factory_with_dependencies() {
        $this->assertEquals('value 1 is 1', $this->injector()->get_dependency('factory2'));
    }

    public function test_inject() {
        $result = $this->injector()->inject(function ($a, $b) {
            return "$a - $b";
        }, ['value2', 'factory2']);

        $this->assertEquals('some string - value 1 is 1', $result);
    }

    public function test_gives_parent_dependencies() {
        $parent = new injector\Injector([
            'a' => $this->factory(function() { return 123; }, []),
            'b' => $this->factory(function($a) { return 2 * $a; }, ['a']),
        ]);
        $injector = new injector\Injector([
            'c' => $this->factory(function() { return 1000; }, []),
            'd' => $this->factory(function($c, $b) { return $c + $b; }, ['c', 'b']),
        ], $parent);

        $this->assertEquals(true, $injector->has_dependency('a'));
        $this->assertEquals(1246, $injector->get_dependency('d'));
        $this->assertEquals(123, $injector->get_dependency('a'));
    }

    public function test_shadows_parent_dependencies() {
        $parent = new injector\Injector([
            'a' => $this->factory(function() { return 123; }, []),
            'b' => $this->factory(function($a) { return 10 * $a; }, ['a']),
        ]);
        $child = new injector\Injector([
            'a' => $this->factory(function() { return 512; }, []),
            'c' => $this->factory(function($a) { return 2 * $a; }, ['a']),
        ], $parent);

        // Shadows the parent dependency
        $this->assertEquals(512, $child->get_dependency('a'));

        // Factories in the child use the shadowed version
        $this->assertEquals(1024, $child->get_dependency('c'));

        // But values from the parent use the original
        $this->assertEquals(1230, $child->get_dependency('b'));
    }

    public function test_injects_self() {
        $injector = $this->injector();
        $this->assertTrue($injector->has_dependency('injector'));
        $this->assertEquals($injector, $injector->get_dependency('injector'));
    }

    public function test_can_inject_constructor() {
        $injector = $this->injector();
        $instance = $injector->inject('TestClass', ['value1', 'value2']);

        $this->assertEquals($injector->get_dependency('value1'), $instance->a);
        $this->assertEquals($injector->get_dependency('value2'), $instance->b);
    }
}
