<?php
namespace GameCourse\Views\ExpressionLanguage;

class GenericBinaryOp extends BinaryOp {
    private $op;

    public function __construct($op, $lhs, $rhs) {
        parent::__construct($lhs, $rhs);
        $this->op = $op;
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor) {
        return $visitor->visitGenericBinaryOp($this);
    }
}
