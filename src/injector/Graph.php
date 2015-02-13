<?php

namespace TT\injector;

class Graph {
    private $dependency_graph;

    public function __construct(array $dependency_graph) {
        $this->dependency_graph = $dependency_graph;
    }

    /**
     * @return string[] names of any missing dependencies
     */
    public function list_missing_dependencies() {
        $missing = [];
        foreach ($this->dependency_graph as $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!array_key_exists($dependency, $this->dependency_graph)) {
                    $missing[$dependency] = true;
                }
            }
        }

        return array_keys($missing);
    }

    /**
     * @return string[] names of any dependencies involved in dependency cycle[s] (or that depend
     *     upon those in the cycle)
     */
    public function list_dependency_cycle() {
        $dep_counts = $this->dependency_counts();
        $depends_on = $this->reverse_graph();

        $deps_met_queue = new \SplQueue();
        foreach ($dep_counts as $dependency => $count) {
            if ($count === 0) {
                $deps_met_queue->enqueue($dependency);
            }
        }

        // Iteratively resolve dependencies
        $num_removed = 0;
        while (!$deps_met_queue->isEmpty()) {
            $name = $deps_met_queue->dequeue();
            $num_removed++;

            if (!array_key_exists($name, $depends_on)) {
                continue;
            }

            foreach ($depends_on[$name] as $dependant) {
                $dep_counts[$dependant]--;
                if ($dep_counts[$dependant] === 0) {
                    $deps_met_queue->enqueue($dependant);
                }
            }
        }

        // Find the dependencies that couldn't be resolved
        $depends_on_cycle = [];
        foreach ($dep_counts as $dependency => $count) {
            if ($count > 0) {
                $depends_on_cycle[] = $dependency;
            }
        }
        return $depends_on_cycle;
    }

    private function dependency_counts() {
        $counts = [];
        foreach ($this->dependency_graph as $name => $dependencies) {
            $counts[$name] = count($dependencies);
        }
        return $counts;
    }

    private function reverse_graph() {
        $depends_on = [];
        foreach ($this->dependency_graph as $name => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!array_key_exists($dependency, $depends_on)) {
                    $depends_on[$dependency] = [];
                }

                $depends_on[$dependency][] = $name;
            }
        }
        return $depends_on;
    }
}
