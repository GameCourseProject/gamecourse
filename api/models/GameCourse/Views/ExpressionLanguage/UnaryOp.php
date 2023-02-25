<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

abstract class UnaryOp extends Node {
    private $rhs;

    public function __construct($rhs, ?Library $library = null) {
        $this->rhs = $rhs;
        $this->setLibrary($library);
    }

    public function getRhs() {
        return $this->rhs;
    }
}
