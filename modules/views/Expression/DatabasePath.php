<?php
namespace Modules\Views\Expression;

class DatabasePath extends Node {
    private $path;
    private $context;
    private $subPath;

    public function __construct($path, $context = null, $subPath = null) {
        $this->path = $path;
        $this->context = $context;
        $this->subPath = $subPath;
    }

    public function getPath() {
        return $this->path;
    }

    public function getContext() {
        return $this->context;
    }

    public function getSubPath() {
        return $this->subPath;
    }

    public function accept($visitor, $parent = null, $returnContinuation = false) {
        return $visitor->visitDatabasePath($this, $parent, $returnContinuation);
    }
}
