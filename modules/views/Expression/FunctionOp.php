<?php
namespace Modules\Views\Expression;

class FunctionOp extends Node {
    public function __construct($name, $args) {
        $this->name = $name;
        $this->args = $args;
    }

    public function getName() {
        return $this->name;
    }

    public function getArgs() {
        return $this->args;
    }

    public function accept($visitor) {
        return $visitor->visitFunctionOp($this);
    }
}
