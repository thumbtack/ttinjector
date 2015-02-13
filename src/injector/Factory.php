<?php

namespace TT\injector;

/**
 * Internal data class
 */
class Factory {
    public $function;
    public $dependencies;

    public function __construct($function, $dependencies) {
        $this->function = $function;
        $this->dependencies = $dependencies;
    }
}
