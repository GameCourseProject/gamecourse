<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class ValueNode extends Node {
    private $value;

    public function __construct($value, ?Library $library = null) {
        $this->value = $value;
        $this->setLibrary($library);
    }

    public function getValue() {
        return $this->value;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitValueNode($this);
    }
}