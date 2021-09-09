<?php
namespace Modules\Views\Expression;

class GenericBinaryOp extends BinaryOp {
    private $op;

    public function __construct($op, $lhs, $rhs) {
        parent::__construct($lhs, $rhs);
        $this->op = $op;
    }

    public function getOp() {
        return $this->op;
    }

    public function accept($visitor) {
        return $visitor->visitGenericBinaryOp($this);
    }
}
