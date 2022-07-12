<?php
namespace GameCourse\Views\ExpressionLanguage;

class ValueNode extends Node {
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function accept(Visitor $visitor) {
        return $visitor->visitValueNode($this);
    }
}