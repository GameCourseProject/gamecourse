<?php
namespace GameCourse\Views\Expression;

class ArgumentSequence extends Node {
    private $node;
    private $next;

    public function __construct($node, $next = null) {
        $this->node = $node;
        $this->next = $next;
    }

    public function getNode() {
        return $this->node;
    }

    public function getNext() {
        return $this->next;
    }

    public function accept($visitor) {
        return $visitor->visitArgumentSequence($this);
    }
}