<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

abstract class BinaryOp extends Node {

    private $lhs;
    private $rhs;

    public function __construct($lhs, $rhs, ?Library $library = null) {
        $this->lhs = $lhs;
        $this->rhs = $rhs;
        $this->setLibrary($library);
    }

    public function getLhs() {
        return $this->lhs;
    }

    public function getRhs() {
        return $this->rhs;
    }
}
