<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

abstract class UnaryOp extends Node {
    public function __construct(private $rhs, ?Library $library = null) {
        $this->setLibrary($library);
    }

    public function getRhs() {
        return $this->rhs;
    }
}
