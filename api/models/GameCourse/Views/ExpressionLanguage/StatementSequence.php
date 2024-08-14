<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Views\Dictionary\Library;

class StatementSequence extends Node {
    public function __construct(private Node $node, private ?\GameCourse\Views\ExpressionLanguage\Node $next = null, ?Library $library = null) {
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