<?php
namespace GameCourse\Views\ExpressionLanguage;

abstract class BinaryOp extends Node {
    private $lhs;
    private $rhs;

    public function __construct($lhs, $rhs) {
        $this->lhs = $lhs;
        $this->rhs = $rhs;
    }

    public function getLhs() {
        return $this->lhs;
    }

    public function getRhs() {
        return $this->rhs;
    }
}
