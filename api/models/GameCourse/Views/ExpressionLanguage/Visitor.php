<?php
namespace GameCourse\Views\ExpressionLanguage;

abstract class Visitor {
    public abstract function visitStatementSequence(StatementSequence $node);
    public abstract function visitArgumentSequence(ArgumentSequence $node);
    public abstract function visitValueNode(ValueNode $node);
    public abstract function visitGenericUnaryOp(GenericUnaryOp $node);
    public abstract function visitGenericBinaryOp(GenericBinaryOp $node);
    public abstract function visitFunctionOp(FunctionOp $node);
    public abstract function visitParameterNode(ParameterNode $node);
}
