<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class ValueNode extends Node {
    private $value;
    private $library;

    public function __construct($value, ?Library $library = null) {
        $this->value = $value;
        $this->library = $library;
    }

    public function getValue() {
        return $this->value;
    }

    public function getLibrary(): Library {
        return $this->library;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitValueNode($this);
    }
}