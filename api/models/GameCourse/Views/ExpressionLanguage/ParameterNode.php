<?php
namespace GameCourse\Views\ExpressionLanguage;

class ParameterNode extends Node {
    private $param;
    private $key; // param may be an array with key

    public function __construct($param, $key = null) {
        $this->param = $param;
        $this->key = $key;
    }

    public function getParameter() {
        return $this->param;
    }
    
    public function getKey() {
        return $this->key;
    }

    public function accept(Visitor $visitor) {
        return $visitor->visitParameterNode($this);
    }
}