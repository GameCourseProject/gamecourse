<?php
namespace Modules\Views\Expression;

class ParameterNode extends Node {
    private $param;

    public function __construct($param) {
        $this->param = $param;
    }

    public function getParameter() {
        return $this->param;
    }

    public function accept($visitor) {
        return $visitor->visitParameterNode($this);
    }
}