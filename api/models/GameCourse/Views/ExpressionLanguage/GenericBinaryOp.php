<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class GenericBinaryOp extends BinaryOp {
    public function __construct(private $op, $lhs, $rhs, ?Library $library = null) {
        parent::__construct($lhs, $rhs, $library);
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitGenericBinaryOp($this);
    }
}
