<?php
namespace GameCourse\Views\ExpressionLanguage;

abstract class UnaryOp extends Node {
    private $rhs;

    public function __construct($rhs) {
        $this->rhs = $rhs;
    }

    public function getRhs() {
        return $this->rhs;
    }
}
