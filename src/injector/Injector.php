<?php

namespace TT\injector;

/**
 * Injects dependencies into an application.
 *
 * Functions to get dependency values are never called more than once, regardless of
 * how many times the values are used.
 */
class Injector {
    private $value_cache;
    private $factories;
    private $parent_injector;

    /**
     * Create an injector with the set of factories.
     *
     * The correct way to construct an instance is with Dependencies->build_injector().
     *
     * @param array $factories a mapping from names to Factory objects
     * @param Injector $parent_injector (optional) an injector to draw aditional dependencies from.
     */
    public function __construct($factories, Injector $parent_injector=null) {
        $this->factories = $factories;
        $this->value_cache = [];
        $this->parent_injector = $parent_injector;

        $this->factories[Dependencies::INJECTOR_NAME] = new Factory(
            function() { return $this; },
            []
        );
    }

    /**
     * @return string[] names of the dependencies this can provide
     */
    public function list_provided_dependencies() {
        $provided_set = [];
        foreach ($this->factories as $name => $unused) {
            $provided_set[$name] = true;
        }

        $parent_provided = $this->parent_injector
            ? $this->parent_injector->list_provided_dependencies()
            : [];
        foreach ($parent_provided as $name) {
            $provided_set[$name] = true;
        }

        return array_keys($provided_set);
    }

    /**
     * Calls the function with the value of the dependencies as the arguments.
     *
     *
     * Examples
     *
     * <code>
     * <?php
     *    $result = $injector->inject(
     *        function($settings) {
     *            return $settings['connection_url'];
     *        },
     *        ['settings']
     *    );
     * </code>
     *
     * @param mixed $callable function to be called or name of class to be constructed
     * @param string[] $dependencies an array of dependency names
     * @return mixed The result of calling the function.
     */
    public function inject($callable, array $dependencies) {
        $args = [];
        foreach ($dependencies as $dependency) {
            $args[] = $this->get_dependency($dependency);
        }

        if (is_string($callable) && class_exists($callable)) {
            $reflection = new \ReflectionClass($callable);
            return $reflection->newInstanceArgs($args);
        } else {
            return call_user_func_array($callable, $args);
        }
    }

    /**
     * @param string $name Dependency name
     * @return true if the injector provides a dependency.
     */
    public function has_dependency($name) {
        return $this->local_has_dependency($name) || $this->parent_has_dependency($name);
    }

    /**
     * @param string $name Dependency name
     * @return mixed The value of a dependency.
     * @throws MissingDependencyError
     */
    public function get_dependency($name) {
        if ($this->local_has_dependency($name)) {
            if (!array_key_exists($name, $this->value_cache)) {
                $factory = $this->factories[$name];
                $this->value_cache[$name] = $this->inject(
                    $factory->function, $factory->dependencies
                );
            }

            return $this->value_cache[$name];
        } else if ($this->parent_has_dependency($name)) {
            return $this->parent_injector->get_dependency($name);
        } else {
            throw new MissingDependencyError("Missing dependency name: $name");
        }
    }

    /**
     * @param string $name Dependency name
     * @return bool True if there is a parent and it has the dependency
     */
    private function parent_has_dependency($name) {
        return $this->parent_injector && $this->parent_injector->has_dependency($name);
    }

    /**
     * @param string $name Dependency name
     * @return bool True if this has a dependency (regardless of what the parent might have)
     */
    private function local_has_dependency($name) {
        return array_key_exists($name, $this->factories);
    }
}
