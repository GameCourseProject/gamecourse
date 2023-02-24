<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

abstract class Node {
    private $library;

    public function setLibrary(?Library $library = null) {
        $this->library = $library;
    }

    public function getLibrary(): ?Library {
        return $this->library;
    }

    public abstract function accept(Visitor $visitor): ValueNode;
}