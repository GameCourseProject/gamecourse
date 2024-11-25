<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class GenericBinaryOp extends BinaryOp {

    private $op;

    public function __construct($op, $lhs, $rhs, ?Library $library = null) {
        parent::__construct($lhs, $rhs, $library);
        $this->op = $op;
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitGenericBinaryOp($this);
    }
}
