<?php
namespace Views\Expression;

abstract class UnaryOp extends Node {
    public function __construct($rhs) {
        $this->rhs = $rhs;
    }

    public function getRhs() {
        return $this->rhs;
    }
}
