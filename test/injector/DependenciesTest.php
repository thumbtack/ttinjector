<?php

use TT\injector;

require_once('TestClass.php');

class DependenciesTest extends \PHPUnit_Framework_TestCase {
    public function test_basic_functionality() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value('my value', 123);
        $dependencies->register_factory(
            'my factory',
            [],
            function() { return 1; }
        );
        $dependencies->register_factory(
            'my dependant factory',
            ['my value'],
            function($my_value) { return 2 * $my_value; }
        );

        $dependencies->register_factory(
            'class based factory',
            ['my value', 'my factory'],
            'TestClass'
        );

        $injector = $dependencies->build_injector();

        $this->assertEquals(123, $injector->get_dependency('class based factory')->a);
    }

    public function test_allows_requesting_injector() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value('my value', 123);
        $dependencies->register_factory(
            'my factory',
            ['injector'],
            function($inj) { return 2 * $inj->get_dependency('my value'); }
        );

        $injector = $dependencies->build_injector();
        $this->assertEquals(246, $injector->get_dependency('my factory'));
    }

    /**
     * @expectedException TT\injector\BadNameError
     */
    public function test_requires_not_null_names() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value(null, 123);
    }

    /**
     * @expectedException TT\injector\BadNameError
     */
    public function test_requires_string_names() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value(5, 123);
    }

    /**
     * @expectedException TT\injector\DuplicateNameError
     */
    public function test_rejects_duplicate_names() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value('name', 1);
        $dependencies->register_value('name', 2);
    }

    /**
     * @expectedException TT\injector\BadNameError
     */
    public function test_rejects_reserved_names() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value(injector\Dependencies::INJECTOR_NAME, 1);
    }

    public function test_creates_injector() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value('v1', 123);
        $injector = $dependencies->build_injector();
        $this->assertTrue($injector->has_dependency('v1'));
    }

    public function test_adding_alias() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_value('v1', 123);
        $dependencies->alias('v1', 'value-1');
        $injector = $dependencies->build_injector();
        $this->assertEquals(123, $injector->get_dependency('value-1'));
    }

    /**
     * @expectedException TT\injector\MissingDependencyError
     */
    public function test_catches_missing_dependency() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_factory('f1', ['f2'], function($f2) { return 1; });
        $dependencies->build_injector();
    }

    /**
     * @expectedException TT\injector\CircularDependencyError
     */
    public function test_catches_circular_dependency() {
        $dependencies = new injector\Dependencies();
        $dependencies->register_factory('f1', ['f2'], function($f2) { return 1; });
        $dependencies->register_factory('f2', ['f3'], function($f3) { return 2; });
        $dependencies->register_factory('f3', ['f1'], function($f1) { return 3; });
        $dependencies->build_injector();
    }

    public function test_uses_dependencies_from_parent_injector() {
        $parent_injector = new injector\Injector([
            'a' => new injector\Factory(function() { return 123; }, []),
        ]);
        $dependencies = injector\Dependencies::DrawFrom($parent_injector);
        $dependencies->register_factory('b', ['a'], function($a) { return 2 * $a; });
        $injector = $dependencies->build_injector();
        $this->assertEquals(246, $injector->get_dependency('b'));
    }
}
