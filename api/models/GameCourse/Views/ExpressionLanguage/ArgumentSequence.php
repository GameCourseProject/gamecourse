<?php
namespace GameCourse\Views\ExpressionLanguage;

class ArgumentSequence extends Node {
    private $node;
    private $next;

    public function __construct(Node $node, Node $next = null) {
        $this->node = $node;
        $this->next = $next;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getNext(): ?Node
    {
        return $this->next;
    }

    public function accept(Visitor $visitor) {
        return $visitor->visitArgumentSequence($this);
    }
}