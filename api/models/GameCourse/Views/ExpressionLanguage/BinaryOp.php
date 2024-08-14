<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

abstract class BinaryOp extends Node {
    public function __construct(private $lhs, private $rhs, ?Library $library = null) {
        $this->setLibrary($library);
    }

    public function getLhs() {
        return $this->lhs;
    }

    public function getRhs() {
        return $this->rhs;
    }
}
