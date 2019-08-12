<?php
namespace Modules\Views\Expression;

class FunctionOp extends Node {
    public function __construct($name, $args,$lib=null,$context=null) {
        $this->lib = $lib;
        $this->context = $context;
        $this->name = $name;
        $this->args = $args;
    }

    public function getName() {
        return $this->name;
    }
    public function getArgs() {
        return $this->args;
    }
    public function getLib() {
        return $this->lib;
    }
    public function setLib($lib) {
        return $this->lib=$lib;
    }
    public function getContext() {
        return $this->context;
    }

    public function accept($visitor) {
        return $visitor->visitFunctionOp($this);
    }
}
