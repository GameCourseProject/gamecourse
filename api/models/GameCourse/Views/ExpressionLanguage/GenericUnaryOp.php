<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class GenericUnaryOp extends UnaryOp {
    public function __construct(private $op, $rhs, ?Library $library = null) {
        parent::__construct($rhs, $library);
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitGenericUnaryOp($this);
    }
}
