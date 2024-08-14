<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class ParameterNode extends Node {
    public function __construct(private string $param, ?Library $library = null) {
        $this->setLibrary($library);
    }

    public function getParameter(): string {
        return $this->param;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitParameterNode($this);
    }
}