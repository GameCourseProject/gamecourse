<?php
namespace Modules\Views\Expression;

abstract class Node {
    public abstract function accept($visitor);
}