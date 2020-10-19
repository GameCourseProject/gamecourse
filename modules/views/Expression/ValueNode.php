<?php
namespace Views\Expression;

class ValueNode extends Node {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function accept($visitor) {
        return $visitor->visitValueNode($this);
    }
}