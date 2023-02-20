<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class ParameterNode extends Node {
    private $param;

    public function __construct($param, ?Library $library = null) {
        $this->param = $param;
        $this->setLibrary($library);
    }

    public function getParameter() {
        return $this->param;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitParameterNode($this);
    }
}