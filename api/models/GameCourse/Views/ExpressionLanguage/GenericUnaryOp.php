<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class GenericUnaryOp extends UnaryOp {

    private $op;

    public function __construct($op, $rhs, ?Library $library = null) {
        parent::__construct($rhs, $library);
        $this->op = $op;
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitGenericUnaryOp($this);
    }
}
