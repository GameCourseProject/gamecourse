<?php
namespace GameCourse\Views\Expression;

abstract class Visitor {
    public abstract function visitStatementSequence($node);
    public abstract function visitArgumentSequence($node);
    public abstract function visitValueNode($node);
    public abstract function visitGenericUnaryOp($node);
    public abstract function visitGenericBinaryOp($node);
    public abstract function visitFunctionOp($node);
    public abstract function visitParameterNode($node);
}
