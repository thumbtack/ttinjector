<?php

use TT\injector;

class GraphTest extends \PHPUnit_Framework_TestCase {
    private function assert_finds_missing_dependencies($expected, $graph) {
        $result = (new injector\Graph($graph))->list_missing_dependencies();
        $this->assertEquals($expected, $result);
    }

    public function test_approves_empty_graph() {
        $this->assert_finds_missing_dependencies([], []);
    }

    public function test_approves_met_dependencies() {
        $this->assert_finds_missing_dependencies([], [
            'a' => ['b', 'c'],
            'b' => ['c'],
            'c' => [],
        ]);
    }

    public function test_missing_dependencies() {
        $this->assert_finds_missing_dependencies(['c'], [
            'a' => ['b', 'c'],
            'b' => ['c'],
        ]);
    }

    private function assert_finds_circular_dependencies($expected, $graph) {
        $result = (new injector\Graph($graph))->list_dependency_cycle();
        $this->assertEquals($expected, $result);
    }

    public function test_no_cycles_in_empty_graph() {
        $this->assert_finds_circular_dependencies([], []);
    }

    public function test_finds_no_cycles() {
        $this->assert_finds_circular_dependencies([], [
            'a' => ['b', 'c', 'd'],
            'b' => ['c', 'd'],
            'c' => ['d'],
            'd' => [],
        ]);
    }

    public function test_finds_cycle() {
        $this->assert_finds_circular_dependencies(['a', 'b', 'c'], [
            'a' => ['b', 'd'],
            'b' => ['c'],
            'c' => ['a'],
            'd' => ['e'],
            'e' => [],
        ]);
    }
}
