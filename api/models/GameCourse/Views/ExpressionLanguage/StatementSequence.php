<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class StatementSequence extends Node {

    private $node;
    private $next;

    public function __construct(Node $node, Node $next = null, ?Library $library = null) {
        $this->node = $node;
        $this->next = $next;
        $this->setLibrary($library);
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getNext(): ?Node
    {
        return $this->next;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitStatementSequence($this);
    }
}