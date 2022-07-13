<?php
namespace GameCourse\Views\ExpressionLanguage;

abstract class Visitor {
    public abstract function visitStatementSequence(StatementSequence $node): ValueNode;
    public abstract function visitArgumentSequence(ArgumentSequence $node): ValueNode;
    public abstract function visitValueNode(ValueNode $node): ValueNode;
    public abstract function visitGenericUnaryOp(GenericUnaryOp $node): ValueNode;
    public abstract function visitGenericBinaryOp(GenericBinaryOp $node): ValueNode;
    public abstract function visitFunctionOp(FunctionOp $node): ValueNode;
    public abstract function visitParameterNode(ParameterNode $node): ValueNode;
}
