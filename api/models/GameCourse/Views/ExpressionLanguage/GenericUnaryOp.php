<?php
namespace GameCourse\Views\ExpressionLanguage;

class GenericUnaryOp extends UnaryOp {
    private $op;

    public function __construct($op, $rhs) {
        parent::__construct($rhs);
        $this->op = $op;
    }

    public function getOp() {
        return $this->op;
    }

    public function accept(Visitor $visitor) {
        return $visitor->visitGenericUnaryOp($this);
    }
}
