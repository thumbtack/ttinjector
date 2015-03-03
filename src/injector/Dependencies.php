<?php

namespace TT\injector;

class Dependencies {
    const INJECTOR_NAME = 'injector';

    private $factories;
    private $parent_injector;

    /**
     * @param Injector $parent_injector (optional) another injector to draw dependencies from
     */
    public function __construct(Injector $parent_injector=null) {
        $this->factories = [];
        $this->parent_injector = $parent_injector;
    }

    /**
     * @param Injector $parent_injector another injector to draw dependencies from
     * @return Dependencies
     */
    public static function DrawFrom(Injector $parent_injector) {
        return new static($parent_injector);
    }

    /**
     * Adds a value for injection.
     *
     * @param string $name name used to get dependency
     * @param mixed $value value to be injected
     */
    public function register_value($name, $value) {
        return $this->register_factory($name, [], function() use ($value) {
            return $value;
        });
    }

    /**
     * Adds a function that produces values for injection. The function will only be called if
     * it is injected, and even then it will only be called once.
     *
     * @param string $name name used to get dependency
     * @param string[] $dependencies (optional) names of dependencies to inject when calling the
     *     factory function
     * @param mixed $factory function or class used to create the value to be injected
     */
    public function register_factory($name, array $dependencies, $factory) {
        $this->check_is_valid_dependency_name($name);
        $this->factories[$name] = new Factory($factory, $dependencies);
    }

    /**
     * @param string $dependency_name Name of existing dependency
     * @param string $alias_name Name of the alias for that dependency
     */
    public function alias($dependency_name, $alias_name) {
        $this->register_factory($alias_name, [$dependency_name], function ($x) { return $x; });
    }

    /**
     * Returns an instance of Injector based on the current state of the dependencies.
     *
     * If there are any problems (missing dependencies or dependency cycles), this will
     * throw an exception.
     *
     * @return Injector
     */
    public function build_injector() {
        $this->check_graph();
        return new Injector($this->factories, $this->parent_injector);
    }

    /**
     * @param string $name Dependency name to check
     * @throws BadNameError if the name is not a valid name
     * @throws DuplicateNameError if the name is already in use
     */
    private function check_is_valid_dependency_name($name) {
        if (!$name || !is_string($name)) {
            throw new BadNameError('Bad value for a name: ' . var_export($name, true));
        }
        if ($name == self::INJECTOR_NAME) {
            throw new BadNameError('The name "' . self::INJECTOR_NAME . '" is reserved');
        }
        if (array_key_exists($name, $this->factories)) {
            throw new DuplicateNameError("Duplicate name: $name");
        }
    }

    /**
     * Makes sure that the graph has no cyclic dependencies or missing dependencies.
     */
    private function check_graph() {
        $dependency_graph = $this->make_dependency_graph();

        $missing_dependencies = $dependency_graph->list_missing_dependencies();
        if ($missing_dependencies) {
            throw new MissingDependencyError(
                'Missing dependencies: ' . implode(', ', $missing_dependencies)
            );
        }

        $cyclic_dependencies = $dependency_graph->list_dependency_cycles();
        if ($cyclic_dependencies) {
            throw new CircularDependencyError(
                'Circular dependencies. Cannot resolve dependency tree for: '
                . implode(', ', $cyclic_dependencies)
            );
        }
    }

    /**
     * @return Graph
     */
    private function make_dependency_graph() {
        $dependency_graph = [];
        foreach ($this->factories as $name => $factory) {
            $dependency_graph[$name] = $factory->dependencies;
        }

        if ($this->parent_injector) {
            foreach ($this->parent_injector->list_provided_dependencies() as $parent_dep) {
                if (!array_key_exists($parent_dep, $dependency_graph)) {
                    $dependency_graph[$parent_dep] = [];
                }
            }
        }
        return new Graph($dependency_graph);
    }
}
