<?php
namespace GameCourse\Views\ExpressionLanguage;

abstract class Node {
    public abstract function accept(Visitor $visitor);
}