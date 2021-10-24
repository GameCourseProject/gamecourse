<?php
namespace GameCourse\Views\Expression;

abstract class Node {
    public abstract function accept($visitor);
}