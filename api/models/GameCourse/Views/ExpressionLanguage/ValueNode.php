<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class ValueNode extends Node {
    public function __construct(private $value, ?Library $library = null) {
        $this->setLibrary($library);
    }

    public function getValue() {
        return $this->value;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitValueNode($this);
    }
}