<?php
namespace GameCourse\Views\ExpressionLanguage;

class FunctionOp extends Node {
    private $name;
    private $args;
    private $context;
    private $libraryId;

    public function __construct(string $name, ?ArgumentSequence $args, ?string $libraryId, Node $context = null) {
        $this->name = $name;
        $this->args = $args;
        $this->context = $context;
        $this->libraryId = $libraryId;
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

    public function getLib(): ?string
    {
        return $this->libraryId;
    }

    public function setLib(?string $libraryId): ?string
    {
        return $this->libraryId = $libraryId;
    }

    public function accept(Visitor $visitor): ValueNode {
        return $visitor->visitFunctionOp($this);
    }
}
