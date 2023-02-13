<?php
namespace GameCourse\Views\ExpressionLanguage;

class ParameterNode extends Node {
    private $param;

    public function __construct($param) {
        $this->param = $param;
    }

    public function getParameter() {
        return $this->param;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitParameterNode($this);
    }
}