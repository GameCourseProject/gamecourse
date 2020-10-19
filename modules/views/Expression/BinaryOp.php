<?php
namespace Views\Expression;

abstract class BinaryOp extends Node {
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
