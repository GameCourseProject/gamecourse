<?php
namespace Modules\Views\Expression;

class DatabasePathFromParameter extends Node {
    private $param;
    private $path;
    private $context;

    public function __construct($param, $path, $context = null) {
        $this->param = $param;
        $this->path = $path;
        $this->context = $context;
    }

    public function getParameter() {
        return $this->param;
    }

    public function getPath() {
        return $this->path;
    }

    public function getContext() {
        return $this->context;
    }

    public function accept($visitor, $returnContinuation = false) {
        return $visitor->visitDatabasePathFromParameter($this, $returnContinuation);
    }
}
