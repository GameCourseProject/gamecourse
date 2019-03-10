<?php
namespace Modules\Views\Expression;

class ContextSequence extends Node {
    private $attribute;
    private $node; 
    private $next;

    public function __construct($attribute,$node, $next = null) {
        $this->attribute=$attribute;
        $this->node = $node;
        $this->next = $next;
    }

    public function getAttribute() {
        return $this->attribute;
    }
    
    public function getNode() {
        return $this->node;
    }

    public function getNext() {
        return $this->next;
    }

    public function accept($visitor, $valueContinuation = null, $dbPath=false) {
        return $visitor->visitContextSequence($this, $valueContinuation, $dbPath);
    }
}