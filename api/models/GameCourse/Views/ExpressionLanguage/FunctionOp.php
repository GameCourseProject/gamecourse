<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Core\Core;

class FunctionOp extends Node {

    private $name;
    private $args;
    private $context;


    public function __construct(string $name, ?ArgumentSequence $args, ?string $libraryId, Node $context = null) {
        $this->name = $name;
        $this->args = $args;
        $this->context = $context;
        $this->setLibrary($libraryId ? Core::dictionary()->getLibraryById($libraryId) : null);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgs(): ?ArgumentSequence
    {
        return $this->args;
    }

    public function getContext(): ?Node
    {
        return $this->context;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitFunctionOp($this);
    }
}
