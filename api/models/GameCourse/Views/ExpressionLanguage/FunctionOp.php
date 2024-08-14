<?php
namespace GameCourse\Views\ExpressionLanguage;

use GameCourse\Core\Core;

class FunctionOp extends Node {
    public function __construct(private string $name, private ?ArgumentSequence $args, ?string $libraryId, private ?\GameCourse\Views\ExpressionLanguage\Node $context = null) {
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
